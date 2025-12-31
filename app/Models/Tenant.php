<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'status',
    ];

    // Status constants
    const STATUS_ACTIVE = 1;
    const STATUS_SUSPENDED = 0;

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function cars()
    {
        return $this->hasMany(Car::class);
    }

    public function drivers()
    {
        return $this->hasMany(Driver::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
