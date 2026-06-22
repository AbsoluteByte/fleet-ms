<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\Car;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AgreementUpgradeService
{
    public function __construct(
        private AgreementInvoiceService $invoiceService
    ) {}

    public function availableCars(Agreement $agreement): Collection
    {
        return Car::where('tenant_id', $agreement->tenant_id)
            ->with(['carModel', 'company', 'mots', 'roadTaxes', 'phvs', 'insurances.status', 'insurances.insuranceProvider', 'agreements', 'reservations'])
            ->get()
            ->filter(function (Car $car) use ($agreement) {
                if ($car->id === $agreement->car_id) {
                    return false;
                }

                return $car->isEligibleForAgreementSelection() && $car->isAvailableForRent();
            })
            ->sortBy('registration')
            ->values();
    }

    public function canUpgrade(Agreement $agreement): bool
    {
        if ($agreement->isReplacementVehicle() || $agreement->isUpgradedAgreement() || $agreement->hasBeenUpgraded()) {
            return false;
        }

        if (strcasecmp((string) optional($agreement->status)->name, 'Active') !== 0) {
            return false;
        }

        $today = now()->startOfDay();

        return $agreement->start_date?->copy()->startOfDay()->lte($today)
            && $agreement->end_date?->copy()->startOfDay()->gte($today);
    }

    /**
     * @return array{next_anchor: string, remaining_days: int, period_days: int, old_agreed_rent: float}
     */
    public function upgradePreview(Agreement $agreement): array
    {
        $upgradeDate = now()->startOfDay();
        $originalStart = $agreement->start_date->copy()->startOfDay();
        $rentInterval = (string) $agreement->rent_interval;
        $nextAnchor = $this->invoiceService->nextBillingAnchor($originalStart, $upgradeDate, $rentInterval);
        $previousAnchor = $this->invoiceService->previousBillingAnchor($originalStart, $nextAnchor, $rentInterval);
        $remainingDays = $this->invoiceService->isBillingAnchor($originalStart, $upgradeDate, $rentInterval)
            ? 0
            : $upgradeDate->diffInDays($nextAnchor);

        return [
            'next_anchor' => $nextAnchor->toDateString(),
            'remaining_days' => $remainingDays,
            'period_days' => max($previousAnchor->diffInDays($nextAnchor), 1),
            'old_agreed_rent' => (float) $agreement->agreed_rent,
        ];
    }

    public function upgrade(Agreement $old, array $input): Agreement
    {
        if (! $this->canUpgrade($old)) {
            throw ValidationException::withMessages([
                'agreement' => ['This agreement is not eligible for a car upgrade.'],
            ]);
        }

        $car = Car::where('tenant_id', $old->tenant_id)
            ->with(['agreements', 'reservations', 'mots', 'roadTaxes', 'phvs', 'insurances.status'])
            ->find($input['car_id']);

        if (! $car || $car->id === $old->car_id) {
            throw ValidationException::withMessages([
                'car_id' => ['Please select a different vehicle.'],
            ]);
        }

        if (! $car->isEligibleForAgreementSelection() || ! $car->isAvailableForRent()) {
            throw ValidationException::withMessages([
                'car_id' => ['The selected vehicle is not available for rent.'],
            ]);
        }

        $newRent = round((float) $input['agreed_rent'], 2);
        $newDeposit = round((float) $input['deposit_amount'], 2);
        $oldRent = round((float) $old->agreed_rent, 2);

        if ($newRent <= $oldRent) {
            throw ValidationException::withMessages([
                'agreed_rent' => ['The new agreed rent must be greater than the current agreed rent.'],
            ]);
        }

        if ($newDeposit < 0) {
            throw ValidationException::withMessages([
                'deposit_amount' => ['Deposit amount must be zero or greater.'],
            ]);
        }

        $terminatedStatus = Status::where('type', 'agreement')->where('name', 'Terminated')->firstOrFail();
        $activeStatus = Status::where('type', 'agreement')->where('name', 'Active')->firstOrFail();
        $upgradeDate = now();
        $upgradeDay = $upgradeDate->copy()->startOfDay();

        return DB::transaction(function () use ($old, $car, $newRent, $newDeposit, $terminatedStatus, $activeStatus, $upgradeDate, $upgradeDay) {
            $originalEndDate = $old->end_date;

            $old->update([
                'status_id' => $terminatedStatus->id,
                'end_date' => $upgradeDay->toDateString(),
                'termination_notice_date' => $upgradeDay->toDateString(),
                'termination_available_from_date' => $upgradeDay->toDateString(),
                'termination_notes' => 'Closed due to car upgrade.',
                'termination_recorded_by' => Auth::id(),
                'updatedBy' => Auth::id(),
            ]);

            $this->releaseCar($old->fresh(['car']));

            $newAgreement = Agreement::create([
                'tenant_id' => $old->tenant_id,
                'company_id' => $old->company_id,
                'driver_id' => $old->driver_id,
                'car_id' => $car->id,
                'start_date' => $upgradeDate,
                'end_date' => $originalEndDate,
                'agreed_rent' => $newRent,
                'rent_interval' => $old->rent_interval,
                'deposit_amount' => $newDeposit,
                'collection_type' => $old->collection_type,
                'auto_schedule_collections' => false,
                'discount_type' => $old->discount_type,
                'discount_value' => $old->discount_value,
                'discount_notes' => $old->discount_notes,
                'using_own_insurance' => $old->using_own_insurance,
                'insurance_provider_id' => $old->insurance_provider_id,
                'own_insurance_provider_name' => $old->own_insurance_provider_name,
                'own_insurance_start_date' => $old->own_insurance_start_date,
                'own_insurance_end_date' => $old->own_insurance_end_date,
                'own_insurance_type' => $old->own_insurance_type,
                'own_insurance_policy_number' => $old->own_insurance_policy_number,
                'own_insurance_proof_document' => $old->own_insurance_proof_document,
                'notes' => $old->notes,
                'status_id' => $activeStatus->id,
                'upgraded_from_agreement_id' => $old->id,
                'createdBy' => Auth::id(),
                'updatedBy' => Auth::id(),
            ]);

            $this->invoiceService->generateForAgreement($newAgreement->fresh(['upgradedFromAgreement']));

            return $newAgreement;
        });
    }

    private function releaseCar(Agreement $agreement): void
    {
        if (! $agreement->termination_notice_date || ! $agreement->car) {
            return;
        }

        $agreement->car->update([
            'fleet_status' => 'available_for_rent',
            'available_from_date' => $agreement->termination_available_from_date,
            'updatedBy' => Auth::id(),
        ]);
    }
}
