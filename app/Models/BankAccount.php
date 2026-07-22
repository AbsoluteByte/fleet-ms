<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    public const DEFAULT_CARD_ACCOUNT_NUMBER = '56069230';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'bank_name',
        'account_number',
        'createdBy',
        'updatedBy',
    ];

    public static function defaultForCardPayment(int $tenantId): ?self
    {
        return static::query()
            ->where('tenant_id', $tenantId)
            ->where('account_number', self::DEFAULT_CARD_ACCOUNT_NUMBER)
            ->first();
    }

    public static function defaultForCardPaymentId(int $tenantId): ?int
    {
        return static::defaultForCardPayment($tenantId)?->id;
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
