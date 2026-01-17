<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'billing_period',
        'price',
        'max_users',
        'max_vehicles',
        'max_drivers',
        'has_notifications',
        'has_reports',
        'has_api_access',
        'is_active',
        'trial_days',
        'features',
        'stripe_price_id'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'has_notifications' => 'boolean',
        'has_reports' => 'boolean',
        'has_api_access' => 'boolean',
        'is_active' => 'boolean',
        'features' => 'array',
    ];

    // ==================== RELATIONSHIPS ====================

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscriptions()
    {
        return $this->subscriptions()->whereIn('status', ['trialing', 'active']);
    }

    // ==================== HELPER METHODS ====================

    public function getPriceFormatted()
    {
        return '£' . number_format($this->price, 2);
    }

    public function getPriceWithPeriod()
    {
        return $this->getPriceFormatted() . '/' . $this->billing_period;
    }

    public function isUnlimited($feature)
    {
        return $this->$feature == -1;
    }

    public function hasFeature($feature)
    {
        if (!$this->features) {
            return false;
        }

        return in_array($feature, $this->features);
    }

    // Get feature limits
    public function getUsersLimit()
    {
        return $this->max_users == -1 ? 'Unlimited' : $this->max_users;
    }

    public function getVehiclesLimit()
    {
        return $this->max_vehicles == -1 ? 'Unlimited' : $this->max_vehicles;
    }

    public function getDriversLimit()
    {
        return $this->max_drivers == -1 ? 'Unlimited' : $this->max_drivers;
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMonthly($query)
    {
        return $query->where('billing_period', 'monthly');
    }

    public function scopeQuarterly($query)
    {
        return $query->where('billing_period', 'quarterly');
    }

    public function scopeYearly($query)
    {
        return $query->where('billing_period', 'yearly');
    }

    // ==================== ACCESSORS ====================

    public function getBillingPeriodLabelAttribute()
    {
        return ucfirst($this->billing_period);
    }

    public function getTrialDaysLabelAttribute()
    {
        return $this->trial_days . ' days trial';
    }

    // Get all features as array
    public function getAllFeaturesAttribute()
    {
        $features = [];

        $features[] = $this->getUsersLimit() . ' Users';
        $features[] = $this->getVehiclesLimit() . ' Vehicles';
        $features[] = $this->getDriversLimit() . ' Drivers';

        if ($this->has_notifications) {
            $features[] = 'Email & SMS Notifications';
        }

        if ($this->has_reports) {
            $features[] = 'Advanced Reports';
        }

        if ($this->has_api_access) {
            $features[] = 'API Access';
        }

        // Add custom features from JSON
        if ($this->features && is_array($this->features)) {
            $features = array_merge($features, $this->features);
        }

        return $features;
    }

    // ==================== STATIC METHODS ====================

    public static function getBasicPackage()
    {
        return self::where('name', 'Basic')->first();
    }

    public static function getStandardPackage()
    {
        return self::where('name', 'Standard')->first();
    }

    public static function getPremiumPackage()
    {
        return self::where('name', 'Premium')->first();
    }

    // Get recommended package based on usage
    public static function getRecommendedPackage($users = 0, $vehicles = 0, $drivers = 0)
    {
        return self::active()
            ->where(function($query) use ($users) {
                $query->where('max_users', '>=', $users)
                    ->orWhere('max_users', -1);
            })
            ->where(function($query) use ($vehicles) {
                $query->where('max_vehicles', '>=', $vehicles)
                    ->orWhere('max_vehicles', -1);
            })
            ->where(function($query) use ($drivers) {
                $query->where('max_drivers', '>=', $drivers)
                    ->orWhere('max_drivers', -1);
            })
            ->orderBy('price', 'asc')
            ->first();
    }
}
