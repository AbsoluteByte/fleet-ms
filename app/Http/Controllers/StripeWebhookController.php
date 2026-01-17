<?php
namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        // Verify webhook signature
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('stripe.webhook_secret');

        try {
            if ($webhookSecret) {
                $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
            } else {
                $event = json_decode($payload);
            }
        } catch (\Exception $e) {
            Log::error('Webhook signature verification failed: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Handle different event types
        switch ($event->type) {
            case 'invoice.payment_succeeded':
                $this->handleInvoicePaymentSucceeded($event->data->object);
                break;

            case 'invoice.payment_failed':
                $this->handleInvoicePaymentFailed($event->data->object);
                break;

            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdated($event->data->object);
                break;

            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($event->data->object);
                break;

            case 'customer.subscription.created':
                $this->handleSubscriptionCreated($event->data->object);
                break;

            default:
                Log::info('Unhandled webhook event type: ' . $event->type);
        }

        return response()->json(['status' => 'success'], 200);
    }

    // ==================== HANDLE PAYMENT SUCCEEDED ====================
    private function handleInvoicePaymentSucceeded($invoice)
    {
        Log::info('Payment succeeded for invoice: ' . $invoice->id);

        // Find subscription by Stripe subscription ID
        $subscription = Subscription::where('stripe_subscription_id', $invoice->subscription)->first();

        if (!$subscription) {
            Log::warning('Subscription not found for invoice: ' . $invoice->id);
            return;
        }

        // Create Invoice record
        Invoice::create([
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'stripe_invoice_id' => $invoice->id,
            'invoice_number' => $invoice->number ?? 'INV-' . time(),
            'amount' => $invoice->amount_due / 100, // Convert from cents
            'tax' => $invoice->tax ?? 0,
            'total' => $invoice->total / 100,
            'status' => 'paid',
            'paid_at' => now(),
            'currency' => $invoice->currency,
        ]);

        // Update subscription status
        $subscription->update([
            'status' => 'active',
            'current_period_start' => \Carbon\Carbon::createFromTimestamp($invoice->period_start),
            'current_period_end' => \Carbon\Carbon::createFromTimestamp($invoice->period_end),
        ]);

        // Activate tenant if suspended
        $tenant = $subscription->tenant;
        if ($tenant && $tenant->isSuspended()) {
            $tenant->activate();
        }

        Log::info('Subscription renewed successfully for tenant: ' . $subscription->tenant_id);
    }

    // ==================== HANDLE PAYMENT FAILED ====================
    private function handleInvoicePaymentFailed($invoice)
    {
        Log::warning('Payment failed for invoice: ' . $invoice->id);

        $subscription = Subscription::where('stripe_subscription_id', $invoice->subscription)->first();

        if (!$subscription) {
            return;
        }

        // Create failed invoice record
        Invoice::create([
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'stripe_invoice_id' => $invoice->id,
            'invoice_number' => $invoice->number ?? 'INV-' . time(),
            'amount' => $invoice->amount_due / 100,
            'tax' => $invoice->tax ?? 0,
            'total' => $invoice->total / 100,
            'status' => 'failed',
            'currency' => $invoice->currency,
        ]);

        // Update subscription status
        $subscription->update([
            'status' => 'past_due',
        ]);

        // Optionally suspend tenant after X failed attempts
        // You can implement retry logic here

        Log::info('Payment failed for tenant: ' . $subscription->tenant_id);
    }

    // ==================== HANDLE SUBSCRIPTION UPDATED ====================
    private function handleSubscriptionUpdated($stripeSubscription)
    {
        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscription->id)->first();

        if (!$subscription) {
            return;
        }

        $subscription->update([
            'status' => $stripeSubscription->status,
            'current_period_start' => \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_start),
            'current_period_end' => \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end),
        ]);

        Log::info('Subscription updated: ' . $stripeSubscription->id);
    }

    // ==================== HANDLE SUBSCRIPTION DELETED ====================
    private function handleSubscriptionDeleted($stripeSubscription)
    {
        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscription->id)->first();

        if (!$subscription) {
            return;
        }

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        // Suspend tenant
        $tenant = $subscription->tenant;
        if ($tenant) {
            $tenant->suspend('Subscription cancelled');
        }

        Log::info('Subscription cancelled: ' . $stripeSubscription->id);
    }

    // ==================== HANDLE SUBSCRIPTION CREATED ====================
    private function handleSubscriptionCreated($stripeSubscription)
    {
        Log::info('Subscription created: ' . $stripeSubscription->id);
        // Already handled in subscribe() method
    }
}
