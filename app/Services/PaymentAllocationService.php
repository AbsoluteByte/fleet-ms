<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class PaymentAllocationService
{
    public function createPayment(
        Driver $driver,
        array $paymentData,
        bool $autoManageInvoices,
        array $manualAllocations = [],
        bool $postImmediately = false
    ): Payment {
        return DB::transaction(function () use ($driver, $paymentData, $autoManageInvoices, $manualAllocations, $postImmediately) {
            $payment = $driver->payments()->create(array_merge([
                'posting_status' => $postImmediately ? Payment::POSTING_STATUS_POSTED : Payment::POSTING_STATUS_PENDING,
                'created_by' => Auth::id(),
                'auto_allocate' => $autoManageInvoices,
                'pending_manual_allocations' => $autoManageInvoices ? null : $manualAllocations,
            ], $paymentData));

            if ($postImmediately) {
                $this->postPayment($payment);
            }

            return $payment->fresh(['driver', 'allocations.invoice']);
        });
    }

    /**
     * @param  list<string>  $invoiceTypes
     */
    public function createPaymentForInvoices(
        Driver $driver,
        array $paymentData,
        int $sourceId,
        array $invoiceTypes,
        bool $postImmediately = false
    ): Payment {
        return DB::transaction(function () use ($driver, $paymentData, $sourceId, $invoiceTypes, $postImmediately) {
            $payment = $driver->payments()->create(array_merge([
                'posting_status' => $postImmediately ? Payment::POSTING_STATUS_POSTED : Payment::POSTING_STATUS_PENDING,
                'created_by' => Auth::id(),
                'auto_allocate' => false,
                'allocation_source_id' => $sourceId,
                'allocation_invoice_types' => $invoiceTypes,
            ], $paymentData));

            if ($postImmediately) {
                $this->postPayment($payment);
            }

            return $payment->fresh(['driver', 'allocations.invoice']);
        });
    }

    public function postPayment(Payment $payment): Payment
    {
        if ($payment->isPosted()) {
            return $payment;
        }

        return DB::transaction(function () use ($payment) {
            $payment->update(['posting_status' => Payment::POSTING_STATUS_POSTED]);

            $this->allocatePostedPayment($payment);

            return $payment->fresh(['driver', 'allocations.invoice']);
        });
    }

    public function updatePendingPayment(
        Payment $payment,
        array $data,
        bool $autoManageInvoices,
        array $manualAllocations = []
    ): Payment {
        return DB::transaction(function () use ($payment, $data, $autoManageInvoices, $manualAllocations) {
            $payment->update(array_merge($data, [
                'auto_allocate' => $autoManageInvoices,
                'pending_manual_allocations' => $autoManageInvoices ? null : $manualAllocations,
            ]));

            return $payment->fresh(['driver', 'allocations.invoice']);
        });
    }

    public function updatePostedPayment(
        Payment $payment,
        array $data,
        DailyFinancialSheetService $sheetService,
        int $actorId
    ): Payment {
        return DB::transaction(function () use ($payment, $data, $sheetService, $actorId) {
            $payment->load(['driver', 'allocations.invoice']);
            $oldSnapshot = $this->paymentSnapshot($payment);

            $this->clearPaymentAllocations($payment);

            $payment->update($data);
            $payment->refresh();

            $this->allocatePostedPayment($payment);

            $sheetService->recordPaymentCorrection($payment->fresh(['driver']), $oldSnapshot, $actorId);

            return $payment->fresh(['driver', 'allocations.invoice']);
        });
    }

    public function deletePayment(Payment $payment, DailyFinancialSheetService $sheetService, int $actorId): void
    {
        if (Schema::hasTable('driver_credit_transaction_lines') && $payment->creditTransactionLines()->exists()) {
            throw ValidationException::withMessages([
                'payment' => 'This payment is linked to a driver credit transaction and cannot be deleted.',
            ]);
        }

        DB::transaction(function () use ($payment, $sheetService, $actorId) {
            $payment->load(['driver', 'allocations.invoice']);

            if ($payment->isPosted()) {
                $tenantId = (int) $payment->driver->tenant_id;
                $paymentDate = $payment->payment_date?->toDateString();

                if ($paymentDate && $sheetService->isDateApproved($tenantId, $paymentDate)) {
                    $sheetService->recordPaymentReversal($payment, $actorId);
                }
            }

            $this->clearPaymentAllocations($payment);
            $payment->delete();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function paymentSnapshot(Payment $payment): array
    {
        return [
            'payment_id' => $payment->id,
            'payment_no' => $payment->payment_no,
            'amount' => (float) $payment->amount,
            'payment_date' => $payment->payment_date?->toDateString(),
            'payment_method' => $payment->payment_method,
            'bank_account_id' => $payment->bank_account_id,
            'driver_name' => trim(($payment->driver->first_name ?? '').' '.($payment->driver->last_name ?? '')),
            'allocations' => $payment->allocations
                ->map(fn ($allocation) => [
                    'invoice_id' => $allocation->invoice_id,
                    'allocated_amount' => (float) $allocation->allocated_amount,
                ])
                ->all(),
        ];
    }

    private function clearPaymentAllocations(Payment $payment): void
    {
        $invoices = $payment->allocations->pluck('invoice')->filter();

        foreach ($payment->allocations as $allocation) {
            $allocation->delete();
        }

        foreach ($invoices as $invoice) {
            $invoice->refreshPaymentTotals();
        }
    }

    private function allocatePostedPayment(Payment $payment): void
    {
        if ($payment->allocation_source_id && is_array($payment->allocation_invoice_types)) {
            $this->autoAllocateForSource($payment, $payment->allocation_source_id, $payment->allocation_invoice_types);
        } elseif ($payment->auto_allocate) {
            $this->autoAllocate($payment);
        } elseif (is_array($payment->pending_manual_allocations)) {
            $this->manualAllocate($payment, $payment->pending_manual_allocations);
        }
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

            $invoiceBalance = max((float) $invoice->balance_amount - $invoice->reserved_credit_amount, 0);
            $allocatedAmount = min($remainingAmount, $invoiceBalance);

            if ($allocatedAmount <= 0) {
                continue;
            }

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

            $invoiceBalance = max((float) $invoice->balance_amount - $invoice->reserved_credit_amount, 0);
            $allocatedAmount = min($remainingAmount, $invoiceBalance);

            if ($allocatedAmount <= 0) {
                continue;
            }

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

            $availableInvoiceBalance = max(
                (float) $invoice->balance_amount - $invoice->reserved_credit_amount,
                0
            );
            if ($amount > $availableInvoiceBalance) {
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

            $remainingBalance = max(
                (float) $invoice->balance_amount - $invoice->reserved_credit_amount,
                0
            );

            $payments = Payment::query()
                ->where('driver_id', $invoice->driver_id)
                ->posted()
                ->orderBy('payment_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($payments as $payment) {
                if ($remainingBalance <= 0) {
                    break;
                }

                $availableCredit = $payment->spendable_credit_amount;

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
