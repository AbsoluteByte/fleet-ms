<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'provider_name', 'insurance_type',
        'amount',
        'policy_number', 'expiry_date', 'status_id', 'tenant_id', 'createdBy', 'updatedBy'
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'amount' => 'decimal:2'
    ];

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function claims()
    {
        return $this->hasMany(Claim::class);
    }

    public function carInsurances()
    {
        return $this->hasMany(CarInsurance::class);
    }
}
