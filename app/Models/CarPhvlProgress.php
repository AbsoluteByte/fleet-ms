<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarPhvlProgress extends Model
{
    protected $table = 'car_phvl_progress';

    protected $fillable = [
        'tenant_id',
        'car_id',
        'mot_status',
        'application_status',
        'applied_date',
        'appointment_confirmation',
        'appointment_at',
        'phvl_result_status',
        'fail_notes',
        'updated_by',
    ];

    protected $casts = [
        'applied_date' => 'date',
        'appointment_at' => 'datetime',
    ];

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
