<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarSornHistory extends Model
{
    protected $fillable = [
        'tenant_id',
        'car_id',
        'sorn_started_at',
        'sorn_started_by',
        'sorn_ended_at',
        'sorn_ended_by',
        'sorn_document',
    ];

    protected $casts = [
        'sorn_started_at' => 'datetime',
        'sorn_ended_at' => 'datetime',
    ];

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sorn_started_by');
    }

    public function endedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sorn_ended_by');
    }

    public function isActive(): bool
    {
        return $this->sorn_ended_at === null;
    }
}
