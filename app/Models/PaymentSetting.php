<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'createdBy',
        'updatedBy',
        'payment_type',
        'bank_name',
        'account_number',
        'sort_code',
        'iban_number',
        'company_id',
        'stripe_public_key',
        'stripe_secret_key',
        'paypal_client_id',
        'paypal_secret',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
