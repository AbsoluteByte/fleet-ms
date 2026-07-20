<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\DriverCreditTransaction;
use App\Models\DriverCreditTransactionLine;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DriverCreditService
{
    public function preview(Driver $driver): array
    {
        $availableCredit = $driver->available_credit_amount;
        $outstanding = round((float) $this->unpaidInvoicesQuery($driver)->sum('balance_amount'), 2);

        return [
            'available_credit' => $availableCredit,
            'outstanding' => $outstanding,
            'application_amount' => round(min($availableCredit, $outstanding), 2),
            'remaining_credit' => round(max($availableCredit - $outstanding, 0), 2),
            'remaining_debt' => round(max($outstanding - $availableCredit, 0), 2),
        ];
    }

    public function requestRefund(Driver $driver, array $data): DriverCreditTransaction
    {
        return DB::transaction(function () use ($driver, $data) {
            $driver = Driver::query()->whereKey($driver->id)->lockForUpdate()->firstOrFail();
            $invoices = $this->unpaidInvoicesQuery($driver)->lockForUpdate()->get();

            if ($invoices->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'credit' => 'Credit can only be refunded when the driver has no unpaid invoices.',
                ]);
            }

            $payments = $this->lockedCreditPayments($driver);
            $amount = round((float) $payments->sum(fn (Payment $payment) => $payment->spendable_credit_amount), 2);

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'credit' => 'This driver has no available credit to refund.',
                ]);
            }

            $transaction = $this->createTransaction($driver, DriverCreditTransaction::KIND_REFUND, $amount, $data);
            foreach ($payments as $payment) {
                $available = $payment->spendable_credit_amount;
                if ($available <= 0) {
                    continue;
                }
                $transaction->lines()->create([
                    'source_payment_id' => $payment->id,
                    'amount' => $available,
                    'status' => DriverCreditTransactionLine::STATUS_RESERVED,
                ]);
            }

            return $transaction->fresh(['lines', 'driver', 'bankAccount']);
        });
    }

    public function requestInvoiceApplication(Driver $driver, array $data): DriverCreditTransaction
    {
        return DB::transaction(function () use ($driver, $data) {
            $driver = Driver::query()->whereKey($driver->id)->lockForUpdate()->firstOrFail();
            $payments = $this->lockedCreditPayments($driver);
            $invoices = $this->unpaidInvoicesQuery($driver)
                ->orderBy('invoice_date')
                ->orderBy('due_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $sourceBalances = $payments
                ->mapWithKeys(fn (Payment $payment) => [$payment->id => $payment->spendable_credit_amount])
                ->all();
            $totalCredit = round(array_sum($sourceBalances), 2);
            $invoiceBalances = $invoices
                ->mapWithKeys(fn (Invoice $invoice) => [
                    $invoice->id => round(max(
                        (float) $invoice->balance_amount - $invoice->reserved_credit_amount,
                        0
                    ), 2),
                ])
                ->all();
            $amount = round(min($totalCredit, array_sum($invoiceBalances)), 2);

            if ($totalCredit <= 0) {
                throw ValidationException::withMessages(['credit' => 'This driver has no available credit.']);
            }
            if ($amount <= 0) {
                throw ValidationException::withMessages(['credit' => 'There are no unpaid invoices available for credit application.']);
            }

            $transaction = $this->createTransaction(
                $driver,
                DriverCreditTransaction::KIND_INVOICE_APPLICATION,
                $amount,
                $data
            );
            $sourceIndex = 0;
            $paymentIds = array_keys($sourceBalances);

            foreach ($invoiceBalances as $invoiceId => $invoiceBalance) {
                $remainingInvoice = $invoiceBalance;
                while ($remainingInvoice > 0 && isset($paymentIds[$sourceIndex])) {
                    $paymentId = $paymentIds[$sourceIndex];
                    $availableSource = $sourceBalances[$paymentId];
                    if ($availableSource <= 0) {
                        $sourceIndex++;

                        continue;
                    }

                    $lineAmount = round(min($remainingInvoice, $availableSource), 2);
                    $transaction->lines()->create([
                        'source_payment_id' => $paymentId,
                        'target_invoice_id' => $invoiceId,
                        'amount' => $lineAmount,
                        'status' => DriverCreditTransactionLine::STATUS_RESERVED,
                    ]);
                    $sourceBalances[$paymentId] = round($availableSource - $lineAmount, 2);
                    $remainingInvoice = round($remainingInvoice - $lineAmount, 2);
                }
            }

            return $transaction->fresh(['lines.sourcePayment', 'lines.targetInvoice', 'driver']);
        });
    }

    public function post(DriverCreditTransaction $creditTransaction, int $approvedBy): DriverCreditTransaction
    {
        return DB::transaction(function () use ($creditTransaction, $approvedBy) {
            $creditTransaction = DriverCreditTransaction::query()
                ->whereKey($creditTransaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($creditTransaction->isPosted()) {
                return $creditTransaction;
            }

            $driver = Driver::query()->whereKey($creditTransaction->driver_id)->lockForUpdate()->firstOrFail();
            $lines = $creditTransaction->lines()->orderBy('id')->lockForUpdate()->get();
            $payments = Payment::query()
                ->whereIn('id', $lines->pluck('source_payment_id')->unique())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $invoices = Invoice::query()
                ->whereIn('id', $lines->pluck('target_invoice_id')->filter()->unique())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($lines as $line) {
                if ($line->status !== DriverCreditTransactionLine::STATUS_RESERVED) {
                    throw ValidationException::withMessages(['credit' => 'Credit reservation is no longer pending.']);
                }

                $payment = $payments->get($line->source_payment_id);
                $ownSourceReservation = (float) $lines
                    ->where('source_payment_id', $line->source_payment_id)
                    ->sum('amount');
                $sourceCapacity = $payment
                    ? (float) $payment->amount
                        - (float) $payment->allocated_amount
                        - $payment->refunded_credit_amount
                        - $payment->reserved_credit_amount
                        + $ownSourceReservation
                    : 0;
                if (! $payment || $sourceCapacity + 0.001 < $ownSourceReservation) {
                    throw ValidationException::withMessages(['credit' => 'Reserved driver credit is no longer available.']);
                }

                if ($creditTransaction->kind === DriverCreditTransaction::KIND_REFUND) {
                    $line->update(['status' => DriverCreditTransactionLine::STATUS_CONSUMED]);

                    continue;
                }

                $invoice = $invoices->get($line->target_invoice_id);
                $ownTargetReservation = (float) $lines
                    ->where('target_invoice_id', $line->target_invoice_id)
                    ->sum('amount');
                $availableInvoice = $invoice
                    ? (float) $invoice->balance_amount - $invoice->reserved_credit_amount + $ownTargetReservation
                    : 0;
                if (! $invoice || $availableInvoice + 0.001 < $ownTargetReservation) {
                    throw ValidationException::withMessages(['credit' => 'A reserved invoice balance is no longer available.']);
                }

                PaymentAllocation::query()->create([
                    'payment_id' => $payment->id,
                    'invoice_id' => $invoice->id,
                    'driver_credit_transaction_line_id' => $line->id,
                    'allocated_amount' => $line->amount,
                ]);
                $invoice->refreshPaymentTotals();
                $line->update(['status' => DriverCreditTransactionLine::STATUS_CONVERTED]);
            }

            $creditTransaction->update([
                'posting_status' => DriverCreditTransaction::STATUS_POSTED,
                'approved_by' => $approvedBy,
                'approved_at' => now(),
            ]);

            return $creditTransaction->fresh(['lines', 'driver', 'bankAccount']);
        });
    }

    private function createTransaction(Driver $driver, string $kind, float $amount, array $data): DriverCreditTransaction
    {
        return DriverCreditTransaction::query()->create([
            'tenant_id' => $driver->tenant_id,
            'driver_id' => $driver->id,
            'kind' => $kind,
            'amount' => $amount,
            'request_date' => $data['request_date'],
            'payment_method' => $kind === DriverCreditTransaction::KIND_REFUND ? $data['payment_method'] : null,
            'bank_account_id' => $kind === DriverCreditTransaction::KIND_REFUND ? ($data['bank_account_id'] ?? null) : null,
            'posting_status' => DriverCreditTransaction::STATUS_PENDING,
            'created_by' => Auth::id(),
            'notes' => $data['notes'] ?? null,
        ]);
    }

    private function unpaidInvoicesQuery(Driver $driver)
    {
        return $driver->invoices()
            ->where('balance_amount', '>', 0)
            ->where('status', '!=', 'cancelled');
    }

    private function lockedCreditPayments(Driver $driver)
    {
        return $driver->payments()
            ->posted()
            ->orderBy('payment_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->filter(fn (Payment $payment) => $payment->spendable_credit_amount > 0)
            ->values();
    }
}
