<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarRoadTax extends Model
{
    use HasFactory;

    protected $fillable = ['car_id', 'start_date', 'term', 'amount'];

    protected $casts = [
        'start_date' => 'date',
        'amount' => 'decimal:2'
    ];

    public function car()
    {
        return $this->belongsTo(Car::class);
    }
}
