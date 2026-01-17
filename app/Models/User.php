<?php
// app/Models/User.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_pic',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ==================== SPATIE ROLE CHECKS ====================

    public function isSuperUser()
    {
        return $this->hasRole('superuser');
    }

    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    public function isDriver()
    {
        return $this->hasRole('driver');
    }

    public function isUser()
    {
        return $this->hasRole('user');
    }

    // ==================== TENANT RELATIONSHIPS ====================

    public function tenants()
    {
        return $this->belongsToMany(Tenant::class, 'tenant_user')
            ->withPivot('role', 'is_primary', 'joined_at')
            ->withTimestamps();
    }

    // ✅ Get current active tenant (Best Method)
    public function currentTenant()
    {
        // First check session
        $tenantId = session('current_tenant_id');

        if ($tenantId) {
            $tenant = $this->tenants()->where('tenants.id', $tenantId)
                ->where('status', Tenant::STATUS_ACTIVE)
                ->first();

            if ($tenant) {
                return $tenant;
            }
        }

        // Fallback to primary tenant
        return $this->primaryTenant();
    }

    // Get primary (default) tenant
    public function primaryTenant()
    {
        return $this->tenants()
            ->where('status', Tenant::STATUS_ACTIVE)
            ->wherePivot('is_primary', true)
            ->first();
    }

    // Switch to different tenant
    public function switchTenant($tenantId)
    {
        if ($this->hasAccessToTenant($tenantId)) {
            session(['current_tenant_id' => $tenantId]);
            return true;
        }
        return false;
    }

    // Check if user has access to tenant
    public function hasAccessToTenant($tenantId): bool
    {
        return $this->tenants()->where('tenants.id', $tenantId)->exists();
    }

    // Get user's role in specific tenant
    public function getRoleInTenant($tenantId): ?string
    {
        $tenant = $this->tenants()->where('tenants.id', $tenantId)->first();
        return $tenant?->pivot->role;
    }

    // Check if user is admin of specific tenant
    public function isAdminOfTenant($tenantId): bool
    {
        return $this->getRoleInTenant($tenantId) === 'admin';
    }

    // Get all tenants where user is admin
    public function adminTenants()
    {
        return $this->tenants()->wherePivot('role', 'admin');
    }
}
