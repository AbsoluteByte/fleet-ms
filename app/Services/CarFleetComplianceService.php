<?php

namespace App\Services;

use App\Models\Car;
use App\Models\CarStatusHistory;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class CarFleetComplianceService
{
    public const RESULT_MARKED_NON_COMPLIANT = 'marked_non_compliant';

    public const RESULT_MARKED_PHVL_PREPARATION = 'marked_phvl_preparation';

    public const RESULT_RESTORED_AVAILABLE = 'restored_available';

    /**
     * Sync fleet status for one car based on PHV presence and MOT, road tax, and PHV compliance.
     *
     * @return self::RESULT_*|null
     */
    public function syncFleetStatusForCar(Car $car, ?int $changedBy = null, ?CarbonInterface $asOf = null): ?string
    {
        if (! $car->relationLoaded('mots')) {
            $car->load('mots');
        }

        if (! $car->relationLoaded('roadTaxes')) {
            $car->load('roadTaxes');
        }

        if (! $car->relationLoaded('phvs')) {
            $car->load('phvs');
        }

        $currentStatus = $car->fleet_status ?? Car::FLEET_STATUS_AVAILABLE_FOR_RENT;

        if (! $car->hasPhvRecord()) {
            if (in_array($currentStatus, [
                Car::FLEET_STATUS_AVAILABLE_FOR_RENT,
                Car::FLEET_STATUS_NON_COMPLIANT,
            ], true)) {
                $this->transition($car, Car::FLEET_STATUS_PREPARATION_FOR_PHVL, $changedBy, $asOf, ['phv_missing']);

                return self::RESULT_MARKED_PHVL_PREPARATION;
            }

            return null;
        }

        $isCompliant = $this->isCompliantAsOf($car, $asOf);

        if ($currentStatus === Car::FLEET_STATUS_PREPARATION_FOR_PHVL && $isCompliant) {
            $this->transition($car, Car::FLEET_STATUS_AVAILABLE_FOR_RENT, $changedBy, $asOf);

            return self::RESULT_RESTORED_AVAILABLE;
        }

        if ($currentStatus === Car::FLEET_STATUS_AVAILABLE_FOR_RENT && ! $isCompliant) {
            $this->transition($car, Car::FLEET_STATUS_NON_COMPLIANT, $changedBy, $asOf);

            return self::RESULT_MARKED_NON_COMPLIANT;
        }

        if ($currentStatus === Car::FLEET_STATUS_NON_COMPLIANT && $isCompliant) {
            $this->transition($car, Car::FLEET_STATUS_AVAILABLE_FOR_RENT, $changedBy, $asOf);

            return self::RESULT_RESTORED_AVAILABLE;
        }

        return null;
    }

    /**
     * @return array{marked_non_compliant: int, marked_phvl_preparation: int, restored_available: int}
     */
    public function syncAllTenants(?CarbonInterface $asOf = null): array
    {
        $counts = [
            self::RESULT_MARKED_NON_COMPLIANT => 0,
            self::RESULT_MARKED_PHVL_PREPARATION => 0,
            self::RESULT_RESTORED_AVAILABLE => 0,
        ];

        Car::query()
            ->whereIn('fleet_status', [
                Car::FLEET_STATUS_PREPARATION_FOR_PHVL,
                Car::FLEET_STATUS_AVAILABLE_FOR_RENT,
                Car::FLEET_STATUS_NON_COMPLIANT,
            ])
            ->with(['mots', 'roadTaxes', 'phvs'])
            ->orderBy('id')
            ->chunkById(100, function ($cars) use ($asOf, &$counts) {
                foreach ($cars as $car) {
                    $result = $this->syncFleetStatusForCar($car, null, $asOf);

                    if ($result !== null) {
                        $counts[$result]++;
                    }
                }
            });

        return $counts;
    }

    private function isCompliantAsOf(Car $car, ?CarbonInterface $asOf): bool
    {
        if ($asOf === null) {
            return $car->isRoadLegalCompliant();
        }

        $asOfDate = $asOf->copy()->startOfDay();

        $mot = $car->latestMot();
        $motValid = (bool) ($mot?->expiry_date && $mot->expiry_date->copy()->startOfDay()->gte($asOfDate));

        $roadTax = $car->latestRoadTax();
        $roadTaxExpiry = $roadTax?->expiryDate();
        $roadTaxValid = (bool) ($roadTaxExpiry && $roadTaxExpiry->copy()->startOfDay()->gte($asOfDate));

        $phv = $car->phvs
            ->sortByDesc(fn ($p) => [optional($p->expiry_date)->timestamp ?? 0, $p->id])
            ->first();
        $phvValid = (bool) ($phv?->expiry_date && $phv->expiry_date->copy()->startOfDay()->gte($asOfDate));

        return $motValid && $roadTaxValid && $phvValid;
    }

    /**
     * @param  list<string>  $reasons
     */
    private function transition(Car $car, string $newStatus, ?int $changedBy, ?CarbonInterface $asOf, array $reasons = []): void
    {
        $previousStatus = $car->fleet_status ?? Car::FLEET_STATUS_AVAILABLE_FOR_RENT;

        DB::transaction(function () use ($car, $newStatus, $previousStatus, $changedBy, $asOf, $reasons) {
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
                    'source' => 'compliance_sync',
                    'reasons' => $reasons !== [] ? $reasons : $car->complianceFailureReasons(),
                    'as_of' => ($asOf ?? now())->toDateString(),
                ],
                'changed_by' => $changedBy,
            ]);
        });
    }
}
