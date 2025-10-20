<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarInsurance extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_id', 'insurance_provider_id', 'start_date',
        'expiry_date', 'insurance_document', 'notify_before_expiry', 'status_id'
    ];

    protected $casts = [
        'start_date' => 'date',
        'expiry_date' => 'date'
    ];

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }
}
