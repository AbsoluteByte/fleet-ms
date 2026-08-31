<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\AgreementAdditionalCharge;
use App\Models\DriverCreditTransactionLine;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AgreementInvoiceService
{
    public function generateDueInvoices(?Carbon $throughDate = null): int
    {
        $throughDate = $throughDate?->copy()->startOfDay() ?? now()->startOfDay();
        $count = 0;

        Agreement::query()
            ->billable()
            ->whereNotNull('driver_id')
            ->whereDate('start_date', '<=', $throughDate)
            ->where(function ($query) {
                $query->whereHas('status', fn ($statusQuery) => $statusQuery->where('name', 'Expired'))
                    ->orWhereDate('end_date', '>=', now()->startOfDay());
            })
            ->chunkById(100, function (Collection $agreements) use (&$count, $throughDate) {
                foreach ($agreements as $agreement) {
                    $count += $this->generateForAgreement($agreement, $throughDate);
                }
            });

        return $count;
    }

    public function generateForAgreement(Agreement $agreement, ?Carbon $throughDate = null): int
    {
        return DB::transaction(function () use ($agreement, $throughDate) {
            $agreement = Agreement::query()
                ->with(['status', 'upgradedFromAgreement'])
                ->whereKey($agreement->id)
                ->lockForUpdate()
                ->first();

            return $agreement ? $this->generateForLockedAgreement($agreement, $throughDate) : 0;
        });
    }

    private function generateForLockedAgreement(Agreement $agreement, ?Carbon $throughDate = null): int
    {
        if (
            ! $agreement->isBillableStatus()
            || $agreement->isReplacementVehicle()
            || ! $agreement->driver_id
            || ! $agreement->start_date
            || ! $agreement->end_date
        ) {
            return 0;
        }

        if ($agreement->upgraded_from_agreement_id) {
            return $this->generateForUpgradedAgreement($agreement, $throughDate);
        }

        $throughDate = $throughDate?->copy()->startOfDay() ?? now()->startOfDay();
        $endDate = $agreement->billingThroughDate($throughDate);
        $generated = $this->syncDepositInvoice($agreement) ? 1 : 0;

        if ($agreement->hasDeferredBillingAnchor()) {
            $periodStart = $agreement->start_date->copy()->startOfDay();
            $anchor = $agreement->billing_anchor_date->copy()->startOfDay();
            $prorationSubtotal = $this->calculateProrationSubtotal(
                $agreement,
                $periodStart,
                $anchor,
                (float) $agreement->agreed_rent
            );

            if ($prorationSubtotal > 0 && $periodStart <= $endDate) {
                if ($this->createInitialProrationInvoice($agreement, $periodStart, $prorationSubtotal, $anchor)) {
                    $generated++;
                }
            }

            $currentDate = $anchor->copy();
        } else {
            $currentDate = $agreement->start_date->copy()->startOfDay();
        }

        while ($currentDate <= $endDate) {
            if ($this->createInvoiceForDate($agreement, $currentDate)) {
                $generated++;
            }

            $currentDate = $this->nextInvoiceDate($currentDate, (string) $agreement->rent_interval);
        }

        return $generated;
    }

    public function createAdditionalChargeInvoice(Agreement $agreement, AgreementAdditionalCharge $charge): Invoice
    {
        if ($charge->invoice_id) {
            return $charge->invoice()->firstOrFail();
        }

        $amount = round((float) $charge->amount, 2);
        $invoiceDate = now()->startOfDay();
        $dueDate = $invoiceDate->copy()->addDays(5);
        $typeLabel = $charge->typeLabel();
        $detail = filled($charge->notes) ? trim((string) $charge->notes) : null;
        $notes = $detail ? $typeLabel.': '.$detail : $typeLabel;

        $invoice = Invoice::create([
            'driver_id' => $agreement->driver_id,
            'source_id' => $agreement->id,
            'invoice_type' => 'agreement_additional_charge',
            'invoice_date' => $invoiceDate->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'subtotal' => $amount,
            'discount_amount' => 0,
            'discount_description' => null,
            'tax_amount' => 0,
            'total_amount' => $amount,
            'paid_amount' => 0,
            'balance_amount' => $amount,
            'status' => $dueDate->lt(now()->startOfDay()) ? 'overdue' : 'pending',
            'notes' => $notes,
        ]);

        app(PaymentAllocationService::class)->allocateAvailableCreditToInvoice($invoice);

        return $invoice;
    }

    public function nextBillingAnchor(Carbon $originalStart, Carbon $fromDate, string $rentInterval): Carbon
    {
        $anchor = $originalStart->copy()->startOfDay();
        $from = $fromDate->copy()->startOfDay();

        while ($anchor->lte($from)) {
            $anchor = $this->nextInvoiceDate($anchor, $rentInterval);
        }

        return $anchor;
    }

    public function previousBillingAnchor(Carbon $originalStart, Carbon $anchor, string $rentInterval): Carbon
    {
        $previous = $originalStart->copy()->startOfDay();
        $target = $anchor->copy()->startOfDay();

        while ($this->nextInvoiceDate($previous, $rentInterval)->lt($target)) {
            $previous = $this->nextInvoiceDate($previous, $rentInterval);
        }

        return $previous;
    }

    public function calculateInitialProrationAmount(Agreement $agreement, Carbon $periodStart, Carbon $billingAnchor): float
    {
        $subtotal = $this->calculateProrationSubtotal(
            $agreement,
            $periodStart,
            $billingAnchor,
            (float) $agreement->agreed_rent
        );

        return round(max($subtotal - $this->discountAmountForNewRentInvoice($agreement, $subtotal), 0), 2);
    }

    public function calculateProrationForPeriod(Agreement $agreement, Carbon $from, Carbon $to, float $rentAmount): float
    {
        $subtotal = $this->calculateProrationSubtotal($agreement, $from, $to, $rentAmount);

        if ($subtotal <= 0) {
            return $subtotal;
        }

        return round(max($subtotal - $this->discountAmountForNewRentInvoice($agreement, $subtotal), 0), 2);
    }

    private function calculateProrationSubtotal(Agreement $agreement, Carbon $from, Carbon $to, float $rentAmount): float
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();

        if ($to->lte($from) || $rentAmount == 0.0) {
            return 0;
        }

        $partialDays = $from->diffInDays($to);

        if ($partialDays <= 0) {
            return 0;
        }

        $rentInterval = (string) $agreement->rent_interval;
        $previousAnchor = $this->previousAnchorBefore($to, $rentInterval);
        $periodDays = $previousAnchor->diffInDays($to);

        if ($periodDays <= 0) {
            return 0;
        }

        return round($rentAmount / $periodDays * $partialDays, 2);
    }

    /**
     * @return list<string>
     */
    public function expectedInvoiceDates(Agreement $agreement, ?Carbon $throughDate = null): array
    {
        if (! $agreement->start_date || ! $agreement->end_date) {
            return [];
        }

        $throughDate = $throughDate?->copy()->startOfDay()
            ?? ($agreement->isOpenHoldover() ? now()->startOfDay() : $agreement->end_date->copy()->startOfDay());
        $endDate = $agreement->billingThroughDate($throughDate);

        if ($agreement->upgraded_from_agreement_id) {
            return $this->expectedInvoiceDatesForUpgradedAgreement($agreement, $endDate);
        }

        $dates = [];

        if ($agreement->hasDeferredBillingAnchor()) {
            $periodStart = $agreement->start_date->copy()->startOfDay();
            $anchor = $agreement->billing_anchor_date->copy()->startOfDay();
            $prorationSubtotal = $this->calculateProrationSubtotal(
                $agreement,
                $periodStart,
                $anchor,
                (float) $agreement->agreed_rent
            );

            if ($prorationSubtotal > 0 && $periodStart <= $endDate) {
                $dates[] = $periodStart->toDateString();
            }

            $currentDate = $anchor->copy();
        } else {
            $currentDate = $agreement->start_date->copy()->startOfDay();
        }

        while ($currentDate <= $endDate) {
            $dates[] = $currentDate->toDateString();
            $currentDate = $this->nextInvoiceDate($currentDate, (string) $agreement->rent_interval);
        }

        return $dates;
    }

    public function reconcileBillingScheduleInvoices(Agreement $agreement): void
    {
        DB::transaction(function () use ($agreement) {
            $agreement = Agreement::query()
                ->with(['status', 'upgradedFromAgreement'])
                ->whereKey($agreement->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $agreement->driver_id || ! $agreement->start_date || ! $agreement->end_date) {
                return;
            }

            $expectedDates = array_flip($this->expectedInvoiceDates($agreement));

            $invoices = Invoice::query()
                ->where('invoice_type', 'agreement')
                ->where('source_id', $agreement->id)
                ->lockForUpdate()
                ->get();

            foreach ($invoices as $invoice) {
                if ((float) $invoice->paid_amount > 0 || $invoice->paymentAllocations()->count() > 0) {
                    continue;
                }

                $notes = (string) $invoice->notes;
                if (str_starts_with($notes, 'Final invoice prorated')) {
                    continue;
                }

                $invoiceDate = $invoice->invoice_date->copy()->startOfDay()->toDateString();
                if (isset($expectedDates[$invoiceDate])) {
                    continue;
                }

                $this->releaseInvoiceCommitments($invoice, 0);
                $this->removePendingManualAllocationTarget($invoice);
                $this->releaseOneTimeDiscountFromInvoice($agreement, $invoice);
                $invoice->delete();
            }
        });
    }

    public function reconcileFinalInvoice(Agreement $agreement, Carbon $closingDate): void
    {
        DB::transaction(function () use ($agreement, $closingDate) {
            $agreement = Agreement::query()
                ->with('status')
                ->whereKey($agreement->id)
                ->lockForUpdate()
                ->firstOrFail();
            $closingDate = $closingDate->copy()->startOfDay();

            $invoices = Invoice::query()
                ->where('invoice_type', 'agreement')
                ->where('source_id', $agreement->id)
                ->orderByDesc('invoice_date')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->get();

            foreach ($invoices->filter(
                fn (Invoice $invoice) => $invoice->invoice_date->copy()->startOfDay()->gte($closingDate)
            ) as $invoice) {
                $this->releaseInvoiceCommitments($invoice, 0);
                $this->removePendingManualAllocationTarget($invoice);
                $this->releaseOneTimeDiscountFromInvoice($agreement, $invoice);
                $invoice->delete();
            }

            $invoice = $invoices->first(
                fn (Invoice $candidate) => $candidate->invoice_date->copy()->startOfDay()->lt($closingDate)
            );

            if (! $invoice) {
                return;
            }

            $periodStart = $invoice->invoice_date->copy()->startOfDay();
            $periodEnd = $this->finalInvoicePeriodEnd($agreement, $invoice);

            if ($closingDate->gte($periodEnd)) {
                return;
            }

            $periodDays = $periodStart->diffInDays($periodEnd);
            $chargedDays = $periodStart->diffInDays($closingDate);
            if ($periodDays <= 0 || $chargedDays <= 0) {
                return;
            }

            $subtotal = round((float) $agreement->agreed_rent / $periodDays * $chargedDays, 2);
            $discountAmount = $this->discountAmountForExistingInvoice($agreement, $invoice, $subtotal);
            $totalAmount = round(max($subtotal - $discountAmount, 0), 2);

            $this->releaseInvoiceCommitments($invoice, $totalAmount);
            $this->removePendingManualAllocationTarget($invoice);

            $invoice->forceFill([
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'discount_description' => $this->discountDescription($agreement, $discountAmount),
                'tax_amount' => 0,
                'total_amount' => $totalAmount,
                'notes' => sprintf(
                    'Final invoice prorated for %d day%s through %s (closing date excluded)',
                    $chargedDays,
                    $chargedDays === 1 ? '' : 's',
                    $closingDate->copy()->subDay()->toDateString()
                ),
            ])->save();
            $invoice->refreshPaymentTotals();
        });
    }

    public function calculateUpgradeProration(Agreement $new, Agreement $old, ?Carbon $upgradeDate = null): float
    {
        $adjustment = $this->calculateChangeCarAdjustment($new, $old, $upgradeDate);

        return max($adjustment, 0);
    }

    public function calculateChangeCarAdjustment(Agreement $new, Agreement $old, ?Carbon $changeDate = null): float
    {
        $changeDate = ($changeDate ?? $new->start_date)->copy()->startOfDay();
        $originalStart = $old->start_date->copy()->startOfDay();
        $rentInterval = (string) $new->rent_interval;

        if ($this->isBillingAnchor($originalStart, $changeDate, $rentInterval)) {
            return 0;
        }

        $nextAnchor = $this->nextBillingAnchor($originalStart, $changeDate, $rentInterval);
        $rentDiff = (float) $new->agreed_rent - (float) $old->agreed_rent;

        if ($rentDiff > 0) {
            $subtotal = $this->calculateProrationSubtotal($new, $changeDate, $nextAnchor, $rentDiff);
            $discountAmount = $new->discount_is_one_time
                ? 0
                : $this->discountAmount($new, $subtotal);

            return round(max($subtotal - $discountAmount, 0), 2);
        }

        $remainingDays = $changeDate->diffInDays($nextAnchor);

        if ($remainingDays <= 0) {
            return 0;
        }

        $previousAnchor = $this->previousBillingAnchor($originalStart, $nextAnchor, $rentInterval);
        $periodDays = $previousAnchor->diffInDays($nextAnchor);

        if ($periodDays <= 0) {
            return 0;
        }

        return round($rentDiff / $periodDays * $remainingDays, 2);
    }

    public function changeCarAdjustmentType(float $adjustment): string
    {
        if ($adjustment > 0) {
            return 'invoice';
        }

        if ($adjustment < 0) {
            return 'credit';
        }

        return 'none';
    }

    public function isBillingAnchor(Carbon $originalStart, Carbon $date, string $rentInterval): bool
    {
        $anchor = $originalStart->copy()->startOfDay();
        $target = $date->copy()->startOfDay();

        if ($anchor->eq($target)) {
            return true;
        }

        while ($anchor->lt($target)) {
            $next = $this->nextInvoiceDate($anchor, $rentInterval);

            if ($next->eq($target)) {
                return true;
            }

            if ($next->gt($target)) {
                return false;
            }

            $anchor = $next;
        }

        return false;
    }

    private function generateForUpgradedAgreement(Agreement $agreement, ?Carbon $throughDate = null): int
    {
        $old = $agreement->upgradedFromAgreement;

        if (! $old || ! $old->start_date) {
            return 0;
        }

        $throughDate = $throughDate?->copy()->startOfDay() ?? now()->startOfDay();
        $endDate = $agreement->billingThroughDate($throughDate);
        $generated = 0;
        $currentDate = $this->firstBillingDateForUpgradedAgreement($agreement);

        if (! $currentDate) {
            return 0;
        }

        $rentInterval = (string) $agreement->rent_interval;

        while ($currentDate <= $endDate) {
            if ($this->createInvoiceForDate($agreement, $currentDate)) {
                $generated++;
            }

            $currentDate = $this->nextInvoiceDate($currentDate, $rentInterval);
        }

        return $generated;
    }

    /**
     * @return list<string>
     */
    private function expectedInvoiceDatesForUpgradedAgreement(Agreement $agreement, Carbon $endDate): array
    {
        $old = $agreement->upgradedFromAgreement;

        if (! $old || ! $old->start_date) {
            return [];
        }

        $dates = [];
        $currentDate = $this->firstBillingDateForUpgradedAgreement($agreement);

        if (! $currentDate) {
            return [];
        }

        $rentInterval = (string) $agreement->rent_interval;

        while ($currentDate <= $endDate) {
            $dates[] = $currentDate->toDateString();
            $currentDate = $this->nextInvoiceDate($currentDate, $rentInterval);
        }

        return $dates;
    }

    private function firstBillingDateForUpgradedAgreement(Agreement $agreement): ?Carbon
    {
        $old = $agreement->upgradedFromAgreement;

        if (! $old || ! $old->start_date || ! $agreement->start_date) {
            return null;
        }

        $upgradeDate = $agreement->start_date->copy()->startOfDay();
        $originalStart = $old->start_date->copy()->startOfDay();
        $rentInterval = (string) $agreement->rent_interval;

        if ($this->isBillingAnchor($originalStart, $upgradeDate, $rentInterval)) {
            return $upgradeDate->copy();
        }

        return $this->nextBillingAnchor($originalStart, $upgradeDate, $rentInterval);
    }

    private function syncDepositInvoice(Agreement $agreement): bool
    {
        $depositAmount = round((float) $agreement->deposit_amount, 2);

        if ($depositAmount <= 0) {
            $invoice = Invoice::query()
                ->where('invoice_type', 'agreement_deposit')
                ->where('source_id', $agreement->id)
                ->first();

            if ($invoice && (float) $invoice->paid_amount <= 0 && $invoice->paymentAllocations()->count() === 0) {
                $invoice->delete();
            }

            return false;
        }

        $invoiceDate = $agreement->start_date->copy()->startOfDay();
        $dueDate = $invoiceDate->copy()->addDays(5);

        $invoice = Invoice::query()
            ->where('invoice_type', 'agreement_deposit')
            ->where('source_id', $agreement->id)
            ->first();

        if ($invoice) {
            if ((float) $invoice->paid_amount > 0) {
                return false;
            }

            $invoice->update([
                'driver_id' => $agreement->driver_id,
                'invoice_date' => $invoiceDate->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'subtotal' => $depositAmount,
                'discount_amount' => 0,
                'discount_description' => null,
                'tax_amount' => 0,
                'total_amount' => $depositAmount,
                'balance_amount' => $depositAmount,
                'status' => $dueDate->lt(now()->startOfDay()) ? 'overdue' : 'pending',
                'notes' => 'Auto-generated agreement deposit invoice',
            ]);

            app(PaymentAllocationService::class)->allocateAvailableCreditToInvoice($invoice);

            return false;
        }

        $invoice = Invoice::create([
            'driver_id' => $agreement->driver_id,
            'source_id' => $agreement->id,
            'invoice_type' => 'agreement_deposit',
            'invoice_date' => $invoiceDate->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'subtotal' => $depositAmount,
            'discount_amount' => 0,
            'discount_description' => null,
            'tax_amount' => 0,
            'total_amount' => $depositAmount,
            'paid_amount' => 0,
            'balance_amount' => $depositAmount,
            'status' => $dueDate->lt(now()->startOfDay()) ? 'overdue' : 'pending',
            'notes' => 'Auto-generated agreement deposit invoice',
        ]);

        app(PaymentAllocationService::class)->allocateAvailableCreditToInvoice($invoice);

        return true;
    }

    private function createInitialProrationInvoice(Agreement $agreement, Carbon $invoiceDate, float $subtotal, Carbon $billingAnchor): bool
    {
        $exists = Invoice::query()
            ->where('invoice_type', 'agreement')
            ->where('source_id', $agreement->id)
            ->whereDate('invoice_date', $invoiceDate)
            ->exists();

        if ($exists) {
            return false;
        }

        $dueDate = $invoiceDate->copy()->addDays(5);
        $discountAmount = $this->discountAmountForNewRentInvoice($agreement, $subtotal);
        $totalAmount = round(max($subtotal - $discountAmount, 0), 2);

        $invoice = Invoice::create([
            'driver_id' => $agreement->driver_id,
            'source_id' => $agreement->id,
            'invoice_type' => 'agreement',
            'invoice_date' => $invoiceDate->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'discount_description' => $this->discountDescription($agreement, $discountAmount),
            'tax_amount' => 0,
            'total_amount' => $totalAmount,
            'paid_amount' => 0,
            'balance_amount' => $totalAmount,
            'status' => $totalAmount <= 0 ? 'paid' : ($dueDate->lt(now()->startOfDay()) ? 'overdue' : 'pending'),
            'notes' => 'Initial proration until '.$billingAnchor->toDateString(),
        ]);

        $this->consumeOneTimeDiscount($agreement, $invoice, $discountAmount);
        app(PaymentAllocationService::class)->allocateAvailableCreditToInvoice($invoice);

        return true;
    }

    private function createChangeCarProrationInvoice(Agreement $agreement, Carbon $invoiceDate, float $totalAmount, Carbon $nextAnchor): bool
    {
        $exists = Invoice::query()
            ->where('invoice_type', 'agreement')
            ->where('source_id', $agreement->id)
            ->whereDate('invoice_date', $invoiceDate)
            ->exists();

        if ($exists) {
            return false;
        }

        $dueDate = $invoiceDate->copy()->addDays(5);

        $invoice = Invoice::create([
            'driver_id' => $agreement->driver_id,
            'source_id' => $agreement->id,
            'invoice_type' => 'agreement',
            'invoice_date' => $invoiceDate->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'subtotal' => $totalAmount,
            'discount_amount' => 0,
            'discount_description' => null,
            'tax_amount' => 0,
            'total_amount' => $totalAmount,
            'paid_amount' => 0,
            'balance_amount' => $totalAmount,
            'status' => $totalAmount <= 0 ? 'paid' : ($dueDate->lt(now()->startOfDay()) ? 'overdue' : 'pending'),
            'notes' => 'Car change proration until '.$nextAnchor->toDateString(),
        ]);

        app(PaymentAllocationService::class)->allocateAvailableCreditToInvoice($invoice);

        return true;
    }

    private function createInvoiceForDate(Agreement $agreement, Carbon $invoiceDate): bool
    {
        $exists = Invoice::query()
            ->where('invoice_type', 'agreement')
            ->where('source_id', $agreement->id)
            ->whereDate('invoice_date', $invoiceDate)
            ->exists();

        if ($exists) {
            return false;
        }

        $subtotal = (float) $agreement->agreed_rent;
        $discountAmount = $this->discountAmountForNewRentInvoice($agreement, $subtotal);
        $totalAmount = round(max($subtotal - $discountAmount, 0), 2);
        $dueDate = $invoiceDate->copy()->addDays(5);

        $invoice = Invoice::create([
            'driver_id' => $agreement->driver_id,
            'source_id' => $agreement->id,
            'invoice_type' => 'agreement',
            'invoice_date' => $invoiceDate->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'discount_description' => $this->discountDescription($agreement, $discountAmount),
            'tax_amount' => 0,
            'total_amount' => $totalAmount,
            'paid_amount' => 0,
            'balance_amount' => $totalAmount,
            'status' => $totalAmount <= 0 ? 'paid' : ($dueDate->lt(now()->startOfDay()) ? 'overdue' : 'pending'),
            'notes' => 'Auto-generated agreement invoice',
        ]);

        $this->consumeOneTimeDiscount($agreement, $invoice, $discountAmount);
        app(PaymentAllocationService::class)->allocateAvailableCreditToInvoice($invoice);

        return true;
    }

    private function finalInvoicePeriodEnd(Agreement $agreement, Invoice $invoice): Carbon
    {
        $invoiceDate = $invoice->invoice_date->copy()->startOfDay();

        if (
            $agreement->hasDeferredBillingAnchor()
            && $invoiceDate->equalTo($agreement->start_date->copy()->startOfDay())
        ) {
            return $agreement->billing_anchor_date->copy()->startOfDay();
        }

        return $this->nextInvoiceDate($invoiceDate, (string) $agreement->rent_interval);
    }

    private function releaseInvoiceCommitments(Invoice $invoice, float $targetTotal): void
    {
        $this->releasePendingCreditReservations($invoice);

        $allocated = round((float) $invoice->paymentAllocations()->sum('allocated_amount'), 2);
        $excess = round(max($allocated - $targetTotal, 0), 2);
        if ($excess <= 0) {
            return;
        }

        $hasCreditLineLink = Schema::hasTable('driver_credit_transaction_lines')
            && Schema::hasColumn('payment_allocations', 'driver_credit_transaction_line_id');
        $allocations = $invoice->paymentAllocations()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->get();

        foreach ($allocations as $allocation) {
            if ($excess <= 0) {
                break;
            }

            $allocatedAmount = round((float) $allocation->allocated_amount, 2);
            $releasedAmount = round(min($allocatedAmount, $excess), 2);
            $retainedAmount = round($allocatedAmount - $releasedAmount, 2);
            $creditLine = $hasCreditLineLink && $allocation->driver_credit_transaction_line_id
                ? DriverCreditTransactionLine::query()
                    ->whereKey($allocation->driver_credit_transaction_line_id)
                    ->lockForUpdate()
                    ->first()
                : null;

            if ($retainedAmount <= 0) {
                $allocation->delete();
                if ($creditLine) {
                    $creditLine->update([
                        'target_invoice_id' => null,
                        'status' => DriverCreditTransactionLine::STATUS_REVERSED,
                    ]);
                }
            } else {
                $allocation->update(['allocated_amount' => $retainedAmount]);
                if ($creditLine) {
                    $creditLine->update(['amount' => $retainedAmount]);
                    $creditLine->transaction->lines()->create([
                        'source_payment_id' => $creditLine->source_payment_id,
                        'target_invoice_id' => null,
                        'amount' => $releasedAmount,
                        'status' => DriverCreditTransactionLine::STATUS_REVERSED,
                    ]);
                }
            }

            $excess = round($excess - $releasedAmount, 2);
        }
    }

    private function releasePendingCreditReservations(Invoice $invoice): void
    {
        if (! Schema::hasTable('driver_credit_transaction_lines')) {
            return;
        }

        $lines = DriverCreditTransactionLine::query()
            ->where('target_invoice_id', $invoice->id)
            ->where('status', DriverCreditTransactionLine::STATUS_RESERVED)
            ->whereHas('transaction', fn ($query) => $query->pending())
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($lines->groupBy('driver_credit_transaction_id') as $transactionLines) {
            $transaction = $transactionLines->first()->transaction;
            foreach ($transactionLines as $line) {
                $line->delete();
            }

            $remainingAmount = round((float) $transaction->lines()->sum('amount'), 2);
            if ($remainingAmount <= 0) {
                $transaction->delete();
            } else {
                $transaction->update(['amount' => $remainingAmount]);
            }
        }
    }

    private function removePendingManualAllocationTarget(Invoice $invoice): void
    {
        Payment::query()
            ->where('driver_id', $invoice->driver_id)
            ->pending()
            ->whereNotNull('pending_manual_allocations')
            ->lockForUpdate()
            ->get()
            ->each(function (Payment $payment) use ($invoice) {
                $allocations = $payment->pending_manual_allocations ?? [];
                if (! array_key_exists((string) $invoice->id, $allocations)
                    && ! array_key_exists($invoice->id, $allocations)) {
                    return;
                }

                unset($allocations[(string) $invoice->id], $allocations[$invoice->id]);
                $payment->update([
                    'pending_manual_allocations' => $allocations === [] ? null : $allocations,
                ]);
            });
    }

    private function discountAmountForNewRentInvoice(Agreement $agreement, float $subtotal): float
    {
        if ($agreement->discount_is_one_time && ! $agreement->hasPendingOneTimeDiscount()) {
            return 0;
        }

        return $this->discountAmount($agreement, $subtotal);
    }

    private function discountAmountForExistingInvoice(
        Agreement $agreement,
        Invoice $invoice,
        float $subtotal
    ): float {
        if (! $agreement->discount_is_one_time) {
            return $this->discountAmount($agreement, $subtotal);
        }

        if ((int) $agreement->discount_consumed_invoice_id !== (int) $invoice->id) {
            return 0;
        }

        return $this->discountAmount($agreement, $subtotal);
    }

    private function consumeOneTimeDiscount(Agreement $agreement, Invoice $invoice, float $discountAmount): void
    {
        if ($discountAmount <= 0 || ! $agreement->hasPendingOneTimeDiscount()) {
            return;
        }

        $agreement->forceFill([
            'discount_consumed_at' => now(),
            'discount_consumed_invoice_id' => $invoice->id,
        ])->save();
    }

    private function releaseOneTimeDiscountFromInvoice(Agreement $agreement, Invoice $invoice): void
    {
        if (
            ! $agreement->discount_is_one_time
            || (int) $agreement->discount_consumed_invoice_id !== (int) $invoice->id
        ) {
            return;
        }

        $agreement->forceFill([
            'discount_consumed_at' => null,
            'discount_consumed_invoice_id' => null,
        ])->save();
    }

    private function discountDescription(Agreement $agreement, float $discountAmount): ?string
    {
        if ($discountAmount <= 0) {
            return null;
        }

        $value = $agreement->discount_type === 'percentage'
            ? rtrim(rtrim(number_format((float) $agreement->discount_value, 2, '.', ''), '0'), '.').' percent'
            : '£'.number_format((float) $agreement->discount_value, 2);
        $mode = $agreement->discount_is_one_time ? 'One-time' : 'Recurring';
        $notes = trim((string) $agreement->discount_notes);

        return trim(sprintf('%s %s discount%s', $mode, $value, $notes !== '' ? ': '.$notes : ''));
    }

    private function discountAmount(Agreement $agreement, float $subtotal): float
    {
        return $agreement->discountAmountFor($subtotal);
    }

    private function previousAnchorBefore(Carbon $anchor, string $rentInterval): Carbon
    {
        return match (strtolower($rentInterval)) {
            'weekly' => $anchor->copy()->subWeek(),
            'quarterly' => $anchor->copy()->subMonthsNoOverflow(3),
            'yearly' => $anchor->copy()->subYearNoOverflow(),
            default => $anchor->copy()->subMonthNoOverflow(),
        };
    }

    private function nextInvoiceDate(Carbon $date, string $rentInterval): Carbon
    {
        return match (strtolower($rentInterval)) {
            'weekly' => $date->copy()->addWeek(),
            'quarterly' => $date->copy()->addMonthsNoOverflow(3),
            'yearly' => $date->copy()->addYearNoOverflow(),
            default => $date->copy()->addMonthNoOverflow(),
        };
    }
}
