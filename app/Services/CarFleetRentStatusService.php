<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\Car;
use App\Models\CarStatusHistory;
use Illuminate\Support\Facades\DB;

class CarFleetRentStatusService
{
    public const RESULT_MARKED_ON_RENT = 'marked_on_rent';

    public const RESULT_RELEASED_FROM_RENT = 'released_from_rent';

    /**
     * @return list<string>
     */
    public static function fleetStatusesBlockedFromOnRent(): array
    {
        return [
            Car::FLEET_STATUS_WRITTEN_OFF,
            Car::FLEET_STATUS_STOLEN,
            Car::FLEET_STATUS_SOLD,
            'damaged',
            'for_sale',
            'sorn',
        ];
    }

    public function syncForAgreement(Agreement $agreement, ?int $previousCarId = null, ?int $changedBy = null): void
    {
        if ($previousCarId && $previousCarId !== (int) $agreement->car_id) {
            $this->syncForCar($previousCarId, $changedBy);
        }

        if ($agreement->car_id) {
            $this->syncForCar((int) $agreement->car_id, $changedBy);
        }
    }

    public function syncForCar(Car|int $car, ?int $changedBy = null): ?string
    {
        $car = $this->resolveCar($car);

        if (! $car) {
            return null;
        }

        if ($this->carHasRentAssignment($car)) {
            return $this->markOnRent($car, $changedBy);
        }

        if (($car->fleet_status ?? '') === Car::FLEET_STATUS_ON_RENT) {
            return $this->releaseFromRent($car, $changedBy);
        }

        return null;
    }

    /**
     * @return array{marked_on_rent: int, released_from_rent: int}
     */
    public function syncAllTenants(): array
    {
        $counts = [
            self::RESULT_MARKED_ON_RENT => 0,
            self::RESULT_RELEASED_FROM_RENT => 0,
        ];

        $rentAssignedCarIds = Agreement::query()
            ->withRentAssignment()
            ->pluck('car_id')
            ->unique()
            ->filter()
            ->values()
            ->all();

        if ($rentAssignedCarIds !== []) {
            Car::query()
                ->whereIn('id', $rentAssignedCarIds)
                ->with(['mots', 'roadTaxes', 'phvs'])
                ->orderBy('id')
                ->chunkById(100, function ($cars) use (&$counts) {
                    foreach ($cars as $car) {
                        if ($this->markOnRent($car, null) === self::RESULT_MARKED_ON_RENT) {
                            $counts[self::RESULT_MARKED_ON_RENT]++;
                        }
                    }
                });
        }

        Car::query()
            ->where('fleet_status', Car::FLEET_STATUS_ON_RENT)
            ->when($rentAssignedCarIds !== [], fn ($query) => $query->whereNotIn('id', $rentAssignedCarIds))
            ->with(['mots', 'roadTaxes', 'phvs'])
            ->orderBy('id')
            ->chunkById(100, function ($cars) use (&$counts) {
                foreach ($cars as $car) {
                    if ($this->releaseFromRent($car, null) === self::RESULT_RELEASED_FROM_RENT) {
                        $counts[self::RESULT_RELEASED_FROM_RENT]++;
                    }
                }
            });

        return $counts;
    }

    private function carHasRentAssignment(Car $car): bool
    {
        if (! $car->tenant_id) {
            return false;
        }

        return in_array(
            $car->id,
            Agreement::rentAssignedCarIdsForTenant((int) $car->tenant_id),
            true
        );
    }

    private function markOnRent(Car $car, ?int $changedBy): ?string
    {
        $currentStatus = $car->fleet_status ?? Car::FLEET_STATUS_AVAILABLE_FOR_RENT;

        if ($currentStatus === Car::FLEET_STATUS_ON_RENT) {
            return null;
        }

        if (in_array($currentStatus, self::fleetStatusesBlockedFromOnRent(), true)) {
            return null;
        }

        $this->transition($car, Car::FLEET_STATUS_ON_RENT, $changedBy);

        return self::RESULT_MARKED_ON_RENT;
    }

    private function releaseFromRent(Car $car, ?int $changedBy): ?string
    {
        if (($car->fleet_status ?? '') !== Car::FLEET_STATUS_ON_RENT) {
            return null;
        }

        app(CarFleetComplianceService::class)->syncFleetStatusForCar($car, $changedBy);

        return self::RESULT_RELEASED_FROM_RENT;
    }

    private function resolveCar(Car|int $car): ?Car
    {
        if ($car instanceof Car) {
            if (! $car->relationLoaded('mots')) {
                $car->load(['mots', 'roadTaxes', 'phvs']);
            }

            return $car;
        }

        return Car::query()
            ->with(['mots', 'roadTaxes', 'phvs'])
            ->find($car);
    }

    private function transition(Car $car, string $newStatus, ?int $changedBy): void
    {
        $previousStatus = $car->fleet_status ?? Car::FLEET_STATUS_AVAILABLE_FOR_RENT;

        DB::transaction(function () use ($car, $newStatus, $previousStatus, $changedBy) {
            $car->update([
                'fleet_status' => $newStatus,
                'updatedBy' => $changedBy,
            ]);

            CarStatusHistory::create([
                'tenant_id' => $car->tenant_id,
                'car_id' => $car->id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'status_data' => [
                    'source' => 'agreement_rent_sync',
                ],
                'changed_by' => $changedBy,
            ]);
        });
    }
}
