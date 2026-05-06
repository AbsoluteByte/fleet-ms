<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleSwap extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const REASON_UPGRADE = 'upgrade';

    public const REASON_DOWNGRADE = 'downgrade';

    public const REASON_PHVL_ISSUES = 'phvl_issues';

    public const REASON_BODY_WORK = 'body_work';

    public const REASON_CAR_RETIRING = 'car_retiring_retired';

    public const REASON_MECHANICAL = 'mechanical_issues';

    public const REASON_OTHERS = 'others';

    public const PHVL_FAILED = 'failed';

    public const PHVL_DOCUMENTATION = 'documentation';

    /** Human labels for swap reasons (stored keys are lowercase snake values above). */
    public static function reasonLabels(): array
    {
        return [
            self::REASON_UPGRADE => 'UP-GRADE',
            self::REASON_DOWNGRADE => 'DOWN-GRADE',
            self::REASON_PHVL_ISSUES => 'PHVL ISSUES',
            self::REASON_BODY_WORK => 'BODY WORK',
            self::REASON_CAR_RETIRING => 'CAR RETIRING/RETIRED',
            self::REASON_MECHANICAL => 'MECHANICAL ISSUES',
            self::REASON_OTHERS => 'OTHERS',
        ];
    }

    public static function phvlIssueTypeLabels(): array
    {
        return [
            self::PHVL_FAILED => 'FAILED',
            self::PHVL_DOCUMENTATION => 'DOCUMENTATION',
        ];
    }

    protected $fillable = [
        'tenant_id',
        'old_car_id',
        'swapped_with_car_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'reservation_date',
        'pick_up_date',
        'agreed_rent',
        'agreed_advance',
        'amount_paid',
        'balance_payable_on_pickup',
        'reason_for_swap',
        'phvl_issue_type',
        'phvl_issue_notes',
        'reason_notes',
        'status',
        'created_by',
    ];

    protected $casts = [
        'reservation_date' => 'date',
        'pick_up_date' => 'date',
        'agreed_rent' => 'decimal:2',
        'agreed_advance' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance_payable_on_pickup' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::deleted(function (VehicleSwap $swap) {
            self::releaseCarFleetAfterSwapRemoved(Car::query()->find($swap->old_car_id));
            self::releaseCarFleetAfterSwapRemoved(Car::query()->find($swap->swapped_with_car_id));
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function oldCar()
    {
        return $this->belongsTo(Car::class, 'old_car_id');
    }

    public function swappedWithCar()
    {
        return $this->belongsTo(Car::class, 'swapped_with_car_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeForCurrentTenant($query)
    {
        $tenantId = auth()->user()?->currentTenant()?->id ?? 0;

        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Whether this car appears as old or new side on any active swap.
     */
    public static function carHasActiveSwap(int $carId, ?int $exceptSwapId = null): bool
    {
        return static::query()
            ->active()
            ->when($exceptSwapId, fn ($q) => $q->where('id', '!=', $exceptSwapId))
            ->where(function ($q) use ($carId) {
                $q->where('old_car_id', $carId)->orWhere('swapped_with_car_id', $carId);
            })
            ->exists();
    }

    /**
     * After removing a swap row, clear fleet_status vehicle_swap when no active swap references the car.
     */
    public static function releaseCarFleetAfterSwapRemoved(?Car $car): void
    {
        if (! $car || $car->fleet_status !== 'vehicle_swap') {
            return;
        }

        if (static::carHasActiveSwap($car->id)) {
            return;
        }

        $car->update([
            'fleet_status' => 'available_for_rent',
            'available_from_date' => null,
        ]);
    }

    /**
     * After a swap is recorded: the driver's previous vehicle (old car) returns to the available pool;
     * the replacement vehicle (swapped_with) stays in vehicle_swap until the swap is removed.
     */
    public static function applyVehicleSwapFleetStatus(?Car $oldCar, ?Car $newCar, ?string $pickUpDate): void
    {
        if ($oldCar) {
            $oldCar->update([
                'fleet_status' => 'available_for_rent',
                'available_from_date' => null,
            ]);
        }

        if ($newCar) {
            $newCar->update([
                'fleet_status' => 'vehicle_swap',
                'available_from_date' => $pickUpDate,
            ]);
        }
    }

    public function reasonLabel(): string
    {
        return self::reasonLabels()[$this->reason_for_swap] ?? ucfirst(str_replace('_', ' ', $this->reason_for_swap));
    }
}
