<?php

namespace App\Services;

use App\Models\Car;
use Illuminate\Support\Collection;

class DamagedCarActiveInsuranceAlertService
{
    /**
     * Store dismissed car ids per-day so the popup returns next day automatically.
     */
    public const SESSION_DISMISSED_KEY_PREFIX = 'dismissed_damaged_active_insurance_car_ids_';

    private function sessionKeyForToday(): string
    {
        return self::SESSION_DISMISSED_KEY_PREFIX.now()->toDateString();
    }

    /**
     * Damaged fleet vehicles that still have active company insurance.
     *
     * @return Collection<int, array{id: int, registration: string, provider: ?string, expiry: ?string, edit_url: string}>
     */
    public function alertableCars(?int $tenantId): Collection
    {
        if (! $tenantId) {
            return collect();
        }

        $dismissedIds = collect(session($this->sessionKeyForToday(), []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->all();

        return Car::query()
            ->where('tenant_id', $tenantId)
            ->where('fleet_status', 'damaged')
            ->with(['insurances.status', 'insurances.insuranceProvider'])
            ->orderBy('registration')
            ->get()
            ->filter(fn (Car $car) => $car->currentActiveInsurance() !== null)
            ->reject(fn (Car $car) => in_array($car->id, $dismissedIds, true))
            ->map(function (Car $car) {
                $insurance = $car->currentActiveInsurance();

                return [
                    'id' => $car->id,
                    'registration' => $car->registration,
                    'provider' => $insurance?->insuranceProvider?->provider_name,
                    'expiry' => $insurance?->expiry_date?->format('d/m/Y'),
                    'edit_url' => route('cars.edit', $car),
                ];
            })
            ->values();
    }

    /**
     * @param  list<int>  $carIds
     */
    public function dismissForSession(array $carIds): void
    {
        $existing = collect(session($this->sessionKeyForToday(), []))
            ->map(fn ($id) => (int) $id)
            ->filter();

        $merged = $existing
            ->merge(collect($carIds)->map(fn ($id) => (int) $id)->filter())
            ->unique()
            ->values()
            ->all();

        session([$this->sessionKeyForToday() => $merged]);
    }
}
