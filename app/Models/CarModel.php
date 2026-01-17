<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarModel extends Model
{
    use HasFactory;

    protected $fillable = ['tenant_id, name', 'createdBy', 'updatedBy'];

    public function cars()
    {
        return $this->hasMany(Car::class);
    }
}
