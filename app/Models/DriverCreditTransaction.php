<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DriverCreditTransaction extends Model
{
    public const KIND_REFUND = 'refund';

    public const KIND_INVOICE_APPLICATION = 'invoice_application';

    public const STATUS_PENDING = 'pending';

    public const STATUS_POSTED = 'posted';

    protected $fillable = [
        'tenant_id',
        'driver_id',
        'kind',
        'amount',
        'request_date',
        'payment_method',
        'bank_account_id',
        'posting_status',
        'created_by',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'request_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(DriverCreditTransactionLine::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('posting_status', self::STATUS_PENDING);
    }

    public function scopePosted(Builder $query): Builder
    {
        return $query->where('posting_status', self::STATUS_POSTED);
    }

    public function isPending(): bool
    {
        return $this->posting_status === self::STATUS_PENDING;
    }

    public function isPosted(): bool
    {
        return $this->posting_status === self::STATUS_POSTED;
    }
}
