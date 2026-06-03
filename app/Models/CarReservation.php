<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class CarReservation extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'car_id',
        'driver_id',
        'reservation_date',
        'pick_up_date',
        'available_from_date',
        'terms_conditions',
        'status',
        'agreed_rent',
        'agreed_advance',
        'amount_paid',
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

    public function clientName(): string
    {
        if ($this->relationLoaded('driver') || $this->driver_id) {
            $name = trim($this->driver?->full_name ?? '');

            if ($name !== '') {
                return $name;
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
}
