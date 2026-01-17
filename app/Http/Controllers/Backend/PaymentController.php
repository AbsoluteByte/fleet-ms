<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    protected $url = 'payments.';
    protected $dir = 'backend.payments.';
    protected $name = 'Payments';

    public function __construct()
    {
        $this->middleware('role:admin');
        view()->share('url', $this->url);
        view()->share('dir', $this->dir);
        view()->share('singular', Str::singular($this->name));
        view()->share('plural', Str::plural($this->name));
    }

    public function index()
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found! Please contact administrator.');
        }
        $payments = Payment::where('tenant_id', $tenant->id)->with('company')->get();
        return view($this->dir.'index', compact('payments'));
    }

    public function create()
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }
        $model = new Payment();
        return view($this->dir.'create', compact('model'));
    }

    public function store(Request $request)
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->back()
                ->with('error', 'No active company found!');
        }
        $rules = [
            'payment_type' => 'required|string|max:255',
            'company_id' => 'required|exists:companies,id',
        ];

        // Conditional validation based on payment type
        if ($request->payment_type === 'Bank Transfer') {
            $rules['bank_name'] = 'required|string|max:255';
            $rules['account_number'] = 'required|string|max:255';
            $rules['sort_code'] = 'required|string|max:10';
            $rules['iban_number'] = 'nullable|string|max:34';
        } elseif ($request->payment_type === 'Stripe') {
            $rules['stripe_public_key'] = 'required|string|max:255';
            $rules['stripe_secret_key'] = 'required|string|max:255';
        } elseif ($request->payment_type === 'PayPal') {
            $rules['paypal_client_id'] = 'required|string|max:255';
            $rules['paypal_secret'] = 'required|string|max:255';
        }

        $validated = $request->validate($rules);
        $validated['tenant_id'] = $tenant->id;
        $validated['createdBy'] = Auth::id();
        Payment::create($validated);

        return redirect()->route($this->url.'index')
            ->with('success', 'Payment method created successfully.');
    }

    public function show(Payment $payment)
    {
        $payment->load('company');
        return view($this->dir.'show', compact('payment'));
    }

    public function edit($id)
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }
        $model = Payment::where('tenant_id', $tenant->id)->findOrFail($id);
        return view($this->dir.'edit', compact('model'));
    }

    public function update(Request $request, Payment $payment)
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->back()
                ->with('error', 'No active company found!');
        }
        $rules = [
            'payment_type' => 'required|string|max:255',
            'company_id' => 'required|exists:companies,id',
        ];

        // Conditional validation based on payment type
        if ($request->payment_type === 'Bank Transfer') {
            $rules['bank_name'] = 'required|string|max:255';
            $rules['account_number'] = 'required|string|max:255';
            $rules['sort_code'] = 'required|string|max:10';
            $rules['iban_number'] = 'nullable|string|max:34';
        } elseif ($request->payment_type === 'Stripe') {
            $rules['stripe_public_key'] = 'required|string|max:255';
            $rules['stripe_secret_key'] = 'required|string|max:255';
        } elseif ($request->payment_type === 'PayPal') {
            $rules['paypal_client_id'] = 'required|string|max:255';
            $rules['paypal_secret'] = 'required|string|max:255';
        }

        $validated = $request->validate($rules);
        $validated['tenant_id'] = $tenant->id;
        $validated['updatedBy'] = Auth::id();
        $payment->update($validated);

        return redirect()->route($this->url.'index')
            ->with('success', 'Payment method updated successfully.');
    }

    public function destroy(Payment $payment)
    {
        $tenant = Auth::user()->currentTenant();

        // ✅ Check ownership
        if ($payment->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access');
        }
        $payment->delete();

        return redirect()->route($this->url.'index')
            ->with('success', 'Payment method deleted successfully.');
    }
}
