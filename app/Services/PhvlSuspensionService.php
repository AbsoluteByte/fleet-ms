<?php

namespace App\Services;

use App\Models\Car;
use App\Models\CarPhvlSuspensionHistory;
use App\Models\CarStatusHistory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PhvlSuspensionService
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_SUSPENSION_UPLIFTED = 'suspension_uplifted';

    public const STATUS_LICENCE_REVOKED = 'licence_revoked';

    public const SUSPENSION_LIMIT_DAYS = 60;

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active (not suspended)',
            self::STATUS_SUSPENDED => 'Suspended',
            self::STATUS_SUSPENSION_UPLIFTED => 'Suspension uplifted',
            self::STATUS_LICENCE_REVOKED => 'Licence revoked',
        ];
    }

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_SUSPENDED,
            self::STATUS_SUSPENSION_UPLIFTED,
            self::STATUS_LICENCE_REVOKED,
        ];
    }

    public function effectiveStatus(Car $car): string
    {
        $status = $car->phvl_suspension_status;

        return $status && in_array($status, self::statuses(), true)
            ? $status
            : self::STATUS_ACTIVE;
    }

    public function applyStatus(
        Car $car,
        string $toStatus,
        ?Carbon $eventDate = null,
        ?string $notes = null,
        ?int $carStatusHistoryId = null,
        ?int $changedBy = null
    ): void {
        if (! in_array($toStatus, self::statuses(), true)) {
            throw ValidationException::withMessages([
                'phvl_suspension_status' => ['Invalid PHVL suspension status.'],
            ]);
        }

        $fromStatus = $this->effectiveStatus($car);

        if ($toStatus === self::STATUS_ACTIVE) {
            $eventDate = null;
        } elseif (! $eventDate) {
            throw ValidationException::withMessages([
                'phvl_suspension_status_date' => ['A status date is required for this PHVL suspension status.'],
            ]);
        }

        $update = [
            'phvl_suspension_status' => $toStatus === self::STATUS_ACTIVE ? null : $toStatus,
            'phvl_suspension_status_date' => $toStatus === self::STATUS_ACTIVE ? null : $eventDate?->toDateString(),
        ];

        if ($toStatus === self::STATUS_LICENCE_REVOKED) {
            $update['phv_status'] = 'need_to_apply';
        }

        $car->update($update);

        if ($fromStatus === $toStatus && $toStatus === self::STATUS_ACTIVE) {
            return;
        }

        CarPhvlSuspensionHistory::create([
            'tenant_id' => $car->tenant_id,
            'car_id' => $car->id,
            'from_status' => $fromStatus === self::STATUS_ACTIVE ? null : $fromStatus,
            'to_status' => $toStatus,
            'event_date' => $eventDate?->toDateString(),
            'car_status_history_id' => $carStatusHistoryId,
            'changed_by' => $changedBy ?? Auth::id(),
            'notes' => $notes,
        ]);
    }

    public function daysSuspended(Car $car): ?int
    {
        if ($this->effectiveStatus($car) !== self::STATUS_SUSPENDED || ! $car->phvl_suspension_status_date) {
            return null;
        }

        return (int) $car->phvl_suspension_status_date->copy()->startOfDay()
            ->diffInDays(now()->startOfDay());
    }

    public function daysUntilSuspensionLimit(Car $car): ?int
    {
        $days = $this->daysSuspended($car);

        if ($days === null) {
            return null;
        }

        return self::SUSPENSION_LIMIT_DAYS - $days;
    }

    public function suspensionWarningLevel(Car $car): ?string
    {
        $days = $this->daysSuspended($car);

        if ($days === null) {
            return null;
        }

        if ($days >= self::SUSPENSION_LIMIT_DAYS) {
            return 'danger';
        }

        if ($days >= 45) {
            return 'warning';
        }

        return 'success';
    }

    public function suspensionWarningLabel(Car $car): ?string
    {
        $days = $this->daysSuspended($car);

        if ($days === null) {
            return null;
        }

        $remaining = max(0, self::SUSPENSION_LIMIT_DAYS - $days);

        if ($days >= self::SUSPENSION_LIMIT_DAYS) {
            return $days.' days suspended — 60-day limit reached';
        }

        return $days.' days suspended — '.$remaining.' days until 60-day limit';
    }

    /**
     * @param  Builder<Car>  $query
     */
    public function scopeNonFaultDamagedCars(Builder $query): Builder
    {
        return $query
            ->where('fleet_status', 'damaged')
            ->whereIn('id', function ($sub) {
                $sub->select('car_id')
                    ->from('car_status_histories')
                    ->where('new_status', 'damaged')
                    ->where('status_data->fault_type', 'non_fault')
                    ->whereIn('id', function ($inner) {
                        $inner->selectRaw('MAX(id)')
                            ->from('car_status_histories as latest_damaged')
                            ->whereColumn('latest_damaged.car_id', 'car_status_histories.car_id')
                            ->where('latest_damaged.new_status', 'damaged')
                            ->groupBy('latest_damaged.car_id');
                    });
            });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function latestDamagedIncident(Car $car): ?array
    {
        $history = $car->statusHistories()
            ->where('new_status', 'damaged')
            ->first();

        return $history?->status_data;
    }
}
