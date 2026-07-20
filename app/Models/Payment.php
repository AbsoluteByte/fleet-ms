<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Payment extends Model
{
    use HasFactory;

    public const POSTING_STATUS_PENDING = 'pending';

    public const POSTING_STATUS_POSTED = 'posted';

    /** @var list<string> */
    public const METHODS_REQUIRING_BANK_ACCOUNT = ['Bank Transfer', 'Card Payment'];

    public static function requiresBankAccount(?string $paymentMethod): bool
    {
        return in_array($paymentMethod, self::METHODS_REQUIRING_BANK_ACCOUNT, true);
    }

    public static function bankAccountIdForMethod(?string $paymentMethod, mixed $bankAccountId): ?int
    {
        if (! self::requiresBankAccount($paymentMethod) || $bankAccountId === null || $bankAccountId === '') {
            return null;
        }

        return (int) $bankAccountId;
    }

    protected $fillable = [
        'payment_no',
        'driver_id',
        'payment_method',
        'bank_account_id',
        'payment_date',
        'amount',
        'notes',
        'posting_status',
        'created_by',
        'auto_allocate',
        'allocation_source_id',
        'allocation_invoice_types',
        'pending_manual_allocations',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'auto_allocate' => 'boolean',
        'allocation_invoice_types' => 'array',
        'pending_manual_allocations' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function (Payment $payment) {
            if (empty($payment->payment_no)) {
                $payment->payment_no = self::generatePaymentNo();
            }
        });
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function creditTransactionLines()
    {
        return $this->hasMany(DriverCreditTransactionLine::class, 'source_payment_id');
    }

    public function sourceAgreement()
    {
        return $this->belongsTo(Agreement::class, 'allocation_source_id');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePosted(Builder $query): Builder
    {
        return $query->where('posting_status', self::POSTING_STATUS_POSTED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('posting_status', self::POSTING_STATUS_PENDING);
    }

    public function isPending(): bool
    {
        return $this->posting_status === self::POSTING_STATUS_PENDING;
    }

    public function isPosted(): bool
    {
        return $this->posting_status === self::POSTING_STATUS_POSTED;
    }

    public function getAllocatedAmountAttribute()
    {
        return (float) $this->allocations()->sum('allocated_amount');
    }

    public function getUnallocatedAmountAttribute()
    {
        return max((float) $this->amount - $this->allocated_amount - $this->refunded_credit_amount, 0);
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

    public function getRefundedCreditAmountAttribute(): float
    {
        if (! Schema::hasTable('driver_credit_transaction_lines')) {
            return 0.0;
        }

        return round((float) $this->creditTransactionLines()
            ->where('status', DriverCreditTransactionLine::STATUS_CONSUMED)
            ->whereHas('transaction', fn ($query) => $query
                ->posted()
                ->where('kind', DriverCreditTransaction::KIND_REFUND))
            ->sum('amount'), 2);
    }

    public function getSpendableCreditAmountAttribute(): float
    {
        if (! $this->isPosted()) {
            return 0.0;
        }

        return round(max(
            (float) $this->amount
            - (float) $this->allocated_amount
            - $this->reserved_credit_amount
            - $this->refunded_credit_amount,
            0
        ), 2);
    }

    public static function generatePaymentNo(): string
    {
        $lastPayment = self::query()->latest('id')->value('payment_no');
        preg_match('/(\d+)$/', (string) $lastPayment, $matches);
        $nextNumber = isset($matches[1]) ? ((int) $matches[1]) + 1 : 1;

        return 'Payment #'.$nextNumber;
    }
}
