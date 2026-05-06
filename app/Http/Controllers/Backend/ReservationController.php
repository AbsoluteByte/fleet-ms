<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\CarReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
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

        $reservations = CarReservation::query()
            ->forCurrentTenant()
            ->with(['car.company', 'car.carModel'])
            ->orderByDesc('reservation_date')
            ->orderByDesc('id')
            ->get();

        return view('backend.reservations.index', compact('reservations'));
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

        return view('backend.reservations.create', compact('cars'));
    }

    public function edit(CarReservation $reservation)
    {
        $this->authorizeTenant($reservation);

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

        return view('backend.reservations.edit', compact('cars', 'reservation'));
    }

    public function store(Request $request)
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found! Please contact administrator.');
        }

        $validated = $this->validatedReservationPayload($request);

        $carId = $validated['car_id'] ?? null;
        if ($carId !== null) {
            $car = Car::query()->forCurrentTenant()->whereKey($carId)->firstOrFail();
            $this->assertCarAssignable($car, null);
        }

        $balance = $this->computeBalance(
            (float) $validated['agreed_rent'],
            (float) $validated['agreed_advance'],
            (float) $validated['amount_paid']
        );

        DB::transaction(function () use ($validated, $balance, $carId, $tenant) {
            $reservation = CarReservation::create([
                'tenant_id' => $tenant->id,
                'car_id' => $carId,
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

            if ($reservation->car_id) {
                $this->markCarReserved(Car::query()->find($reservation->car_id), $validated['pick_up_date']);
            }
        });

        return redirect()->route('reservations.index')->with('success', 'Reservation created.');
    }

    public function update(Request $request, CarReservation $reservation)
    {
        $this->authorizeTenant($reservation);

        if (! Auth::user()->currentTenant()) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found! Please contact administrator.');
        }

        $validated = $this->validatedReservationPayload($request);

        $carId = $validated['car_id'] ?? null;
        if ($carId !== null) {
            $car = Car::query()->forCurrentTenant()->whereKey($carId)->firstOrFail();
            $this->assertCarAssignable($car, $reservation->id);
        }

        $balance = $this->computeBalance(
            (float) $validated['agreed_rent'],
            (float) $validated['agreed_advance'],
            (float) $validated['amount_paid']
        );

        $oldCarId = $reservation->car_id;

        DB::transaction(function () use ($reservation, $validated, $balance, $carId, $oldCarId) {
            $reservation->update([
                'car_id' => $carId,
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
            ]);

            if ($oldCarId !== $carId) {
                CarReservation::releaseCarFleetStatusIfUnused(Car::query()->find($oldCarId));
            }

            if ($carId) {
                $this->markCarReserved(Car::query()->find($carId), $validated['pick_up_date']);
            }
        });

        return redirect()->route('reservations.index')->with('success', 'Reservation updated.');
    }

    public function destroy(CarReservation $reservation)
    {
        $this->authorizeTenant($reservation);

        $reservation->delete();

        return redirect()->route('reservations.index')->with('success', 'Reservation removed.');
    }

    private function authorizeTenant(CarReservation $reservation): void
    {
        $tenant = Auth::user()->currentTenant();

        abort_if(! $tenant || $reservation->tenant_id !== $tenant->id, 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedReservationPayload(Request $request): array
    {
        $tenant = Auth::user()->currentTenant();

        abort_if(! $tenant, 403);

        return $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'customer_email' => 'nullable|email|max:255',
            'car_id' => [
                'nullable',
                Rule::exists('cars', 'id')->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'reservation_date' => 'required|date',
            'pick_up_date' => 'required|date',
            'agreed_rent' => 'required|numeric|min:0',
            'agreed_advance' => 'required|numeric|min:0',
            'amount_paid' => 'required|numeric|min:0',
        ]);
    }

    private function computeBalance(float $agreedRent, float $agreedAdvance, float $amountPaid): string
    {
        $balance = $agreedRent + $agreedAdvance - $amountPaid;

        return number_format(max(0, round($balance, 2)), 2, '.', '');
    }

    private function assertCarAssignable(Car $car, ?int $reservationBeingEditedId): void
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

    private function markCarReserved(?Car $car, ?string $pickUpDate): void
    {
        if (! $car) {
            return;
        }

        $car->update([
            'fleet_status' => 'reserved',
            'available_from_date' => $pickUpDate,
        ]);
    }
}
