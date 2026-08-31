<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\Car;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AgreementUpgradeService
{
    public function __construct(
        private AgreementInvoiceService $invoiceService
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
     * Create a Swap agreement from an Active original: terminate original, carry deposit,
     * inherit billing cycle via upgraded_from_agreement_id. Generates a full-period rent
     * invoice when the swap occurs on a billing anchor day; mid-period swaps stay invoice-free
     * until the next anchor.
     *
     * @param  array<string, mixed>  $input
     */
    public function createSwapFromAgreement(Agreement $old, array $input): Agreement
    {
        if (! $this->canUpgrade($old)) {
            throw ValidationException::withMessages([
                'upgraded_from_agreement_id' => ['This agreement is not eligible for a vehicle swap.'],
            ]);
        }

        $car = Car::where('tenant_id', $old->tenant_id)
            ->with(['mots', 'roadTaxes', 'phvs', 'reservations', 'insurances.status'])
            ->find($input['car_id'] ?? null);

        if (! $car || $car->id === $old->car_id) {
            throw ValidationException::withMessages([
                'car_id' => ['Please select a different vehicle.'],
            ]);
        }

        $rentedCarIds = Agreement::rentedCarIdsForTenant($old->tenant_id, $old->id);

        if (! $car->isSelectableForAgreement($rentedCarIds)) {
            throw ValidationException::withMessages([
                'car_id' => ['The selected vehicle is not available for this vehicle swap.'],
            ]);
        }

        if ((int) ($input['driver_id'] ?? 0) !== (int) $old->driver_id) {
            throw ValidationException::withMessages([
                'driver_id' => ['The driver must match the original agreement.'],
            ]);
        }

        $newRent = round((float) ($input['agreed_rent'] ?? 0), 2);

        if ($newRent < 0) {
            throw ValidationException::withMessages([
                'agreed_rent' => ['Agreed rent must be zero or greater.'],
            ]);
        }

        $terminatedStatus = Status::where('type', 'agreement')->where('name', 'Terminated')->firstOrFail();
        $swapStatus = Status::where('type', 'agreement')->where('name', 'Swap')->firstOrFail();
        $changeDate = isset($input['start_date'])
            ? Carbon::parse($input['start_date'])
            : now();
        $changeDay = $changeDate->copy()->startOfDay();

        return DB::transaction(function () use ($old, $car, $newRent, $terminatedStatus, $swapStatus, $changeDate, $changeDay, $input) {
            $old = Agreement::query()->whereKey($old->id)->lockForUpdate()->firstOrFail();

            if (! $this->canUpgrade($old->load('status'))) {
                throw ValidationException::withMessages([
                    'upgraded_from_agreement_id' => ['This agreement is not eligible for a vehicle swap.'],
                ]);
            }

            $originalEndDate = $old->end_date;
            $transferDiscount = ! $old->discount_is_one_time || $old->hasPendingOneTimeDiscount();

            $old->update([
                'status_id' => $terminatedStatus->id,
                'end_date' => $changeDay->toDateString(),
                'closing_date' => $changeDate,
                'termination_notice_date' => $changeDay->toDateString(),
                'termination_available_from_date' => $changeDay->toDateString(),
                'termination_notes' => 'Closed due to vehicle swap.',
                'termination_recorded_by' => Auth::id(),
                'updatedBy' => Auth::id(),
            ]);

            $this->invoiceService->reconcileFinalInvoice($old->fresh(), $changeDate);

            $this->releaseCar($old->fresh(['car']));

            $attributes = [
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
                'discount_type' => $transferDiscount ? $old->discount_type : null,
                'discount_value' => $transferDiscount ? $old->discount_value : null,
                'discount_notes' => $transferDiscount ? $old->discount_notes : null,
                'discount_is_one_time' => $transferDiscount && (bool) $old->discount_is_one_time,
                'discount_started_at' => $transferDiscount ? $old->discount_started_at : null,
                'discount_consumed_at' => null,
                'discount_consumed_invoice_id' => null,
                'using_own_insurance' => (bool) ($input['using_own_insurance'] ?? $old->using_own_insurance ?? false),
                'insurance_provider_id' => $input['insurance_provider_id'] ?? $old->insurance_provider_id,
                'own_insurance_provider_name' => $input['own_insurance_provider_name'] ?? $old->own_insurance_provider_name,
                'own_insurance_start_date' => $input['own_insurance_start_date'] ?? $old->own_insurance_start_date,
                'own_insurance_end_date' => $input['own_insurance_end_date'] ?? $old->own_insurance_end_date,
                'own_insurance_type' => $input['own_insurance_type'] ?? $old->own_insurance_type,
                'own_insurance_policy_number' => $input['own_insurance_policy_number'] ?? $old->own_insurance_policy_number,
                'own_insurance_proof_document' => $input['own_insurance_proof_document'] ?? $old->own_insurance_proof_document,
                'notes' => $input['notes'] ?? $old->notes,
                'status_id' => $swapStatus->id,
                'upgraded_from_agreement_id' => $old->id,
                'createdBy' => Auth::id(),
                'updatedBy' => Auth::id(),
            ];

            foreach ([
                'paying_company_name' => $old->paying_company_name,
                'mutual_detail_slip_document' => $input['mutual_detail_slip_document'] ?? null,
                'mileage_out' => $input['mileage_out'] ?? null,
                'mileage_in' => $input['mileage_in'] ?? null,
                'condition_report' => $input['condition_report'] ?? null,
            ] as $column => $value) {
                if ($value !== null && Schema::hasColumn('agreements', $column)) {
                    $attributes[$column] = $value;
                }
            }

            $newAgreement = Agreement::create($attributes);
            $newAgreement = $newAgreement->fresh(['upgradedFromAgreement', 'status']);

            app(DriverAgreementStatusService::class)->syncForAgreement($newAgreement);

            app(CarFleetRentStatusService::class)->syncForAgreement($newAgreement, (int) $old->car_id, Auth::id());

            $this->invoiceService->generateForAgreement(
                $newAgreement->fresh(['upgradedFromAgreement', 'status']),
                $changeDate
            );

            return $newAgreement->fresh(['upgradedFromAgreement', 'status']);
        });
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

        app(CarFleetRentStatusService::class)->syncForCar($car->fresh(), Auth::id());
    }

    public function canRenew(Agreement $agreement): bool
    {
        $agreement->loadMissing('status');

        if (
            $agreement->isReplacementVehicle()
            || $agreement->isUpgradedAgreement()
            || $agreement->isRenewedAgreement()
            || $agreement->hasBeenUpgraded()
            || $agreement->hasBeenRenewed()
        ) {
            return false;
        }

        $name = strtolower(trim((string) optional($agreement->status)->name));

        if ($name === 'expired') {
            return ! $agreement->closing_date;
        }

        if (! in_array($name, ['active', 'swap'], true)) {
            return false;
        }

        $today = now()->startOfDay();

        return $agreement->end_date?->copy()->startOfDay()->lte($today) ?? false;
    }

    /**
     * Create an Active successor from an expired/ended hire: same driver and car,
     * user-chosen dates, fresh billing cycle. Old agreement stays Expired in history.
     *
     * @param  array<string, mixed>  $input
     */
    public function createRenewFromAgreement(Agreement $old, array $input): Agreement
    {
        if (! $this->canRenew($old)) {
            throw ValidationException::withMessages([
                'start_date' => ['This agreement is not eligible for renewal.'],
            ]);
        }

        $newStart = isset($input['start_date'])
            ? Carbon::parse($input['start_date'])
            : now();
        $newEnd = isset($input['end_date'])
            ? Carbon::parse($input['end_date'])->startOfDay()
            : null;

        if (! $newEnd) {
            throw ValidationException::withMessages([
                'end_date' => ['Please choose a new expiry date.'],
            ]);
        }

        if ($newEnd->lt($newStart->copy()->startOfDay())) {
            throw ValidationException::withMessages([
                'end_date' => ['The new expiry date must be on or after the new start date.'],
            ]);
        }

        $expiredStatus = Status::where('type', 'agreement')->where('name', 'Expired')->firstOrFail();
        $activeStatus = Status::where('type', 'agreement')->where('name', 'Active')->firstOrFail();

        return DB::transaction(function () use ($old, $input, $newStart, $newEnd, $expiredStatus, $activeStatus) {
            $old = Agreement::query()->whereKey($old->id)->lockForUpdate()->firstOrFail();

            if (! $this->canRenew($old->load('status'))) {
                throw ValidationException::withMessages([
                    'start_date' => ['This agreement is not eligible for renewal.'],
                ]);
            }

            $transferDiscount = ! $old->discount_is_one_time || $old->hasPendingOneTimeDiscount();

            $old->update([
                'status_id' => $expiredStatus->id,
                'closing_date' => $newStart,
                'updatedBy' => Auth::id(),
            ]);

            $this->invoiceService->reconcileFinalInvoice($old->fresh(), $newStart);

            $attributes = [
                'tenant_id' => $old->tenant_id,
                'company_id' => $old->company_id,
                'driver_id' => $old->driver_id,
                'car_id' => $old->car_id,
                'start_date' => $newStart,
                'end_date' => $newEnd->toDateString(),
                'billing_anchor_date' => $newStart->copy()->startOfDay()->toDateString(),
                'agreed_rent' => $old->agreed_rent,
                'rent_interval' => $old->rent_interval,
                'deposit_amount' => $old->deposit_amount,
                'collection_type' => $old->collection_type,
                'auto_schedule_collections' => false,
                'discount_type' => $transferDiscount ? $old->discount_type : null,
                'discount_value' => $transferDiscount ? $old->discount_value : null,
                'discount_notes' => $transferDiscount ? $old->discount_notes : null,
                'discount_is_one_time' => $transferDiscount && (bool) $old->discount_is_one_time,
                'discount_started_at' => $transferDiscount ? $old->discount_started_at : null,
                'discount_consumed_at' => null,
                'discount_consumed_invoice_id' => null,
                'using_own_insurance' => (bool) $old->using_own_insurance,
                'insurance_provider_id' => $old->insurance_provider_id,
                'own_insurance_provider_name' => $old->own_insurance_provider_name,
                'own_insurance_start_date' => $old->own_insurance_start_date,
                'own_insurance_end_date' => $old->own_insurance_end_date,
                'own_insurance_type' => $old->own_insurance_type,
                'own_insurance_policy_number' => $old->own_insurance_policy_number,
                'own_insurance_proof_document' => $old->own_insurance_proof_document,
                'notes' => $old->notes,
                'status_id' => $activeStatus->id,
                'renewed_from_agreement_id' => $old->id,
                'createdBy' => Auth::id(),
                'updatedBy' => Auth::id(),
            ];

            foreach ([
                'paying_company_name' => $old->paying_company_name,
                'payment_bank_account_id' => $old->payment_bank_account_id,
                'mutual_detail_slip_document' => $old->mutual_detail_slip_document,
            ] as $column => $value) {
                if ($value !== null && Schema::hasColumn('agreements', $column)) {
                    $attributes[$column] = $value;
                }
            }

            $newAgreement = Agreement::create($attributes);
            $newAgreement = $newAgreement->fresh(['renewedFromAgreement', 'status', 'driver', 'car']);

            $this->invoiceService->generateForAgreement($newAgreement);
            app(DriverAgreementStatusService::class)->syncForAgreement($newAgreement);
            app(CarFleetRentStatusService::class)->syncForAgreement($newAgreement, (int) $old->car_id, Auth::id());

            return $newAgreement;
        });
    }
}
