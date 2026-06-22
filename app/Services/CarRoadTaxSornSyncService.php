<?php

namespace App\Services;

use App\Models\Car;
use App\Models\CarSornHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class CarRoadTaxSornSyncService
{
    /**
     * When road tax is renewed (latest period starts on or after SORN was applied), clear SORN automatically.
     */
    public function syncAfterRoadTaxesSaved(Car $car): void
    {
        if (! $car->sorn_applied) {
            return;
        }

        $sornAt = $car->sorn_applied_at;
        if (! $sornAt) {
            return;
        }

        $car->load('roadTaxes');
        $latest = $car->roadTaxes
            ->sortByDesc(fn ($rt) => [optional($rt->start_date)->timestamp ?? 0, $rt->id])
            ->first();

        if (! $latest || ! $latest->start_date) {
            return;
        }

        if ((float) $latest->amount <= 0) {
            return;
        }

        if ($latest->start_date->copy()->startOfDay()->lt($sornAt->copy()->startOfDay())) {
            return;
        }

        $this->clearSornState($car);
    }

    private function clearSornState(Car $car): void
    {
        $activeHistory = CarSornHistory::where('car_id', $car->id)
            ->whereNull('sorn_ended_at')
            ->latest('sorn_started_at')
            ->first();

        if ($activeHistory) {
            $activeHistory->update([
                'sorn_ended_at' => now(),
                'sorn_ended_by' => Auth::id(),
            ]);
        }

        if ($car->sorn_document) {
            $path = public_path('uploads/cars/sorn_documents/'.$car->sorn_document);
            if (File::exists($path)) {
                File::delete($path);
            }
        }

        $car->update([
            'sorn_applied' => false,
            'sorn_applied_at' => null,
            'sorn_applied_by' => null,
            'sorn_document' => null,
            'fleet_status' => 'available_for_rent',
            'updatedBy' => Auth::id(),
        ]);
    }
}
