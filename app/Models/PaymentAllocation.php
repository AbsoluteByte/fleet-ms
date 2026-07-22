<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentAllocation extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'payment_id',
        'invoice_id',
        'driver_credit_transaction_line_id',
        'allocated_amount',
        'created_at',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creditTransactionLine()
    {
        return $this->belongsTo(DriverCreditTransactionLine::class, 'driver_credit_transaction_line_id');
    }
}
