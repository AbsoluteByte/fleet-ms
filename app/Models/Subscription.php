<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'package_id',
        'stripe_subscription_id',
        'stripe_customer_id',
        'status',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'cancelled_at',
        'suspended_at'
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'cancelled_at' => 'datetime',
        'suspended_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class, 'tenant_id', 'tenant_id');
    }

    // ==================== STATUS CHECKS ====================

    public function isActive(): bool
    {
        return $this->status === 'active' &&
            $this->current_period_end > now();
    }

    public function isTrialing(): bool
    {
        return $this->status === 'trialing' &&
            $this->trial_ends_at &&
            $this->trial_ends_at > now();
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' ||
            ($this->current_period_end && $this->current_period_end < now());
    }

    public function onGracePeriod(): bool
    {
        return $this->isCancelled() &&
            $this->current_period_end > now();
    }

    public function hasExpired(): bool
    {
        return $this->current_period_end &&
            $this->current_period_end < now();
    }

    // ==================== TRIAL METHODS ====================

    public function trialDaysRemaining(): int
    {
        if (!$this->isTrialing()) {
            return 0;
        }

        return (int) now()->diffInDays($this->trial_ends_at, false);
    }

    public function trialHoursRemaining(): int
    {
        if (!$this->isTrialing()) {
            return 0;
        }

        return (int) now()->diffInHours($this->trial_ends_at, false);
    }

    public function trialEndsAt(): ?Carbon
    {
        return $this->trial_ends_at;
    }

    public function onTrial(): bool
    {
        return $this->isTrialing();
    }

    public function trialHasEnded(): bool
    {
        return $this->trial_ends_at &&
            $this->trial_ends_at <= now();
    }

    // ==================== SUBSCRIPTION ACTIONS ====================

    public function suspend($reason = null)
    {
        $this->update([
            'status' => 'suspended',
            'suspended_at' => now()
        ]);

        // Also suspend the tenant
        $this->tenant->suspend($reason ?? 'Subscription suspended');

        return $this;
    }

    public function resume()
    {
        $this->update([
            'status' => 'active',
            'suspended_at' => null
        ]);

        // Activate the tenant
        $this->tenant->activate();

        return $this;
    }

    public function cancel($immediately = false)
    {
        if ($immediately) {
            $this->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'current_period_end' => now()
            ]);
        } else {
            // Cancel at period end
            $this->update([
                'status' => 'cancelled',
                'cancelled_at' => now()
            ]);
        }

        return $this;
    }

    public function markAsExpired()
    {
        $this->update([
            'status' => 'expired'
        ]);

        // Suspend tenant
        $this->tenant->suspend('Subscription expired');

        return $this;
    }

    public function renew(Package $newPackage = null)
    {
        $package = $newPackage ?? $this->package;

        $this->update([
            'package_id' => $package->id,
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => $this->calculateNextBillingDate($package),
            'cancelled_at' => null,
            'suspended_at' => null
        ]);

        $this->tenant->activate();

        return $this;
    }

    public function changePlan(Package $newPackage)
    {
        $this->update([
            'package_id' => $newPackage->id
        ]);

        return $this;
    }

    // ==================== BILLING METHODS ====================

    public function calculateNextBillingDate(Package $package = null): Carbon
    {
        $package = $package ?? $this->package;
        $startDate = $this->current_period_start ?? now();

        return match($package->billing_period) {
            'monthly' => $startDate->copy()->addMonth(),
            'quarterly' => $startDate->copy()->addMonths(3),
            'yearly' => $startDate->copy()->addYear(),
            default => $startDate->copy()->addMonth()
        };
    }

    public function daysUntilDue(): int
    {
        if (!$this->current_period_end) {
            return 0;
        }

        return (int) now()->diffInDays($this->current_period_end, false);
    }

    public function isOverdue(): bool
    {
        return $this->current_period_end &&
            $this->current_period_end < now() &&
            $this->status !== 'cancelled';
    }

    // ==================== ACCESSORS ====================

    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'trialing' => '<span class="badge badge-warning">Trial</span>',
            'active' => '<span class="badge badge-success">Active</span>',
            'suspended' => '<span class="badge badge-danger">Suspended</span>',
            'cancelled' => '<span class="badge badge-secondary">Cancelled</span>',
            'expired' => '<span class="badge badge-dark">Expired</span>',
            default => '<span class="badge badge-secondary">' . ucfirst($this->status) . '</span>'
        };
    }

    public function getStatusLabelAttribute()
    {
        if ($this->isTrialing()) {
            return 'Trial (' . $this->trialDaysRemaining() . ' days left)';
        }

        if ($this->isActive()) {
            return 'Active until ' . $this->current_period_end->format('d M, Y');
        }

        return ucfirst($this->status);
    }

    public function getPriceAttribute()
    {
        return $this->package->price;
    }

    public function getBillingPeriodAttribute()
    {
        return $this->package->billing_period;
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeTrialing($query)
    {
        return $query->where('status', 'trialing')
            ->where('trial_ends_at', '>', now());
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeExpiring($query, $days = 7)
    {
        return $query->where('status', 'active')
            ->whereBetween('current_period_end', [now(), now()->addDays($days)]);
    }

    public function scopeOverdue($query)
    {
        return $query->where('current_period_end', '<', now())
            ->where('status', '!=', 'cancelled');
    }

    // ==================== STATIC METHODS ====================

    public static function createTrial(Tenant $tenant, Package $package)
    {
        return self::create([
            'tenant_id' => $tenant->id,
            'package_id' => $package->id,
            'status' => 'trialing',
            'trial_ends_at' => now()->addDays($package->trial_days),
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth()
        ]);
    }

    public static function createPaid(Tenant $tenant, Package $package, $stripeSubscriptionId = null, $stripeCustomerId = null)
    {
        return self::create([
            'tenant_id' => $tenant->id,
            'package_id' => $package->id,
            'stripe_subscription_id' => $stripeSubscriptionId,
            'stripe_customer_id' => $stripeCustomerId,
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => (new self)->calculateNextBillingDate($package)
        ]);
    }

    // ==================== OBSERVERS/EVENTS ====================

    protected static function booted()
    {
        // Auto-expire subscriptions
        static::updating(function ($subscription) {
            if ($subscription->current_period_end &&
                $subscription->current_period_end < now() &&
                $subscription->status === 'active') {
                $subscription->markAsExpired();
            }
        });
    }
}
