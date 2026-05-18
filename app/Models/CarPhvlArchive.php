<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarPhvlArchive extends Model
{
    protected $fillable = [
        'tenant_id',
        'car_id',
        'car_phv_id',
        'mot_status',
        'application_status',
        'applied_date',
        'appointment_confirmation',
        'appointment_notes',
        'appointment_at',
        'phvl_result_status',
        'fail_notes',
        'renewal_context',
        'phv_summary',
        'completed_at',
        'completed_by',
    ];

    protected $casts = [
        'applied_date' => 'date',
        'appointment_at' => 'datetime',
        'completed_at' => 'datetime',
        'phv_summary' => 'array',
    ];

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    public function carPhv(): BelongsTo
    {
        return $this->belongsTo(CarPhv::class, 'car_phv_id');
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(CarPhvlProgressEvent::class, 'archive_id');
    }
}
