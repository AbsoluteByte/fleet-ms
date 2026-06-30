<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarPhvlSuspensionHistory extends Model
{
    protected $fillable = [
        'tenant_id',
        'car_id',
        'from_status',
        'to_status',
        'event_date',
        'car_status_history_id',
        'changed_by',
        'notes',
    ];

    protected $casts = [
        'event_date' => 'date',
    ];

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function carStatusHistory(): BelongsTo
    {
        return $this->belongsTo(CarStatusHistory::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
