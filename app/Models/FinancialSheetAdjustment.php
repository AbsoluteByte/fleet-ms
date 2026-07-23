<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialSheetAdjustment extends Model
{
    public const EVENT_CORRECTION = 'correction';

    public const EVENT_REVERSAL = 'reversal';

    public const SOURCE_PAYMENT = 'payment';

    protected $fillable = [
        'tenant_id',
        'sheet_date',
        'source_type',
        'source_id',
        'event_type',
        'direction',
        'amount',
        'payment_method',
        'bank_account_id',
        'description',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'sheet_date' => 'date',
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
