<?php
// app/Http/Controllers/Backend/SubscriptionController.php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Subscription;
use App\Models\PaymentMethod;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubscriptionController extends Controller
{
    protected $url = 'subscription.';
    protected $dir = 'backend.subscription.';

    public function __construct()
    {
        $this->middleware('role:admin');

        view()->share('url', $this->url);
        view()->share('dir', $this->dir);
    }

    // ==================== SUBSCRIPTION OVERVIEW ====================

    public function index()
    {
        $tenant = auth()->user()->currentTenant();

        if (!$tenant) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'No active tenant found!');
        }

        $subscription = $tenant->subscription;
        $paymentMethods = $tenant->paymentMethods ?? collect();

        return view($this->dir . 'index', compact('tenant', 'subscription', 'paymentMethods'));
    }

    // ==================== PACKAGES PAGE ====================

    public function packages()
    {
        $tenant = auth()->user()->currentTenant();
        $currentSubscription = $tenant->subscription;

        $monthlyPackages = Package::where('is_active', true)
            ->where('billing_period', 'monthly')
            ->where('name', '!=', 'Free Trial')
            ->get();

        $quarterlyPackages = Package::where('is_active', true)
            ->where('billing_period', 'quarterly')
            ->get();

        $yearlyPackages = Package::where('is_active', true)
            ->where('billing_period', 'yearly')
            ->get();

        return view($this->dir . 'packages', compact(
            'tenant',
            'currentSubscription',
            'monthlyPackages',
            'quarterlyPackages',
            'yearlyPackages'
        ));
    }

    // ==================== SUBSCRIBE TO PACKAGE ====================

    public function subscribe(Request $request, Package $package)
    {
        $request->validate([
            'stripe_payment_method_id' => ['required', 'string'],
        ]);

        $tenant = auth()->user()->currentTenant();
        $user = auth()->user();

        try {
            \Stripe\Stripe::setApiKey(config('stripe.secret'));

            // 1️⃣ Get or Create Customer
            if ($tenant->stripe_customer_id) {
                $customerId = $tenant->stripe_customer_id;
                try {
                    $customer = \Stripe\Customer::retrieve($customerId);
                } catch (\Exception $e) {
                    $customer = \Stripe\Customer::create([
                        'email' => $user->email,
                        'name' => $tenant->company_name,
                    ]);
                    $customerId = $customer->id;
                    $tenant->update(['stripe_customer_id' => $customerId]);
                }
            } else {
                $customer = \Stripe\Customer::create([
                    'email' => $user->email,
                    'name' => $tenant->company_name,
                    'metadata' => [
                        'tenant_id' => $tenant->id
                    ]
                ]);
                $customerId = $customer->id;
                $tenant->update(['stripe_customer_id' => $customerId]);
            }

            // 2️⃣ Attach Payment Method
            $paymentMethodId = $request->stripe_payment_method_id;
            $this->attachPaymentMethodToCustomer($paymentMethodId, $customerId);

            // 3️⃣ Set as Default
            \Stripe\Customer::update($customerId, [
                'invoice_settings' => [
                    'default_payment_method' => $paymentMethodId,
                ],
            ]);

            // 4️⃣ Get Price
            $stripePrice = $this->getOrCreateStripePrice($package);

            // 5️⃣ Cancel existing subscription
            $existingSubscription = $tenant->subscription;
            if ($existingSubscription && $existingSubscription->stripe_subscription_id) {
                try {
                    \Stripe\Subscription::update($existingSubscription->stripe_subscription_id, [
                        'cancel_at_period_end' => true
                    ]);
                } catch (\Exception $e) {
                    \Log::warning('Could not cancel old subscription: ' . $e->getMessage());
                }
            }

            // 6️⃣ Create Subscription
            $stripeSubscription = \Stripe\Subscription::create([
                'customer' => $customerId,
                'items' => [
                    ['price' => $stripePrice->id]
                ],
                'default_payment_method' => $paymentMethodId,
                'metadata' => [
                    'tenant_id' => $tenant->id,
                    'package_id' => $package->id,
                    'package_name' => $package->name
                ]
            ]);

            // ✅ Debug: Log Stripe response
            \Log::info('Stripe Subscription Created:', [
                'id' => $stripeSubscription->id,
                'status' => $stripeSubscription->status,
                'current_period_start' => $stripeSubscription->current_period_start ?? 'NULL',
                'current_period_end' => $stripeSubscription->current_period_end ?? 'NULL',
            ]);

            // 7️⃣ Save to Database with NULL checks
            $currentPeriodStart = isset($stripeSubscription->current_period_start) && $stripeSubscription->current_period_start
                ? \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_start)
                : now();

            $currentPeriodEnd = isset($stripeSubscription->current_period_end) && $stripeSubscription->current_period_end
                ? \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end)
                : now()->addMonth();

            Subscription::updateOrCreate(
                ['tenant_id' => $tenant->id],
                [
                    'package_id' => $package->id,
                    'stripe_subscription_id' => $stripeSubscription->id,
                    'stripe_customer_id' => $customerId,
                    'status' => $stripeSubscription->status ?? 'active',
                    'trial_ends_at' => null,
                    'current_period_start' => $currentPeriodStart,
                    'current_period_end' => $currentPeriodEnd,
                    'cancelled_at' => null,
                    'suspended_at' => null,
                ]
            );

            // 8️⃣ Save Payment Method
            $card = \Stripe\PaymentMethod::retrieve($paymentMethodId);

            PaymentMethod::where('tenant_id', $tenant->id)->update(['is_default' => false]);

            PaymentMethod::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'stripe_payment_method_id' => $paymentMethodId
                ],
                [
                    'card_brand' => $card->card->brand,
                    'card_last_four' => $card->card->last4,
                    'exp_month' => $card->card->exp_month,
                    'exp_year' => $card->card->exp_year,
                    'is_default' => true,
                ]
            );

            // 9️⃣ Activate Tenant
            if ($tenant->isSuspended()) {
                $tenant->activate();
            }

            return redirect()
                ->route($this->url . 'index')
                ->with('success', 'Successfully subscribed to ' . $package->name . '! 🎉');

        } catch (\Stripe\Exception\CardException $e) {
            \Log::error('Card Error:', ['error' => $e->getMessage()]);
            return redirect()
                ->back()
                ->with('error', 'Card declined: ' . $e->getError()->message);

        } catch (\Stripe\Exception\InvalidRequestException $e) {
            \Log::error('Stripe Invalid Request:', ['error' => $e->getMessage()]);
            return redirect()
                ->back()
                ->with('error', 'Payment error: ' . $e->getMessage());

        } catch (\Exception $e) {
            \Log::error('Subscription Error:', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()
                ->back()
                ->with('error', 'Subscription failed: ' . $e->getMessage());
        }
    }

    // ✅ Helper: Attach Payment Method using CURL
    private function attachPaymentMethodToCustomer($paymentMethodId, $customerId)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "https://api.stripe.com/v1/payment_methods/{$paymentMethodId}/attach",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['customer' => $customerId]),
            CURLOPT_USERPWD => config('stripe.secret') . ':',
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 && strpos($response, 'already been attached') === false) {
            \Log::error('Failed to attach payment method', ['response' => $response]);
            throw new \Exception('Failed to attach payment method to customer');
        }

        return true;
    }

    // ==================== CANCEL SUBSCRIPTION ====================

    public function cancel(Request $request)
    {
        $tenant = auth()->user()->currentTenant();
        $subscription = $tenant->subscription;

        if (!$subscription || !$subscription->stripe_subscription_id) {
            return redirect()
                ->back()
                ->with('error', 'No active subscription found!');
        }

        try {
            \Stripe\Stripe::setApiKey(config('stripe.secret')); // ✅ Changed

            \Stripe\Subscription::update($subscription->stripe_subscription_id, [
                'cancel_at_period_end' => true
            ]);

            $subscription->cancel();

            return redirect()
                ->back()
                ->with('success', 'Subscription cancelled. Access will continue until ' .
                    $subscription->current_period_end->format('d M, Y'));

        } catch (\Exception $e) {
            \Log::error('Subscription Cancel Error:', ['error' => $e->getMessage()]);
            return redirect()
                ->back()
                ->with('error', 'Failed to cancel subscription: ' . $e->getMessage());
        }
    }

    // ==================== RESUME SUBSCRIPTION ====================

    public function resume(Request $request)
    {
        $tenant = auth()->user()->currentTenant();
        $subscription = $tenant->subscription;

        if (!$subscription || !$subscription->stripe_subscription_id) {
            return redirect()
                ->back()
                ->with('error', 'No subscription found!');
        }

        try {
            \Stripe\Stripe::setApiKey(config('stripe.secret')); // ✅ Changed

            \Stripe\Subscription::update($subscription->stripe_subscription_id, [
                'cancel_at_period_end' => false
            ]);

            $subscription->update([
                'status' => 'active',
                'cancelled_at' => null
            ]);

            return redirect()
                ->back()
                ->with('success', 'Subscription resumed successfully!');

        } catch (\Exception $e) {
            \Log::error('Subscription Resume Error:', ['error' => $e->getMessage()]);
            return redirect()
                ->back()
                ->with('error', 'Failed to resume subscription: ' . $e->getMessage());
        }
    }

    // ==================== INVOICES ====================

    public function invoices()
    {
        $tenant = auth()->user()->currentTenant();
        $invoices = $tenant->invoices()->get();

        return view($this->dir . 'invoices', compact('invoices', 'tenant'));
    }

    // ==================== PAYMENT METHODS ====================

    public function paymentMethods()
    {
        $tenant = auth()->user()->currentTenant();
        $paymentMethods = $tenant->paymentMethods;

        return view($this->dir . 'payment-methods', compact('paymentMethods', 'tenant'));
    }

    // ==================== ADD PAYMENT METHOD ====================

    public function addPaymentMethod(Request $request)
    {
        $request->validate([
            'stripe_payment_method_id' => ['required', 'string'],
        ]);

        $tenant = auth()->user()->currentTenant();

        try {
            // Attach using CURL
            if ($tenant->stripe_customer_id) {
                $this->attachPaymentMethodToCustomer($request->stripe_payment_method_id, $tenant->stripe_customer_id);
            }

            // Get card details
            \Stripe\Stripe::setApiKey(config('stripe.secret')); // ✅ Changed
            $card = \Stripe\PaymentMethod::retrieve($request->stripe_payment_method_id);

            PaymentMethod::create([
                'tenant_id' => $tenant->id,
                'stripe_payment_method_id' => $request->stripe_payment_method_id,
                'card_brand' => $card->card->brand,
                'card_last_four' => $card->card->last4,
                'exp_month' => $card->card->exp_month,
                'exp_year' => $card->card->exp_year,
                'is_default' => false,
            ]);

            return redirect()
                ->back()
                ->with('success', 'Payment method added successfully!');

        } catch (\Exception $e) {
            \Log::error('Add Payment Method Error:', ['error' => $e->getMessage()]);
            return redirect()
                ->back()
                ->with('error', 'Failed to add payment method: ' . $e->getMessage());
        }
    }

    // ==================== REMOVE PAYMENT METHOD ====================

    public function removePaymentMethod(PaymentMethod $paymentMethod)
    {
        $tenant = auth()->user()->currentTenant();

        if ($paymentMethod->tenant_id !== $tenant->id) {
            abort(403);
        }

        try {
            // Detach using CURL
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => "https://api.stripe.com/v1/payment_methods/{$paymentMethod->stripe_payment_method_id}/detach",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_USERPWD => config('stripe.secret') . ':', // ✅ Changed
            ]);

            curl_exec($ch);
            curl_close($ch);

            // Delete from database
            $paymentMethod->delete();

            return redirect()
                ->back()
                ->with('success', 'Payment method removed successfully!');

        } catch (\Exception $e) {
            \Log::error('Remove Payment Method Error:', ['error' => $e->getMessage()]);
            return redirect()
                ->back()
                ->with('error', 'Failed to remove payment method: ' . $e->getMessage());
        }
    }

    // ==================== HELPER: GET OR CREATE STRIPE PRICE ====================

    private function getOrCreateStripePrice(Package $package)
    {
        \Stripe\Stripe::setApiKey(config('stripe.secret')); // ✅ Changed

        if ($package->stripe_price_id) {
            try {
                return \Stripe\Price::retrieve($package->stripe_price_id);
            } catch (\Exception $e) {
                \Log::warning('Stripe price not found, creating new one');
            }
        }

        $product = \Stripe\Product::create([
            'name' => $package->name,
            'description' => $package->description,
        ]);

        $interval = 'month';
        $intervalCount = 1;

        switch ($package->billing_period) {
            case 'quarterly':
                $interval = 'month';
                $intervalCount = 3;
                break;
            case 'yearly':
                $interval = 'year';
                $intervalCount = 1;
                break;
        }

        $price = \Stripe\Price::create([
            'product' => $product->id,
            'unit_amount' => $package->price * 100,
            'currency' => 'gbp',
            'recurring' => [
                'interval' => $interval,
                'interval_count' => $intervalCount,
            ],
        ]);

        $package->update(['stripe_price_id' => $price->id]);

        return $price;
    }

    public function viewInvoice(Invoice $invoice)
    {
        $tenant = auth()->user()->currentTenant();

        if ($invoice->tenant_id !== $tenant->id) {
            abort(403);
        }

        return view($this->dir . 'invoice-view', compact('invoice'));
    }
}
