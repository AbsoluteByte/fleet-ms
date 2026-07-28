<?php

namespace App\Services;

use App\Models\DailyFinancialSheet;
use App\Models\DepositRefund;
use App\Models\DriverCreditTransaction;
use App\Models\Expense;
use App\Models\FinancialSheetAdjustment;
use App\Models\Invoice;
use App\Models\OtherPayment;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DailyFinancialSheetService
{
    public function __construct(
        private PaymentAllocationService $paymentAllocationService,
        private DriverCreditService $driverCreditService
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

        $otherPaymentDates = OtherPayment::query()
            ->pending()
            ->where('tenant_id', $tenantId)
            ->pluck('payment_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString());

        $refundDates = DepositRefund::query()
            ->pending()
            ->where('tenant_id', $tenantId)
            ->pluck('refund_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString());

        $creditDates = DriverCreditTransaction::query()
            ->pending()
            ->where('tenant_id', $tenantId)
            ->pluck('request_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString());

        return $paymentDates
            ->merge($expenseDates)
            ->merge($otherPaymentDates)
            ->merge($refundDates)
            ->merge($creditDates)
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
        $refunds = DepositRefund::query()
            ->with(['driver', 'bankAccount', 'createdByUser', 'agreement.car'])
            ->where('tenant_id', $tenantId)
            ->whereDate('refund_date', $date)
            ->get();

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
            ->map(function (Payment $payment) use ($refunds) {
                $linkedRefund = $refunds->first(fn (DepositRefund $refund) => in_array(
                    $payment->id,
                    array_filter([$refund->debt_payment_id, $refund->refund_credit_payment_id]),
                    true
                ));

                return $this->formatPaymentEntry($payment, $linkedRefund);
            });

        $expenses = Expense::query()
            ->with(['car.carModel', 'createdByUser', 'bankAccount'])
            ->where('tenant_id', $tenantId)
            ->whereDate('date', $date)
            ->get()
            ->map(fn (Expense $expense) => $this->formatExpenseEntry($expense));

        $otherPayments = OtherPayment::query()
            ->with(['car.carModel', 'createdByUser', 'bankAccount'])
            ->where('tenant_id', $tenantId)
            ->whereDate('payment_date', $date)
            ->get()
            ->map(fn (OtherPayment $otherPayment) => $this->formatOtherPaymentEntry($otherPayment));

        $refundEntries = $refunds
            ->map(fn (DepositRefund $refund) => $this->formatDepositRefundEntry($refund));

        $creditEntries = DriverCreditTransaction::query()
            ->with(['driver', 'bankAccount', 'createdByUser'])
            ->where('tenant_id', $tenantId)
            ->whereDate('request_date', $date)
            ->get()
            ->map(fn (DriverCreditTransaction $transaction) => $this->formatDriverCreditEntry($transaction));

        $adjustmentEntries = FinancialSheetAdjustment::query()
            ->with(['bankAccount', 'createdByUser'])
            ->where('tenant_id', $tenantId)
            ->whereDate('sheet_date', $date)
            ->get()
            ->map(fn (FinancialSheetAdjustment $adjustment) => $this->formatAdjustmentEntry($adjustment));

        return collect($payments)
            ->merge($expenses)
            ->merge($otherPayments)
            ->merge($refundEntries)
            ->merge($creditEntries)
            ->merge($adjustmentEntries)
            ->sortBy('sort_at')
            ->values();
    }

    public function recordPaymentReversal(Payment $payment, int $actorId): void
    {
        $tenantId = (int) $payment->driver->tenant_id;
        $sheetDate = $payment->payment_date?->toDateString();

        if (! $sheetDate || ! $this->isDateApproved($tenantId, $sheetDate)) {
            return;
        }

        $driverName = trim(($payment->driver->first_name ?? '').' '.($payment->driver->last_name ?? ''));

        $adjustment = FinancialSheetAdjustment::query()->create([
            'tenant_id' => $tenantId,
            'sheet_date' => $sheetDate,
            'source_type' => FinancialSheetAdjustment::SOURCE_PAYMENT,
            'source_id' => $payment->id,
            'event_type' => FinancialSheetAdjustment::EVENT_REVERSAL,
            'direction' => 'out',
            'amount' => (float) $payment->amount,
            'payment_method' => $payment->payment_method,
            'bank_account_id' => $payment->bank_account_id,
            'description' => trim($driverName.' — Payment reversed — '.$payment->payment_no),
            'metadata' => [
                'payment_no' => $payment->payment_no,
                'original_amount' => (float) $payment->amount,
            ],
            'created_by' => $actorId,
        ]);

        $this->mergeAdjustmentIntoApprovedSheet($tenantId, $sheetDate, $adjustment);
    }

    /**
     * @param  array<string, mixed>  $oldSnapshot
     */
    public function recordPaymentCorrection(Payment $payment, array $oldSnapshot, int $actorId): void
    {
        $tenantId = (int) $payment->driver->tenant_id;
        $oldDate = (string) ($oldSnapshot['payment_date'] ?? '');
        $newDate = $payment->payment_date?->toDateString() ?? '';

        if ($oldDate !== '' && $oldDate !== $newDate && $this->isDateApproved($tenantId, $oldDate)) {
            $this->createPaymentCorrectionAdjustment(
                tenantId: $tenantId,
                sheetDate: $oldDate,
                payment: $payment,
                snapshot: $oldSnapshot,
                direction: 'out',
                amount: (float) ($oldSnapshot['amount'] ?? 0),
                eventType: FinancialSheetAdjustment::EVENT_CORRECTION,
                descriptionSuffix: 'moved from this date',
                actorId: $actorId,
                metadata: ['reason' => 'date_change_old_date', 'new_date' => $newDate]
            );
        }

        if ($newDate === '' || ! $this->isDateApproved($tenantId, $newDate)) {
            return;
        }

        if ($oldDate !== $newDate) {
            $this->createPaymentCorrectionAdjustment(
                tenantId: $tenantId,
                sheetDate: $newDate,
                payment: $payment,
                snapshot: $oldSnapshot,
                direction: 'in',
                amount: (float) $payment->amount,
                eventType: FinancialSheetAdjustment::EVENT_CORRECTION,
                descriptionSuffix: 'moved to this date',
                actorId: $actorId,
                metadata: ['reason' => 'date_change_new_date', 'old_date' => $oldDate]
            );

            return;
        }

        $oldContribution = $this->paymentContributionEntry($oldSnapshot);
        $newContribution = $this->paymentContributionEntry([
            'amount' => (float) $payment->amount,
            'payment_method' => $payment->payment_method,
            'bank_account_id' => $payment->bank_account_id,
        ]);

        $this->createContributionDeltaAdjustments(
            $tenantId,
            $newDate,
            $payment,
            $oldSnapshot,
            $oldContribution,
            $newContribution,
            $actorId
        );
    }

    private function mergeAdjustmentIntoApprovedSheet(int $tenantId, string $date, FinancialSheetAdjustment $adjustment): void
    {
        $signedTotals = $this->signedTotalsForAdjustment($adjustment);

        DB::transaction(function () use ($tenantId, $date, $signedTotals) {
            $existing = DailyFinancialSheet::query()
                ->where('tenant_id', $tenantId)
                ->whereDate('sheet_date', $date)
                ->lockForUpdate()
                ->first();

            if (! $existing || ! $existing->isApproved()) {
                return;
            }

            $existing->fill([
                'cash_in' => $this->addMoney((float) $existing->cash_in, $signedTotals['cash_in']),
                'cash_out' => $this->addMoney((float) $existing->cash_out, $signedTotals['cash_out']),
                'bank_in_json' => $this->mergeBankTotals($existing->bank_in_json ?? [], $signedTotals['bank_in']),
                'bank_out_json' => $this->mergeBankTotals($existing->bank_out_json ?? [], $signedTotals['bank_out']),
            ]);
            $existing->save();
        });
    }

    /**
     * @return array{cash_in: float, cash_out: float, bank_in: array<int, array{bank_account_id: int|null, bank_name: string, account_number: string, total: float}>, bank_out: array<int, array{bank_account_id: int|null, bank_name: string, account_number: string, total: float}>}
     */
    private function signedTotalsForAdjustment(FinancialSheetAdjustment $adjustment): array
    {
        $adjustment->loadMissing('bankAccount');

        $magnitude = $this->computeTotals(collect([[
            'direction' => 'in',
            'payment_method' => $adjustment->payment_method,
            'bank_account_id' => $adjustment->bank_account_id,
            'bank_name' => $adjustment->bankAccount?->bank_name,
            'account_number' => $adjustment->bankAccount?->account_number,
            'amount' => (float) $adjustment->amount,
            'posting_status' => Payment::POSTING_STATUS_POSTED,
        ]]), pendingOnly: false);

        $sign = $adjustment->direction === 'out' ? -1 : 1;

        return [
            'cash_in' => round($sign * $magnitude['cash_in'], 2),
            'cash_out' => round($sign * $magnitude['cash_out'], 2),
            'bank_in' => $this->scaleBankTotals($magnitude['bank_in'], $sign),
            'bank_out' => $this->scaleBankTotals($magnitude['bank_out'], $sign),
        ];
    }

    /**
     * @param  array<int, array{bank_account_id: int|null, bank_name: string, account_number: string, total: float}>  $rows
     * @return array<int, array{bank_account_id: int|null, bank_name: string, account_number: string, total: float}>
     */
    private function scaleBankTotals(array $rows, int $sign): array
    {
        return array_map(function (array $row) use ($sign) {
            $row['total'] = round($sign * (float) ($row['total'] ?? 0), 2);

            return $row;
        }, $rows);
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

            $selectedIds = $this->expandSettlementEntryIds($tenantId, $date, $selectedIds);
            $pendingEntries = $pendingEntries
                ->filter(fn (array $entry) => in_array($entry['id'], $selectedIds, true))
                ->values();

            if ($pendingEntries->isEmpty()) {
                throw ValidationException::withMessages([
                    'entry_ids' => 'Select at least one pending entry to approve.',
                ]);
            }
        }

        $batchTotals = $this->computeTotals($pendingEntries, pendingOnly: false);
        $paymentIds = $this->idsOfType($pendingEntries, 'payment');
        $expenseIds = $this->idsOfType($pendingEntries, 'expense');
        $otherPaymentIds = $this->idsOfType($pendingEntries, 'other-payment');
        $refundIds = $this->idsOfType($pendingEntries, 'deposit-refund');
        $creditTransactionIds = $this->idsOfType($pendingEntries, 'driver-credit');

        return DB::transaction(function () use (
            $tenantId,
            $date,
            $approvedByUserId,
            $notes,
            $batchTotals,
            $paymentIds,
            $expenseIds,
            $otherPaymentIds,
            $refundIds,
            $creditTransactionIds
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

            if ($otherPaymentIds !== []) {
                OtherPayment::query()
                    ->pending()
                    ->where('tenant_id', $tenantId)
                    ->whereDate('payment_date', $date)
                    ->whereIn('id', $otherPaymentIds)
                    ->update(['posting_status' => OtherPayment::POSTING_STATUS_POSTED]);
            }

            if ($refundIds !== []) {
                DepositRefund::query()
                    ->pending()
                    ->where('tenant_id', $tenantId)
                    ->whereDate('refund_date', $date)
                    ->whereIn('id', $refundIds)
                    ->update(['posting_status' => DepositRefund::POSTING_STATUS_POSTED]);
            }

            if ($creditTransactionIds !== []) {
                $creditTransactions = DriverCreditTransaction::query()
                    ->pending()
                    ->where('tenant_id', $tenantId)
                    ->whereDate('request_date', $date)
                    ->whereIn('id', $creditTransactionIds)
                    ->lockForUpdate()
                    ->get();

                if ($creditTransactions->count() !== count($creditTransactionIds)) {
                    throw ValidationException::withMessages([
                        'entry_ids' => 'One or more credit transactions are no longer pending.',
                    ]);
                }

                foreach ($creditTransactions as $creditTransaction) {
                    $this->driverCreditService->post($creditTransaction, $approvedByUserId);
                }
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
     * Reject (discard) pending sheet entries for a date.
     *
     * When $entryIds is null or empty, all pending entries are rejected.
     * Deposit-refund selections expand to include linked settlement payments.
     *
     * @param  list<string>|null  $entryIds  Keys like payment-12, expense-3, deposit-refund-5
     * @return int Number of pending entry records discarded
     */
    public function rejectEntries(
        int $tenantId,
        string $date,
        int $actorId,
        ?array $entryIds = null
    ): int {
        $entries = $this->entriesForDate($tenantId, $date);
        $pendingEntries = $entries
            ->where('posting_status', Payment::POSTING_STATUS_PENDING)
            ->values();

        if ($pendingEntries->isEmpty()) {
            throw ValidationException::withMessages([
                'date' => 'There are no pending entries to reject for this date.',
            ]);
        }

        $selectedIds = $this->normalizeSelectedEntryIds($entryIds);
        if ($selectedIds !== []) {
            $allPendingIds = $pendingEntries->pluck('id')->all();
            $invalidSelected = array_values(array_diff($selectedIds, $allPendingIds));
            if ($invalidSelected !== []) {
                throw ValidationException::withMessages([
                    'entry_ids' => 'One or more selected entries are not pending for this date.',
                ]);
            }

            $selectedIds = $this->expandSettlementEntryIds($tenantId, $date, $selectedIds);
            $pendingEntries = $pendingEntries
                ->filter(fn (array $entry) => in_array($entry['id'], $selectedIds, true))
                ->values();

            if ($pendingEntries->isEmpty()) {
                throw ValidationException::withMessages([
                    'entry_ids' => 'Select at least one pending entry to reject.',
                ]);
            }
        } else {
            $selectedIds = $this->expandSettlementEntryIds(
                $tenantId,
                $date,
                $pendingEntries->pluck('id')->all()
            );
        }

        $paymentIds = $this->idsOfType(
            collect($selectedIds)->map(fn (string $id) => ['id' => $id]),
            'payment'
        );
        $expenseIds = $this->idsOfType($pendingEntries, 'expense');
        $otherPaymentIds = $this->idsOfType($pendingEntries, 'other-payment');
        $refundIds = $this->idsOfType($pendingEntries, 'deposit-refund');
        $creditTransactionIds = $this->idsOfType($pendingEntries, 'driver-credit');

        return DB::transaction(function () use (
            $tenantId,
            $date,
            $actorId,
            $paymentIds,
            $expenseIds,
            $otherPaymentIds,
            $refundIds,
            $creditTransactionIds
        ) {
            $rejectedCount = 0;
            $deletedPaymentIds = [];

            if ($refundIds !== []) {
                $refunds = DepositRefund::query()
                    ->pending()
                    ->where('tenant_id', $tenantId)
                    ->whereDate('refund_date', $date)
                    ->whereIn('id', $refundIds)
                    ->lockForUpdate()
                    ->get();

                foreach ($refunds as $refund) {
                    $linkedPaymentIds = array_values(array_filter([
                        $refund->debt_payment_id ? (int) $refund->debt_payment_id : null,
                        $refund->refund_credit_payment_id ? (int) $refund->refund_credit_payment_id : null,
                    ]));

                    $refund->update([
                        'debt_payment_id' => null,
                        'refund_credit_payment_id' => null,
                    ]);

                    foreach ($linkedPaymentIds as $linkedPaymentId) {
                        $payment = Payment::query()
                            ->whereKey($linkedPaymentId)
                            ->whereHas('driver', fn ($query) => $query->where('tenant_id', $tenantId))
                            ->lockForUpdate()
                            ->first();

                        if ($payment) {
                            $this->paymentAllocationService->deletePayment($payment, $this, $actorId);
                            $deletedPaymentIds[] = $linkedPaymentId;
                        }
                    }

                    $refund->delete();
                    $rejectedCount++;
                }
            }

            $remainingPaymentIds = array_values(array_diff($paymentIds, $deletedPaymentIds));
            if ($remainingPaymentIds !== []) {
                $payments = Payment::query()
                    ->pending()
                    ->whereIn('id', $remainingPaymentIds)
                    ->whereDate('payment_date', $date)
                    ->whereHas('driver', fn ($query) => $query->where('tenant_id', $tenantId))
                    ->lockForUpdate()
                    ->get();

                foreach ($payments as $payment) {
                    $this->paymentAllocationService->deletePayment($payment, $this, $actorId);
                    $rejectedCount++;
                }
            }

            if ($expenseIds !== []) {
                $expenses = Expense::query()
                    ->pending()
                    ->where('tenant_id', $tenantId)
                    ->whereDate('date', $date)
                    ->whereIn('id', $expenseIds)
                    ->lockForUpdate()
                    ->get();

                foreach ($expenses as $expense) {
                    $expense->delete();
                    $rejectedCount++;
                }
            }

            if ($otherPaymentIds !== []) {
                $otherPayments = OtherPayment::query()
                    ->pending()
                    ->where('tenant_id', $tenantId)
                    ->whereDate('payment_date', $date)
                    ->whereIn('id', $otherPaymentIds)
                    ->lockForUpdate()
                    ->get();

                foreach ($otherPayments as $otherPayment) {
                    $otherPayment->delete();
                    $rejectedCount++;
                }
            }

            if ($creditTransactionIds !== []) {
                $creditTransactions = DriverCreditTransaction::query()
                    ->pending()
                    ->where('tenant_id', $tenantId)
                    ->whereDate('request_date', $date)
                    ->whereIn('id', $creditTransactionIds)
                    ->lockForUpdate()
                    ->get();

                if ($creditTransactions->count() !== count($creditTransactionIds)) {
                    throw ValidationException::withMessages([
                        'entry_ids' => 'One or more credit transactions are no longer pending.',
                    ]);
                }

                foreach ($creditTransactions as $creditTransaction) {
                    $this->driverCreditService->cancelPending($creditTransaction);
                    $rejectedCount++;
                }
            }

            if ($rejectedCount === 0) {
                throw ValidationException::withMessages([
                    'entry_ids' => 'No pending entries were rejected.',
                ]);
            }

            return $rejectedCount;
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
            if (! preg_match('/^(payment|expense|deposit-refund|driver-credit)-\d+$/', $id)) {
                throw ValidationException::withMessages([
                    'entry_ids' => 'Invalid entry selection.',
                ]);
            }
            $normalized[] = $id;
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param  list<string>  $selectedIds
     * @return list<string>
     */
    private function expandSettlementEntryIds(int $tenantId, string $date, array $selectedIds): array
    {
        $refunds = DepositRefund::query()
            ->pending()
            ->where('tenant_id', $tenantId)
            ->whereDate('refund_date', $date)
            ->get(['id', 'debt_payment_id', 'refund_credit_payment_id']);

        foreach ($refunds as $refund) {
            $groupIds = ['deposit-refund-'.$refund->id];
            if ($refund->debt_payment_id) {
                $groupIds[] = 'payment-'.$refund->debt_payment_id;
            }
            if ($refund->refund_credit_payment_id) {
                $groupIds[] = 'payment-'.$refund->refund_credit_payment_id;
            }

            if (array_intersect($selectedIds, $groupIds) !== []) {
                $selectedIds = array_merge($selectedIds, $groupIds);
            }
        }

        return array_values(array_unique($selectedIds));
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
    private function formatPaymentEntry(Payment $payment, ?DepositRefund $linkedRefund = null): array
    {
        $driverName = trim(($payment->driver->first_name ?? '').' '.($payment->driver->last_name ?? ''));
        $targetInvoice = $this->resolveTargetInvoice($payment);
        $agreementId = $linkedRefund?->agreement_id
            ?? $payment->allocation_source_id
            ?? $this->agreementIdFromInvoice($targetInvoice);
        $carRegistration = $this->resolvePaymentCarRegistration($payment, $targetInvoice);
        $isDebtOffset = $linkedRefund && (int) $linkedRefund->debt_payment_id === (int) $payment->id;
        $isRefundCredit = $linkedRefund && (int) $linkedRefund->refund_credit_payment_id === (int) $payment->id;
        $description = trim($driverName.($payment->notes ? ' — '.$payment->notes : ''));
        $category = $payment->allocation_source_id ? 'Agreement payment' : 'Driver payment';
        $direction = 'in';

        if ($isDebtOffset) {
            $description = trim($driverName.' — Deposit applied to driver debt');
            $category = 'Deposit applied to driver debt';
            $direction = 'internal';
            $carRegistration = $linkedRefund->agreement?->car?->registration;
        } elseif ($isRefundCredit) {
            $description = trim($driverName.' — Deposit retained as driver credit');
            $category = 'Deposit retained as driver credit';
            $direction = 'internal';
            $carRegistration = $linkedRefund->agreement?->car?->registration;
        }

        return [
            'id' => 'payment-'.$payment->id,
            'sort_at' => $payment->created_at?->timestamp ?? 0,
            'direction' => $direction,
            'employee' => $payment->createdByUser?->name ?? '—',
            'description' => $description,
            'category' => $category,
            'car_registration' => $carRegistration,
            'agreement_id' => $agreementId,
            'agreement_url' => $agreementId ? route('agreements.show', $agreementId) : null,
            'paying_company_name' => $this->resolvePaymentPayingCompanyName($payment, $linkedRefund, $targetInvoice),
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

    private function resolvePaymentPayingCompanyName(
        Payment $payment,
        ?DepositRefund $linkedRefund = null,
        ?Invoice $targetInvoice = null,
    ): ?string {
        $candidates = [
            $linkedRefund?->agreement?->paying_company_name,
            $payment->sourceAgreement?->paying_company_name,
        ];

        foreach ($candidates as $candidate) {
            $name = trim((string) ($candidate ?? ''));
            if ($name !== '') {
                return $name;
            }
        }

        $targetInvoice ??= $this->resolveTargetInvoice($payment);

        return $targetInvoice?->payingCompanyNameLabel();
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
    private function formatOtherPaymentEntry(OtherPayment $otherPayment): array
    {
        $description = trim((string) $otherPayment->title);
        if ($otherPayment->notes) {
            $description = trim($description.' — '.$otherPayment->notes);
        }

        $isVehiclePayment = $otherPayment->other_payment_type === OtherPayment::TYPE_VEHICLE;
        $category = $isVehiclePayment ? 'Other payment — Vehicle' : 'Other payment — Office';
        $carRegistration = $isVehiclePayment ? $otherPayment->car?->registration : null;

        return [
            'id' => 'other-payment-'.$otherPayment->id,
            'sort_at' => $otherPayment->created_at?->timestamp ?? 0,
            'direction' => 'in',
            'employee' => $otherPayment->createdByUser?->name ?? '—',
            'description' => $description,
            'category' => $category,
            'car_registration' => $carRegistration,
            'payment_method' => $otherPayment->payment_method ?: 'Cash',
            'bank_account_id' => $otherPayment->bank_account_id,
            'bank_name' => $otherPayment->bankAccount?->bank_name,
            'account_number' => $otherPayment->bankAccount?->account_number,
            'amount' => (float) $otherPayment->amount,
            'posting_status' => $otherPayment->posting_status,
        ];
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
            $isVehicleExpense = $expense->daily_expense_type === Expense::DAILY_TYPE_VEHICLE;
            $category = $isVehicleExpense ? 'Daily expense — Vehicle' : 'Daily expense — Office';
            $carRegistration = $isVehicleExpense ? $expense->car?->registration : null;
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
            'paying_company_name' => filled($refund->agreement?->paying_company_name)
                ? trim((string) $refund->agreement->paying_company_name)
                : null,
            'payment_method' => $refund->payment_method,
            'bank_account_id' => $refund->bank_account_id,
            'bank_name' => $refund->bankAccount?->bank_name,
            'account_number' => $refund->bankAccount?->account_number,
            'amount' => (float) $refund->amount,
            'posting_status' => $refund->posting_status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatDriverCreditEntry(DriverCreditTransaction $transaction): array
    {
        $driverName = trim(
            ($transaction->driver->first_name ?? '').' '.($transaction->driver->last_name ?? '')
        );
        $isRefund = $transaction->kind === DriverCreditTransaction::KIND_REFUND;

        return [
            'id' => 'driver-credit-'.$transaction->id,
            'sort_at' => $transaction->created_at?->timestamp ?? 0,
            'direction' => $isRefund ? 'out' : 'internal',
            'employee' => $transaction->createdByUser?->name ?? '—',
            'description' => trim($driverName.' — '.($isRefund
                ? 'Driver credit refund'
                : 'Driver credit applied to invoices')),
            'category' => $isRefund ? 'Driver credit refund' : 'Credit applied to invoices',
            'car_registration' => null,
            'payment_method' => $isRefund ? $transaction->payment_method : 'Internal Credit',
            'bank_account_id' => $transaction->bank_account_id,
            'bank_name' => $transaction->bankAccount?->bank_name,
            'account_number' => $transaction->bankAccount?->account_number,
            'amount' => (float) $transaction->amount,
            'posting_status' => $transaction->posting_status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatAdjustmentEntry(FinancialSheetAdjustment $adjustment): array
    {
        return [
            'id' => 'adjustment-'.$adjustment->id,
            'sort_at' => $adjustment->created_at?->timestamp ?? 0,
            'direction' => $adjustment->direction,
            'employee' => $adjustment->createdByUser?->name ?? '—',
            'description' => $adjustment->description,
            'category' => $adjustment->event_type === FinancialSheetAdjustment::EVENT_REVERSAL
                ? 'Payment reversal'
                : 'Payment correction',
            'car_registration' => null,
            'agreement_id' => null,
            'agreement_url' => null,
            'paying_company_name' => null,
            'payment_method' => $adjustment->payment_method,
            'bank_account_id' => $adjustment->bank_account_id,
            'bank_name' => $adjustment->bankAccount?->bank_name,
            'account_number' => $adjustment->bankAccount?->account_number,
            'amount' => (float) $adjustment->amount,
            'posting_status' => 'adjustment',
            'is_adjustment' => true,
            'adjustment_event_type' => $adjustment->event_type,
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @param  array<string, mixed>|null  $metadata
     */
    private function createPaymentCorrectionAdjustment(
        int $tenantId,
        string $sheetDate,
        Payment $payment,
        array $snapshot,
        string $direction,
        float $amount,
        string $eventType,
        string $descriptionSuffix,
        int $actorId,
        ?array $metadata = null
    ): void {
        if ($amount <= 0) {
            return;
        }

        $driverName = trim(($payment->driver->first_name ?? '').' '.($payment->driver->last_name ?? ''));
        $method = $direction === 'out'
            ? (string) ($snapshot['payment_method'] ?? $payment->payment_method)
            : $payment->payment_method;
        $bankAccountId = $direction === 'out'
            ? ($snapshot['bank_account_id'] ?? $payment->bank_account_id)
            : $payment->bank_account_id;

        $adjustment = FinancialSheetAdjustment::query()->create([
            'tenant_id' => $tenantId,
            'sheet_date' => $sheetDate,
            'source_type' => FinancialSheetAdjustment::SOURCE_PAYMENT,
            'source_id' => $payment->id,
            'event_type' => $eventType,
            'direction' => $direction,
            'amount' => round($amount, 2),
            'payment_method' => $method,
            'bank_account_id' => $bankAccountId,
            'description' => trim($driverName.' — Payment corrected — '.$payment->payment_no.' ('.$descriptionSuffix.')'),
            'metadata' => array_merge([
                'payment_no' => $payment->payment_no,
                'old_snapshot' => $snapshot,
            ], $metadata ?? []),
            'created_by' => $actorId,
        ]);

        $this->mergeAdjustmentIntoApprovedSheet($tenantId, $sheetDate, $adjustment);
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    private function paymentContributionEntry(array $snapshot): array
    {
        return [
            'direction' => 'in',
            'payment_method' => $snapshot['payment_method'] ?? null,
            'bank_account_id' => $snapshot['bank_account_id'] ?? null,
            'bank_name' => null,
            'account_number' => null,
            'amount' => (float) ($snapshot['amount'] ?? 0),
            'posting_status' => Payment::POSTING_STATUS_POSTED,
        ];
    }

    /**
     * @param  array<string, mixed>  $oldSnapshot
     * @param  array<string, mixed>  $oldContribution
     * @param  array<string, mixed>  $newContribution
     */
    private function createContributionDeltaAdjustments(
        int $tenantId,
        string $sheetDate,
        Payment $payment,
        array $oldSnapshot,
        array $oldContribution,
        array $newContribution,
        int $actorId
    ): void {
        $oldTotals = $this->computeTotals(collect([$oldContribution]), pendingOnly: false);
        $newTotals = $this->computeTotals(collect([$newContribution]), pendingOnly: false);

        $cashDelta = round($newTotals['cash_in'] - $oldTotals['cash_in'], 2);
        if (abs($cashDelta) >= 0.01) {
            $this->createPaymentCorrectionAdjustment(
                tenantId: $tenantId,
                sheetDate: $sheetDate,
                payment: $payment,
                snapshot: $oldSnapshot,
                direction: $cashDelta >= 0 ? 'in' : 'out',
                amount: abs($cashDelta),
                eventType: FinancialSheetAdjustment::EVENT_CORRECTION,
                descriptionSuffix: 'cash amount corrected',
                actorId: $actorId,
                metadata: ['bucket' => 'cash', 'delta' => $cashDelta]
            );
        }

        $this->createBankDeltaAdjustments(
            $tenantId,
            $sheetDate,
            $payment,
            $oldSnapshot,
            $oldTotals['bank_in'],
            $newTotals['bank_in'],
            'in',
            $actorId
        );
    }

    /**
     * @param  array<int, array{bank_account_id: int|null, bank_name: string, account_number: string, total: float}>  $oldRows
     * @param  array<int, array{bank_account_id: int|null, bank_name: string, account_number: string, total: float}>  $newRows
     * @param  array<string, mixed>  $oldSnapshot
     */
    private function createBankDeltaAdjustments(
        int $tenantId,
        string $sheetDate,
        Payment $payment,
        array $oldSnapshot,
        array $oldRows,
        array $newRows,
        string $direction,
        int $actorId
    ): void {
        $keys = array_unique(array_merge(
            array_map(fn ($row) => (string) ($row['bank_account_id'] ?? 'unknown'), $oldRows),
            array_map(fn ($row) => (string) ($row['bank_account_id'] ?? 'unknown'), $newRows)
        ));

        foreach ($keys as $key) {
            $oldAmount = 0.0;
            foreach ($oldRows as $row) {
                if ((string) ($row['bank_account_id'] ?? 'unknown') === $key) {
                    $oldAmount = (float) $row['total'];
                    break;
                }
            }

            $newAmount = 0.0;
            $bankAccountId = $key === 'unknown' ? null : (int) $key;
            foreach ($newRows as $row) {
                if ((string) ($row['bank_account_id'] ?? 'unknown') === $key) {
                    $newAmount = (float) $row['total'];
                    $bankAccountId = $row['bank_account_id'] ?? $bankAccountId;
                    break;
                }
            }

            $delta = round($newAmount - $oldAmount, 2);
            if (abs($delta) < 0.01) {
                continue;
            }

            $this->createPaymentCorrectionAdjustment(
                tenantId: $tenantId,
                sheetDate: $sheetDate,
                payment: $payment,
                snapshot: $oldSnapshot,
                direction: $delta >= 0 ? 'in' : 'out',
                amount: abs($delta),
                eventType: FinancialSheetAdjustment::EVENT_CORRECTION,
                descriptionSuffix: 'bank amount corrected',
                actorId: $actorId,
                metadata: ['bucket' => 'bank', 'bank_account_id' => $bankAccountId, 'delta' => $delta]
            );
        }
    }
}
