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
        'stripe_customer_id',
        'settings',
        'suspended_at',
        'suspension_reason'
    ];

    protected $casts = [
        'settings' => 'array',
        'suspended_at' => 'datetime',
    ];

    const STATUS_ACTIVE = 1;
    const STATUS_SUSPENDED = 0;

    // ==================== RELATIONSHIPS ====================

    public function users()
    {
        return $this->belongsToMany(User::class, 'tenant_user')
            ->withPivot('role', 'is_primary', 'joined_at')
            ->withTimestamps();
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class)->latest();
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function paymentMethods()
    {
        return $this->hasMany(\App\Models\PaymentMethod::class);
    }

    // ✅ Add this relationship
    public function invoices()
    {
        return $this->hasMany(\App\Models\Invoice::class);
    }

    public function cars()
    {
        return $this->hasMany(\App\Models\Car::class);
    }

    public function drivers()
    {
        return $this->hasMany(\App\Models\Driver::class);
    }

    public function agreements()
    {
        return $this->hasMany(\App\Models\Agreement::class);
    }

    // ==================== STATUS METHODS ====================

    public function isActive()
    {
        return $this->status == self::STATUS_ACTIVE;
    }

    public function isSuspended()
    {
        return $this->status == self::STATUS_SUSPENDED;
    }

    public function activate()
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);
    }

    public function suspend($reason = null)
    {
        $this->update([
            'status' => self::STATUS_SUSPENDED,
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ]);
    }
}
