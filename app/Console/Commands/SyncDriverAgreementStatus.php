<?php

namespace App\Console\Commands;

use App\Services\DriverAgreementStatusService;
use Illuminate\Console\Command;

class SyncDriverAgreementStatus extends Command
{
    protected $signature = 'drivers:sync-agreement-status';

    protected $description = 'Sync driver active/inactive status based on Active or Swap agreements';

    public function handle(DriverAgreementStatusService $service): int
    {
        $changed = $service->syncAllDrivers();

        $this->info("Updated {$changed} driver(s).");

        return self::SUCCESS;
    }
}
