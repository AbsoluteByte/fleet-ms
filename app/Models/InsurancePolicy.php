<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsurancePolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_id', 'insurance_provider_id', 'policy_number', 'policy_type',
        'premium_amount', 'excess_amount', 'policy_start_date', 'policy_end_date',
        'next_renewal_date', 'coverage_details', 'payment_frequency', 'monthly_premium',
        'auto_renewal', 'notify_days_before_expiry', 'policy_document', 'status', 'notes'
    ];

    protected $casts = [
        'policy_start_date' => 'date',
        'policy_end_date' => 'date',
        'next_renewal_date' => 'date',
        'premium_amount' => 'decimal:2',
        'excess_amount' => 'decimal:2',
        'monthly_premium' => 'decimal:2',
        'coverage_details' => 'array',
        'auto_renewal' => 'boolean'
    ];

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class);
    }

    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            'active' => 'badge-success',
            'pending' => 'badge-warning',
            'expired' => 'badge-danger',
            'cancelled' => 'badge-secondary',
            default => 'badge-secondary'
        };
    }

    public function getDaysUntilExpiryAttribute()
    {
        return now()->diffInDays($this->policy_end_date, false);
    }

    public function getIsExpiringAttribute()
    {
        return $this->days_until_expiry <= $this->notify_days_before_expiry && $this->days_until_expiry > 0;
    }

    public function getIsExpiredAttribute()
    {
        return $this->policy_end_date->isPast();
    }

    public function scopeExpiring($query, $days = 30)
    {
        return $query->where('policy_end_date', '<=', now()->addDays($days))
            ->where('policy_end_date', '>', now())
            ->where('status', 'active');
    }

    public function scopeExpired($query)
    {
        return $query->where('policy_end_date', '<', now())
            ->where('status', '!=', 'expired');
    }
}
