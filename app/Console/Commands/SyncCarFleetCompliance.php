<?php

namespace App\Console\Commands;

use App\Services\CarFleetComplianceService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncCarFleetCompliance extends Command
{
    protected $signature = 'cars:sync-fleet-compliance {--date= : Evaluate compliance as of this date (Y-m-d)}';

    protected $description = 'Sync car fleet status for PHV preparation, non-compliance, and available-for-rent restoration';

    public function handle(CarFleetComplianceService $service): int
    {
        $asOf = $this->option('date')
            ? Carbon::parse($this->option('date'))->startOfDay()
            : now()->startOfDay();

        $counts = $service->syncAllTenants($asOf);

        $this->info(sprintf(
            'Fleet compliance sync complete (as of %s): %d moved to PHVL preparation, %d marked non-compliant, %d restored to available for rent.',
            $asOf->toDateString(),
            $counts[CarFleetComplianceService::RESULT_MARKED_PHVL_PREPARATION],
            $counts[CarFleetComplianceService::RESULT_MARKED_NON_COMPLIANT],
            $counts[CarFleetComplianceService::RESULT_RESTORED_AVAILABLE]
        ));

        return self::SUCCESS;
    }
}
