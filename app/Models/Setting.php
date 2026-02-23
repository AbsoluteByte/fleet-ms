<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'tenant_id',
        'esign_provider',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get or create settings for tenant
     */
    public static function getForTenant($tenantId)
    {
        return self::firstOrCreate(
            ['tenant_id' => $tenantId],
            ['esign_provider' => 'custom'] // Default
        );
    }

    /**
     * Check if using HelloSign
     */
    public function isUsingHelloSign()
    {
        return $this->esign_provider === 'hellosign';
    }

    /**
     * Check if using custom signing
     */
    public function isUsingCustomSigning()
    {
        return $this->esign_provider === 'custom';
    }
}
