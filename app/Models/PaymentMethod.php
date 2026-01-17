<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'stripe_payment_method_id',
        'card_brand',
        'card_last_four',
        'exp_month',
        'exp_year',
        'is_default'
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    // ==================== RELATIONSHIPS ====================

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    // ==================== HELPER METHODS ====================

    public function getCardDisplay()
    {
        return ucfirst($this->card_brand) . ' •••• ' . $this->card_last_four;
    }

    public function getExpiryDisplay()
    {
        return sprintf('%02d/%d', $this->exp_month, $this->exp_year);
    }

    public function isExpired(): bool
    {
        $now = now();
        return $this->exp_year < $now->year ||
            ($this->exp_year == $now->year && $this->exp_month < $now->month);
    }

    public function isExpiringSoon(): bool
    {
        $expiryDate = now()->setDate($this->exp_year, $this->exp_month, 1);
        return $expiryDate->diffInDays(now()) <= 30;
    }

    public function makeDefault()
    {
        // Remove default from all other cards
        self::where('tenant_id', $this->tenant_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);

        return $this;
    }

    // ==================== SCOPES ====================

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeExpired($query)
    {
        $now = now();
        return $query->where(function($q) use ($now) {
            $q->where('exp_year', '<', $now->year)
                ->orWhere(function($q2) use ($now) {
                    $q2->where('exp_year', $now->year)
                        ->where('exp_month', '<', $now->month);
                });
        });
    }
}
