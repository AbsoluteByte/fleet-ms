<?php

namespace App\Services;

use App\Models\CarInsurance;
use App\Models\Status;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class CarInsuranceAutoCancelService
{
    /**
     * Cancel latest active car insurance policies whose expiry date has passed.
     * Sets canceled_date to the policy expiry_date (same as manual cancel lifecycle).
     */
    public function cancelExpiredActivePolicies(?CarbonInterface $asOf = null): int
    {
        $asOfDate = ($asOf ?? now())->copy()->startOfDay();

        $activeStatusId = Status::query()
            ->where('type', 'insurance')
            ->where('name', 'Active')
            ->value('id');

        $cancelledStatusId = Status::query()
            ->where('type', 'insurance')
            ->whereIn('name', ['Cancelled', 'Canceled'])
            ->orderByRaw("CASE name WHEN 'Cancelled' THEN 0 ELSE 1 END")
            ->value('id');

        if (! $activeStatusId || ! $cancelledStatusId) {
            return 0;
        }

        $cancelledCount = 0;

        DB::transaction(function () use ($activeStatusId, $cancelledStatusId, $asOfDate, &$cancelledCount) {
            $carIds = CarInsurance::query()
                ->distinct()
                ->pluck('car_id');

            foreach ($carIds as $carId) {
                $latestInsurance = CarInsurance::query()
                    ->where('car_id', $carId)
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->first();

                if (! $latestInsurance) {
                    continue;
                }

                if ((int) $latestInsurance->status_id !== (int) $activeStatusId) {
                    continue;
                }

                if (! $latestInsurance->expiry_date) {
                    continue;
                }

                if ($latestInsurance->expiry_date->copy()->startOfDay()->gte($asOfDate)) {
                    continue;
                }

                $latestInsurance->update([
                    'status_id' => $cancelledStatusId,
                    'canceled_date' => $latestInsurance->expiry_date->format('Y-m-d'),
                    'notify_before_expiry' => null,
                ]);

                $cancelledCount++;
            }
        });

        return $cancelledCount;
    }
}
