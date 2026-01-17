<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Counsel extends Model
{
    use HasFactory;

    protected $fillable = ['tenant_id, name', 'createdBy', 'updatedBy'];

    public function carPhvs()
    {
        return $this->hasMany(CarPhv::class);
    }
}
