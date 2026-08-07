<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    protected $fillable = [
        'tenant_id',
        'esign_provider',
        'payment_reminder_user_ids',
    ];

    protected $casts = [
        'payment_reminder_user_ids' => 'array',
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

    public static function userReceivesPaymentReminders(int $tenantId, int $userId): bool
    {
        if (! Schema::hasTable('settings')) {
            return true;
        }

        $setting = self::getForTenant($tenantId);
        $allowedIds = $setting->payment_reminder_user_ids;

        if (! is_array($allowedIds) || $allowedIds === []) {
            return true;
        }

        return in_array($userId, array_map('intval', $allowedIds), true);
    }
}
