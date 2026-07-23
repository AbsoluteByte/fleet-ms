<?php

namespace App\Console\Commands;

use App\Services\CarFleetRentStatusService;
use Illuminate\Console\Command;

class SyncCarFleetRentStatus extends Command
{
    protected $signature = 'cars:sync-fleet-rent-status';

    protected $description = 'Sync car fleet status to On Rent for vehicles on Active, Swap, or Replacement Vehicle agreements';

    public function handle(CarFleetRentStatusService $service): int
    {
        $counts = $service->syncAllTenants();

        $this->info(sprintf(
            'Fleet rent status sync complete: %d marked on rent, %d released from rent.',
            $counts[CarFleetRentStatusService::RESULT_MARKED_ON_RENT],
            $counts[CarFleetRentStatusService::RESULT_RELEASED_FROM_RENT]
        ));

        return self::SUCCESS;
    }
}
