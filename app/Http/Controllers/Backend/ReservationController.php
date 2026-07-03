<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Agreement;
use App\Models\BankAccount;
use App\Models\Car;
use App\Models\CarReservation;
use App\Models\Driver;
use App\Services\DriverPersistenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    private const BLOCKED_FLEET_STATUSES = [
        Car::FLEET_STATUS_PREPARATION_FOR_PHVL,
        'damaged',
        'written_off',
        'stolen',
        'for_sale',
        'sold',
    ];

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
            ->with(['car.company', 'car.carModel', 'driver'])
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

        $cars = Car::forAgreementFormSelection($tenant->id);

        $drivers = Driver::query()
            ->where('tenant_id', $tenant->id)
            ->active()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $driver = null;
        $selectedDriverId = null;
        $driverMode = old('driver_mode', 'existing');
        $bankAccounts = $this->bankAccountsForTenant($tenant->id);

        return view('backend.reservations.create', compact('cars', 'drivers', 'driver', 'selectedDriverId', 'driverMode', 'bankAccounts'));
    }

    public function edit(CarReservation $reservation)
    {
        $this->authorizeTenant($reservation);

        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found! Please contact administrator.');
        }

        $cars = Car::forAgreementFormSelection($tenant->id, $reservation->car_id);

        $drivers = Driver::query()
            ->where('tenant_id', $tenant->id)
            ->active()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $reservation->load('driver');
        $driver = $reservation->driver ?? $this->legacyDriverStub($reservation);
        if ($reservation->driver_id && ! $drivers->contains('id', $reservation->driver_id)) {
            $currentDriver = Driver::query()
                ->where('tenant_id', $tenant->id)
                ->find($reservation->driver_id);
            if ($currentDriver) {
                $drivers->push($currentDriver);
                $drivers = $drivers->sortBy(fn (Driver $driver) => $driver->first_name.' '.$driver->last_name)->values();
            }
        }
        $selectedDriverId = $reservation->driver_id;
        $driverMode = old('driver_mode', $reservation->driver_id ? 'existing' : 'new');
        $driverProfileIncomplete = $reservation->driver_id
            && $driver->exists
            && ! $driver->isProfileCompleteForAgreement();
        $missingDriverFields = $driverProfileIncomplete ? $driver->missingProfileFieldLabels() : [];
        $bankAccounts = $this->bankAccountsForTenant($tenant->id);

        return view('backend.reservations.edit', compact(
            'cars',
            'drivers',
            'reservation',
            'driver',
            'selectedDriverId',
            'driverMode',
            'driverProfileIncomplete',
            'missingDriverFields',
            'bankAccounts'
        ));
    }

    public function store(Request $request, DriverPersistenceService $driverPersistence)
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found! Please contact administrator.');
        }

        $validated = $this->validatedReservationPayload(
            $request,
            $driverPersistence,
            null,
            true
        );

        $carId = $validated['car_id'] ?? null;
        if ($carId !== null) {
            $car = Car::query()->forCurrentTenant()->whereKey($carId)->firstOrFail();
            $this->assertCarAssignable($car, null, $tenant->id);
        }

        $balance = $this->computeBalance(
            (float) $validated['agreed_rent'],
            (float) $validated['agreed_advance'],
            (float) $validated['amount_paid']
        );

        DB::transaction(function () use ($request, $validated, $balance, $carId, $tenant, $driverPersistence) {
            $driverId = $this->resolveDriverId($request, $validated, $tenant, $driverPersistence, true);
            $driverSnapshot = $this->driverSnapshot($driverId);

            $reservation = CarReservation::create([
                'tenant_id' => $tenant->id,
                'car_id' => $carId,
                'driver_id' => $driverId,
                'customer_name' => $driverSnapshot['customer_name'],
                'customer_phone' => $driverSnapshot['customer_phone'],
                'customer_email' => $driverSnapshot['customer_email'],
                'reservation_date' => $validated['reservation_date'],
                'pick_up_date' => $validated['pick_up_date'],
                'available_from_date' => $validated['pick_up_date'],
                'agreed_rent' => $validated['agreed_rent'],
                'agreed_advance' => $validated['agreed_advance'],
                'amount_paid' => $validated['amount_paid'],
                ...$this->paymentAttributesFromValidated($validated),
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

    public function update(Request $request, CarReservation $reservation, DriverPersistenceService $driverPersistence)
    {
        $this->authorizeTenant($reservation);

        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found! Please contact administrator.');
        }

        $reservation->load('driver');

        $linkedDriver = $reservation->driver;
        $completeLinkedDriver = $reservation->driver_id
            && $linkedDriver
            && ! $linkedDriver->isProfileCompleteForAgreement();

        $validated = $this->validatedReservationPayload(
            $request,
            $driverPersistence,
            $completeLinkedDriver ? $linkedDriver : null,
            false,
            $completeLinkedDriver
        );

        $carId = $validated['car_id'] ?? null;
        if ($carId !== null) {
            $car = Car::query()->forCurrentTenant()->whereKey($carId)->firstOrFail();
            $this->assertCarAssignable($car, $reservation->id, $tenant->id);
        }

        $balance = $this->computeBalance(
            (float) $validated['agreed_rent'],
            (float) $validated['agreed_advance'],
            (float) $validated['amount_paid']
        );

        $oldCarId = $reservation->car_id;

        DB::transaction(function () use ($request, $reservation, $validated, $balance, $carId, $oldCarId, $tenant, $driverPersistence, $completeLinkedDriver, $linkedDriver) {
            $driverId = $this->resolveDriverId(
                $request,
                $validated,
                $tenant,
                $driverPersistence,
                false,
                $completeLinkedDriver ? $linkedDriver : null
            );
            $driverSnapshot = $this->driverSnapshot($driverId);

            $reservation->update([
                'car_id' => $carId,
                'driver_id' => $driverId,
                'customer_name' => $driverSnapshot['customer_name'],
                'customer_phone' => $driverSnapshot['customer_phone'],
                'customer_email' => $driverSnapshot['customer_email'],
                'reservation_date' => $validated['reservation_date'],
                'pick_up_date' => $validated['pick_up_date'],
                'available_from_date' => $validated['pick_up_date'],
                'agreed_rent' => $validated['agreed_rent'],
                'agreed_advance' => $validated['agreed_advance'],
                'amount_paid' => $validated['amount_paid'],
                ...$this->paymentAttributesFromValidated($validated),
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
    private function validatedReservationPayload(
        Request $request,
        DriverPersistenceService $driverPersistence,
        ?Driver $existingDriver = null,
        bool $allowMinimalNewDriver = false,
        bool $completeLinkedDriver = false
    ): array {
        $tenant = Auth::user()->currentTenant();

        abort_if(! $tenant, 403);

        $driverMode = $request->input('driver_mode', 'new');

        $rules = [
            'driver_mode' => 'required|in:existing,new',
            'car_id' => [
                'nullable',
                Rule::exists('cars', 'id')->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'reservation_date' => 'required|date',
            'pick_up_date' => 'required|date',
            'agreed_rent' => 'required|numeric|min:0',
            'agreed_advance' => 'required|numeric|min:0',
            'amount_paid' => 'required|numeric|min:0',
            'payment_method' => [
                Rule::requiredIf(fn () => (float) $request->input('amount_paid', 0) > 0),
                'nullable',
                'string',
                'max:255',
            ],
            'bank_account_id' => [
                'nullable',
                Rule::requiredIf(fn () => $request->input('payment_method') === 'Bank Transfer'
                    && (float) $request->input('amount_paid', 0) > 0),
                Rule::exists('bank_accounts', 'id')->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
            ],
        ];

        if ($completeLinkedDriver && $existingDriver) {
            $rules['driver_mode'] = 'required|in:existing';
            $rules['driver_id'] = [
                'required',
                Rule::exists('drivers', 'id')->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
                Rule::in([$existingDriver->id]),
            ];
            $rules = array_merge($rules, $driverPersistence->validationRules($existingDriver));
        } elseif ($driverMode === 'existing') {
            $rules['driver_id'] = [
                'required',
                Rule::exists('drivers', 'id')->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
            ];
        } else {
            $driverRules = $allowMinimalNewDriver
                ? $driverPersistence->reservationMinimalValidationRules($existingDriver)
                : $driverPersistence->validationRules($existingDriver);
            $rules = array_merge($rules, $driverRules);
        }

        return $request->validate($rules);
    }

    private function resolveDriverId(
        Request $request,
        array $validated,
        $tenant,
        DriverPersistenceService $driverPersistence,
        bool $minimalNewDriver = false,
        ?Driver $linkedDriverToUpdate = null
    ): int {
        if ($linkedDriverToUpdate !== null) {
            $driverAttributes = $driverPersistence->attributesFromValidated(
                $request,
                $validated,
                $linkedDriverToUpdate,
                false
            );
            $driverPersistence->updateFromValidatedAttributes($linkedDriverToUpdate, $driverAttributes, $tenant);

            return $linkedDriverToUpdate->id;
        }

        if (($validated['driver_mode'] ?? 'new') === 'existing') {
            return (int) $validated['driver_id'];
        }

        $driverAttributes = $driverPersistence->attributesFromValidated(
            $request,
            $validated,
            null,
            $minimalNewDriver
        );
        $driver = $driverPersistence->createFromValidatedAttributes($driverAttributes, $tenant);

        return $driver->id;
    }

    /**
     * @return array{customer_name: string, customer_phone: ?string, customer_email: ?string}
     */
    private function driverSnapshot(int $driverId): array
    {
        $driver = Driver::query()->findOrFail($driverId);
        $fullName = trim((string) $driver->full_name);

        return [
            'customer_name' => $fullName !== '' ? $fullName : ('Driver #'.$driver->id),
            'customer_phone' => $driver->phone_number ?: null,
            'customer_email' => $driver->email ?: null,
        ];
    }

    private function legacyDriverStub(CarReservation $reservation): Driver
    {
        $driver = new Driver;
        $name = trim((string) ($reservation->attributes['customer_name'] ?? ''));

        if ($name !== '') {
            $parts = preg_split('/\s+/', $name, 2);
            $driver->first_name = $parts[0] ?? '';
            $driver->last_name = $parts[1] ?? '';
        }

        $driver->phone_number = $reservation->attributes['customer_phone'] ?? null;
        $driver->email = $reservation->attributes['customer_email'] ?? null;

        return $driver;
    }

    private function computeBalance(float $agreedRent, float $agreedAdvance, float $amountPaid): string
    {
        $balance = $agreedRent + $agreedAdvance - $amountPaid;

        return number_format(max(0, round($balance, 2)), 2, '.', '');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{payment_method: ?string, bank_account_id: ?int}
     */
    private function paymentAttributesFromValidated(array $validated): array
    {
        $amountPaid = (float) ($validated['amount_paid'] ?? 0);

        if ($amountPaid <= 0) {
            return [
                'payment_method' => null,
                'bank_account_id' => null,
            ];
        }

        $paymentMethod = $validated['payment_method'] ?? null;

        return [
            'payment_method' => $paymentMethod,
            'bank_account_id' => $paymentMethod === 'Bank Transfer'
                ? ($validated['bank_account_id'] ?? null)
                : null,
        ];
    }

    private function bankAccountsForTenant(int $tenantId)
    {
        return BankAccount::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('bank_name')
            ->get();
    }

    private function assertCarAssignable(Car $car, ?int $reservationBeingEditedId, int $tenantId): void
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

        $keepingSameCar = $reservationBeingEditedId
            && CarReservation::query()->find($reservationBeingEditedId)?->car_id === $car->id;

        if (! $keepingSameCar) {
            $rentedCarIds = Agreement::rentedCarIdsForTenant($tenantId);
            if (! $car->isSelectableForAgreement($rentedCarIds)) {
                throw ValidationException::withMessages([
                    'car_id' => __('This vehicle is not available for reservation.'),
                ]);
            }
        }

        if ($car->fleet_status !== 'reserved') {
            return;
        }

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
