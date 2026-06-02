<?php

namespace App\Console\Commands;

use App\Services\AgreementInvoiceService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateAgreementInvoices extends Command
{
    protected $signature = 'invoices:generate-agreements {--through= : Generate invoices due up to this date (Y-m-d)}';

    protected $description = 'Generate recurring invoices for agreements';

    public function handle(AgreementInvoiceService $invoiceService): int
    {
        $throughDate = $this->option('through')
            ? Carbon::parse($this->option('through'))->startOfDay()
            : now()->startOfDay();

        $generatedCount = $invoiceService->generateDueInvoices($throughDate);

        $this->info("Generated {$generatedCount} agreement invoices.");

        return self::SUCCESS;
    }
}
