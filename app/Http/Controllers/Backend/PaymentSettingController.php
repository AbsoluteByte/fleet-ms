<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PaymentSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PaymentSettingController extends Controller
{
    protected $url = 'payment-settings.';

    protected $dir = 'backend.payment-settings.';

    protected $name = 'Payment Settings';

    public function __construct()
    {
        $this->middleware('role:admin');
        view()->share('url', $this->url);
        view()->share('dir', $this->dir);
        view()->share('singular', Str::singular($this->name));
        view()->share('plural', $this->name);
    }

    public function index()
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found! Please contact administrator.');
        }

        $paymentSettings = PaymentSetting::where('tenant_id', $tenant->id)
            ->with('company')
            ->get();

        return view($this->dir.'index', compact('paymentSettings'));
    }

    public function create()
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }

        $model = new PaymentSetting;
        $companies = Company::where('tenant_id', $tenant->id)->orderBy('name')->pluck('name', 'id');

        return view($this->dir.'create', compact('model', 'companies'));
    }

    public function store(Request $request)
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->back()
                ->with('error', 'No active company found!');
        }

        $validated = $this->validatedData($request, $tenant->id);
        $validated['tenant_id'] = $tenant->id;
        $validated['createdBy'] = Auth::id();

        PaymentSetting::create($validated);

        return redirect()->route($this->url.'index')
            ->with('success', 'Payment setting created successfully.');
    }

    public function show(PaymentSetting $paymentSetting)
    {
        $this->authorizeTenant($paymentSetting);
        $paymentSetting->load('company');

        return view($this->dir.'show', compact('paymentSetting'));
    }

    public function edit(PaymentSetting $paymentSetting)
    {
        $this->authorizeTenant($paymentSetting);
        $tenant = Auth::user()->currentTenant();
        $model = $paymentSetting;
        $companies = Company::where('tenant_id', $tenant->id)->orderBy('name')->pluck('name', 'id');

        return view($this->dir.'edit', compact('model', 'companies'));
    }

    public function update(Request $request, PaymentSetting $paymentSetting)
    {
        $this->authorizeTenant($paymentSetting);
        $tenant = Auth::user()->currentTenant();

        $validated = $this->validatedData($request, $tenant->id);
        $validated['updatedBy'] = Auth::id();

        $paymentSetting->update($validated);

        return redirect()->route($this->url.'index')
            ->with('success', 'Payment setting updated successfully.');
    }

    public function destroy(PaymentSetting $paymentSetting)
    {
        $this->authorizeTenant($paymentSetting);
        $paymentSetting->delete();

        return redirect()->route($this->url.'index')
            ->with('success', 'Payment setting deleted successfully.');
    }

    private function validatedData(Request $request, int $tenantId): array
    {
        $rules = [
            'payment_type' => 'required|string|max:255',
            'company_id' => 'required|exists:companies,id',
            'iban_number' => 'nullable|string|max:34',
        ];

        if ($request->payment_type === 'Bank Transfer') {
            $rules['bank_name'] = 'required|string|max:255';
            $rules['account_number'] = 'required|string|max:255';
            $rules['sort_code'] = 'required|string|max:10';
        } elseif ($request->payment_type === 'Stripe') {
            $rules['stripe_public_key'] = 'required|string|max:255';
            $rules['stripe_secret_key'] = 'required|string|max:255';
        } elseif ($request->payment_type === 'PayPal') {
            $rules['paypal_client_id'] = 'required|string|max:255';
            $rules['paypal_secret'] = 'required|string|max:255';
        }

        $validated = $request->validate($rules);

        abort_unless(
            Company::where('tenant_id', $tenantId)->whereKey($validated['company_id'])->exists(),
            403,
            'Unauthorized company selected.'
        );

        return $validated;
    }

    private function authorizeTenant(PaymentSetting $paymentSetting): void
    {
        $tenant = Auth::user()->currentTenant();
        abort_unless($tenant && (int) $paymentSetting->tenant_id === (int) $tenant->id, 403);
    }
}
