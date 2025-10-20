<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Claim extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_id', 'insurance_provider_id', 'case_date', 'incident_date',
        'our_reference', 'case_reference', 'courtesy_type',
        'follow_up', 'notes', 'status_id'
    ];

    protected $casts = [
        'case_date' => 'date',
        'incident_date' => 'date'
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
