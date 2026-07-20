<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgreementDeduction extends Model
{
    protected $fillable = [
        'tenant_id',
        'agreement_id',
        'amount',
        'notes',
        'sort_order',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
