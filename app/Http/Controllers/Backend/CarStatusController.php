<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\CarReservation;
use App\Models\CarStatusHistory;
use App\Models\Driver;
use App\Models\VehicleSwap;
use App\Services\CarStatusChangeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CarStatusController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin|manager|user');
    }

    public function create()
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found! Please contact administrator.');
        }

        $cars = Car::query()
            ->forCurrentTenant()
            ->with(['company', 'carModel'])
            ->orderBy('registration')
            ->get();

        $drivers = Driver::query()
            ->where('tenant_id', $tenant->id)
            ->active()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $carIds = $cars->pluck('id')->all();

        $carsWithActiveReservation = CarReservation::query()
            ->where('status', 'active')
            ->whereIn('car_id', $carIds)
            ->pluck('car_id')
            ->unique()
            ->all();

        $reservationSet = array_fill_keys($carsWithActiveReservation, true);

        $swapInvolvedIds = [];
        VehicleSwap::query()
            ->active()
            ->where(function ($q) use ($carIds) {
                $q->whereIn('old_car_id', $carIds)->orWhereIn('swapped_with_car_id', $carIds);
            })
            ->get(['old_car_id', 'swapped_with_car_id'])
            ->each(function (VehicleSwap $s) use (&$swapInvolvedIds) {
                $swapInvolvedIds[$s->old_car_id] = true;
                $swapInvolvedIds[$s->swapped_with_car_id] = true;
            });

        $carFleetFlags = [];
        foreach ($cars as $car) {
            $carFleetFlags[$car->id] = [
                'active_reservation' => isset($reservationSet[$car->id]),
                'active_swap' => isset($swapInvolvedIds[$car->id]),
            ];
        }

        $prefillCarId = null;
        $prefillTargetStatus = null;
        $prefillStatusPayload = [];
        $editCurrentStatus = false;

        $request = request();
        $isEditRequest = $request->boolean('edit_current_status')
            || $request->filled('edit_current_status')
            || old('edit_current_status');

        if ($isEditRequest) {
            $requestedCarId = (int) ($request->input('car_id') ?: old('car_id'));
            $selectedCar = $cars->firstWhere('id', $requestedCarId);

            if ($selectedCar) {
                $latestStatusEntry = $this->latestEntryForCurrentFleetStatus($selectedCar);
                if ($latestStatusEntry) {
                    $editCurrentStatus = true;
                    $prefillCarId = $selectedCar->id;
                    $prefillTargetStatus = old('target_status', $selectedCar->fleet_status);
                    $prefillStatusPayload = is_array($latestStatusEntry->status_data)
                        ? $latestStatusEntry->status_data
                        : [];
                }
            }
        }

        return view('backend.car_status.wizard', compact(
            'cars',
            'drivers',
            'carFleetFlags',
            'editCurrentStatus',
            'prefillCarId',
            'prefillTargetStatus',
            'prefillStatusPayload'
        ));
    }

    public function store(Request $request, CarStatusChangeService $carStatusChangeService)
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found! Please contact administrator.');
        }

        $base = $request->validate([
            'car_id' => [
                'required',
                Rule::exists('cars', 'id')->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'target_status' => ['required', Rule::in(CarStatusChangeService::TARGET_STATUSES)],
        ]);

        /** @var Car $car */
        $car = Car::query()->forCurrentTenant()->whereKey($base['car_id'])->firstOrFail();

        $carStatusChangeService->apply($request, $tenant, $car, $base['target_status']);

        return redirect()->route('cars.show', $car)->with('success', 'Vehicle status updated.');
    }

    public function updateCurrent(Request $request, Car $car, CarStatusChangeService $carStatusChangeService)
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant || (int) $car->tenant_id !== (int) $tenant->id) {
            abort(403);
        }

        $validated = $request->validate([
            'target_status' => ['required', Rule::in(CarStatusChangeService::TARGET_STATUSES)],
        ]);

        if (! $request->filled('edit_current_status')) {
            return redirect()
                ->route('car-status.create')
                ->with('error', 'Invalid current-status edit request.');
        }

        if ($validated['target_status'] !== $car->fleet_status) {
            return redirect()
                ->route('cars.show', $car)
                ->with('error', 'You can only edit fields for the current fleet status.');
        }

        $latestEntry = $this->latestEntryForCurrentFleetStatus($car);
        if (! $latestEntry) {
            return redirect()
                ->route('cars.show', $car)
                ->with('error', 'No current status entry found to edit.');
        }

        if (in_array($car->fleet_status, ['reserved', 'vehicle_swap'], true)) {
            return redirect()
                ->route('cars.show', $car)
                ->with('error', 'Please edit reservation/swap from its dedicated edit page.');
        }

        $payload = $this->validateEditablePayloadForCurrentStatus($request, $tenant->id, $car->fleet_status);
        $statusData = is_array($latestEntry->status_data) ? $latestEntry->status_data : [];

        if ($car->fleet_status === 'sold') {
            $existingDocuments = isset($statusData['documents']) && is_array($statusData['documents'])
                ? $statusData['documents']
                : [];
            $payload['documents'] = $carStatusChangeService->mergeSoldDocumentUploads(
                $request,
                $latestEntry->id,
                $existingDocuments
            );
        }

        $latestEntry->update([
            'status_data' => $payload,
            'changed_by' => Auth::id(),
        ]);

        return redirect()
            ->route('cars.show', $car)
            ->with('success', 'Current status details updated.');
    }

    private function latestEntryForCurrentFleetStatus(Car $car): ?CarStatusHistory
    {
        return CarStatusHistory::query()
            ->where('car_id', $car->id)
            ->where('new_status', $car->fleet_status)
            ->latest('id')
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateEditablePayloadForCurrentStatus(Request $request, int $tenantId, string $status): array
    {
        return match ($status) {
            'damaged' => $this->validateDamagedPayload($request, $tenantId),
            'written_off' => $this->validateWrittenOffPayload($request, $tenantId),
            'stolen' => $this->validateStolenPayload($request, $tenantId),
            'for_sale' => $this->validateForSalePayload($request),
            'sold' => $this->validateSoldPayload($request),
            default => abort(422, 'This status does not support inline field edits.'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function validateDamagedPayload(Request $request, int $tenantId): array
    {
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

        $payload = $validated['payload'] ?? [];
        $payload['mechanical'] = $request->boolean('payload.mechanical');

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateWrittenOffPayload(Request $request, int $tenantId): array
    {
        $validated = $request->validate([
            'payload.disposal_outcome' => [
                'required',
                Rule::in(array_keys(CarStatusChangeService::WRITTEN_OFF_DISPOSAL_OUTCOMES)),
            ],
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

        return $validated['payload'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateStolenPayload(Request $request, int $tenantId): array
    {
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

        $payload = $validated['payload'] ?? [];
        if (($payload['driver_id'] ?? '') === '') {
            $payload['driver_id'] = null;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateForSalePayload(Request $request): array
    {
        $validated = $request->validate([
            'payload.preparation_date' => 'nullable|date',
            'payload.ready_date' => 'nullable|date',
            'payload.advertised_date' => 'nullable|date',
        ]);

        $payload = $validated['payload'] ?? [];
        foreach (['preparation_date', 'ready_date', 'advertised_date'] as $key) {
            if (($payload[$key] ?? '') === '') {
                $payload[$key] = null;
            }
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateSoldPayload(Request $request): array
    {
        $validated = $request->validate([
            'payload.sell_date' => 'required|date',
            'payload.sell_price' => 'required|numeric|min:0',
            'payload.payment_terms' => ['required', Rule::in(['cash', 'bank', 'auto_total'])],
            'payload.buyer_name' => 'required|string|max:255',
            'payload.buyer_contact' => 'required|string|max:255',
            'payload.buyer_address' => 'required|string',
            'payload.notes' => 'nullable|string',
        ]);

        return $validated['payload'] ?? [];
    }
}
