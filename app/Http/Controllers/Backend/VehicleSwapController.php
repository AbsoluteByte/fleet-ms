<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\CarReservation;
use App\Models\VehicleSwap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VehicleSwapController extends Controller
{
    private const BLOCKED_FLEET_STATUSES = ['damaged', 'written_off', 'stolen', 'for_sale', 'sold'];

    public function __construct()
    {
        $this->middleware('role:admin|manager|user');
    }

    public function index()
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found! Please contact administrator.');
        }

        $swaps = VehicleSwap::query()
            ->forCurrentTenant()
            ->active()
            ->with(['oldCar.company', 'oldCar.carModel', 'swappedWithCar.company', 'swappedWithCar.carModel'])
            ->orderByDesc('reservation_date')
            ->orderByDesc('id')
            ->get();

        return view('backend.vehicle_swaps.index', compact('swaps'));
    }

    public function create()
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found! Please contact administrator.');
        }

        $cars = $this->carsForTenantSelect();

        return view('backend.vehicle_swaps.create', compact('cars'));
    }

    public function store(Request $request)
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found! Please contact administrator.');
        }

        $validated = $this->sanitizeReasonFields($this->validatedSwapPayload($request));

        $oldCar = Car::query()->forCurrentTenant()->whereKey($validated['old_car_id'])->firstOrFail();
        $newCar = Car::query()->forCurrentTenant()->whereKey($validated['swapped_with_car_id'])->firstOrFail();

        $this->assertCarUsableInSwap($oldCar, null, 'old_car_id');
        $this->assertCarUsableInSwap($newCar, null, 'swapped_with_car_id');

        $balance = $this->computeBalance(
            (float) $validated['agreed_rent'],
            (float) $validated['agreed_advance'],
            (float) $validated['amount_paid']
        );

        DB::transaction(function () use ($validated, $balance, $tenant) {
            $swap = VehicleSwap::create([
                'tenant_id' => $tenant->id,
                'old_car_id' => $validated['old_car_id'],
                'swapped_with_car_id' => $validated['swapped_with_car_id'],
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
        });

        return redirect()->route('vehicle-swaps.index')->with('success', 'Vehicle swap created.');
    }

    public function edit(VehicleSwap $vehicleSwap)
    {
        $this->authorizeTenant($vehicleSwap);

        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found! Please contact administrator.');
        }

        $cars = $this->carsForTenantSelect();

        return view('backend.vehicle_swaps.edit', compact('cars', 'vehicleSwap'));
    }

    public function update(Request $request, VehicleSwap $vehicleSwap)
    {
        $this->authorizeTenant($vehicleSwap);

        if (! Auth::user()->currentTenant()) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found! Please contact administrator.');
        }

        $validated = $this->sanitizeReasonFields($this->validatedSwapPayload($request));

        $oldCar = Car::query()->forCurrentTenant()->whereKey($validated['old_car_id'])->firstOrFail();
        $newCar = Car::query()->forCurrentTenant()->whereKey($validated['swapped_with_car_id'])->firstOrFail();

        $this->assertCarUsableInSwap($oldCar, $vehicleSwap->id, 'old_car_id');
        $this->assertCarUsableInSwap($newCar, $vehicleSwap->id, 'swapped_with_car_id');

        $balance = $this->computeBalance(
            (float) $validated['agreed_rent'],
            (float) $validated['agreed_advance'],
            (float) $validated['amount_paid']
        );

        $beforeOld = $vehicleSwap->old_car_id;
        $beforeNew = $vehicleSwap->swapped_with_car_id;

        DB::transaction(function () use ($vehicleSwap, $validated, $balance, $beforeOld, $beforeNew) {
            $vehicleSwap->update([
                'old_car_id' => $validated['old_car_id'],
                'swapped_with_car_id' => $validated['swapped_with_car_id'],
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
            ]);

            foreach ([$beforeOld, $beforeNew] as $carId) {
                if ($carId && ! in_array($carId, [$vehicleSwap->old_car_id, $vehicleSwap->swapped_with_car_id], true)) {
                    VehicleSwap::releaseCarFleetAfterSwapRemoved(Car::query()->find($carId));
                }
            }

            VehicleSwap::applyVehicleSwapFleetStatus(
                Car::query()->find($vehicleSwap->old_car_id),
                Car::query()->find($vehicleSwap->swapped_with_car_id),
                $validated['pick_up_date']
            );
        });

        return redirect()->route('vehicle-swaps.index')->with('success', 'Vehicle swap updated.');
    }

    public function destroy(VehicleSwap $vehicleSwap)
    {
        $this->authorizeTenant($vehicleSwap);

        $vehicleSwap->delete();

        return redirect()->route('vehicle-swaps.index')->with('success', 'Vehicle swap removed.');
    }

    private function authorizeTenant(VehicleSwap $swap): void
    {
        $tenant = Auth::user()->currentTenant();

        abort_if(! $tenant || $swap->tenant_id !== $tenant->id, 403);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Car>
     */
    private function carsForTenantSelect()
    {
        return Car::query()
            ->forCurrentTenant()
            ->with(['company', 'carModel'])
            ->orderBy('registration')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedSwapPayload(Request $request): array
    {
        $tenant = Auth::user()->currentTenant();

        abort_if(! $tenant, 403);

        $reasonKeys = array_keys(VehicleSwap::reasonLabels());
        $phvlKeys = array_keys(VehicleSwap::phvlIssueTypeLabels());

        return $request->validate([
            'old_car_id' => [
                'required',
                Rule::exists('cars', 'id')->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'swapped_with_car_id' => [
                'required',
                Rule::exists('cars', 'id')->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
                'different:old_car_id',
            ],
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'customer_email' => 'nullable|email|max:255',
            'reservation_date' => 'required|date',
            'pick_up_date' => 'required|date',
            'agreed_rent' => 'required|numeric|min:0',
            'agreed_advance' => 'required|numeric|min:0',
            'amount_paid' => 'required|numeric|min:0',
            'reason_for_swap' => ['required', Rule::in($reasonKeys)],
            'phvl_issue_type' => [
                Rule::requiredIf(fn () => $request->input('reason_for_swap') === VehicleSwap::REASON_PHVL_ISSUES),
                'nullable',
                Rule::in($phvlKeys),
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
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function sanitizeReasonFields(array $validated): array
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

    private function computeBalance(float $agreedRent, float $agreedAdvance, float $amountPaid): string
    {
        $balance = $agreedRent + $agreedAdvance - $amountPaid;

        return number_format(max(0, round($balance, 2)), 2, '.', '');
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
