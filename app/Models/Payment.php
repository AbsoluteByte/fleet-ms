<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
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
        'tenant_id', 'createdBy', 'updatedBy'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
