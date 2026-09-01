<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\AgreementInvoiceService;
use Illuminate\Console\Command;

class RemoveDuplicateSwapInvoicesCommand extends Command
{
    protected $signature = 'agreements:remove-duplicate-swap-invoices
                            {--agreement= : Limit cleanup to a specific swap agreement ID}
                            {--dry-run : List duplicates without deleting (default)}
                            {--force : Delete duplicate swap invoices}';

    protected $description = 'Remove unsettled swap agreement invoices that duplicate a settled old-agreement invoice for the same billing date';

    public function handle(AgreementInvoiceService $invoiceService): int
    {
        $swapAgreementId = $this->option('agreement') !== null
            ? (int) $this->option('agreement')
            : null;
        $force = (bool) $this->option('force');
        $dryRun = ! $force || (bool) $this->option('dry-run');

        if ($force && $this->option('dry-run')) {
            $this->warn('Both --force and --dry-run were provided; running in dry-run mode only.');
            $force = false;
            $dryRun = true;
        }

        $duplicates = $invoiceService->findDuplicateSwapInvoices($swapAgreementId);

        if ($duplicates === []) {
            $this->info('No duplicate swap invoices found.');

            return self::SUCCESS;
        }

        $this->table(
            ['Swap Agreement', 'Swap Invoice', 'Invoice Date', 'Old Agreement', 'Old Invoice'],
            collect($duplicates)->map(fn (array $row) => [
                $row['swap_agreement_id'],
                $row['swap_invoice_id'],
                $row['invoice_date'],
                $row['old_agreement_id'],
                $row['old_invoice_id'],
            ])->all()
        );

        if ($dryRun) {
            $this->info('Dry run only. Re-run with --force to delete these swap invoices.');

            return self::SUCCESS;
        }

        $deleted = 0;

        foreach ($duplicates as $duplicate) {
            $invoice = Invoice::query()->find($duplicate['swap_invoice_id']);

            if (! $invoice) {
                continue;
            }

            $invoiceService->deleteUnsettledAgreementInvoice($invoice);
            $deleted++;
        }

        $this->info("Deleted {$deleted} duplicate swap invoice(s).");

        return self::SUCCESS;
    }
}
