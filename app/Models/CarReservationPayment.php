<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarReservationPayment extends Model
{
    public const POSTING_STATUS_PENDING = 'pending';

    public const POSTING_STATUS_POSTED = 'posted';

    public const POSTING_STATUS_CANCELLED = 'cancelled';

    public const POSTING_STATUS_CONVERTED = 'converted';

    protected $fillable = [
        'car_reservation_id',
        'payment_method',
        'bank_account_id',
        'amount',
        'posting_status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(CarReservation::class, 'car_reservation_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function isPendingFinancialSheet(): bool
    {
        return $this->posting_status === self::POSTING_STATUS_PENDING;
    }

    public function isPostedFinancialSheet(): bool
    {
        return $this->posting_status === self::POSTING_STATUS_POSTED;
    }

    public function scopePendingFinancialSheet(Builder $query): Builder
    {
        return $query->where('posting_status', self::POSTING_STATUS_PENDING);
    }

    public function scopePostedFinancialSheet(Builder $query): Builder
    {
        return $query->where('posting_status', self::POSTING_STATUS_POSTED);
    }

    public function scopeVisibleOnFinancialSheet(Builder $query): Builder
    {
        return $query->whereIn('posting_status', [
            self::POSTING_STATUS_PENDING,
            self::POSTING_STATUS_POSTED,
        ]);
    }
}
