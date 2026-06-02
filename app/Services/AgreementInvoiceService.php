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
        if (! $agreement->driver_id || ! $agreement->start_date || ! $agreement->end_date) {
            return 0;
        }

        $throughDate = $throughDate?->copy()->startOfDay() ?? now()->startOfDay();
        $currentDate = $agreement->start_date->copy()->startOfDay();
        $endDate = $agreement->end_date->copy()->startOfDay()->min($throughDate);
        $generated = 0;

        while ($currentDate <= $endDate) {
            if ($this->createInvoiceForDate($agreement, $currentDate)) {
                $generated++;
            }

            $currentDate = $this->nextInvoiceDate($currentDate, (string) $agreement->rent_interval);
        }

        return $generated;
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
