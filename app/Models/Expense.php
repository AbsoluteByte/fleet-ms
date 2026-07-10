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

    protected $fillable = [
        'tenant_id', 'car_id', 'type', 'date', 'description', 'amount', 'document',
        'posting_status', 'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function car()
    {
        return $this->belongsTo(Car::class);
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
}
