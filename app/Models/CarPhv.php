<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarPhv extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_id', 'counsel_id', 'amount', 'start_date',
        'expiry_date', 'notify_before_expiry', 'document'
    ];

    protected $casts = [
        'start_date' => 'date',
        'expiry_date' => 'date',
        'amount' => 'decimal:2'
    ];

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function counsel()
    {
        return $this->belongsTo(Counsel::class);
    }
}
