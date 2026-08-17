<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class AgreementExpiryService
{
    public function markExpired(?Carbon $asOf = null): int
    {
        $asOf = $asOf?->copy()->startOfDay() ?? now()->startOfDay();
        $expiredStatus = Status::query()
            ->where('type', 'agreement')
            ->where('name', 'Expired')
            ->first();

        if (! $expiredStatus) {
            return 0;
        }

        $count = 0;

        Agreement::query()
            ->whereHas('status', fn ($query) => $query->whereIn('name', ['Active', 'Swap']))
            ->whereDate('end_date', '<', $asOf->toDateString())
            ->whereNull('closing_date')
            ->whereDoesntHave('upgradedToAgreement')
            ->when(
                Schema::hasColumn('agreements', 'renewed_from_agreement_id'),
                fn ($query) => $query->whereDoesntHave('renewedToAgreement')
            )
            ->chunkById(100, function ($agreements) use ($expiredStatus, &$count) {
                foreach ($agreements as $agreement) {
                    $agreement->update(['status_id' => $expiredStatus->id]);
                    $count++;

                    $fresh = $agreement->fresh(['status', 'car', 'driver']);
                    if (! $fresh) {
                        continue;
                    }

                    app(DriverAgreementStatusService::class)->syncForAgreement($fresh);
                    app(CarFleetRentStatusService::class)->syncForAgreement($fresh);
                }
            });

        return $count;
    }
}
