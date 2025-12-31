<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id','company_id', 'car_model_id', 'registration', 'color',
        'vin', 'v5_document', 'manufacture_year', 'registration_year',
        'purchase_date', 'purchase_price', 'purchase_type', 'createdBy', 'updatedBy'
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_price' => 'decimal:2'
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function carModel()
    {
        return $this->belongsTo(CarModel::class);
    }

    public function agreements()
    {
        return $this->hasMany(Agreement::class);
    }

    public function claims()
    {
        return $this->hasMany(Claim::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function mots()
    {
        return $this->hasMany(CarMot::class);
    }

    public function roadTaxes()
    {
        return $this->hasMany(CarRoadTax::class);
    }

    public function phvs()
    {
        return $this->hasMany(CarPhv::class);
    }

    public function insurances()
    {
        return $this->hasMany(CarInsurance::class);
    }
}
