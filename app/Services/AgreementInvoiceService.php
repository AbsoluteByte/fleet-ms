<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Collection;

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
            ->whereDate('end_date', '>=', now()->startOfDay())
            ->chunkById(100, function (Collection $agreements) use (&$count, $throughDate) {
                foreach ($agreements as $agreement) {
                    $count += $this->generateForAgreement($agreement, $throughDate);
                }
            });

        return $count;
    }

    public function generateForAgreement(Agreement $agreement, ?Carbon $throughDate = null): int
    {
        if ($agreement->isReplacementVehicle() || ! $agreement->driver_id || ! $agreement->start_date || ! $agreement->end_date) {
            return 0;
        }

        if ($agreement->upgraded_from_agreement_id) {
            return $this->generateForUpgradedAgreement($agreement, $throughDate);
        }

        $throughDate = $throughDate?->copy()->startOfDay() ?? now()->startOfDay();
        $currentDate = $agreement->start_date->copy()->startOfDay();
        $endDate = $agreement->end_date->copy()->startOfDay()->min($throughDate);
        $generated = $this->syncDepositInvoice($agreement) ? 1 : 0;

        while ($currentDate <= $endDate) {
            if ($this->createInvoiceForDate($agreement, $currentDate)) {
                $generated++;
            }

            $currentDate = $this->nextInvoiceDate($currentDate, (string) $agreement->rent_interval);
        }

        return $generated;
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
        $remainingDays = $changeDate->diffInDays($nextAnchor);

        if ($remainingDays <= 0) {
            return 0;
        }

        $previousAnchor = $this->previousBillingAnchor($originalStart, $nextAnchor, $rentInterval);
        $periodDays = $previousAnchor->diffInDays($nextAnchor);

        if ($periodDays <= 0) {
            return 0;
        }

        $rentDiff = (float) $new->agreed_rent - (float) $old->agreed_rent;
        $subtotal = round($rentDiff / $periodDays * $remainingDays, 2);

        if ($subtotal > 0) {
            $discountAmount = $this->discountAmount($new, $subtotal);

            return round(max($subtotal - $discountAmount, 0), 2);
        }

        return $subtotal;
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
        $endDate = $agreement->end_date->copy()->startOfDay()->min($throughDate);
        $generated = 0;
        $upgradeDate = $agreement->start_date->copy()->startOfDay();
        $originalStart = $old->start_date->copy()->startOfDay();
        $rentInterval = (string) $agreement->rent_interval;
        $nextAnchor = $this->nextBillingAnchor($originalStart, $upgradeDate, $rentInterval);
        $adjustment = $this->calculateChangeCarAdjustment($agreement, $old, $upgradeDate);

        if ($adjustment > 0) {
            if ($this->createChangeCarProrationInvoice($agreement, $upgradeDate, $adjustment, $nextAnchor)) {
                $generated++;
            }
        }

        $currentDate = $nextAnchor->copy();

        while ($currentDate <= $endDate) {
            if ($this->createInvoiceForDate($agreement, $currentDate)) {
                $generated++;
            }

            $currentDate = $this->nextInvoiceDate($currentDate, $rentInterval);
        }

        return $generated;
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
        $discountAmount = $this->discountAmount($agreement, $subtotal);
        $totalAmount = max($subtotal - $discountAmount, 0);
        $dueDate = $invoiceDate->copy()->addDays(5);

        $invoice = Invoice::create([
            'driver_id' => $agreement->driver_id,
            'source_id' => $agreement->id,
            'invoice_type' => 'agreement',
            'invoice_date' => $invoiceDate->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'discount_description' => $agreement->discount_notes,
            'tax_amount' => 0,
            'total_amount' => $totalAmount,
            'paid_amount' => 0,
            'balance_amount' => $totalAmount,
            'status' => $totalAmount <= 0 ? 'paid' : ($dueDate->lt(now()->startOfDay()) ? 'overdue' : 'pending'),
            'notes' => 'Auto-generated agreement invoice',
        ]);

        app(PaymentAllocationService::class)->allocateAvailableCreditToInvoice($invoice);

        return true;
    }

    private function discountAmount(Agreement $agreement, float $subtotal): float
    {
        $discountValue = (float) ($agreement->discount_value ?? 0);

        if ($discountValue <= 0 || ! in_array($agreement->discount_type, ['percentage', 'fixed'], true)) {
            return 0;
        }

        if ($agreement->discount_type === 'percentage') {
            return round(min($subtotal * ($discountValue / 100), $subtotal), 2);
        }

        return round(min($discountValue, $subtotal), 2);
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
