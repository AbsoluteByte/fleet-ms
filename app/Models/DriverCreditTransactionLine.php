<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DriverCreditTransactionLine extends Model
{
    public const STATUS_RESERVED = 'reserved';

    public const STATUS_CONSUMED = 'consumed';

    public const STATUS_CONVERTED = 'converted';

    public const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'driver_credit_transaction_id',
        'source_payment_id',
        'target_invoice_id',
        'amount',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(DriverCreditTransaction::class, 'driver_credit_transaction_id');
    }

    public function sourcePayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'source_payment_id');
    }

    public function targetInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'target_invoice_id');
    }

    public function paymentAllocation(): HasOne
    {
        return $this->hasOne(PaymentAllocation::class, 'driver_credit_transaction_line_id');
    }
}
