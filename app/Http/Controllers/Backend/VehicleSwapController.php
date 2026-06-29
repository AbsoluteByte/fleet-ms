<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Agreement;
use App\Models\Car;
use App\Models\VehicleSwap;
use App\Services\AgreementUpgradeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VehicleSwapController extends Controller
{
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

        $swaps = Agreement::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('upgraded_from_agreement_id')
            ->with([
                'driver',
                'car.carModel',
                'upgradedFromAgreement.car.carModel',
                'status',
            ])
            ->orderByDesc('start_date')
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

        $upgradeService = app(AgreementUpgradeService::class);
        $oldCars = $upgradeService->carsWithActiveUpgradeableAgreements($tenant->id);

        $rentedCarIds = Agreement::rentedCarIdsForTenant($tenant->id);
        $replacementCars = Car::query()
            ->forCurrentTenant()
            ->with(['company', 'carModel', 'mots', 'roadTaxes', 'phvs', 'reservations'])
            ->get()
            ->filter(fn (Car $car) => $car->isSelectableForAgreement($rentedCarIds))
            ->sortBy('registration')
            ->values();

        return view('backend.vehicle_swaps.create', compact('oldCars', 'replacementCars'));
    }

    public function store(Request $request, AgreementUpgradeService $upgradeService)
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found! Please contact administrator.');
        }

        $validated = $this->validatedSwapPayload($request);

        $agreement = Agreement::activeAgreementForCar($tenant->id, (int) $validated['old_car_id']);

        if (! $agreement || ! $upgradeService->canUpgrade($agreement)) {
            throw ValidationException::withMessages([
                'old_car_id' => ['The selected vehicle must have an active agreement eligible for a car change.'],
            ]);
        }

        try {
            $newAgreement = $upgradeService->upgrade($agreement, [
                'car_id' => (int) $validated['swapped_with_car_id'],
                'agreed_rent' => (float) $validated['agreed_rent'],
                'swap_reason' => $validated['reason_for_swap'],
                'swap_phvl_issue_type' => $validated['phvl_issue_type'] ?? null,
                'swap_phvl_issue_notes' => $validated['phvl_issue_notes'] ?? null,
                'swap_reason_notes' => $validated['reason_notes'] ?? null,
            ]);

            return redirect()->route('agreements.show', $newAgreement)
                ->with('success', 'Vehicle swap completed. A new agreement has been created — you can generate a permission letter from this page.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error completing vehicle swap: '.$e->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedSwapPayload(Request $request): array
    {
        $validated = $request->validate([
            'old_car_id' => 'required|exists:cars,id',
            'swapped_with_car_id' => 'required|exists:cars,id|different:old_car_id',
            'agreed_rent' => 'required|numeric|min:0',
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

        if (($validated['reason_for_swap'] ?? null) !== VehicleSwap::REASON_PHVL_ISSUES) {
            $validated['phvl_issue_type'] = null;
            $validated['phvl_issue_notes'] = null;
        }

        if (($validated['reason_for_swap'] ?? null) !== VehicleSwap::REASON_OTHERS) {
            $validated['reason_notes'] = null;
        }

        return $validated;
    }
}
