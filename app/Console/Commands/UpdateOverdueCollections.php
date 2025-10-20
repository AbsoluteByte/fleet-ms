<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AgreementCollection;

class UpdateOverdueCollections extends Command
{
    protected $signature = 'collections:update-overdue';
    protected $description = 'Update overdue collection statuses';

    public function handle()
    {
        $this->info('Updating overdue collections...');

        $updatedCount = AgreementCollection::where('payment_status', 'pending')
            ->where('due_date', '<', now())
            ->update(['payment_status' => 'overdue']);

        $this->info("Updated {$updatedCount} collections to overdue status");
    }
}
