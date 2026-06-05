<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentAllocationService
{
    public function createPayment(Driver $driver, array $paymentData, bool $autoManageInvoices, array $manualAllocations = []): Payment
    {
        return DB::transaction(function () use ($driver, $paymentData, $autoManageInvoices, $manualAllocations) {
            $payment = $driver->payments()->create($paymentData);

            if ($autoManageInvoices) {
                $this->autoAllocate($payment);
            } else {
                $this->manualAllocate($payment, $manualAllocations);
            }

            return $payment->fresh(['driver', 'allocations.invoice']);
        });
    }

    /**
     * @param  list<string>  $invoiceTypes
     */
    public function createPaymentForInvoices(Driver $driver, array $paymentData, int $sourceId, array $invoiceTypes): Payment
    {
        return DB::transaction(function () use ($driver, $paymentData, $sourceId, $invoiceTypes) {
            $payment = $driver->payments()->create($paymentData);
            $this->autoAllocateForSource($payment, $sourceId, $invoiceTypes);

            return $payment->fresh(['driver', 'allocations.invoice']);
        });
    }

    private function autoAllocate(Payment $payment): void
    {
        $remainingAmount = (float) $payment->amount;

        $invoices = Invoice::query()
            ->where('driver_id', $payment->driver_id)
            ->where('balance_amount', '>', 0)
            ->orderBy('invoice_date')
            ->orderBy('due_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($invoices as $invoice) {
            if ($remainingAmount <= 0) {
                break;
            }

            $invoiceBalance = (float) $invoice->balance_amount;
            $allocatedAmount = min($remainingAmount, $invoiceBalance);

            $this->createAllocation($payment, $invoice, $allocatedAmount);
            $remainingAmount = round($remainingAmount - $allocatedAmount, 2);
        }
    }

    /**
     * @param  list<string>  $invoiceTypes
     */
    private function autoAllocateForSource(Payment $payment, int $sourceId, array $invoiceTypes): void
    {
        $remainingAmount = (float) $payment->amount;

        $invoices = Invoice::query()
            ->where('driver_id', $payment->driver_id)
            ->where('source_id', $sourceId)
            ->whereIn('invoice_type', $invoiceTypes)
            ->where('balance_amount', '>', 0)
            ->orderBy('invoice_date')
            ->orderBy('due_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($invoices as $invoice) {
            if ($remainingAmount <= 0) {
                break;
            }

            $invoiceBalance = (float) $invoice->balance_amount;
            $allocatedAmount = min($remainingAmount, $invoiceBalance);

            $this->createAllocation($payment, $invoice, $allocatedAmount);
            $remainingAmount = round($remainingAmount - $allocatedAmount, 2);
        }
    }

    private function manualAllocate(Payment $payment, array $manualAllocations): void
    {
        $paymentAmount = (float) $payment->amount;
        $totalAllocated = 0;

        foreach ($manualAllocations as $invoiceId => $amount) {
            $amount = round((float) $amount, 2);

            if ($amount <= 0) {
                continue;
            }

            $totalAllocated = round($totalAllocated + $amount, 2);

            if ($totalAllocated > $paymentAmount) {
                throw ValidationException::withMessages([
                    'allocations' => 'Total allocated amount cannot be greater than payment amount.',
                ]);
            }

            $invoice = Invoice::query()
                ->where('driver_id', $payment->driver_id)
                ->where('balance_amount', '>', 0)
                ->whereKey($invoiceId)
                ->lockForUpdate()
                ->first();

            if (! $invoice) {
                throw ValidationException::withMessages([
                    'allocations' => 'Selected invoice is not available for allocation.',
                ]);
            }

            if ($amount > (float) $invoice->balance_amount) {
                throw ValidationException::withMessages([
                    "allocations.{$invoiceId}" => 'Allocation cannot be greater than invoice balance.',
                ]);
            }

            $this->createAllocation($payment, $invoice, $amount);
        }
    }

    public function allocateAvailableCreditToInvoice(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            $invoice = Invoice::query()
                ->whereKey($invoice->id)
                ->where('balance_amount', '>', 0)
                ->lockForUpdate()
                ->first();

            if (! $invoice) {
                return;
            }

            $remainingBalance = (float) $invoice->balance_amount;

            $payments = Payment::query()
                ->where('driver_id', $invoice->driver_id)
                ->orderBy('payment_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($payments as $payment) {
                if ($remainingBalance <= 0) {
                    break;
                }

                $allocatedAmount = (float) $payment->allocations()->sum('allocated_amount');
                $availableCredit = max((float) $payment->amount - $allocatedAmount, 0);

                if ($availableCredit <= 0) {
                    continue;
                }

                $amountToAllocate = min($availableCredit, $remainingBalance);
                $this->createAllocation($payment, $invoice, $amountToAllocate);
                $remainingBalance = round($remainingBalance - $amountToAllocate, 2);
                $invoice->refresh();
            }
        });
    }

    private function createAllocation(Payment $payment, Invoice $invoice, float $allocatedAmount): void
    {
        $payment->allocations()->create([
            'invoice_id' => $invoice->id,
            'allocated_amount' => round($allocatedAmount, 2),
        ]);

        $invoice->refreshPaymentTotals();
    }
}
