<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarStatusHistory extends Model
{
    protected $fillable = [
        'tenant_id',
        'car_id',
        'previous_status',
        'new_status',
        'reservation_id',
        'vehicle_swap_id',
        'status_data',
        'changed_by',
    ];

    protected $casts = [
        'status_data' => 'array',
    ];

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(CarReservation::class, 'reservation_id');
    }

    public function vehicleSwap(): BelongsTo
    {
        return $this->belongsTo(VehicleSwap::class, 'vehicle_swap_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
