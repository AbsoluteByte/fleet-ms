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
            ->with(['car.carModel', 'createdByUser', 'bankAccount'])
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
        foreach ($filtered->where('direction', 'in')->filter(fn ($entry) => Payment::requiresBankAccount($entry['payment_method'] ?? null)) as $entry) {
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
        foreach ($filtered->where('direction', 'out')->filter(fn ($entry) => Payment::requiresBankAccount($entry['payment_method'] ?? null)) as $entry) {
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

    /**
     * Approve pending sheet entries for a date.
     *
     * When $entryIds is null or empty, all pending entries are approved.
     * When a sheet already exists for the date, new totals are merged into it.
     *
     * @param  list<string>|null  $entryIds  Keys like payment-12, expense-3, deposit-refund-5
     */
    public function approveSheet(
        int $tenantId,
        string $date,
        int $approvedByUserId,
        ?string $notes = null,
        ?array $entryIds = null
    ): DailyFinancialSheet {
        $entries = $this->entriesForDate($tenantId, $date);
        $pendingEntries = $entries
            ->where('posting_status', Payment::POSTING_STATUS_PENDING)
            ->values();

        if ($pendingEntries->isEmpty()) {
            throw ValidationException::withMessages([
                'date' => 'There are no pending entries to approve for this date.',
            ]);
        }

        $selectedIds = $this->normalizeSelectedEntryIds($entryIds);
        if ($selectedIds !== []) {
            $pendingEntries = $pendingEntries
                ->filter(fn (array $entry) => in_array($entry['id'], $selectedIds, true))
                ->values();

            if ($pendingEntries->isEmpty()) {
                throw ValidationException::withMessages([
                    'entry_ids' => 'Select at least one pending entry to approve.',
                ]);
            }

            $allPendingIds = $entries
                ->where('posting_status', Payment::POSTING_STATUS_PENDING)
                ->pluck('id')
                ->all();
            $invalidSelected = array_values(array_diff($selectedIds, $allPendingIds));
            if ($invalidSelected !== []) {
                throw ValidationException::withMessages([
                    'entry_ids' => 'One or more selected entries are not pending for this date.',
                ]);
            }
        }

        $batchTotals = $this->computeTotals($pendingEntries, pendingOnly: false);
        $paymentIds = $this->idsOfType($pendingEntries, 'payment');
        $expenseIds = $this->idsOfType($pendingEntries, 'expense');
        $refundIds = $this->idsOfType($pendingEntries, 'deposit-refund');

        return DB::transaction(function () use (
            $tenantId,
            $date,
            $approvedByUserId,
            $notes,
            $batchTotals,
            $paymentIds,
            $expenseIds,
            $refundIds
        ) {
            if ($paymentIds !== []) {
                $pendingPayments = Payment::query()
                    ->pending()
                    ->whereIn('id', $paymentIds)
                    ->whereDate('payment_date', $date)
                    ->whereHas('driver', fn ($query) => $query->where('tenant_id', $tenantId))
                    ->lockForUpdate()
                    ->get();

                foreach ($pendingPayments as $payment) {
                    $this->paymentAllocationService->postPayment($payment);
                }
            }

            if ($expenseIds !== []) {
                Expense::query()
                    ->pending()
                    ->where('tenant_id', $tenantId)
                    ->whereDate('date', $date)
                    ->whereIn('id', $expenseIds)
                    ->update(['posting_status' => Expense::POSTING_STATUS_POSTED]);
            }

            if ($refundIds !== []) {
                DepositRefund::query()
                    ->pending()
                    ->where('tenant_id', $tenantId)
                    ->whereDate('refund_date', $date)
                    ->whereIn('id', $refundIds)
                    ->update(['posting_status' => DepositRefund::POSTING_STATUS_POSTED]);
            }

            $existing = DailyFinancialSheet::query()
                ->where('tenant_id', $tenantId)
                ->whereDate('sheet_date', $date)
                ->lockForUpdate()
                ->first();

            $cashIn = $batchTotals['cash_in'];
            $cashOut = $batchTotals['cash_out'];
            $bankIn = $batchTotals['bank_in'];
            $bankOut = $batchTotals['bank_out'];
            $mergedNotes = $this->mergeApprovalNotes($existing?->approval_notes, $notes);

            if ($existing) {
                $cashIn = $this->addMoney((float) $existing->cash_in, $batchTotals['cash_in']);
                $cashOut = $this->addMoney((float) $existing->cash_out, $batchTotals['cash_out']);
                $bankIn = $this->mergeBankTotals($existing->bank_in_json ?? [], $batchTotals['bank_in']);
                $bankOut = $this->mergeBankTotals($existing->bank_out_json ?? [], $batchTotals['bank_out']);

                $existing->fill([
                    'status' => DailyFinancialSheet::STATUS_APPROVED,
                    'cash_in' => $cashIn,
                    'cash_out' => $cashOut,
                    'bank_in_json' => $bankIn,
                    'bank_out_json' => $bankOut,
                    'approval_notes' => $mergedNotes,
                    'approved_by' => $approvedByUserId,
                    'approved_at' => now(),
                ]);
                $existing->save();

                return $existing->refresh();
            }

            return DailyFinancialSheet::query()->create([
                'tenant_id' => $tenantId,
                'sheet_date' => $date,
                'status' => DailyFinancialSheet::STATUS_APPROVED,
                'cash_in' => $cashIn,
                'cash_out' => $cashOut,
                'bank_in_json' => $bankIn,
                'bank_out_json' => $bankOut,
                'approval_notes' => $mergedNotes,
                'approved_by' => $approvedByUserId,
                'approved_at' => now(),
            ]);
        });
    }

    /**
     * @param  list<string>|null  $entryIds
     * @return list<string>
     */
    private function normalizeSelectedEntryIds(?array $entryIds): array
    {
        if ($entryIds === null || $entryIds === []) {
            return [];
        }

        $normalized = [];
        foreach ($entryIds as $id) {
            $id = trim((string) $id);
            if ($id === '') {
                continue;
            }
            if (! preg_match('/^(payment|expense|deposit-refund)-\d+$/', $id)) {
                throw ValidationException::withMessages([
                    'entry_ids' => 'Invalid entry selection.',
                ]);
            }
            $normalized[] = $id;
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $entries
     * @return list<int>
     */
    private function idsOfType(Collection $entries, string $type): array
    {
        $prefix = $type.'-';

        return $entries
            ->pluck('id')
            ->filter(fn ($id) => is_string($id) && str_starts_with($id, $prefix))
            ->map(fn (string $id) => (int) substr($id, strlen($prefix)))
            ->filter(fn (int $id) => $id > 0)
            ->values()
            ->all();
    }

    private function addMoney(float $left, float $right): float
    {
        return round($left + $right, 2);
    }

    /**
     * @param  array<int, array{bank_account_id: int|null, bank_name: string, account_number: string, total: float}>  $existing
     * @param  array<int, array{bank_account_id: int|null, bank_name: string, account_number: string, total: float}>  $incoming
     * @return array<int, array{bank_account_id: int|null, bank_name: string, account_number: string, total: float}>
     */
    private function mergeBankTotals(array $existing, array $incoming): array
    {
        $merged = [];

        foreach (array_merge($existing, $incoming) as $row) {
            $key = (string) ($row['bank_account_id'] ?? 'unknown');
            if (! isset($merged[$key])) {
                $merged[$key] = [
                    'bank_account_id' => $row['bank_account_id'] ?? null,
                    'bank_name' => $row['bank_name'] ?? 'Bank',
                    'account_number' => $row['account_number'] ?? '',
                    'total' => 0.0,
                ];
            }
            $merged[$key]['total'] = $this->addMoney(
                (float) $merged[$key]['total'],
                (float) ($row['total'] ?? 0)
            );
        }

        return array_values($merged);
    }

    private function mergeApprovalNotes(?string $existing, ?string $incoming): ?string
    {
        $existing = trim((string) $existing);
        $incoming = trim((string) $incoming);

        if ($existing === '' && $incoming === '') {
            return null;
        }
        if ($incoming === '') {
            return $existing === '' ? null : $existing;
        }
        if ($existing === '') {
            return $incoming;
        }

        return $existing."\n\n---\n".$incoming;
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
        $isDaily = $expense->isDailyExpense();
        $paymentMethod = $expense->payment_method ?: 'Cash';

        if ($isDaily) {
            $description = trim((string) ($expense->title ?: $expense->description));
            if ($expense->notes) {
                $description = trim($description.' — '.$expense->notes);
            }
            $category = 'Daily expense';
            $carRegistration = null;
        } else {
            $carLabel = $expense->car
                ? trim(($expense->car->registration ?? '').' '.($expense->car->carModel->name ?? ''))
                : '—';
            $description = trim($carLabel.' — '.$expense->description);
            $category = $expense->type;
            $carRegistration = $expense->car?->registration;
        }

        return [
            'id' => 'expense-'.$expense->id,
            'sort_at' => $expense->created_at?->timestamp ?? 0,
            'direction' => 'out',
            'employee' => $expense->createdByUser?->name ?? '—',
            'description' => $description,
            'category' => $category,
            'car_registration' => $carRegistration,
            'payment_method' => $paymentMethod,
            'bank_account_id' => $expense->bank_account_id,
            'bank_name' => $expense->bankAccount?->bank_name,
            'account_number' => $expense->bankAccount?->account_number,
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
