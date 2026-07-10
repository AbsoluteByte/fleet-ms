<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\Car;
use App\Models\Driver;
use App\Models\Status;
use App\Models\VehicleSwap;
use App\Services\CarFleetComplianceService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AgreementUpgradeService
{
    public function __construct(
        private AgreementInvoiceService $invoiceService,
        private PaymentAllocationService $paymentAllocationService
    ) {}

    public function availableCars(Agreement $agreement): Collection
    {
        $rentedCarIds = Agreement::rentedCarIdsForTenant($agreement->tenant_id, $agreement->id);

        return Car::where('tenant_id', $agreement->tenant_id)
            ->with(['carModel', 'company', 'mots', 'roadTaxes', 'phvs', 'reservations', 'insurances.status', 'insurances.insuranceProvider'])
            ->get()
            ->filter(function (Car $car) use ($agreement, $rentedCarIds) {
                if ($car->id === $agreement->car_id) {
                    return false;
                }

                return $car->isSelectableForAgreement($rentedCarIds);
            })
            ->sortBy('registration')
            ->values();
    }

    /**
     * Cars that currently have an active agreement eligible for a car change / vehicle swap.
     */
    public function carsWithActiveUpgradeableAgreements(int $tenantId): Collection
    {
        return Agreement::query()
            ->where('tenant_id', $tenantId)
            ->withActiveAssignment()
            ->with(['car.company', 'car.carModel', 'driver', 'status'])
            ->get()
            ->filter(fn (Agreement $agreement) => $this->canUpgrade($agreement))
            ->map(function (Agreement $agreement) {
                $car = $agreement->car;

                if ($car) {
                    $car->setRelation('activeAgreement', $agreement);
                }

                return $car;
            })
            ->filter()
            ->unique('id')
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

        return $agreement->end_date?->copy()->startOfDay()->gte($today) ?? false;
    }

    /**
     * @return array{next_anchor: string, remaining_days: int, period_days: int, old_agreed_rent: float, estimated_adjustment?: float, adjustment_type?: string}
     */
    public function upgradePreview(Agreement $agreement, ?float $newRent = null): array
    {
        $changeDate = now()->startOfDay();
        $originalStart = $agreement->start_date->copy()->startOfDay();
        $rentInterval = (string) $agreement->rent_interval;
        $nextAnchor = $this->invoiceService->nextBillingAnchor($originalStart, $changeDate, $rentInterval);
        $previousAnchor = $this->invoiceService->previousBillingAnchor($originalStart, $nextAnchor, $rentInterval);
        $remainingDays = $this->invoiceService->isBillingAnchor($originalStart, $changeDate, $rentInterval)
            ? 0
            : $changeDate->diffInDays($nextAnchor);

        $preview = [
            'next_anchor' => $nextAnchor->toDateString(),
            'remaining_days' => $remainingDays,
            'period_days' => max($previousAnchor->diffInDays($nextAnchor), 1),
            'old_agreed_rent' => (float) $agreement->agreed_rent,
        ];

        if ($newRent !== null) {
            $tempNew = new Agreement([
                'agreed_rent' => round($newRent, 2),
                'rent_interval' => $agreement->rent_interval,
                'discount_type' => $agreement->discount_type,
                'discount_value' => $agreement->discount_value,
                'start_date' => $changeDate,
            ]);

            $adjustment = $this->invoiceService->calculateChangeCarAdjustment($tempNew, $agreement, $changeDate);
            $preview['estimated_adjustment'] = $adjustment;
            $preview['adjustment_type'] = $this->invoiceService->changeCarAdjustmentType($adjustment);
        }

        return $preview;
    }

    public function upgrade(Agreement $old, array $input): Agreement
    {
        if (! $this->canUpgrade($old)) {
            throw ValidationException::withMessages([
                'agreement' => ['This agreement is not eligible for a car change.'],
            ]);
        }

        $car = Car::where('tenant_id', $old->tenant_id)
            ->with(['mots', 'roadTaxes', 'phvs', 'reservations', 'insurances.status'])
            ->find($input['car_id']);

        if (! $car || $car->id === $old->car_id) {
            throw ValidationException::withMessages([
                'car_id' => ['Please select a different vehicle.'],
            ]);
        }

        $rentedCarIds = Agreement::rentedCarIdsForTenant($old->tenant_id, $old->id);

        if (! $car->isSelectableForAgreement($rentedCarIds)) {
            throw ValidationException::withMessages([
                'car_id' => ['The selected vehicle is not available for this car change.'],
            ]);
        }

        $newRent = round((float) $input['agreed_rent'], 2);

        if ($newRent < 0) {
            throw ValidationException::withMessages([
                'agreed_rent' => ['Agreed rent must be zero or greater.'],
            ]);
        }

        $terminatedStatus = Status::where('type', 'agreement')->where('name', 'Terminated')->firstOrFail();
        $activeStatus = Status::where('type', 'agreement')->where('name', 'Active')->firstOrFail();
        $changeDate = now();
        $changeDay = $changeDate->copy()->startOfDay();
        $swapReason = $input['swap_reason'] ?? null;
        $swapReasonLabel = $swapReason
            ? (VehicleSwap::reasonLabels()[$swapReason] ?? $swapReason)
            : null;
        $terminationNote = $swapReasonLabel
            ? 'Vehicle swap: '.$swapReasonLabel
            : 'Closed due to car change.';

        return DB::transaction(function () use ($old, $car, $newRent, $terminatedStatus, $activeStatus, $changeDate, $changeDay, $input, $terminationNote, $swapReason) {
            $originalEndDate = $old->end_date;

            $old->update([
                'status_id' => $terminatedStatus->id,
                'end_date' => $changeDay->toDateString(),
                'termination_notice_date' => $changeDay->toDateString(),
                'termination_available_from_date' => $changeDay->toDateString(),
                'termination_notes' => $terminationNote,
                'termination_recorded_by' => Auth::id(),
                'updatedBy' => Auth::id(),
            ]);

            $this->releaseCar($old->fresh(['car']));

            $newAgreement = Agreement::create([
                'tenant_id' => $old->tenant_id,
                'company_id' => $car->company_id,
                'driver_id' => $old->driver_id,
                'car_id' => $car->id,
                'start_date' => $changeDate,
                'end_date' => $originalEndDate,
                'agreed_rent' => $newRent,
                'rent_interval' => $old->rent_interval,
                'deposit_amount' => $old->deposit_amount,
                'collection_type' => $old->collection_type,
                'auto_schedule_collections' => false,
                'discount_type' => $old->discount_type,
                'discount_value' => $old->discount_value,
                'discount_notes' => $old->discount_notes,
                'using_own_insurance' => (bool) ($old->using_own_insurance ?? false),
                'insurance_provider_id' => $old->insurance_provider_id,
                'own_insurance_provider_name' => $old->own_insurance_provider_name,
                'own_insurance_start_date' => $old->own_insurance_start_date,
                'own_insurance_end_date' => $old->own_insurance_end_date,
                'own_insurance_type' => $old->own_insurance_type,
                'own_insurance_policy_number' => $old->own_insurance_policy_number,
                'own_insurance_proof_document' => $old->own_insurance_proof_document,
                'notes' => $old->notes,
                'swap_reason' => $swapReason,
                'swap_phvl_issue_type' => $input['swap_phvl_issue_type'] ?? null,
                'swap_phvl_issue_notes' => $input['swap_phvl_issue_notes'] ?? null,
                'swap_reason_notes' => $input['swap_reason_notes'] ?? null,
                'status_id' => $activeStatus->id,
                'upgraded_from_agreement_id' => $old->id,
                'createdBy' => Auth::id(),
                'updatedBy' => Auth::id(),
            ]);

            $newAgreement = $newAgreement->fresh(['upgradedFromAgreement']);
            $adjustment = $this->invoiceService->calculateChangeCarAdjustment($newAgreement, $old, $changeDay);

            $this->invoiceService->generateForAgreement($newAgreement);

            if ($adjustment < 0 && $old->driver_id) {
                $this->createChangeCarCredit($old->driver, abs($adjustment), $changeDay, $old, $newAgreement);
            }

            return $newAgreement;
        });
    }

    private function createChangeCarCredit(Driver $driver, float $amount, Carbon $paymentDate, Agreement $old, Agreement $new): void
    {
        if ($amount <= 0) {
            return;
        }

        $this->paymentAllocationService->createPayment($driver, [
            'payment_method' => 'Car Change Credit',
            'payment_date' => $paymentDate->toDateString(),
            'amount' => round($amount, 2),
            'notes' => sprintf(
                'Car change rent credit from agreement #%d to #%d (prorated rent decrease).',
                $old->id,
                $new->id
            ),
        ], true, [], true);
    }

    private function releaseCar(Agreement $agreement): void
    {
        if (! $agreement->termination_notice_date || ! $agreement->car) {
            return;
        }

        $car = $agreement->car;
        $car->update([
            'available_from_date' => $agreement->termination_available_from_date,
            'updatedBy' => Auth::id(),
        ]);

        $stillRented = in_array(
            $car->id,
            Agreement::rentedCarIdsForTenant($agreement->tenant_id),
            true
        );

        if ($stillRented) {
            return;
        }

        $car = $car->fresh();
        $car->load(['mots', 'roadTaxes', 'phvs']);

        if (in_array($car->fleet_status, [
            Car::FLEET_STATUS_AVAILABLE_FOR_RENT,
            Car::FLEET_STATUS_NON_COMPLIANT,
            Car::FLEET_STATUS_PREPARATION_FOR_PHVL,
        ], true)) {
            app(CarFleetComplianceService::class)->syncFleetStatusForCar($car, Auth::id());
        }
    }
}
