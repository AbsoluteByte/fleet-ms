<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Driver;
use App\Models\Payment;
use App\Support\BatchPaymentInput;
use App\Services\DriverCreditService;
use App\Services\DailyFinancialSheetService;
use App\Services\PaymentAllocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    private const PAYMENT_MANAGER_EMAIL = 'jawad@samoretraders.com';

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
            ->withPaymentIndexAggregates()
            ->with(['agreements' => fn ($query) => $query->currentlyActive()->with([
                'car',
                'replacementVehicleAgreements' => fn ($replacementQuery) => $replacementQuery
                    ->currentlyActiveReplacement()
                    ->with('car'),
            ])])
            ->withMax(['payments as last_posted_payment_date' => function ($query) {
                $query->posted();
            }], 'payment_date')
            ->withMax('invoices as latest_invoice_date', 'invoice_date')
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
            ->with(['paymentAllocations.payment', 'sourceAgreement'])
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->get();

        $activeInvoices = $driver->activeInvoices()
            ->with('sourceAgreement')
            ->orderBy('invoice_date')
            ->orderBy('due_date')
            ->get();

        $dueInvoices = $driver->overdueInvoices()
            ->with('sourceAgreement')
            ->orderBy('due_date')
            ->get();

        $payments = $driver->payments()
            ->with(['allocations.invoice.sourceAgreement', 'bankAccount'])
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get();

        $summary = $this->driverSummary($driver);
        $creditPreview = app(DriverCreditService::class)->preview($driver);
        $bankAccounts = $this->bankAccountsForTenant($tenant->id);
        $canManagePayments = $this->canManagePayments();

        return view($this->dir.'show', compact(
            'driver',
            'invoices',
            'activeInvoices',
            'dueInvoices',
            'payments',
            'summary',
            'creditPreview',
            'bankAccounts',
            'canManagePayments'
        ));
    }

    public function refundCredit(Request $request, Driver $driver, DriverCreditService $creditService)
    {
        $tenant = Auth::user()->currentTenant();
        $this->authorizeDriver($driver, $tenant);

        $validated = $request->validate([
            'payment_method' => 'required|in:Cash,Bank Transfer,Cheque,Card Payment,Direct Debit',
            'bank_account_id' => [
                'nullable',
                Rule::requiredIf(fn () => Payment::requiresBankAccount($request->input('payment_method'))),
                Rule::exists('bank_accounts', 'id')->where(fn ($query) => $query->where('tenant_id', $tenant->id)),
            ],
            'request_date' => 'required|date',
            'notes' => 'nullable|string|max:5000',
        ]);
        $validated['bank_account_id'] = Payment::bankAccountIdForMethod(
            $validated['payment_method'],
            $validated['bank_account_id'] ?? null
        );

        $creditService->requestRefund($driver, $validated);

        return redirect()->route('payments.driver', $driver)
            ->with('success', 'Credit refund submitted for daily financial sheet approval.');
    }

    public function applyCredit(Request $request, Driver $driver, DriverCreditService $creditService)
    {
        $tenant = Auth::user()->currentTenant();
        $this->authorizeDriver($driver, $tenant);

        $validated = $request->validate([
            'request_date' => 'required|date',
            'notes' => 'nullable|string|max:5000',
        ]);

        $creditService->requestInvoiceApplication($driver, $validated);

        return redirect()->route('payments.driver', $driver)
            ->with('success', 'Credit application submitted for daily financial sheet approval.');
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
                    ->with(['sourceAgreement.car'])
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

        $validated = $request->validate(array_merge([
            'driver_id' => [
                'required',
                Rule::exists('drivers', 'id')->where(fn ($query) => $query->where('tenant_id', $tenant->id)),
            ],
            'auto_manage_invoices' => 'boolean',
            'allocations' => 'nullable|array',
            'allocations.*' => 'nullable|numeric|min:0',
        ], BatchPaymentInput::validationRules($request, $tenant->id)));

        $driver = Driver::where('tenant_id', $tenant->id)->findOrFail($validated['driver_id']);
        $paymentRows = BatchPaymentInput::normalizeRows($validated);
        $isBatch = count($paymentRows) > 1;
        $autoManageInvoices = $isBatch ? true : $request->boolean('auto_manage_invoices', true);
        $manualAllocations = $isBatch ? [] : ($validated['allocations'] ?? []);

        DB::transaction(function () use ($paymentAllocationService, $driver, $paymentRows, $autoManageInvoices, $manualAllocations) {
            foreach ($paymentRows as $index => $paymentRow) {
                $paymentAllocationService->createPayment(
                    $driver,
                    [
                        'payment_method' => $paymentRow['payment_method'],
                        'bank_account_id' => $paymentRow['bank_account_id'],
                        'payment_date' => $paymentRow['payment_date'],
                        'amount' => $paymentRow['amount'],
                        'notes' => $paymentRow['notes'],
                    ],
                    $autoManageInvoices,
                    $index === 0 ? $manualAllocations : []
                );
            }
        });

        $message = count($paymentRows) > 1
            ? count($paymentRows).' payments recorded. They will apply to invoices after daily financial sheet approval.'
            : 'Payment recorded. It will apply to invoices after daily financial sheet approval.';

        return redirect()->route('payments.driver', $driver->id)
            ->with('success', $message);
    }

    public function show(Payment $payment)
    {
        $tenant = Auth::user()->currentTenant();
        $payment->load(['driver', 'bankAccount', 'sourceAgreement', 'allocations.invoice.sourceAgreement']);
        $this->authorizeDriver($payment->driver, $tenant);

        $canManagePayments = $this->canManagePayments();

        return view($this->dir.'payment', compact('payment', 'canManagePayments'));
    }

    public function edit(Payment $payment)
    {
        abort_unless($this->canManagePayments(), 403);

        $tenant = Auth::user()->currentTenant();
        $payment->load(['driver', 'bankAccount', 'allocations.invoice']);
        $this->authorizeDriver($payment->driver, $tenant);

        if (Schema::hasTable('driver_credit_transaction_lines') && $payment->creditTransactionLines()->exists()) {
            return redirect()->route('payments.show', $payment)
                ->with('error', 'This payment is linked to a driver credit transaction and cannot be edited.');
        }

        $drivers = Driver::where('tenant_id', $tenant->id)
            ->active()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $selectedDriver = $payment->driver;
        $openInvoices = $selectedDriver->activeInvoices()
            ->with(['sourceAgreement.car'])
            ->orderBy('invoice_date')
            ->orderBy('due_date')
            ->get();

        $model = $payment;
        $bankAccounts = $this->bankAccountsForTenant($tenant->id);
        $isPosted = $payment->isPosted();

        return view($this->dir.'edit', compact(
            'model',
            'drivers',
            'selectedDriver',
            'openInvoices',
            'bankAccounts',
            'isPosted'
        ));
    }

    public function update(
        Request $request,
        Payment $payment,
        PaymentAllocationService $paymentAllocationService,
        DailyFinancialSheetService $dailyFinancialSheetService
    ) {
        abort_unless($this->canManagePayments(), 403);

        $tenant = Auth::user()->currentTenant();
        $payment->load('driver');
        $this->authorizeDriver($payment->driver, $tenant);

        if (Schema::hasTable('driver_credit_transaction_lines') && $payment->creditTransactionLines()->exists()) {
            return redirect()->route('payments.show', $payment)
                ->with('error', 'This payment is linked to a driver credit transaction and cannot be edited.');
        }

        $validated = $this->validatePaymentRequest($request, $tenant, $payment);
        $autoManageInvoices = $request->boolean('auto_manage_invoices', true);

        $paymentData = [
            'payment_method' => $validated['payment_method'],
            'bank_account_id' => Payment::bankAccountIdForMethod(
                $validated['payment_method'],
                $validated['bank_account_id'] ?? null
            ),
            'payment_date' => $validated['payment_date'],
            'amount' => $validated['amount'],
            'notes' => $validated['notes'] ?? null,
        ];

        if ($payment->isPosted()) {
            $paymentAllocationService->updatePostedPayment(
                $payment,
                $paymentData,
                $dailyFinancialSheetService,
                (int) Auth::id()
            );
        } else {
            $paymentAllocationService->updatePendingPayment(
                $payment,
                $paymentData,
                $autoManageInvoices,
                $validated['allocations'] ?? []
            );
        }

        return redirect()->route('payments.show', $payment)
            ->with('success', 'Payment updated successfully.');
    }

    public function updateNotes(Request $request, Payment $payment)
    {
        $tenant = Auth::user()->currentTenant();
        $payment->load('driver');
        $this->authorizeDriver($payment->driver, $tenant);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:5000',
        ]);

        $payment->update([
            'notes' => $validated['notes'] ?? null,
        ]);

        $redirectTo = $request->input('redirect_to', route('payments.driver', $payment->driver_id));

        return redirect()->to($redirectTo)
            ->with('success', 'Payment notes updated.');
    }

    public function updateDriverFollowUp(Request $request, Driver $driver)
    {
        $tenant = Auth::user()->currentTenant();
        $this->authorizeDriver($driver, $tenant);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:5000',
            'set_reminder' => 'nullable|boolean',
            'remind_at' => [
                Rule::requiredIf(fn () => $request->boolean('set_reminder')),
                'nullable',
                'date',
            ],
        ]);

        $notes = isset($validated['notes']) ? trim((string) $validated['notes']) : '';
        $setReminder = $request->boolean('set_reminder');

        $driver->payment_follow_up_notes = $notes !== '' ? $notes : null;

        if ($setReminder) {
            $driver->payment_remind_at = \Carbon\Carbon::parse($validated['remind_at']);
            $driver->payment_reminder_dismissed_at = null;
        } else {
            $driver->payment_remind_at = null;
            $driver->payment_reminder_dismissed_at = null;
        }

        $driver->save();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Note / reminder saved.',
                'driver' => [
                    'id' => $driver->id,
                    'notes' => $driver->payment_follow_up_notes,
                    'remind_at' => $driver->payment_remind_at?->toIso8601String(),
                    'remind_at_display' => $driver->payment_remind_at?->timezone(config('app.timezone'))->format('d M Y, H:i'),
                    'has_note' => $driver->hasPaymentFollowUpNote(),
                    'has_reminder' => $driver->hasPaymentReminder(),
                    'is_due' => $driver->isPaymentReminderDue(),
                ],
            ]);
        }

        return redirect()->route('payments.index')
            ->with('success', 'Note / reminder saved.');
    }

    public function dueFollowUpReminders()
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return response()->json(['reminders' => []]);
        }

        $drivers = Driver::query()
            ->where('tenant_id', $tenant->id)
            ->withDuePaymentReminder()
            ->orderBy('payment_remind_at')
            ->get();

        return response()->json([
            'reminders' => $drivers->map(fn (Driver $driver) => [
                'id' => $driver->id,
                'name' => $driver->selectOptionLabel() ?: trim($driver->first_name.' '.$driver->last_name),
                'phone' => $driver->phone_number,
                'notes' => $driver->payment_follow_up_notes,
                'remind_at' => $driver->payment_remind_at?->toIso8601String(),
                'remind_at_display' => $driver->payment_remind_at?->format('d M Y, H:i'),
                'payments_url' => route('payments.driver', $driver),
                'dismiss_url' => route('payments.follow-up.dismiss', $driver),
            ])->values(),
        ]);
    }

    public function dismissFollowUpReminder(Driver $driver)
    {
        $tenant = Auth::user()->currentTenant();
        $this->authorizeDriver($driver, $tenant);

        $driver->payment_reminder_dismissed_at = now();
        $driver->save();

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Reminder dismissed.',
            ]);
        }

        return redirect()->back()->with('success', 'Reminder dismissed.');
    }

    public function destroy(
        Payment $payment,
        PaymentAllocationService $paymentAllocationService,
        DailyFinancialSheetService $dailyFinancialSheetService
    ) {
        abort_unless($this->canManagePayments(), 403);

        $tenant = Auth::user()->currentTenant();
        $payment->load('driver');
        $this->authorizeDriver($payment->driver, $tenant);

        try {
            $driverId = $payment->driver_id;
            $paymentAllocationService->deletePayment($payment, $dailyFinancialSheetService, (int) Auth::id());
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return redirect()->route('payments.driver', $payment->driver_id)
                ->with('error', collect($exception->errors())->flatten()->first());
        }

        return redirect()->route('payments.driver', $driverId)
            ->with('success', 'Payment deleted successfully.');
    }

    private function canManagePayments(): bool
    {
        return strtolower(trim((string) Auth::user()?->email)) === self::PAYMENT_MANAGER_EMAIL;
    }

    private function validatePaymentRequest(Request $request, $tenant, ?Payment $payment = null): array
    {
        $driverRule = Rule::exists('drivers', 'id')->where(fn ($query) => $query->where('tenant_id', $tenant->id));

        if ($payment?->isPosted()) {
            return $request->validate([
                'payment_method' => 'required|string|max:255',
                'bank_account_id' => [
                    'nullable',
                    Rule::requiredIf(fn () => Payment::requiresBankAccount($request->input('payment_method'))),
                    Rule::exists('bank_accounts', 'id')->where(fn ($query) => $query->where('tenant_id', $tenant->id)),
                ],
                'payment_date' => 'required|date',
                'amount' => 'required|numeric|min:0.01',
                'notes' => 'nullable|string',
            ]);
        }

        return $request->validate([
            'driver_id' => [
                'required',
                $driverRule,
            ],
            'payment_method' => 'required|string|max:255',
            'bank_account_id' => [
                'nullable',
                Rule::requiredIf(fn () => Payment::requiresBankAccount($request->input('payment_method'))),
                Rule::exists('bank_accounts', 'id')->where(fn ($query) => $query->where('tenant_id', $tenant->id)),
            ],
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
            'auto_manage_invoices' => 'boolean',
            'allocations' => 'nullable|array',
            'allocations.*' => 'nullable|numeric|min:0',
        ]);
    }

    private function authorizeDriver(?Driver $driver, $tenant): void
    {
        abort_unless($tenant && $driver && (int) $driver->tenant_id === (int) $tenant->id, 403);
    }

    private function driverSummary(Driver $driver): array
    {
        $postedPayments = $driver->payments()->posted();

        return [
            'total_invoiced' => (float) $driver->invoices()->sum('total_amount'),
            'total_paid' => (float) $postedPayments->sum('amount'),
            'total_pending' => (float) $driver->payments()->pending()->sum('amount'),
            'total_allocated' => (float) $postedPayments
                ->join('payment_allocations', 'payments.id', '=', 'payment_allocations.payment_id')
                ->sum('payment_allocations.allocated_amount'),
            'total_due' => (float) $driver->activeInvoices()->sum('balance_amount'),
            'overdue_due' => (float) $driver->overdueInvoices()->sum('balance_amount'),
        ];
    }

    private function bankAccountsForTenant(int $tenantId)
    {
        if (! Schema::hasTable('bank_accounts')) {
            return collect();
        }

        return BankAccount::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('bank_name')
            ->get();
    }
}
