<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\CarReservation;
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

        return view('backend.car_status.wizard', compact('cars', 'drivers', 'carFleetFlags'));
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
}
