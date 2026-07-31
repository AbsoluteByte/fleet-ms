<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class CarReservation extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const POSTING_STATUS_PENDING = 'pending';

    public const POSTING_STATUS_POSTED = 'posted';

    public const POSTING_STATUS_CANCELLED = 'cancelled';

    public const POSTING_STATUS_CONVERTED = 'converted';

    protected $fillable = [
        'tenant_id',
        'car_id',
        'driver_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'reservation_date',
        'pick_up_date',
        'available_from_date',
        'terms_conditions',
        'status',
        'agreed_rent',
        'agreed_advance',
        'amount_paid',
        'payment_method',
        'bank_account_id',
        'posting_status',
        'converted_agreement_id',
        'balance_payable_on_pickup',
        'created_by',
    ];

    protected $casts = [
        'reservation_date' => 'date',
        'pick_up_date' => 'date',
        'available_from_date' => 'date',
        'agreed_rent' => 'decimal:2',
        'agreed_advance' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance_payable_on_pickup' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::restoring(function (CarReservation $reservation) {
            $reservation->deleted_by = null;
        });

        static::softDeleted(function (CarReservation $reservation) {
            self::maybeReleaseReservedCar($reservation->car_id);
        });

        static::forceDeleted(function (CarReservation $reservation) {
            self::maybeReleaseReservedCar($reservation->car_id);
        });
    }

    /**
     * Soft delete including who performed the delete (for audit).
     */
    protected function runSoftDelete(): void
    {
        $query = $this->setKeysForSaveQuery($this->newModelQuery());

        $time = $this->freshTimestamp();

        $columns = [$this->getDeletedAtColumn() => $this->fromDateTime($time)];

        $this->{$this->getDeletedAtColumn()} = $time;

        if ($this->usesTimestamps() && $this->getUpdatedAtColumn() !== null) {
            $this->{$this->getUpdatedAtColumn()} = $time;

            $columns[$this->getUpdatedAtColumn()] = $this->fromDateTime($time);
        }

        $columns['deleted_by'] = Auth::check() ? Auth::id() : null;

        $query->update($columns);

        $this->syncOriginalAttributes(array_keys($columns));

        $this->fireModelEvent('trashed', false);
    }

    /**
     * If no other active (non-trashed) reservation uses this car, clear reserved fleet status.
     */
    public static function releaseCarFleetStatusIfUnused(?Car $car): void
    {
        if (! $car || $car->fleet_status !== 'reserved') {
            return;
        }

        $stillReserved = static::query()
            ->where('car_id', $car->id)
            ->where('status', 'active')
            ->exists();

        if ($stillReserved) {
            return;
        }

        $car->update([
            'fleet_status' => 'available_for_rent',
            'available_from_date' => null,
        ]);
    }

    private static function maybeReleaseReservedCar(?int $carId): void
    {
        if (! $carId) {
            return;
        }

        static::releaseCarFleetStatusIfUnused(Car::query()->find($carId));
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function clientName(): string
    {
        if ($this->relationLoaded('driver') || $this->driver_id) {
            $label = trim($this->driver?->selectOptionLabel() ?? '');

            if ($label !== '') {
                return $label;
            }
        }

        return (string) ($this->attributes['customer_name'] ?? '');
    }

    public function clientPhone(): ?string
    {
        if ($this->relationLoaded('driver') || $this->driver_id) {
            $phone = trim($this->driver?->phone_number ?? '');

            if ($phone !== '') {
                return $phone;
            }
        }

        $legacy = $this->attributes['customer_phone'] ?? null;

        return $legacy !== null && $legacy !== '' ? (string) $legacy : null;
    }

    public function clientEmail(): ?string
    {
        if ($this->relationLoaded('driver') || $this->driver_id) {
            $email = trim($this->driver?->email ?? '');

            if ($email !== '') {
                return $email;
            }
        }

        $legacy = $this->attributes['customer_email'] ?? null;

        return $legacy !== null && $legacy !== '' ? (string) $legacy : null;
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Effective pick-up date for display (new column or legacy field).
     */
    public function effectivePickUpDate(): ?\Carbon\CarbonInterface
    {
        return $this->pick_up_date ?? $this->available_from_date;
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForCurrentTenant($query)
    {
        $tenant = auth()->user()?->currentTenant();

        return $query->where('tenant_id', $tenant->id ?? 0);
    }

    public function convertedAgreement()
    {
        return $this->belongsTo(Agreement::class, 'converted_agreement_id');
    }

    public function hasFinancialSheetPayment(): bool
    {
        return (float) $this->amount_paid > 0;
    }

    public function isPendingFinancialSheet(): bool
    {
        return $this->posting_status === self::POSTING_STATUS_PENDING;
    }

    public function isPostedFinancialSheet(): bool
    {
        return $this->posting_status === self::POSTING_STATUS_POSTED;
    }

    public function scopePendingFinancialSheet(Builder $query): Builder
    {
        return $query
            ->where('amount_paid', '>', 0)
            ->where('posting_status', self::POSTING_STATUS_PENDING);
    }

    public function scopePostedFinancialSheet(Builder $query): Builder
    {
        return $query
            ->where('amount_paid', '>', 0)
            ->where('posting_status', self::POSTING_STATUS_POSTED);
    }

    public function scopeVisibleOnFinancialSheet(Builder $query): Builder
    {
        return $query
            ->where('amount_paid', '>', 0)
            ->whereIn('posting_status', [
                self::POSTING_STATUS_PENDING,
                self::POSTING_STATUS_POSTED,
            ]);
    }

    public function syncFinancialSheetStatus(?float $previousAmountPaid = null): void
    {
        $amountPaid = round((float) $this->amount_paid, 2);
        $previousAmountPaid = $previousAmountPaid === null
            ? null
            : round($previousAmountPaid, 2);

        if ($amountPaid <= 0) {
            if ($this->isPendingFinancialSheet()) {
                $this->forceFill(['posting_status' => self::POSTING_STATUS_CANCELLED])->save();
            }

            return;
        }

        if ($this->isPostedFinancialSheet()) {
            return;
        }

        if ($this->posting_status === null
            || $this->posting_status === self::POSTING_STATUS_CANCELLED
            || $this->posting_status === self::POSTING_STATUS_CONVERTED
            || $this->isPendingFinancialSheet()
            || ($previousAmountPaid !== null && $previousAmountPaid <= 0)) {
            $this->forceFill(['posting_status' => self::POSTING_STATUS_PENDING])->save();
        }
    }

    public function cancelPendingFinancialSheet(): void
    {
        if ($this->isPendingFinancialSheet()) {
            $this->forceFill(['posting_status' => self::POSTING_STATUS_CANCELLED])->save();
        }
    }

    public function markConvertedToAgreement(int $agreementId, bool $wasPosted): void
    {
        $this->forceFill([
            'converted_agreement_id' => $agreementId,
            'posting_status' => $wasPosted
                ? self::POSTING_STATUS_POSTED
                : self::POSTING_STATUS_CANCELLED,
        ])->save();
    }
}
