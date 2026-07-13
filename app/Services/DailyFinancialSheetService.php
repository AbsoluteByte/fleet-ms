<?php

namespace App\Services;

use App\Models\DailyFinancialSheet;
use App\Models\DepositRefund;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DailyFinancialSheetService
{
    public function __construct(
        private PaymentAllocationService $paymentAllocationService
    ) {}

    public function isDateApproved(int $tenantId, string $date): bool
    {
        return DailyFinancialSheet::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('sheet_date', $date)
            ->where('status', DailyFinancialSheet::STATUS_APPROVED)
            ->exists();
    }

    public function ensureDateNotApproved(int $tenantId, string $date, string $field = 'payment_date'): void
    {
        if ($this->isDateApproved($tenantId, $date)) {
            throw ValidationException::withMessages([
                $field => 'This date has already been approved on the daily financial sheet. Choose a different date.',
            ]);
        }
    }

    public function ensureExpenseDateNotApproved(int $tenantId, string $date): void
    {
        if ($this->isDateApproved($tenantId, $date)) {
            throw ValidationException::withMessages([
                'date' => 'This date has already been approved on the daily financial sheet. Choose a different date.',
            ]);
        }
    }

    /**
     * @return list<string>
     */
    public function openSheetDates(int $tenantId): array
    {
        $paymentDates = Payment::query()
            ->pending()
            ->whereHas('driver', fn ($query) => $query->where('tenant_id', $tenantId))
            ->pluck('payment_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString());

        $expenseDates = Expense::query()
            ->pending()
            ->where('tenant_id', $tenantId)
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString());

        $refundDates = DepositRefund::query()
            ->pending()
            ->where('tenant_id', $tenantId)
            ->pluck('refund_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString());

        return $paymentDates
            ->merge($expenseDates)
            ->merge($refundDates)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function sheetForDate(int $tenantId, string $date): ?DailyFinancialSheet
    {
        return DailyFinancialSheet::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('sheet_date', $date)
            ->first();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function entriesForDate(int $tenantId, string $date): Collection
    {
        $payments = Payment::query()
            ->with([
                'driver',
                'bankAccount',
                'createdByUser',
                'sourceAgreement.car',
                'allocations.invoice.sourceAgreement.car',
            ])
            ->whereDate('payment_date', $date)
            ->whereHas('driver', fn ($query) => $query->where('tenant_id', $tenantId))
            ->get()
            ->map(fn (Payment $payment) => $this->formatPaymentEntry($payment));

        $expenses = Expense::query()
            ->with(['car.carModel', 'createdByUser'])
            ->where('tenant_id', $tenantId)
            ->whereDate('date', $date)
            ->get()
            ->map(fn (Expense $expense) => $this->formatExpenseEntry($expense));

        $refunds = DepositRefund::query()
            ->with(['driver', 'bankAccount', 'createdByUser', 'agreement.car'])
            ->where('tenant_id', $tenantId)
            ->whereDate('refund_date', $date)
            ->get()
            ->map(fn (DepositRefund $refund) => $this->formatDepositRefundEntry($refund));

        return collect($payments)
            ->merge($expenses)
            ->merge($refunds)
            ->sortBy('sort_at')
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $entries
     * @return array{cash_in: float, cash_out: float, net_cash: float, bank_in: array<int, array{bank_account_id: int|null, bank_name: string, account_number: string, total: float}>, bank_out: array<int, array{bank_account_id: int|null, bank_name: string, account_number: string, total: float}>}
     */
    public function computeTotals(Collection $entries, bool $pendingOnly = false): array
    {
        $filtered = $pendingOnly
            ? $entries->where('posting_status', Payment::POSTING_STATUS_PENDING)
            : $entries;

        $cashIn = round((float) $filtered
            ->where('direction', 'in')
            ->where('payment_method', 'Cash')
            ->sum('amount'), 2);

        $cashOut = round((float) $filtered
            ->where('direction', 'out')
            ->where('payment_method', 'Cash')
            ->sum('amount'), 2);

        $bankIn = [];
        foreach ($filtered->where('direction', 'in')->where('payment_method', 'Bank Transfer') as $entry) {
            $key = (string) ($entry['bank_account_id'] ?? 'unknown');
            if (! isset($bankIn[$key])) {
                $bankIn[$key] = [
                    'bank_account_id' => $entry['bank_account_id'],
                    'bank_name' => $entry['bank_name'] ?? 'Bank',
                    'account_number' => $entry['account_number'] ?? '',
                    'total' => 0.0,
                ];
            }
            $bankIn[$key]['total'] = round($bankIn[$key]['total'] + (float) $entry['amount'], 2);
        }

        $bankOut = [];
        foreach ($filtered->where('direction', 'out')->where('payment_method', 'Bank Transfer') as $entry) {
            $key = (string) ($entry['bank_account_id'] ?? 'unknown');
            if (! isset($bankOut[$key])) {
                $bankOut[$key] = [
                    'bank_account_id' => $entry['bank_account_id'],
                    'bank_name' => $entry['bank_name'] ?? 'Bank',
                    'account_number' => $entry['account_number'] ?? '',
                    'total' => 0.0,
                ];
            }
            $bankOut[$key]['total'] = round($bankOut[$key]['total'] + (float) $entry['amount'], 2);
        }

        return [
            'cash_in' => $cashIn,
            'cash_out' => $cashOut,
            'net_cash' => round($cashIn - $cashOut, 2),
            'bank_in' => array_values($bankIn),
            'bank_out' => array_values($bankOut),
        ];
    }

    public function approveSheet(int $tenantId, string $date, int $approvedByUserId, ?string $notes = null): DailyFinancialSheet
    {
        if ($this->isDateApproved($tenantId, $date)) {
            throw ValidationException::withMessages([
                'date' => 'This daily financial sheet has already been approved.',
            ]);
        }

        $entries = $this->entriesForDate($tenantId, $date);
        $pendingEntries = $entries->where('posting_status', Payment::POSTING_STATUS_PENDING);

        if ($pendingEntries->isEmpty()) {
            throw ValidationException::withMessages([
                'date' => 'There are no pending entries to approve for this date.',
            ]);
        }

        $totals = $this->computeTotals($entries, pendingOnly: true);

        return DB::transaction(function () use ($tenantId, $date, $approvedByUserId, $notes, $totals) {
            $pendingPayments = Payment::query()
                ->pending()
                ->whereDate('payment_date', $date)
                ->whereHas('driver', fn ($query) => $query->where('tenant_id', $tenantId))
                ->lockForUpdate()
                ->get();

            foreach ($pendingPayments as $payment) {
                $this->paymentAllocationService->postPayment($payment);
            }

            Expense::query()
                ->pending()
                ->where('tenant_id', $tenantId)
                ->whereDate('date', $date)
                ->update(['posting_status' => Expense::POSTING_STATUS_POSTED]);

            DepositRefund::query()
                ->pending()
                ->where('tenant_id', $tenantId)
                ->whereDate('refund_date', $date)
                ->update(['posting_status' => DepositRefund::POSTING_STATUS_POSTED]);

            return DailyFinancialSheet::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'sheet_date' => $date,
                ],
                [
                    'status' => DailyFinancialSheet::STATUS_APPROVED,
                    'cash_in' => $totals['cash_in'],
                    'cash_out' => $totals['cash_out'],
                    'bank_in_json' => $totals['bank_in'],
                    'bank_out_json' => $totals['bank_out'],
                    'approval_notes' => $notes,
                    'approved_by' => $approvedByUserId,
                    'approved_at' => now(),
                ]
            );
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function formatPaymentEntry(Payment $payment): array
    {
        $driverName = trim(($payment->driver->first_name ?? '').' '.($payment->driver->last_name ?? ''));
        $targetInvoice = $this->resolveTargetInvoice($payment);
        $agreementId = $payment->allocation_source_id
            ?? $this->agreementIdFromInvoice($targetInvoice);
        $carRegistration = $this->resolvePaymentCarRegistration($payment, $targetInvoice);

        return [
            'id' => 'payment-'.$payment->id,
            'sort_at' => $payment->created_at?->timestamp ?? 0,
            'direction' => 'in',
            'employee' => $payment->createdByUser?->name ?? '—',
            'description' => trim($driverName.($payment->notes ? ' — '.$payment->notes : '')),
            'category' => $payment->allocation_source_id ? 'Agreement payment' : 'Driver payment',
            'car_registration' => $carRegistration,
            'agreement_id' => $agreementId,
            'agreement_url' => $agreementId ? route('agreements.show', $agreementId) : null,
            'payment_method' => $payment->payment_method,
            'bank_account_id' => $payment->bank_account_id,
            'bank_name' => $payment->bankAccount?->bank_name,
            'account_number' => $payment->bankAccount?->account_number,
            'amount' => (float) $payment->amount,
            'posting_status' => $payment->posting_status,
        ];
    }

    private function resolvePaymentCarRegistration(Payment $payment, ?Invoice $targetInvoice = null): ?string
    {
        $registration = $payment->sourceAgreement?->car?->registration;
        if (is_string($registration) && $registration !== '') {
            return $registration;
        }

        foreach ($payment->allocations as $allocation) {
            $label = $allocation->invoice?->vehicleRegistrationLabel();
            if (is_string($label) && $label !== '' && $label !== '—') {
                return $label;
            }
        }

        $manualInvoiceIds = array_keys($payment->pending_manual_allocations ?? []);
        if ($manualInvoiceIds !== []) {
            $invoice = Invoice::query()
                ->with('sourceAgreement.car')
                ->where('driver_id', $payment->driver_id)
                ->whereIn('id', $manualInvoiceIds)
                ->first();

            $label = $invoice?->vehicleRegistrationLabel();
            if (is_string($label) && $label !== '' && $label !== '—') {
                return $label;
            }
        }

        $targetInvoice ??= $this->resolveTargetInvoice($payment);
        $label = $targetInvoice?->vehicleRegistrationLabel();
        if (is_string($label) && $label !== '' && $label !== '—') {
            return $label;
        }

        return null;
    }

    private function resolveTargetInvoice(Payment $payment): ?Invoice
    {
        if ($payment->allocation_source_id && is_array($payment->allocation_invoice_types)) {
            return Invoice::query()
                ->with('sourceAgreement.car')
                ->where('driver_id', $payment->driver_id)
                ->where('source_id', $payment->allocation_source_id)
                ->whereIn('invoice_type', $payment->allocation_invoice_types)
                ->where('balance_amount', '>', 0)
                ->orderBy('invoice_date')
                ->orderBy('due_date')
                ->orderBy('id')
                ->first();
        }

        $manualInvoiceIds = array_keys($payment->pending_manual_allocations ?? []);
        if ($manualInvoiceIds !== []) {
            return Invoice::query()
                ->with('sourceAgreement.car')
                ->where('driver_id', $payment->driver_id)
                ->whereIn('id', $manualInvoiceIds)
                ->orderBy('invoice_date')
                ->orderBy('due_date')
                ->orderBy('id')
                ->first();
        }

        if ($payment->auto_allocate) {
            return Invoice::query()
                ->with('sourceAgreement.car')
                ->where('driver_id', $payment->driver_id)
                ->where('balance_amount', '>', 0)
                ->orderBy('invoice_date')
                ->orderBy('due_date')
                ->orderBy('id')
                ->first();
        }

        return null;
    }

    private function agreementIdFromInvoice(?Invoice $invoice): ?int
    {
        if (! $invoice || ! in_array($invoice->invoice_type, ['agreement', 'agreement_deposit'], true)) {
            return null;
        }

        return $invoice->source_id ? (int) $invoice->source_id : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatExpenseEntry(Expense $expense): array
    {
        $carLabel = $expense->car
            ? trim(($expense->car->registration ?? '').' '.($expense->car->carModel->name ?? ''))
            : '—';

        return [
            'id' => 'expense-'.$expense->id,
            'sort_at' => $expense->created_at?->timestamp ?? 0,
            'direction' => 'out',
            'employee' => $expense->createdByUser?->name ?? '—',
            'description' => trim($carLabel.' — '.$expense->description),
            'category' => $expense->type,
            'car_registration' => $expense->car?->registration,
            'payment_method' => 'Cash',
            'bank_account_id' => null,
            'bank_name' => null,
            'account_number' => null,
            'amount' => (float) $expense->amount,
            'posting_status' => $expense->posting_status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatDepositRefundEntry(DepositRefund $refund): array
    {
        $driverName = trim(($refund->driver->first_name ?? '').' '.($refund->driver->last_name ?? ''));
        $registration = $refund->agreement?->car?->registration;

        return [
            'id' => 'deposit-refund-'.$refund->id,
            'sort_at' => $refund->created_at?->timestamp ?? 0,
            'direction' => 'out',
            'employee' => $refund->createdByUser?->name ?? '—',
            'description' => trim($driverName.' — Deposit refund'),
            'category' => 'Deposit refund',
            'car_registration' => $registration,
            'agreement_id' => $refund->agreement_id,
            'agreement_url' => route('agreements.show', $refund->agreement_id),
            'payment_method' => $refund->payment_method,
            'bank_account_id' => $refund->bank_account_id,
            'bank_name' => $refund->bankAccount?->bank_name,
            'account_number' => $refund->bankAccount?->account_number,
            'amount' => (float) $refund->amount,
            'posting_status' => $refund->posting_status,
        ];
    }
}
