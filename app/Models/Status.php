<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'color'];

    public function claims()
    {
        return $this->hasMany(Claim::class);
    }

    public function penalties()
    {
        return $this->hasMany(Penalty::class);
    }

    public function insuranceProviders()
    {
        return $this->hasMany(InsuranceProvider::class);
    }

    public function agreements()
    {
        return $this->hasMany(Agreement::class);
    }

    public function carInsurances()
    {
        return $this->hasMany(CarInsurance::class);
    }
}
