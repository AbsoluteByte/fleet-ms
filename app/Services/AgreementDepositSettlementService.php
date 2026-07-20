<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\DepositRefund;
use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AgreementDepositSettlementService
{
    public function preview(Agreement $agreement, bool $lockInvoices = false): array
    {
        $grossDeposit = round(max((float) $agreement->deposit_amount, 0), 2);
        $deductions = round((float) $agreement->deductions()->sum('amount'), 2);
        $availableAfterDeductions = round(max($grossDeposit - $deductions, 0), 2);
        $invoiceQuery = Invoice::query()
            ->where('driver_id', $agreement->driver_id)
            ->where('balance_amount', '>', 0);
        $driverOutstanding = $lockInvoices
            ? round((float) $invoiceQuery->lockForUpdate()->get()->sum('balance_amount'), 2)
            : round((float) $invoiceQuery->sum('balance_amount'), 2);
        $debtOffset = round(min($availableAfterDeductions, $driverOutstanding), 2);

        return [
            'gross_deposit_amount' => $grossDeposit,
            'deductions_amount' => $deductions,
            'available_after_deductions' => $availableAfterDeductions,
            'driver_outstanding_amount' => $driverOutstanding,
            'debt_offset_amount' => $debtOffset,
            'remaining_debt_amount' => round(max($driverOutstanding - $debtOffset, 0), 2),
            'refund_amount' => round(max($availableAfterDeductions - $debtOffset, 0), 2),
        ];
    }

    public function record(
        Agreement $agreement,
        array $refundData,
        PaymentAllocationService $paymentAllocationService
    ): DepositRefund {
        return DB::transaction(function () use ($agreement, $refundData, $paymentAllocationService) {
            $agreement = Agreement::query()
                ->whereKey($agreement->id)
                ->lockForUpdate()
                ->firstOrFail();
            $agreement->load(['status', 'driver', 'depositRefund', 'deductions']);

            if (! $agreement->canRequestDepositRefund()) {
                throw ValidationException::withMessages([
                    'agreement' => $agreement->hasBeenUpgraded()
                        ? 'This deposit was transferred to the upgraded agreement.'
                        : 'This agreement is not eligible for a deposit settlement.',
                ]);
            }

            $preview = $this->preview($agreement, true);
            $refundAmount = $preview['refund_amount'];
            $paymentMethod = $refundAmount > 0
                ? (string) ($refundData['payment_method'] ?? '')
                : 'No Refund Due';

            if ($refundAmount > 0 && $paymentMethod === '') {
                throw ValidationException::withMessages([
                    'payment_method' => 'Payment method is required when a refund is due.',
                ]);
            }

            $refund = DepositRefund::query()->create([
                'tenant_id' => $agreement->tenant_id,
                'agreement_id' => $agreement->id,
                'driver_id' => $agreement->driver_id,
                'amount' => $refundAmount,
                'gross_deposit_amount' => $preview['gross_deposit_amount'],
                'deductions_amount' => $preview['deductions_amount'],
                'debt_offset_amount' => $preview['debt_offset_amount'],
                'payment_method' => $paymentMethod,
                'bank_account_id' => $refundAmount > 0 ? ($refundData['bank_account_id'] ?? null) : null,
                'refund_date' => $refundData['refund_date'],
                'posting_status' => DepositRefund::POSTING_STATUS_PENDING,
                'created_by' => Auth::id(),
                'notes' => $refundData['notes'] ?? null,
            ]);

            if ($preview['debt_offset_amount'] > 0) {
                $debtPayment = $paymentAllocationService->createPayment($agreement->driver, [
                    'payment_method' => 'Deposit Offset',
                    'payment_date' => $refundData['refund_date'],
                    'amount' => $preview['debt_offset_amount'],
                    'notes' => "Deposit applied to driver debt from agreement #{$agreement->id}.",
                ], true);
                $refund->update(['debt_payment_id' => $debtPayment->id]);
            }

            if ($refundAmount > 0 && $paymentMethod === 'Driver Credit') {
                $notes = trim((string) ($refundData['notes'] ?? ''));
                $creditNotes = "Deposit retained as driver credit from agreement #{$agreement->id}.";
                if ($notes !== '') {
                    $creditNotes .= ' '.$notes;
                }

                $creditPayment = $paymentAllocationService->createPayment($agreement->driver, [
                    'payment_method' => 'Driver Credit',
                    'payment_date' => $refundData['refund_date'],
                    'amount' => $refundAmount,
                    'notes' => $creditNotes,
                ], false);
                $refund->update(['refund_credit_payment_id' => $creditPayment->id]);
            }

            return $refund->fresh(['debtPayment', 'refundCreditPayment']);
        });
    }
}
