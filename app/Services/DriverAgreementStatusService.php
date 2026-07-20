<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\Driver;

class DriverAgreementStatusService
{
    public function syncForDriver(Driver|int|null $driver): bool
    {
        if ($driver === null) {
            return false;
        }

        $driver = $driver instanceof Driver
            ? $driver->fresh()
            : Driver::query()->find($driver);

        if (! $driver) {
            return false;
        }

        $shouldBeActive = $this->driverHasActiveOrSwapAgreement($driver);

        if ((bool) $driver->is_active === $shouldBeActive) {
            return false;
        }

        $driver->update(['is_active' => $shouldBeActive]);

        return true;
    }

    public function syncForAgreement(Agreement $agreement, ?int $previousDriverId = null): void
    {
        $this->syncForDriver($agreement->driver_id);

        if ($previousDriverId !== null && (int) $previousDriverId !== (int) $agreement->driver_id) {
            $this->syncForDriver($previousDriverId);
        }
    }

    public function syncAllDrivers(): int
    {
        $changed = 0;

        Driver::query()
            ->select('id')
            ->orderBy('id')
            ->chunkById(200, function ($drivers) use (&$changed) {
                foreach ($drivers as $driver) {
                    if ($this->syncForDriver($driver->id)) {
                        $changed++;
                    }
                }
            });

        return $changed;
    }

    public function driverHasActiveOrSwapAgreement(Driver|int $driver): bool
    {
        $driverId = $driver instanceof Driver ? $driver->id : $driver;

        return Agreement::query()
            ->where('driver_id', $driverId)
            ->billable()
            ->whereDate('end_date', '>=', now()->startOfDay())
            ->exists();
    }
}
