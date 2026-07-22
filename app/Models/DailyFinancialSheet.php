<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyFinancialSheet extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_APPROVED = 'approved';

    protected $fillable = [
        'tenant_id',
        'sheet_date',
        'status',
        'cash_in',
        'cash_out',
        'bank_in_json',
        'bank_out_json',
        'approval_notes',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'sheet_date' => 'date',
        'cash_in' => 'decimal:2',
        'cash_out' => 'decimal:2',
        'bank_in_json' => 'array',
        'bank_out_json' => 'array',
        'approved_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}
