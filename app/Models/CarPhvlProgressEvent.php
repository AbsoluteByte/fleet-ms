<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarPhvlProgressEvent extends Model
{
    protected $fillable = [
        'tenant_id',
        'car_id',
        'archive_id',
        'field',
        'old_value',
        'new_value',
        'user_id',
    ];

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    public function archive(): BelongsTo
    {
        return $this->belongsTo(CarPhvlArchive::class, 'archive_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
