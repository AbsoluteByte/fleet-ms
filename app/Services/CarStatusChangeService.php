<?php

namespace App\Services;

use App\Models\Car;
use App\Models\CarReservation;
use App\Models\CarStatusHistory;
use App\Models\Tenant;
use App\Models\VehicleSwap;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CarStatusChangeService
{
    /** @var list<string> */
    public const TARGET_STATUSES = [
        'available_for_rent',
        'reserved',
        'vehicle_swap',
        'damaged',
        'written_off',
        'stolen',
        'for_sale',
        'sold',
    ];

    private const BLOCKED_FLEET_STATUSES = ['damaged', 'written_off', 'stolen', 'for_sale', 'sold'];

    public function apply(Request $request, Tenant $tenant, Car $car, string $target): void
    {
        if (! in_array($target, self::TARGET_STATUSES, true)) {
            abort(422, 'Unsupported status.');
        }

        $previousStatus = $car->fleet_status;

        DB::transaction(function () use ($request, $tenant, $car, $target, $previousStatus): void {

            $reservationId = null;
            $vehicleSwapId = null;
            $statusData = [];

            switch ($target) {
                case 'available_for_rent':
                    $statusData = $this->applyAvailableForRentWithCleanup($car, $previousStatus);
                    break;

                case 'reserved':
                    [$reservationId, $statusData] = $this->applyReserved($request, $tenant, $car);
                    break;

                case 'vehicle_swap':
                    [$vehicleSwapId, $statusData] = $this->applyVehicleSwap($request, $tenant, $car);
                    break;

                case 'damaged':
                    $statusData = $this->applyDamaged($request, $tenant, $car);
                    break;

                case 'written_off':
                    $statusData = $this->applyWrittenOff($request, $tenant, $car);
                    break;

                case 'stolen':
                    $statusData = $this->applyStolen($request, $tenant, $car);
                    break;

                case 'for_sale':
                    $statusData = $this->applyForSale($request, $car);
                    break;

                case 'sold':
                    $statusData = $this->applySoldCarUpdate($request, $car);
                    break;

                default:
                    abort(422, 'Unsupported status.');
            }

            $history = CarStatusHistory::create([
                'tenant_id' => $car->tenant_id,
                'car_id' => $car->id,
                'previous_status' => $previousStatus,
                'new_status' => $target,
                'reservation_id' => $reservationId,
                'vehicle_swap_id' => $vehicleSwapId,
                'status_data' => $statusData,
                'changed_by' => Auth::id(),
            ]);

            if ($target === 'sold') {
                $documents = $this->storeSoldDocuments($request, $history->id);
                $history->update([
                    'status_data' => array_merge($statusData, ['documents' => $documents]),
                ]);
            }
        });
    }

    /**
     * Cancels active reservations and removes active swaps involving this car, then marks it available.
     *
     * @return array<string, mixed>
     */
    private function applyAvailableForRentWithCleanup(Car $car, ?string $previousStatus): array
    {
        $hadActiveReservation = CarReservation::query()
            ->where('car_id', $car->id)
            ->where('status', 'active')
            ->exists();

        $hadActiveSwap = VehicleSwap::carHasActiveSwap($car->id);

        $this->cancelActiveReservationsAndSwapsForCar($car);

        $this->applyAvailableForRent($car);

        return [
            'previous_snapshot' => ['fleet_status' => $previousStatus],
            'cancelled_active_reservation' => $hadActiveReservation,
            'removed_active_vehicle_swap' => $hadActiveSwap,
        ];
    }

    private function applyAvailableForRent(Car $car): void
    {
        $car->update([
            'fleet_status' => 'available_for_rent',
            'available_from_date' => null,
        ]);
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    private function applyReserved(Request $request, Tenant $tenant, Car $car): array
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'customer_email' => 'nullable|email|max:255',
            'reservation_date' => 'required|date',
            'pick_up_date' => 'required|date',
            'agreed_rent' => 'required|numeric|min:0',
            'agreed_advance' => 'required|numeric|min:0',
            'amount_paid' => 'required|numeric|min:0',
        ]);

        $this->assertCarAssignableForReservation($car, null);

        $balance = $this->computeBalance(
            (float) $validated['agreed_rent'],
            (float) $validated['agreed_advance'],
            (float) $validated['amount_paid']
        );

        $reservation = CarReservation::create([
            'tenant_id' => $tenant->id,
            'car_id' => $car->id,
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'],
            'customer_email' => $validated['customer_email'],
            'reservation_date' => $validated['reservation_date'],
            'pick_up_date' => $validated['pick_up_date'],
            'available_from_date' => $validated['pick_up_date'],
            'agreed_rent' => $validated['agreed_rent'],
            'agreed_advance' => $validated['agreed_advance'],
            'amount_paid' => $validated['amount_paid'],
            'balance_payable_on_pickup' => $balance,
            'status' => 'active',
            'created_by' => Auth::id(),
        ]);

        $car->update([
            'fleet_status' => 'reserved',
            'available_from_date' => $validated['pick_up_date'],
        ]);

        $snapshot = array_merge($validated, [
            'balance_payable_on_pickup' => $balance,
            'reservation_id' => $reservation->id,
        ]);

        return [$reservation->id, $snapshot];
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    private function applyVehicleSwap(Request $request, Tenant $tenant, Car $car): array
    {
        $request->validate([
            'swapped_with_car_id' => ['required', Rule::in([(string) $car->id])],
        ]);

        $validated = $request->validate([
            'old_car_id' => [
                'required',
                Rule::exists('cars', 'id')->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
                Rule::notIn([$car->id]),
            ],
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'customer_email' => 'nullable|email|max:255',
            'reservation_date' => 'required|date',
            'pick_up_date' => 'required|date',
            'agreed_rent' => 'required|numeric|min:0',
            'agreed_advance' => 'required|numeric|min:0',
            'amount_paid' => 'required|numeric|min:0',
            'reason_for_swap' => ['required', Rule::in(array_keys(VehicleSwap::reasonLabels()))],
            'phvl_issue_type' => [
                Rule::requiredIf(fn () => $request->input('reason_for_swap') === VehicleSwap::REASON_PHVL_ISSUES),
                'nullable',
                Rule::in(array_keys(VehicleSwap::phvlIssueTypeLabels())),
            ],
            'phvl_issue_notes' => [
                Rule::requiredIf(fn () => in_array($request->input('phvl_issue_type'), [
                    VehicleSwap::PHVL_FAILED,
                    VehicleSwap::PHVL_DOCUMENTATION,
                ], true)),
                'nullable',
                'string',
            ],
            'reason_notes' => [
                Rule::requiredIf(fn () => $request->input('reason_for_swap') === VehicleSwap::REASON_OTHERS),
                'nullable',
                'string',
            ],
        ]);

        $validated = $this->sanitizeSwapReasonPayload($validated);

        $oldCar = Car::query()->where('tenant_id', $tenant->id)->whereKey($validated['old_car_id'])->firstOrFail();

        $this->assertCarUsableInSwap($oldCar, null, 'old_car_id');
        $this->assertCarUsableInSwap($car, null, 'car_id');

        $balance = $this->computeBalance(
            (float) $validated['agreed_rent'],
            (float) $validated['agreed_advance'],
            (float) $validated['amount_paid']
        );

        $swap = VehicleSwap::create([
            'tenant_id' => $tenant->id,
            'old_car_id' => $validated['old_car_id'],
            'swapped_with_car_id' => $car->id,
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'],
            'customer_email' => $validated['customer_email'],
            'reservation_date' => $validated['reservation_date'],
            'pick_up_date' => $validated['pick_up_date'],
            'agreed_rent' => $validated['agreed_rent'],
            'agreed_advance' => $validated['agreed_advance'],
            'amount_paid' => $validated['amount_paid'],
            'balance_payable_on_pickup' => $balance,
            'reason_for_swap' => $validated['reason_for_swap'],
            'phvl_issue_type' => $validated['phvl_issue_type'],
            'phvl_issue_notes' => $validated['phvl_issue_notes'],
            'reason_notes' => $validated['reason_notes'],
            'status' => VehicleSwap::STATUS_ACTIVE,
            'created_by' => Auth::id(),
        ]);

        VehicleSwap::applyVehicleSwapFleetStatus(
            Car::query()->find($swap->old_car_id),
            Car::query()->find($swap->swapped_with_car_id),
            $validated['pick_up_date']
        );

        $snapshot = array_merge($validated, [
            'balance_payable_on_pickup' => $balance,
            'swapped_with_car_id' => $car->id,
            'vehicle_swap_id' => $swap->id,
        ]);

        return [$swap->id, $snapshot];
    }

    /**
     * @return array<string, mixed>
     */
    private function applyDamaged(Request $request, Tenant $tenant, Car $car): array
    {
        $tenantId = $tenant->id;

        $validated = $request->validate([
            'payload.damage_date' => 'required|date',
            'payload.driver_id' => [
                'nullable',
                Rule::exists('drivers', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'payload.insurance_status' => ['required', Rule::in(['company', 'driver'])],
            'payload.insurance_excess_amount' => 'required|numeric|min:0',
            'payload.fault_type' => ['required', Rule::in(['fault', 'non_fault'])],
            'payload.incident_date' => 'required|date',
            'payload.excess_status' => [
                Rule::requiredIf(fn () => $request->input('payload.fault_type') === 'fault'),
                'nullable',
                'string',
                'max:255',
            ],
            'payload.fault_notes' => [
                Rule::requiredIf(fn () => $request->input('payload.fault_type') === 'fault'),
                'nullable',
                'string',
            ],
            'payload.insurance_claim_reference' => [
                Rule::requiredIf(fn () => $request->input('payload.fault_type') === 'non_fault'),
                'nullable',
                'string',
                'max:255',
            ],
            'payload.mechanical' => 'nullable|boolean',
            'payload.mechanical_notes' => [
                Rule::requiredIf(fn () => $request->boolean('payload.mechanical')),
                'nullable',
                'string',
            ],
        ]);

        $payload = $validated['payload'];
        $payload['mechanical'] = $request->boolean('payload.mechanical');

        $this->cancelActiveReservationsAndSwapsForCar($car);

        $car->update([
            'fleet_status' => 'damaged',
            'available_from_date' => null,
        ]);

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function applyWrittenOff(Request $request, Tenant $tenant, Car $car): array
    {
        $tenantId = $tenant->id;

        $validated = $request->validate([
            'payload.driver_id' => [
                'nullable',
                Rule::exists('drivers', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'payload.insurance_status' => ['required', Rule::in(['company', 'driver'])],
            'payload.insurance_excess_amount' => 'required|numeric|min:0',
            'payload.fault_type' => ['required', Rule::in(['fault', 'non_fault'])],
            'payload.incident_date' => 'required|date',
            'payload.excess_status' => [
                Rule::requiredIf(fn () => $request->input('payload.fault_type') === 'fault'),
                'nullable',
                'string',
                'max:255',
            ],
            'payload.fault_notes' => [
                Rule::requiredIf(fn () => $request->input('payload.fault_type') === 'fault'),
                'nullable',
                'string',
            ],
            'payload.insurance_claim_reference' => [
                Rule::requiredIf(fn () => $request->input('payload.fault_type') === 'non_fault'),
                'nullable',
                'string',
                'max:255',
            ],
            'payload.written_notes' => 'nullable|string',
        ]);

        $this->cancelActiveReservationsAndSwapsForCar($car);

        $car->update([
            'fleet_status' => 'written_off',
            'available_from_date' => null,
        ]);

        return $validated['payload'];
    }

    /**
     * @return array<string, mixed>
     */
    private function applyStolen(Request $request, Tenant $tenant, Car $car): array
    {
        $tenantId = $tenant->id;

        $validated = $request->validate([
            'payload.driver_id' => [
                'nullable',
                Rule::exists('drivers', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'payload.insurance_status' => ['required', Rule::in(['company', 'driver'])],
            'payload.insurance_excess_amount' => 'required|numeric|min:0',
            'payload.insurance_claim_reference' => 'required|string|max:255',
            'payload.notes' => 'nullable|string',
        ]);

        $this->cancelActiveReservationsAndSwapsForCar($car);

        $car->update([
            'fleet_status' => 'stolen',
            'available_from_date' => null,
        ]);

        return $validated['payload'];
    }

    /**
     * @return array<string, mixed>
     */
    private function applyForSale(Request $request, Car $car): array
    {
        $validated = $request->validate([
            'payload.preparation_date' => 'required|date',
            'payload.ready_date' => 'required|date',
            'payload.advertised_date' => 'required|date',
        ]);

        $this->cancelActiveReservationsAndSwapsForCar($car);

        $car->update([
            'fleet_status' => 'for_sale',
            'available_from_date' => null,
        ]);

        return $validated['payload'];
    }

    /**
     * @return array<string, mixed>
     */
    private function applySoldCarUpdate(Request $request, Car $car): array
    {
        $validated = $request->validate([
            'payload.sell_date' => 'required|date',
            'payload.sell_price' => 'required|numeric|min:0',
            'payload.payment_terms' => ['required', Rule::in(['cash', 'bank', 'auto_total'])],
            'payload.buyer_name' => 'required|string|max:255',
            'payload.buyer_contact' => 'required|string|max:255',
            'payload.buyer_address' => 'required|string',
            'sold_documents' => 'nullable|array',
            'sold_documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $this->cancelActiveReservationsAndSwapsForCar($car);

        $payload = $validated['payload'];

        $car->update([
            'fleet_status' => 'sold',
            'available_from_date' => null,
        ]);

        return array_merge($payload, ['documents' => []]);
    }

    /**
     * @return list<string>
     */
    private function storeSoldDocuments(Request $request, int $historyId): array
    {
        $relativeDir = 'uploads/cars/status_history/'.$historyId;
        $absoluteDir = public_path($relativeDir);

        if (! file_exists($absoluteDir)) {
            mkdir($absoluteDir, 0755, true);
        }

        $uploaded = [];

        $soldDocs = $request->file('sold_documents');
        $soldDocsList = [];
        if ($soldDocs !== null) {
            $soldDocsList = is_array($soldDocs) ? $soldDocs : [$soldDocs];
        }

        foreach ($soldDocsList as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $uploaded[] = $this->moveUploadedFile($file, $relativeDir);
            }
        }

        return $uploaded;
    }

    private function moveUploadedFile(UploadedFile $file, string $relativeDirectory): string
    {
        $mimeType = $file->getMimeType();

        if (str_starts_with($mimeType, 'image/')) {
            $dims = @getimagesize($file->getRealPath());
            $width = $dims[0] ?? 0;
            $height = $dims[1] ?? 0;
            $name = time().'-'.uniqid().'-'.$width.'-'.$height.'.'.$file->extension();
        } else {
            $name = time().'-'.uniqid().'.'.$file->extension();
        }

        $path = public_path($relativeDirectory);

        if (! file_exists($path)) {
            mkdir($path, 0755, true);
        }

        if ($file->move($path, $name)) {
            return $relativeDirectory.'/'.$name;
        }

        throw new \RuntimeException('Failed to upload file');
    }

    private function cancelActiveReservationsAndSwapsForCar(Car $car): void
    {
        CarReservation::query()
            ->where('car_id', $car->id)
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);

        CarReservation::releaseCarFleetStatusIfUnused($car);

        VehicleSwap::query()
            ->active()
            ->where(function ($q) use ($car) {
                $q->where('old_car_id', $car->id)->orWhere('swapped_with_car_id', $car->id);
            })
            ->get()
            ->each(fn (VehicleSwap $s) => $s->delete());

        $car->refresh();
    }

    private function computeBalance(float $agreedRent, float $agreedAdvance, float $amountPaid): string
    {
        $balance = $agreedRent + $agreedAdvance - $amountPaid;

        return number_format(max(0, round($balance, 2)), 2, '.', '');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function sanitizeSwapReasonPayload(array $validated): array
    {
        if (($validated['reason_for_swap'] ?? '') !== VehicleSwap::REASON_PHVL_ISSUES) {
            $validated['phvl_issue_type'] = null;
            $validated['phvl_issue_notes'] = null;
        }

        if (($validated['reason_for_swap'] ?? '') !== VehicleSwap::REASON_OTHERS) {
            $validated['reason_notes'] = null;
        }

        return $validated;
    }

    private function assertCarAssignableForReservation(Car $car, ?int $reservationBeingEditedId): void
    {
        foreach (self::BLOCKED_FLEET_STATUSES as $blocked) {
            if ($car->fleet_status === $blocked) {
                throw ValidationException::withMessages([
                    'car_id' => __('This car cannot be reserved.'),
                ]);
            }
        }

        if ($car->fleet_status === 'vehicle_swap') {
            throw ValidationException::withMessages([
                'car_id' => __('This car is part of an active vehicle swap.'),
            ]);
        }

        $otherActive = CarReservation::query()
            ->where('car_id', $car->id)
            ->where('status', 'active')
            ->when($reservationBeingEditedId, fn ($q) => $q->where('id', '!=', $reservationBeingEditedId))
            ->exists();

        if ($otherActive) {
            throw ValidationException::withMessages([
                'car_id' => __('This car already has an active reservation.'),
            ]);
        }

        if ($car->fleet_status !== 'reserved') {
            return;
        }

        $keepingSameCar = $reservationBeingEditedId
            && CarReservation::query()->find($reservationBeingEditedId)?->car_id === $car->id;

        if (! $keepingSameCar) {
            throw ValidationException::withMessages([
                'car_id' => __('This car is already reserved.'),
            ]);
        }
    }

    private function assertCarUsableInSwap(Car $car, ?int $swapBeingEditedId, string $errorKey): void
    {
        foreach (self::BLOCKED_FLEET_STATUSES as $blocked) {
            if ($car->fleet_status === $blocked) {
                throw ValidationException::withMessages([
                    $errorKey => __('This car cannot be used in a swap.'),
                ]);
            }
        }

        if (CarReservation::query()->where('car_id', $car->id)->where('status', 'active')->exists()) {
            throw ValidationException::withMessages([
                $errorKey => __('This car has an active reservation.'),
            ]);
        }

        if (VehicleSwap::carHasActiveSwap($car->id, $swapBeingEditedId)) {
            throw ValidationException::withMessages([
                $errorKey => __('This car is already part of another vehicle swap.'),
            ]);
        }

        if ($car->fleet_status === 'reserved') {
            throw ValidationException::withMessages([
                $errorKey => __('This car is reserved and cannot be used in a swap.'),
            ]);
        }

        if ($car->fleet_status === 'vehicle_swap') {
            $sameSwap = $swapBeingEditedId
                && VehicleSwap::query()
                    ->whereKey($swapBeingEditedId)
                    ->where(function ($q) use ($car) {
                        $q->where('old_car_id', $car->id)->orWhere('swapped_with_car_id', $car->id);
                    })
                    ->exists();

            if (! $sameSwap) {
                throw ValidationException::withMessages([
                    $errorKey => __('This car is not available for a swap.'),
                ]);
            }
        }
    }
}
