<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_no',
        'driver_id',
        'source_id',
        'invoice_type',
        'invoice_date',
        'subtotal',
        'discount_amount',
        'discount_description',
        'tax_amount',
        'total_amount',
        'paid_amount',
        'balance_amount',
        'status',
        'due_date',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(function (Invoice $invoice) {
            if (empty($invoice->invoice_no)) {
                $invoice->invoice_no = self::generateInvoiceNo();
            }

            if ($invoice->balance_amount === null) {
                $invoice->balance_amount = ((float) $invoice->total_amount) - ((float) $invoice->paid_amount);
            }
        });
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function paymentAllocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function creditTransactionLines()
    {
        return $this->hasMany(DriverCreditTransactionLine::class, 'target_invoice_id');
    }

    public function getReservedCreditAmountAttribute(): float
    {
        if (! Schema::hasTable('driver_credit_transaction_lines')) {
            return 0.0;
        }

        return round((float) $this->creditTransactionLines()
            ->where('status', DriverCreditTransactionLine::STATUS_RESERVED)
            ->whereHas('transaction', fn ($query) => $query->pending())
            ->sum('amount'), 2);
    }

    public function sourceAgreement()
    {
        return $this->belongsTo(Agreement::class, 'source_id');
    }

    public function vehicleRegistrationLabel(): string
    {
        if (! in_array($this->invoice_type, ['agreement', 'agreement_deposit', 'agreement_additional_charge'], true) || ! $this->source_id) {
            return '—';
        }

        $registration = $this->sourceAgreement?->car?->registration;

        return is_string($registration) && $registration !== '' ? $registration : '—';
    }

    public function linkedAgreementHasActiveOrSwapStatus(): bool
    {
        if (! in_array($this->invoice_type, ['agreement', 'agreement_deposit', 'agreement_additional_charge'], true) || ! $this->source_id) {
            return false;
        }

        $statusName = (string) optional($this->sourceAgreement?->status)->name;

        return in_array($statusName, ['Active', 'Swap'], true);
    }

    public function payingCompanyNameLabel(): ?string
    {
        if (! in_array($this->invoice_type, ['agreement', 'agreement_deposit', 'agreement_additional_charge'], true) || ! $this->source_id) {
            return null;
        }

        $name = trim((string) ($this->sourceAgreement?->paying_company_name ?? ''));

        return $name !== '' ? $name : null;
    }

    public function linkedAgreementId(): ?int
    {
        if (! in_array($this->invoice_type, ['agreement', 'agreement_deposit', 'agreement_additional_charge'], true)) {
            return null;
        }

        $agreementId = (int) ($this->source_id ?? 0);

        return $agreementId > 0 ? $agreementId : null;
    }

    public function markAsPaid($amountPaid = null)
    {
        $payment = $amountPaid ?? $this->balance_amount;
        $newPaidAmount = ((float) $this->paid_amount) + ((float) $payment);
        $newBalance = ((float) $this->total_amount) - $newPaidAmount;

        $this->update([
            'status' => $newBalance <= 0 ? 'paid' : 'partial',
            'paid_amount' => $newPaidAmount,
            'balance_amount' => max($newBalance, 0),
        ]);

        return $this;
    }

    public function refreshPaymentTotals()
    {
        $paidAmount = (float) $this->paymentAllocations()->sum('allocated_amount');
        $totalAmount = (float) $this->total_amount;
        $balanceAmount = max($totalAmount - $paidAmount, 0);

        $this->forceFill([
            'paid_amount' => $paidAmount,
            'balance_amount' => $balanceAmount,
            'status' => $this->resolveStatus($balanceAmount, $paidAmount),
        ])->save();

        return $this;
    }

    public function resolveStatus(float $balanceAmount, float $paidAmount): string
    {
        if ($balanceAmount <= 0) {
            return 'paid';
        }

        if ($paidAmount > 0) {
            return 'partial';
        }

        return $this->due_date && $this->due_date->lt(now()->startOfDay()) ? 'overdue' : 'pending';
    }

    public function isOverdue(): bool
    {
        return $this->due_date &&
            $this->due_date < now() &&
            ! in_array($this->status, ['paid', 'cancelled'], true);
    }

    public function getFormattedSubtotal()
    {
        return '£'.number_format((float) $this->subtotal, 2);
    }

    public function getFormattedTotalAmount()
    {
        return '£'.number_format((float) $this->total_amount, 2);
    }

    // Backward compatibility for existing views/controllers.
    public function getInvoiceNumberAttribute()
    {
        return $this->invoice_no;
    }

    public function setInvoiceNumberAttribute($value)
    {
        $this->attributes['invoice_no'] = $value;
    }

    public function getTaxAttribute()
    {
        return $this->tax_amount;
    }

    public function getTotalAttribute()
    {
        return $this->total_amount;
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->where('status', '!=', 'paid');
    }

    public function scopeAgreement($query)
    {
        return $query->where('invoice_type', 'agreement');
    }

    public function scopeActive($query)
    {
        return $query->where('balance_amount', '>', 0);
    }

    public function scopeDue($query)
    {
        return $query->active()->whereDate('due_date', '<', now());
    }

    public static function generateInvoiceNo(): string
    {
        $lastInvoice = self::query()->latest('id')->value('invoice_no');
        preg_match('/(\d+)$/', (string) $lastInvoice, $matches);
        $nextNumber = isset($matches[1]) ? ((int) $matches[1]) + 1 : 1;

        return 'Invoice #'.$nextNumber;
    }
}
