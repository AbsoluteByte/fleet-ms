<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgreementAdditionalCharge extends Model
{
    public const TYPE_INSURANCE_EXCESS = 'insurance_excess';

    public const TYPE_MISCELLANEOUS_CHARGES = 'miscellaneous_charges';

    protected $fillable = [
        'tenant_id',
        'agreement_id',
        'type',
        'amount',
        'notes',
        'invoice_id',
        'sort_order',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return array<string, string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_INSURANCE_EXCESS => 'Insurance Excess',
            self::TYPE_MISCELLANEOUS_CHARGES => 'Miscellaneous Charges',
        ];
    }

    public function typeLabel(): string
    {
        return self::types()[$this->type] ?? self::types()[self::TYPE_MISCELLANEOUS_CHARGES];
    }
}
