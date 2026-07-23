<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('invoices:generate-agreements')->daily();
// Disabled: do not auto-cancel car insurance when the provider/policy expires.
// Schedule::command('insurance:auto-cancel-expired')->daily();
Schedule::command('cars:sync-fleet-rent-status')->daily();
Schedule::command('cars:sync-fleet-compliance')->daily();
Schedule::command('drivers:sync-agreement-status')->daily();
