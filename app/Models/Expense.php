<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    public const POSTING_STATUS_PENDING = 'pending';

    public const POSTING_STATUS_POSTED = 'posted';

    public const TYPE_DAILY = 'Daily';

    public const DAILY_TYPE_OFFICE = 'office';

    public const DAILY_TYPE_VEHICLE = 'vehicle';

    protected $fillable = [
        'tenant_id',
        'car_id',
        'type',
        'daily_expense_type',
        'title',
        'date',
        'description',
        'amount',
        'payment_method',
        'bank_account_id',
        'document',
        'notes',
        'posting_status',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDailyExpense(): bool
    {
        return $this->type === self::TYPE_DAILY;
    }

    public function scopePosted(Builder $query): Builder
    {
        return $query->where('posting_status', self::POSTING_STATUS_POSTED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('posting_status', self::POSTING_STATUS_PENDING);
    }

    public function scopeDaily(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_DAILY);
    }

    public function scopeCarLinked(Builder $query): Builder
    {
        return $query
            ->whereNotNull('car_id')
            ->where(function (Builder $query) {
                $query->whereNull('type')
                    ->orWhere('type', '!=', self::TYPE_DAILY);
            });
    }

    public function isPending(): bool
    {
        return $this->posting_status === self::POSTING_STATUS_PENDING;
    }

    public function isPosted(): bool
    {
        return $this->posting_status === self::POSTING_STATUS_POSTED;
    }
}
