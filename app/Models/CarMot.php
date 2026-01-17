<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarMot extends Model
{
    use HasFactory;

    protected $fillable = ['tenant_id', 'car_id', 'expiry_date', 'amount', 'term', 'document'];

    protected $casts = [
        'expiry_date' => 'date',
        'amount' => 'decimal:2'
    ];

    public function car()
    {
        return $this->belongsTo(Car::class);
    }
}
