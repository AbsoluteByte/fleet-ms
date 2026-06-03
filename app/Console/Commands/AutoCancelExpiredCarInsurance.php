<?php

namespace App\Console\Commands;

use App\Services\CarInsuranceAutoCancelService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoCancelExpiredCarInsurance extends Command
{
    protected $signature = 'insurance:auto-cancel-expired {--date= : Cancel policies expired before this date (Y-m-d)}';

    protected $description = 'Auto-cancel active car insurance policies past their expiry date';

    public function handle(CarInsuranceAutoCancelService $service): int
    {
        $asOf = $this->option('date')
            ? Carbon::parse($this->option('date'))->startOfDay()
            : now()->startOfDay();

        $cancelledCount = $service->cancelExpiredActivePolicies($asOf);

        $this->info("Auto-cancelled {$cancelledCount} expired active insurance policies (as of {$asOf->toDateString()}).");

        return self::SUCCESS;
    }
}
