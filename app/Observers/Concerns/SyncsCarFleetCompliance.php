<?php

namespace App\Observers\Concerns;

use App\Models\Car;
use App\Services\CarFleetComplianceService;
use Illuminate\Support\Facades\Auth;

trait SyncsCarFleetCompliance
{
    protected function syncCarFleetCompliance(?int $carId): void
    {
        if (! $carId) {
            return;
        }

        $car = Car::query()->with(['mots', 'roadTaxes', 'phvs'])->find($carId);

        if (! $car) {
            return;
        }

        app(CarFleetComplianceService::class)->syncFleetStatusForCar(
            $car,
            Auth::id()
        );
    }
}
