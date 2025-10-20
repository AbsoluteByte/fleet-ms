<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Agreement;
use App\Models\AgreementCollection;

class GenerateScheduledCollections extends Command
{
    protected $signature = 'collections:generate';
    protected $description = 'Generate scheduled collections for active agreements';

    public function handle()
    {
        $this->info('Generating scheduled collections...');

        $activeAgreements = Agreement::where('auto_schedule_collections', true)
            ->where('end_date', '>=', now())
            ->get();

        $generatedCount = 0;

        foreach ($activeAgreements as $agreement) {
            // Check if new collections need to be generated
            $lastCollection = $agreement->collections()
                ->where('is_auto_generated', true)
                ->orderBy('due_date', 'desc')
                ->first();

            if (!$lastCollection || $lastCollection->due_date <= now()->addDays(7)) {
                $agreement->generateCollections();
                $generatedCount++;
            }

            // Update overdue collections
            $agreement->updateOverdueCollections();
        }

        $this->info("Generated collections for {$generatedCount} agreements");
        $this->info('Updated overdue collection statuses');
    }
}
