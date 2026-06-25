<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_no',
        'driver_id',
        'payment_method',
        'bank_account_id',
        'payment_date',
        'amount',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
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

    public function getAllocatedAmountAttribute()
    {
        return (float) $this->allocations()->sum('allocated_amount');
    }

    public function getUnallocatedAmountAttribute()
    {
        return max((float) $this->amount - $this->allocated_amount, 0);
    }

    public static function generatePaymentNo(): string
    {
        $lastPayment = self::query()->latest('id')->value('payment_no');
        preg_match('/(\d+)$/', (string) $lastPayment, $matches);
        $nextNumber = isset($matches[1]) ? ((int) $matches[1]) + 1 : 1;

        return 'Payment #'.$nextNumber;
    }
}
