<?php

namespace App\Services;

use App\Models\Car;
use App\Models\CarInsurance;
use App\Models\Status;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class CarInsuranceAutoCancelService
{
    public function __construct(
        private readonly InsuranceStatusResolver $statusResolver,
    ) {}

    /**
     * Cancel the latest active policy for one car when expiry has passed.
     */
    public function cancelExpiredActiveForCar(int $carId, ?CarbonInterface $asOf = null): bool
    {
        $asOfDate = ($asOf ?? now())->copy()->startOfDay();
        $activeStatusId = $this->statusResolver->activeStatusId();
        $cancelledStatusId = $this->statusResolver->cancelledStatusId();

        $latestInsurance = CarInsurance::query()
            ->where('car_id', $carId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if (! $latestInsurance) {
            return false;
        }

        if ((int) $latestInsurance->status_id !== $activeStatusId) {
            return false;
        }

        if (! $latestInsurance->expiry_date) {
            return false;
        }

        if ($latestInsurance->expiry_date->copy()->startOfDay()->gte($asOfDate)) {
            return false;
        }

        $latestInsurance->update([
            'status_id' => $cancelledStatusId,
            'canceled_date' => $latestInsurance->expiry_date->format('Y-m-d'),
            'notify_before_expiry' => null,
        ]);

        return true;
    }

    /**
     * Cancel latest active car insurance policies whose expiry date has passed.
     * Sets canceled_date to the policy expiry_date (same as manual cancel lifecycle).
     */
    public function cancelExpiredActivePolicies(?CarbonInterface $asOf = null): int
    {
        $asOfDate = ($asOf ?? now())->copy()->startOfDay();

        $activeStatusId = $this->statusResolver->activeStatusId();
        $cancelledStatusId = $this->statusResolver->cancelledStatusId();

        if (! $activeStatusId || ! $cancelledStatusId) {
            return 0;
        }

        $cancelledCount = 0;

        DB::transaction(function () use ($activeStatusId, $cancelledStatusId, $asOfDate, &$cancelledCount) {
            $carIds = CarInsurance::query()
                ->distinct()
                ->pluck('car_id');

            foreach ($carIds as $carId) {
                if ($this->cancelExpiredActiveForCar((int) $carId, $asOfDate)) {
                    $cancelledCount++;
                }
            }
        });

        return $cancelledCount;
    }
}
