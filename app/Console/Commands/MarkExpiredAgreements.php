<?php

namespace App\Console\Commands;

use App\Services\AgreementExpiryService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkExpiredAgreements extends Command
{
    protected $signature = 'agreements:mark-expired {--date= : Treat agreements ending before this date as expired (Y-m-d)}';

    protected $description = 'Mark Active/Swap agreements as Expired when their end date has passed and the vehicle is still on hire';

    public function handle(AgreementExpiryService $expiryService): int
    {
        $asOf = $this->option('date')
            ? Carbon::parse($this->option('date'))->startOfDay()
            : now()->startOfDay();

        $count = $expiryService->markExpired($asOf);

        $this->info("Marked {$count} agreement(s) as Expired.");

        return self::SUCCESS;
    }
}
