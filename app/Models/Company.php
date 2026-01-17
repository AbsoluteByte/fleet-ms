<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'director_name', 'logo', 'address_line_1',
        'address_line_2', 'postcode', 'town', 'county',
        'country_id', 'phone', 'email', 'tenant_id', 'createdBy', 'updatedBy'
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function cars()
    {
        return $this->hasMany(Car::class);
    }

    public function agreements()
    {
        return $this->hasMany(Agreement::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
