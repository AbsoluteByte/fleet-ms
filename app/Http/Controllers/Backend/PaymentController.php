<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Driver;
use App\Models\Payment;
use App\Services\PaymentAllocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    protected $url = 'payments.';

    protected $dir = 'backend.payments.';

    protected $name = 'Payments';

    public function __construct()
    {
        $this->middleware('role:admin|manager|user');
        view()->share('url', $this->url);
        view()->share('dir', $this->dir);
        view()->share('singular', Str::singular($this->name));
        view()->share('plural', Str::plural($this->name));
    }

    public function index()
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found! Please contact administrator.');
        }

        $drivers = Driver::query()
            ->where('tenant_id', $tenant->id)
            ->withCount(['invoices', 'payments'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return view($this->dir.'index', compact('drivers'));
    }

    public function driver(Driver $driver)
    {
        $tenant = Auth::user()->currentTenant();
        $this->authorizeDriver($driver, $tenant);

        $invoices = $driver->invoices()
            ->with('paymentAllocations.payment')
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->get();

        $activeInvoices = $driver->activeInvoices()
            ->orderBy('invoice_date')
            ->orderBy('due_date')
            ->get();

        $dueInvoices = $driver->overdueInvoices()
            ->orderBy('due_date')
            ->get();

        $payments = $driver->payments()
            ->with(['allocations.invoice', 'bankAccount'])
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get();

        $summary = $this->driverSummary($driver);

        return view($this->dir.'show', compact('driver', 'invoices', 'activeInvoices', 'dueInvoices', 'payments', 'summary'));
    }

    public function create(Request $request)
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }

        $drivers = Driver::where('tenant_id', $tenant->id)
            ->active()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $selectedDriver = null;
        $openInvoices = collect();
        $selectedDriverId = old('driver_id', $request->query('driver_id'));

        if ($selectedDriverId) {
            $selectedDriver = Driver::where('tenant_id', $tenant->id)->find($selectedDriverId);

            if ($selectedDriver) {
                $openInvoices = $selectedDriver->activeInvoices()
                    ->orderBy('invoice_date')
                    ->orderBy('due_date')
                    ->get();
            }
        }

        $model = new Payment;
        $bankAccounts = $this->bankAccountsForTenant($tenant->id);

        return view($this->dir.'create', compact('model', 'drivers', 'selectedDriver', 'openInvoices', 'bankAccounts'));
    }

    public function store(Request $request, PaymentAllocationService $paymentAllocationService)
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->back()
                ->with('error', 'No active company found!');
        }

        $validated = $request->validate([
            'driver_id' => [
                'required',
                Rule::exists('drivers', 'id')->where(fn ($query) => $query->where('tenant_id', $tenant->id)),
            ],
            'payment_method' => 'required|string|max:255',
            'bank_account_id' => [
                'nullable',
                'required_if:payment_method,Bank Transfer',
                Rule::exists('bank_accounts', 'id')->where(fn ($query) => $query->where('tenant_id', $tenant->id)),
            ],
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
            'auto_manage_invoices' => 'boolean',
            'allocations' => 'nullable|array',
            'allocations.*' => 'nullable|numeric|min:0',
        ]);

        $driver = Driver::where('tenant_id', $tenant->id)->findOrFail($validated['driver_id']);
        $autoManageInvoices = $request->boolean('auto_manage_invoices', true);

        $payment = $paymentAllocationService->createPayment(
            $driver,
            [
                'payment_method' => $validated['payment_method'],
                'bank_account_id' => ($validated['payment_method'] ?? '') === 'Bank Transfer'
                    ? ($validated['bank_account_id'] ?? null)
                    : null,
                'payment_date' => $validated['payment_date'],
                'amount' => $validated['amount'],
                'notes' => $validated['notes'] ?? null,
            ],
            $autoManageInvoices,
            $validated['allocations'] ?? []
        );

        return redirect()->route('payments.driver', $payment->driver_id)
            ->with('success', 'Payment added successfully.');
    }

    public function show(Payment $payment)
    {
        $tenant = Auth::user()->currentTenant();
        $payment->load(['driver', 'bankAccount', 'allocations.invoice']);
        $this->authorizeDriver($payment->driver, $tenant);

        return view($this->dir.'payment', compact('payment'));
    }

    public function edit(Payment $payment)
    {
        return redirect()->route('payments.show', $payment);
    }

    public function update(Request $request, Payment $payment)
    {
        return redirect()->route('payments.show', $payment);
    }

    public function destroy(Payment $payment)
    {
        $tenant = Auth::user()->currentTenant();
        $payment->load(['driver', 'allocations.invoice']);
        $this->authorizeDriver($payment->driver, $tenant);

        $invoices = $payment->allocations->pluck('invoice')->filter();

        foreach ($payment->allocations as $allocation) {
            $allocation->delete();
        }

        $payment->delete();

        foreach ($invoices as $invoice) {
            $invoice->refreshPaymentTotals();
        }

        return redirect()->route('payments.driver', $payment->driver_id)
            ->with('success', 'Payment deleted successfully.');
    }

    private function authorizeDriver(?Driver $driver, $tenant): void
    {
        abort_unless($tenant && $driver && (int) $driver->tenant_id === (int) $tenant->id, 403);
    }

    private function driverSummary(Driver $driver): array
    {
        return [
            'total_invoiced' => (float) $driver->invoices()->sum('total_amount'),
            'total_paid' => (float) $driver->payments()->sum('amount'),
            'total_allocated' => (float) $driver->payments()
                ->join('payment_allocations', 'payments.id', '=', 'payment_allocations.payment_id')
                ->sum('payment_allocations.allocated_amount'),
            'total_due' => (float) $driver->activeInvoices()->sum('balance_amount'),
            'overdue_due' => (float) $driver->overdueInvoices()->sum('balance_amount'),
        ];
    }

    private function bankAccountsForTenant(int $tenantId)
    {
        return BankAccount::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('bank_name')
            ->get();
    }
}
