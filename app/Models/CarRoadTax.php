<?php
namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarRoadTax extends Model
{
    use HasFactory;

    protected $fillable = ['tenant_id', 'car_id', 'start_date', 'term', 'amount'];

    protected $casts = [
        'start_date' => 'date',
        'amount' => 'decimal:2'
    ];

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function expiryDate(): ?Carbon
    {
        if (! $this->start_date || ! $this->term) {
            return null;
        }

        $startDate = $this->start_date->copy();

        switch (strtolower($this->term)) {
            case '6 months':
                return $startDate->addMonths(6);
            case '12 months':
            case '1 year':
                return $startDate->addYear();
            default:
                if (preg_match('/(\d+)\s*(month|year)/', strtolower($this->term), $matches)) {
                    $number = (int) $matches[1];
                    $unit = $matches[2];

                    if ($unit === 'month') {
                        return $startDate->addMonths($number);
                    }
                    if ($unit === 'year') {
                        return $startDate->addYears($number);
                    }
                }

                return null;
        }
    }
}
