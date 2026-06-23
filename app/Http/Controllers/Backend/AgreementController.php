<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Agreement;
use App\Models\Car;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Invoice;
use App\Models\Status;
use App\Models\Tenant;
use App\Services\AgreementInvoiceService;
use App\Services\AgreementUpgradeService;
use App\Services\PaymentAllocationService;
use App\Services\PermissionLetterService;
// Add this
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PDF;

class AgreementController extends Controller
{
    private const DISCOUNT_ALLOWED_EMAIL = 'jawad@samoretraders.com';

    protected $url = 'agreements.';

    protected $dir = 'backend.agreements.';

    protected $name = 'Agreements';

    public function __construct()
    {
        $this->middleware('role:admin|manager|user');
        view()->share('url', $this->url);
        view()->share('dir', $this->dir);
        view()->share('singular', Str::singular($this->name));
        view()->share('plural', Str::plural($this->name));
    }

    public function index()
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found! Please contact administrator.');
        }
        $agreements = Agreement::where('tenant_id', $tenant->id)->with(['company', 'driver', 'car', 'status', 'parentAgreement'])
            ->withCount(['collections', 'pendingCollections', 'overdueCollections'])
            ->get();

        return view($this->dir.'index', compact('agreements'));
    }

    public function create()
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }
        $companies = Company::where('tenant_id', $tenant->id)->get();
        $drivers = Driver::where('tenant_id', $tenant->id)->active()->get();
        $cars = $this->carsForAgreementForm($tenant);
        $model = new Agreement;
        $statuses = Status::where('type', 'agreement')->get();

        $canManageDiscount = $this->canManageDiscount();

        $agreementPaymentLimit = null;
        $agreementPaymentAllowed = true;
        $originalAgreements = $this->originalAgreementsForForm($tenant);
        $replacementVehicleStatusId = $this->replacementVehicleStatusId();
        $driversActiveAgreements = $this->driversActiveAgreementsForForm($tenant);

        return view($this->dir.'create', compact('model', 'companies', 'drivers', 'cars', 'statuses', 'canManageDiscount', 'agreementPaymentLimit', 'agreementPaymentAllowed', 'originalAgreements', 'replacementVehicleStatusId', 'driversActiveAgreements'));
    }

    public function store(Request $request)
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->back()
                ->with('error', 'No active company found!');
        }
        $validated = $this->validateAgreementRequest($request);
        $isReplacementVehicle = $this->isReplacementVehicleStatusId((int) $validated['status_id']);
        $this->assertEndDateOnOrAfterStartDate($validated);

        if ($isReplacementVehicle) {
            $this->assertReplacementVehicleParentAgreement($validated, $tenant);
        }

        $this->assertCarNotCurrentlyRented((int) $validated['car_id'], $tenant);

        $validated['auto_schedule_collections'] = false;
        [$validated, $agreementPaymentData] = $this->prepareAgreementPaymentData($validated, $request, $isReplacementVehicle);

        if (! $isReplacementVehicle) {
            $this->assertAgreementPaymentLimit(
                $agreementPaymentData,
                round(((float) $validated['agreed_rent']) + ((float) $validated['deposit_amount']), 2),
                'The total payment cannot be greater than agreed rent plus deposit amount.'
            );
        }

        try {
            $agreement = DB::transaction(function () use ($validated, $request, $tenant, $agreementPaymentData, $isReplacementVehicle) {
                $validated = $this->mergeInsuranceData($request, $validated);

                // Create agreement record
                $validated['tenant_id'] = $tenant->id;
                $validated['createdBy'] = Auth::id();
                $validated = $this->mergeTerminationData($validated);
                $validated = $this->applyDiscountData($validated, $request);
                $validated = $this->mergeReplacementVehicleData($validated);
                $agreement = Agreement::create($validated);
                $this->syncTerminatedCarAvailability($agreement);

                if (! $isReplacementVehicle) {
                    // Handle collections based on auto schedule setting
                    if ($validated['auto_schedule_collections']) {
                        $agreement->generateCollections();
                    } elseif ($request->has('collections')) {
                        foreach ($request->input('collections') as $collectionData) {
                            $collectionData['payment_status'] = 'pending';
                            $collectionData['is_auto_generated'] = false;
                            $collectionData['due_date'] = $collectionData['due_date'] ?? $collectionData['date'];
                            $agreement->collections()->create($collectionData);
                        }
                    }

                    app(AgreementInvoiceService::class)->generateForAgreement($agreement->fresh());

                    if ($agreementPaymentData !== []) {
                        $driver = $agreement->driver()->firstOrFail();
                        $paymentService = app(PaymentAllocationService::class);

                        foreach ($agreementPaymentData as $paymentData) {
                            $paymentService->createPaymentForInvoices(
                                $driver,
                                $paymentData,
                                $agreement->id,
                                ['agreement', 'agreement_deposit']
                            );
                        }
                    }
                }

                return $agreement;
            });

            return redirect()->route('agreements.index')
                ->with('success', 'Agreement created successfully.');

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating agreement: '.$e->getMessage());
        }
    }

    public function show(Agreement $agreement)
    {
        $tenant = Auth::user()->currentTenant();

        // ✅ Check ownership
        if ($agreement->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to this car');
        }
        $agreement->load([
            'company', 'driver', 'car.carModel', 'car.insurances.status', 'car.insurances.insuranceProvider',
            'status', 'insuranceProvider', 'terminationRecordedBy', 'parentAgreement.car', 'parentAgreement.driver',
            'upgradedFromAgreement.car', 'upgradedToAgreement.car',
            'collections' => function ($query) {
                $query->orderBy('due_date');
            },
        ]);

        // Update overdue collections
        $agreement->updateOverdueCollections();

        $upgradeService = app(AgreementUpgradeService::class);
        $canUpgradeCar = $upgradeService->canUpgrade($agreement);
        $upgradePreview = $canUpgradeCar ? $upgradeService->upgradePreview($agreement) : null;

        return view($this->dir.'show', compact('agreement', 'canUpgradeCar', 'upgradePreview'));
    }

    public function upgradeCars(Agreement $agreement)
    {
        $tenant = Auth::user()->currentTenant();

        if ($agreement->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to this agreement');
        }

        $upgradeService = app(AgreementUpgradeService::class);

        if (! $upgradeService->canUpgrade($agreement)) {
            return response()->json(['message' => 'This agreement is not eligible for a car upgrade.'], 422);
        }

        $cars = $upgradeService->availableCars($agreement)->map(function (Car $car) {
            $insurance = $car->currentActiveInsurance();

            return [
                'id' => $car->id,
                'registration' => $car->registration,
                'model' => $car->carModel?->name,
                'company' => $car->company?->name,
                'has_active_insurance' => $car->isInsuranceCurrentlyActive(),
                'insurance_provider' => $insurance?->insuranceProvider?->name,
            ];
        })->values();

        return response()->json([
            'cars' => $cars,
            'preview' => $upgradeService->upgradePreview($agreement),
        ]);
    }

    public function upgradeCar(Request $request, Agreement $agreement)
    {
        $tenant = Auth::user()->currentTenant();

        if ($agreement->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to this agreement');
        }

        $validated = $request->validate([
            'car_id' => 'required|exists:cars,id',
            'agreed_rent' => 'required|numeric|min:0',
            'deposit_amount' => 'required|numeric|min:0',
        ]);

        try {
            $newAgreement = app(AgreementUpgradeService::class)->upgrade($agreement, $validated);

            return redirect()->route('agreements.show', $newAgreement)
                ->with('success', 'Car upgraded successfully. A new agreement has been created.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error upgrading car: '.$e->getMessage());
        }
    }

    public function edit(Agreement $agreement)
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }
        $model = $agreement->load('collections');
        $companies = Company::where('tenant_id', $tenant->id)->get();
        $drivers = Driver::where('tenant_id', $tenant->id)->active()->get();
        if ($agreement->driver_id && ! $drivers->contains('id', $agreement->driver_id)) {
            $currentDriver = Driver::where('tenant_id', $tenant->id)->find($agreement->driver_id);
            if ($currentDriver) {
                $drivers->push($currentDriver);
                $drivers = $drivers->sortBy(fn (Driver $driver) => $driver->first_name.' '.$driver->last_name)->values();
            }
        }
        $cars = $this->carsForAgreementForm($tenant, $agreement->car_id, $agreement->id);
        $statuses = Status::where('type', 'agreement')->get();

        $canManageDiscount = $this->canManageDiscount();
        $agreementPaymentLimit = $this->unpaidAgreementInvoiceBalance($agreement);
        $agreementPaymentAllowed = $agreementPaymentLimit > 0 && ! $agreement->isReplacementVehicle();
        $originalAgreements = $this->originalAgreementsForForm($tenant, $agreement->id, $agreement->parent_agreement_id);
        $replacementVehicleStatusId = $this->replacementVehicleStatusId();

        return view($this->dir.'edit', compact('model', 'companies', 'drivers', 'cars', 'statuses', 'canManageDiscount', 'agreementPaymentLimit', 'agreementPaymentAllowed', 'originalAgreements', 'replacementVehicleStatusId'));
    }

    public function update(Request $request, Agreement $agreement)
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->back()
                ->with('error', 'No active company found!');
        }
        $validated = $this->validateAgreementRequest($request);
        $isReplacementVehicle = $this->isReplacementVehicleStatusId((int) $validated['status_id']);
        $this->assertEndDateOnOrAfterStartDate($validated);

        if ($isReplacementVehicle) {
            $this->assertReplacementVehicleParentAgreement($validated, $tenant, $agreement);
        }

        $this->assertCarNotCurrentlyRented((int) $validated['car_id'], $tenant, $agreement->id);

        $validated['auto_schedule_collections'] = false;
        [$validated, $agreementPaymentData] = $this->prepareAgreementPaymentData($validated, $request, $isReplacementVehicle);

        if (! $isReplacementVehicle) {
            $this->assertAgreementPaymentLimit(
                $agreementPaymentData,
                $this->unpaidAgreementInvoiceBalance($agreement),
                'Payments can only be added up to this agreement\'s unpaid invoice balance.'
            );
        }

        try {
            $updatedAgreement = DB::transaction(function () use ($validated, $request, $agreement, $tenant, $agreementPaymentData, $isReplacementVehicle) {
                $oldAutoSchedule = $agreement->auto_schedule_collections;

                $validated = $this->mergeInsuranceData($request, $validated, $agreement);

                // Update agreement record
                $validated['tenant_id'] = $tenant->id;
                $validated['updatedBy'] = Auth::id();
                $validated = $this->mergeTerminationData($validated, $agreement);
                $validated = $this->applyDiscountData($validated, $request, $agreement);
                $validated = $this->mergeReplacementVehicleData($validated);
                $agreement->update($validated);
                $this->syncTerminatedCarAvailability($agreement);

                if (! $isReplacementVehicle) {
                    if ($validated['auto_schedule_collections']) {
                        if ($oldAutoSchedule !== $validated['auto_schedule_collections'] ||
                            $agreement->wasChanged(['start_date', 'end_date', 'collection_type', 'agreed_rent'])) {
                            $agreement->generateCollections();
                        }
                    } else {
                        $agreement->collections()->where('is_auto_generated', false)->delete();

                        if ($request->has('collections')) {
                            foreach ($request->input('collections') as $collectionData) {
                                $collectionData['payment_status'] = 'pending';
                                $collectionData['is_auto_generated'] = false;
                                $collectionData['due_date'] = $collectionData['due_date'] ?? $collectionData['date'];
                                $agreement->collections()->create($collectionData);
                            }
                        }
                    }

                    app(AgreementInvoiceService::class)->generateForAgreement($agreement->fresh());

                    if ($agreementPaymentData !== []) {
                        $driver = $agreement->driver()->firstOrFail();
                        $paymentService = app(PaymentAllocationService::class);

                        foreach ($agreementPaymentData as $paymentData) {
                            $paymentService->createPaymentForInvoices(
                                $driver,
                                $paymentData,
                                $agreement->id,
                                ['agreement', 'agreement_deposit']
                            );
                        }
                    }
                }

                return $agreement;
            });

            return redirect()->route('agreements.index')
                ->with('success', 'Agreement updated successfully.');

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating agreement: '.$e->getMessage());
        }
    }

    public function destroy(Agreement $agreement)
    {
        try {
            $tenant = Auth::user()->currentTenant();

            // ✅ Check ownership
            if ($agreement->tenant_id !== $tenant->id) {
                abort(403, 'Unauthorized access');
            }
            DB::transaction(function () use ($agreement) {
                foreach ($agreement->ownInsuranceProofFileNames() as $name) {
                    $this->deleteInsuranceProofFile($name);
                }

                // Delete related collections first
                $agreement->collections()->delete();
                // Delete the agreement
                $agreement->delete();
            });

            return redirect()->route('agreements.index')
                ->with('success', 'Agreement deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error deleting agreement: '.$e->getMessage());
        }
    }

    private function mergeTerminationData(array $validated, ?Agreement $existing = null): array
    {
        if (empty($validated['termination_notice_date'])) {
            $validated['termination_available_from_date'] = null;
            $validated['termination_notes'] = null;
            $validated['termination_recorded_by'] = null;

            return $validated;
        }

        $validated['termination_recorded_by'] = $existing && $existing->termination_recorded_by
            ? $existing->termination_recorded_by
            : Auth::id();

        return $validated;
    }

    private function validateAgreementRequest(Request $request): array
    {
        $isReplacementVehicle = $this->isReplacementVehicleStatusId((int) $request->input('status_id'));

        $rules = [
            'company_id' => 'required|exists:companies,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'driver_id' => 'required|exists:drivers,id',
            'car_id' => 'required|exists:cars,id',
            'mileage_out' => 'nullable|integer|min:0',
            'mileage_in' => 'nullable|integer|min:0',
            'condition_report' => 'nullable|string',
            'notes' => 'nullable|string',
            'status_id' => 'required|exists:statuses,id',
            'using_own_insurance' => 'boolean',
            'own_insurance_provider_name' => 'required_if:using_own_insurance,1|nullable|string|max:255',
            'own_insurance_start_date' => 'required_if:using_own_insurance,1|nullable|date',
            'own_insurance_end_date' => 'required_if:using_own_insurance,1|nullable|date|after:own_insurance_start_date',
            'own_insurance_type' => 'required_if:using_own_insurance,1|nullable|string|max:255',
            'own_insurance_policy_number' => 'required_if:using_own_insurance,1|nullable|string|max:255',
            'own_insurance_proof_document' => 'nullable|array',
            'own_insurance_proof_document.*' => 'file|mimes:pdf,jpg,jpeg,png|max:2048',
            'termination_notice_date' => 'nullable|date',
            'termination_available_from_date' => 'nullable|date',
            'termination_notes' => 'nullable|string',
        ];

        if ($isReplacementVehicle) {
            $rules['parent_agreement_id'] = 'required|exists:agreements,id';
        } else {
            $rules = array_merge($rules, [
                'agreed_rent' => 'required|numeric|min:0',
                'rent_interval' => 'required|string',
                'deposit_amount' => 'required|numeric|min:0',
                'discount_type' => 'nullable|in:percentage,fixed',
                'discount_value' => 'nullable|numeric|min:0',
                'discount_notes' => 'nullable|string',
                'collection_type' => 'required|in:weekly,monthly,static',
                'auto_schedule_collections' => 'boolean',
                'collections' => 'array',
                'collections.*.date' => 'required_if:auto_schedule_collections,0|nullable|date',
                'collections.*.due_date' => 'nullable|date',
                'collections.*.method' => 'required_if:auto_schedule_collections,0|nullable|string',
                'collections.*.amount' => 'required_if:auto_schedule_collections,0|nullable|numeric|min:0',
                'add_payment' => 'boolean',
                'agreement_payments' => 'required_if:add_payment,1|array',
                'agreement_payments.*.payment_method' => 'required_if:add_payment,1|nullable|string|max:255',
                'agreement_payments.*.payment_date' => 'required_if:add_payment,1|nullable|date',
                'agreement_payments.*.amount' => 'required_if:add_payment,1|nullable|numeric|min:0.01',
                'agreement_payments.*.notes' => 'nullable|string',
            ]);
        }

        return $request->validate($rules);
    }

    private function isReplacementVehicleStatusId(?int $statusId): bool
    {
        if (! $statusId) {
            return false;
        }

        $replacementVehicleStatusId = $this->replacementVehicleStatusId();

        if ($replacementVehicleStatusId !== null && $statusId === $replacementVehicleStatusId) {
            return true;
        }

        return Status::query()
            ->where('id', $statusId)
            ->where('type', 'agreement')
            ->where('name', 'Replacement Vehicle')
            ->exists();
    }

    private function replacementVehicleStatusId(): ?int
    {
        return Status::query()
            ->where('type', 'agreement')
            ->where('name', 'Replacement Vehicle')
            ->value('id');
    }

    private function originalAgreementsForForm(Tenant $tenant, ?int $excludeAgreementId = null, ?int $alwaysIncludeAgreementId = null)
    {
        $agreements = Agreement::query()
            ->where('tenant_id', $tenant->id)
            ->eligibleAsOriginal()
            ->with(['car.carModel', 'driver'])
            ->when($excludeAgreementId, fn ($query) => $query->where('id', '!=', $excludeAgreementId))
            ->orderByDesc('start_date')
            ->get();

        if ($alwaysIncludeAgreementId && ! $agreements->contains('id', $alwaysIncludeAgreementId)) {
            $included = Agreement::query()
                ->where('tenant_id', $tenant->id)
                ->where('id', $alwaysIncludeAgreementId)
                ->with(['car.carModel', 'driver'])
                ->first();

            if ($included) {
                $agreements->push($included);
            }
        }

        return $agreements
            ->map(function (Agreement $agreement) {
                return [
                    'id' => $agreement->id,
                    'driver_id' => $agreement->driver_id,
                    'label' => sprintf(
                        '#%d — %s — %s (%s to %s)',
                        $agreement->id,
                        $agreement->car->registration,
                        $agreement->driver->full_name,
                        $agreement->start_date->format('d M Y'),
                        $agreement->end_date->format('d M Y')
                    ),
                ];
            })
            ->values();
    }

    /**
     * @return array<int|string, list<array{id: int, label: string, url: string}>>
     */
    private function driversActiveAgreementsForForm(Tenant $tenant): array
    {
        return Agreement::query()
            ->where('tenant_id', $tenant->id)
            ->currentlyActive()
            ->with(['car.carModel'])
            ->orderByDesc('start_date')
            ->get()
            ->groupBy('driver_id')
            ->map(fn ($agreements) => $agreements->map(function (Agreement $agreement) {
                return [
                    'id' => $agreement->id,
                    'label' => sprintf(
                        '#%d — %s (%s to %s)',
                        $agreement->id,
                        $agreement->car->registration,
                        $agreement->start_date->format('d M Y'),
                        $agreement->end_date->format('d M Y')
                    ),
                    'url' => route('agreements.show', $agreement),
                ];
            })->values()->all())
            ->all();
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function assertReplacementVehicleParentAgreement(array $validated, Tenant $tenant, ?Agreement $existing = null): void
    {
        $parentId = (int) $validated['parent_agreement_id'];

        if ($existing && $parentId === $existing->id) {
            throw ValidationException::withMessages([
                'parent_agreement_id' => ['An agreement cannot be linked to itself.'],
            ]);
        }

        $parent = Agreement::query()
            ->with('status')
            ->where('tenant_id', $tenant->id)
            ->find($parentId);

        if (! $parent) {
            throw ValidationException::withMessages([
                'parent_agreement_id' => ['The selected original agreement could not be found.'],
            ]);
        }

        if ((int) $parent->driver_id !== (int) $validated['driver_id']) {
            throw ValidationException::withMessages([
                'parent_agreement_id' => ['The original agreement must belong to the same driver.'],
            ]);
        }

        if ($parent->parent_agreement_id !== null) {
            throw ValidationException::withMessages([
                'parent_agreement_id' => ['The selected agreement is not eligible as an original agreement.'],
            ]);
        }

        if (strcasecmp((string) optional($parent->status)->name, 'Active') !== 0) {
            throw ValidationException::withMessages([
                'parent_agreement_id' => ['The original agreement must have Active status.'],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function mergeReplacementVehicleData(array $validated): array
    {
        if (! $this->isReplacementVehicleStatusId((int) ($validated['status_id'] ?? 0))) {
            $validated['parent_agreement_id'] = null;

            return $validated;
        }

        $validated['agreed_rent'] = 0;
        $validated['deposit_amount'] = 0;
        $validated['collection_type'] = 'static';
        $validated['rent_interval'] = 'Monthly';
        $validated['auto_schedule_collections'] = false;
        $validated['discount_type'] = null;
        $validated['discount_value'] = null;
        $validated['discount_notes'] = null;

        return $validated;
    }

    private function carsForAgreementForm(Tenant $tenant, ?int $alwaysIncludeCarId = null, ?int $excludeAgreementId = null)
    {
        $rentedCarIds = $this->rentedCarIdsForAgreementForm($tenant, $excludeAgreementId);

        $cars = Car::where('tenant_id', $tenant->id)
            ->with(['carModel', 'mots', 'roadTaxes', 'phvs', 'reservations', 'insurances.status', 'insurances.insuranceProvider'])
            ->get()
            ->filter(function (Car $car) use ($rentedCarIds, $alwaysIncludeCarId) {
                if ($alwaysIncludeCarId && (int) $car->id === (int) $alwaysIncludeCarId) {
                    return true;
                }

                return $car->isSelectableForAgreement($rentedCarIds);
            });

        if ($alwaysIncludeCarId) {
            $currentCar = Car::where('tenant_id', $tenant->id)
                ->where('id', $alwaysIncludeCarId)
                ->with(['carModel', 'mots', 'roadTaxes', 'phvs', 'reservations', 'insurances.status', 'insurances.insuranceProvider'])
                ->first();

            if ($currentCar && ! $cars->contains('id', $alwaysIncludeCarId)) {
                $cars = $cars->push($currentCar);
            }
        }

        return $cars->sortBy('registration')->values();
    }

    /**
     * @return list<int>
     */
    private function rentedCarIdsForAgreementForm(Tenant $tenant, ?int $excludeAgreementId = null): array
    {
        return Agreement::rentedCarIdsForTenant($tenant->id, $excludeAgreementId);
    }

    private function assertCarNotCurrentlyRented(int $carId, Tenant $tenant, ?int $excludeAgreementId = null): void
    {
        $car = Car::where('tenant_id', $tenant->id)
            ->with(['mots', 'roadTaxes', 'phvs', 'reservations'])
            ->find($carId);

        if (! $car) {
            throw ValidationException::withMessages([
                'car_id' => ['The selected vehicle could not be found.'],
            ]);
        }

        $rentedCarIds = $this->rentedCarIdsForAgreementForm($tenant, $excludeAgreementId);

        if (! $car->isSelectableForAgreement($rentedCarIds)) {
            throw ValidationException::withMessages([
                'car_id' => ['The selected vehicle is not available for an agreement.'],
            ]);
        }
    }

    private function mergeInsuranceData(Request $request, array $validated, ?Agreement $existing = null): array
    {
        $usingOwn = $request->boolean('using_own_insurance');
        $validated['using_own_insurance'] = $usingOwn;

        if (! $usingOwn) {
            if ($existing) {
                foreach ($existing->ownInsuranceProofFileNames() as $name) {
                    $this->deleteInsuranceProofFile($name);
                }
            }

            $validated['own_insurance_provider_name'] = null;
            $validated['own_insurance_start_date'] = null;
            $validated['own_insurance_end_date'] = null;
            $validated['own_insurance_type'] = null;
            $validated['own_insurance_policy_number'] = null;
            $validated['own_insurance_proof_document'] = null;
            $validated['insurance_provider_id'] = null;

            return $validated;
        }

        $validated['insurance_provider_id'] = null;

        $names = $existing?->ownInsuranceProofFileNames() ?? [];

        foreach ($this->collectInsuranceProofUploads($request) as $file) {
            $names[] = $this->uploadInsuranceProofFile($file);
        }

        $validated['own_insurance_proof_document'] = $names === [] ? null : array_values(array_unique($names));

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function assertEndDateOnOrAfterStartDate(array $validated): void
    {
        $startDay = Carbon::parse($validated['start_date'])->startOfDay();
        $endDay = Carbon::parse($validated['end_date'])->startOfDay();

        if ($endDay->lt($startDay)) {
            throw ValidationException::withMessages([
                'end_date' => ['The end date must be on or after the start date.'],
            ]);
        }
    }

    private function canManageDiscount(): bool
    {
        return strtolower(trim((string) Auth::user()?->email)) === self::DISCOUNT_ALLOWED_EMAIL;
    }

    private function prepareAgreementPaymentData(array $validated, Request $request, bool $isReplacementVehicle = false): array
    {
        $paymentData = [];

        if ($isReplacementVehicle && $request->boolean('add_payment')) {
            throw ValidationException::withMessages([
                'add_payment' => ['Payments cannot be added to a replacement vehicle agreement. Use the original agreement instead.'],
            ]);
        }

        if ($request->boolean('add_payment')) {
            foreach (($validated['agreement_payments'] ?? []) as $index => $paymentRow) {
                if (! is_array($paymentRow) || empty($paymentRow['amount'])) {
                    continue;
                }

                $paymentData[] = [
                    'payment_method' => $paymentRow['payment_method'] ?? null,
                    'payment_date' => $paymentRow['payment_date'] ?? null,
                    'amount' => round((float) $paymentRow['amount'], 2),
                    'notes' => $paymentRow['notes'] ?? null,
                ];
            }

            if ($paymentData === []) {
                throw ValidationException::withMessages([
                    'agreement_payments.0.amount' => 'Add at least one payment amount.',
                ]);
            }
        }

        unset(
            $validated['add_payment'],
            $validated['agreement_payments']
        );

        return [$validated, $paymentData];
    }

    /**
     * @param  list<array<string, mixed>>  $payments
     */
    private function assertAgreementPaymentLimit(array $payments, float $limit, string $message): void
    {
        if ($payments === []) {
            return;
        }

        $total = round(array_sum(array_map(fn ($payment) => (float) $payment['amount'], $payments)), 2);

        if ($limit <= 0 || $total > round($limit, 2)) {
            throw ValidationException::withMessages([
                'agreement_payments.0.amount' => $message,
            ]);
        }
    }

    private function unpaidAgreementInvoiceBalance(Agreement $agreement): float
    {
        return round((float) Invoice::query()
            ->where('source_id', $agreement->id)
            ->whereIn('invoice_type', ['agreement', 'agreement_deposit'])
            ->where('balance_amount', '>', 0)
            ->sum('balance_amount'), 2);
    }

    private function applyDiscountData(array $validated, Request $request, ?Agreement $existing = null): array
    {
        if (! $this->canManageDiscount()) {
            $validated['discount_type'] = $existing?->discount_type;
            $validated['discount_value'] = $existing?->discount_value;
            $validated['discount_notes'] = $existing?->discount_notes;

            return $validated;
        }

        $validated['discount_notes'] = $request->filled('discount_notes')
            ? trim((string) $request->input('discount_notes'))
            : null;

        $discountType = $request->input('discount_type');
        $discountValue = $request->input('discount_value');

        if (! in_array($discountType, ['percentage', 'fixed'], true) || $discountValue === null || $discountValue === '') {
            $validated['discount_type'] = null;
            $validated['discount_value'] = null;

            return $validated;
        }

        $discountValue = (float) $discountValue;

        if ($discountType === 'percentage' && $discountValue > 100) {
            throw ValidationException::withMessages([
                'discount_value' => 'Percentage discount cannot be greater than 100.',
            ]);
        }

        $validated['discount_type'] = $discountType;
        $validated['discount_value'] = round($discountValue, 2);

        return $validated;
    }

    /**
     * @return list<\Illuminate\Http\UploadedFile>
     */
    private function collectInsuranceProofUploads(Request $request): array
    {
        if (! $request->hasFile('own_insurance_proof_document')) {
            return [];
        }

        $files = $request->file('own_insurance_proof_document');

        return collect(is_array($files) ? $files : [$files])
            ->filter(fn ($file) => $file && $file->isValid())
            ->values()
            ->all();
    }

    private function uploadInsuranceProofFile($file): string
    {
        $directory = 'uploads/insurance_documents';
        $path = public_path($directory);

        if (! file_exists($path)) {
            mkdir($path, 0755, true);
        }

        $filename = time().'_'.$file->getClientOriginalName();

        if (! $file->move($path, $filename)) {
            throw new \Exception('Failed to upload insurance proof document');
        }

        return $filename;
    }

    private function deleteInsuranceProofFile(string $filename): void
    {
        $filePath = public_path('uploads/insurance_documents/'.$filename);

        if (File::exists($filePath)) {
            File::delete($filePath);
        }
    }

    private function syncTerminatedCarAvailability(Agreement $agreement): void
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

    public function payCollection(Request $request, Agreement $agreement, $collectionId)
    {
        $collection = $agreement->collections()->findOrFail($collectionId);

        $validated = $request->validate([
            'amount_paid' => 'required|numeric|min:0|max:'.$collection->remaining_amount,
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        try {
            $collection->markAsPaid($validated['amount_paid'], $validated['payment_date']);

            if ($validated['notes']) {
                $collection->update(['notes' => $validated['notes']]);
            }

            return redirect()->back()->with('success', 'Payment recorded successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error recording payment: '.$e->getMessage());
        }
    }

    public function generatePDF(Agreement $agreement)
    {
        try {
            [$pdf, $filename] = $this->makeAgreementPdf($agreement);

            return $pdf->download($filename);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to generate PDF: '.$e->getMessage());
        }
    }

    public function previewPDF(Agreement $agreement)
    {
        try {
            [$pdf, $filename] = $this->makeAgreementPdf($agreement);

            return $pdf->stream($filename);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to preview PDF: '.$e->getMessage());
        }
    }

    public function permissionLetterPDF(Agreement $agreement)
    {
        $tenant = Auth::user()->currentTenant();

        if ($agreement->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access');
        }

        try {
            [$pdf, $filename] = $this->makePermissionLetterPdf($agreement);

            return $pdf->stream($filename);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to generate permission letter: '.$e->getMessage());
        }
    }

    /**
     * @return array{0: \Barryvdh\DomPDF\PDF, 1: string}
     */
    private function makePermissionLetterPdf(Agreement $agreement): array
    {
        $agreement->load([
            'company', 'driver', 'car', 'car.carModel', 'car.insurances.status', 'car.insurances.insuranceProvider',
        ]);

        $activeCarInsurance = $agreement->car?->currentActiveInsurance();

        $policyNumber = $agreement->using_own_insurance
            ? $agreement->own_insurance_policy_number
            : optional($activeCarInsurance?->insuranceProvider)->policy_number;

        $insuranceExpiryDate = $agreement->using_own_insurance
            ? $agreement->own_insurance_end_date
            : $activeCarInsurance?->expiry_date;

        $letterMeta = app(PermissionLetterService::class)->resolveLetterMeta($agreement->company);

        $data = [
            'agreement' => $agreement,
            'company' => $agreement->company,
            'driver' => $agreement->driver,
            'car' => $agreement->car,
            'policyNumber' => $policyNumber,
            'letterDate' => $agreement->start_date->format('d.m.Y'),
            'contractEndingDate' => $insuranceExpiryDate?->format('d.m.Y') ?? '—',
            'letterMeta' => $letterMeta,
        ];

        $pdf = PDF::loadView($this->dir.'.permission_letter_pdf', $data);
        $pdf->setPaper('A4', 'portrait');

        $filename = 'Permission_Letter_'.$agreement->id.'_'.str_replace(' ', '_', $agreement->driver->full_name).'.pdf';

        return [$pdf, $filename];
    }

    /**
     * @return array{0: \Barryvdh\DomPDF\PDF, 1: string}
     */
    private function makeAgreementPdf(Agreement $agreement): array
    {
        $agreement->load([
            'company', 'driver', 'car', 'car.carModel', 'status', 'insuranceProvider',
        ]);

        $data = [
            'agreement' => $agreement,
            'driver' => $agreement->driver,
            'car' => $agreement->car,
            'company' => $agreement->company,
            'currentDate' => Carbon::now()->format('d/m/Y'),
        ];

        $pdf = PDF::loadView($this->dir.'.agreement_pdf', $data);
        $pdf->setPaper('A4', 'portrait');

        $filename = 'Agreement_'.$agreement->id.'_'.str_replace(' ', '_', $agreement->driver->full_name).'.pdf';

        return [$pdf, $filename];
    }

    /**
     * ✅ MAIN: Send agreement for e-signature (Smart routing)
     */
    public function sendForESignature(Agreement $agreement)
    {
        $tenant = Auth::user()->currentTenant();

        if ($agreement->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access');
        }

        if ($agreement->hellosign_request_id || $agreement->hellosign_status === 'pending') {
            return redirect()->back()
                ->with('warning', 'Agreement already sent for signature.');
        }

        if (! $agreement->driver || ! $agreement->driver->email) {
            return redirect()->back()
                ->with('error', 'Driver email is required for e-signature.');
        }

        try {
            // ✅ Get tenant's settings
            $settings = $agreement->getSettings();

            // ✅ Route based on provider
            if ($settings && $settings->esign_provider === 'hellosign') {
                return $this->sendViaHelloSign($agreement);
            } else {
                return $this->sendViaCustomSigning($agreement);
            }

        } catch (\Exception $e) {
            \Log::error('E-Signature Error: '.$e->getMessage());

            return redirect()->back()
                ->with('error', 'Error: '.$e->getMessage());
        }
    }

    /**
     * ✅ Send via HelloSign
     */
    protected function sendViaHelloSign(Agreement $agreement)
    {
        $pdfPath = $this->generatePDFForESign($agreement);

        if (! $pdfPath) {
            throw new \Exception('Failed to generate PDF');
        }

        $helloSignService = new \App\Services\HelloSignService;
        $result = $helloSignService->sendAgreementForSignature($agreement, $pdfPath);

        if ($result['success']) {
            $agreement->update([
                'hellosign_request_id' => $result['request_id'],
                'hellosign_status' => 'pending',
                'esign_sent_at' => now(),
            ]);

            return redirect()->route('agreements.show', $agreement)
                ->with('success', '✅ Agreement sent via HelloSign! Driver will receive email.');
        }

        return redirect()->back()
            ->with('error', 'HelloSign Error: '.($result['error'] ?? 'Unknown error'));
    }

    /**
     * ✅ Send via Custom Signing
     */
    protected function sendViaCustomSigning(Agreement $agreement)
    {
        $customSigningService = new \App\Services\CustomSigningService;
        $result = $customSigningService->sendForSigning($agreement);

        if ($result['success']) {
            return redirect()->route('agreements.show', $agreement)
                ->with('success', 'Agreement sent for signature! Driver will receive email with signing link.');
        }

        return redirect()->back()
            ->with('error', 'Custom Signing Error: '.($result['error'] ?? 'Unknown error'));
    }

    /**
     * ✅ Generate PDF for e-signature
     */
    private function generatePDFForESign(Agreement $agreement)
    {
        try {
            $agreement->load(['company', 'driver', 'car', 'car.carModel', 'status']);

            $data = [
                'agreement' => $agreement,
                'driver' => $agreement->driver,
                'car' => $agreement->car,
                'company' => $agreement->company,
                'currentDate' => Carbon::now()->format('d/m/Y'),
            ];

            $pdf = PDF::loadView('backend.agreements.agreement_pdf', $data);
            $pdf->setPaper('A4', 'portrait');

            // Create directory
            $directory = public_path('uploads/agreements/temp');
            if (! file_exists($directory)) {
                \File::makeDirectory($directory, 0755, true, true);
            }

            $fileName = "agreement_{$agreement->id}_esign.pdf";
            $fullPath = "{$directory}/{$fileName}";
            $relativePath = "uploads/agreements/temp/{$fileName}";

            // Save PDF
            $pdf->save($fullPath);

            if (file_exists($fullPath)) {
                return $relativePath;
            }

            throw new \Exception('PDF file not created');
        } catch (\Exception $e) {
            \Log::error('PDF Generation Error: '.$e->getMessage());

            return false;
        }
    }

    /**
     * ✅ Check e-signature status (Works for both providers)
     */
    public function checkESignStatus(Agreement $agreement)
    {
        $tenant = Auth::user()->currentTenant();

        if ($agreement->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access');
        }

        $settings = $agreement->getSettings();

        // ✅ Route based on provider
        if ($settings && $settings->esign_provider === 'hellosign' && $agreement->hellosign_request_id) {
            return $this->checkHelloSignStatus($agreement);
        } else {
            return $this->checkCustomSigningStatus($agreement);
        }
    }

    /**
     * ✅ Check HelloSign status
     */
    protected function checkHelloSignStatus(Agreement $agreement)
    {
        if (! $agreement->hellosign_request_id) {
            return redirect()->back()
                ->with('error', 'No signature request found.');
        }

        try {
            $helloSignService = new \App\Services\HelloSignService;

            \Log::info('Checking HelloSign status', [
                'agreement_id' => $agreement->id,
                'request_id' => $agreement->hellosign_request_id,
            ]);

            $status = $helloSignService->getSignatureStatus($agreement->hellosign_request_id);

            if (! $status['success']) {
                return redirect()->back()
                    ->with('error', 'Failed to check status: '.($status['error'] ?? 'Unknown error'));
            }

            // ✅ Update status
            $agreement->update(['hellosign_status' => $status['status']]);

            // ✅ If complete and no document yet, download it
            if ($status['is_complete'] && ! $agreement->esign_document_path) {

                \Log::info('Document is complete, downloading...', [
                    'agreement_id' => $agreement->id,
                ]);

                $download = $helloSignService->downloadSignedPDF(
                    $agreement->hellosign_request_id,
                    $agreement->id
                );

                if ($download['success']) {
                    $agreement->update([
                        'hellosign_status' => 'signed',
                        'esign_document_path' => $download['path'],
                        'esign_completed_at' => now(),
                    ]);

                    // ✅ Delete temporary PDF
                    $tempPath = public_path("uploads/agreements/temp/agreement_{$agreement->id}_esign.pdf");
                    if (file_exists($tempPath)) {
                        unlink($tempPath);
                    }

                    return redirect()->back()
                        ->with('success', '✅ Agreement is fully signed! Signed document downloaded successfully.');
                } else {
                    return redirect()->back()
                        ->with('warning', 'Agreement is signed but failed to download PDF: '.($download['error'] ?? 'Unknown error'));
                }
            }

            // ✅ If already downloaded
            if ($status['is_complete'] && $agreement->esign_document_path) {
                return redirect()->back()
                    ->with('info', '✅ Agreement is already signed. Document is available below.');
            }

            // ✅ Still pending
            if ($status['status'] === 'pending') {
                return redirect()->back()
                    ->with('info', '⏳ Signature is still pending. Driver needs to sign the document.');
            }

            // ✅ Other status
            return redirect()->back()
                ->with('info', 'Current Status: '.ucfirst($status['status']));

        } catch (\Exception $e) {
            \Log::error('HelloSign Status Check Error: '.$e->getMessage());

            return redirect()->back()
                ->with('error', 'Error checking status: '.$e->getMessage());
        }
    }

    /**
     * ✅ Check Custom Signing status
     */
    protected function checkCustomSigningStatus(Agreement $agreement)
    {
        $token = $agreement->getLatestSignatureToken();

        if (! $token) {
            return redirect()->back()
                ->with('error', 'No signing request found');
        }

        if ($token->isSigned()) {
            return redirect()->back()
                ->with('success', '✅ Agreement is signed! Document available below.');
        }

        if ($token->isExpired()) {
            return redirect()->back()
                ->with('warning', '⚠️ Signing link has expired. Please resend.');
        }

        return redirect()->back()
            ->with('info', '⏳ Signature is pending. Waiting for driver to sign.');
    }

    /**
     * ✅ Resend signature reminder
     */
    public function resendESignature(Agreement $agreement)
    {
        $tenant = Auth::user()->currentTenant();

        if ($agreement->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access');
        }

        if ($agreement->hellosign_status === 'signed') {
            return redirect()->back()
                ->with('warning', 'This agreement is already signed.');
        }

        if ($agreement->hellosign_status !== 'pending') {
            return redirect()->back()
                ->with('error', 'Cannot send reminder for this agreement.');
        }

        try {
            $settings = $agreement->getSettings();

            // ✅ Route based on provider
            if ($settings && $settings->esign_provider === 'hellosign' && $agreement->hellosign_request_id) {
                return $this->resendHelloSignReminder($agreement);
            } else {
                return $this->resendCustomSigningLink($agreement);
            }

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error: '.$e->getMessage());
        }
    }

    /**
     * ✅ Resend HelloSign reminder
     */
    protected function resendHelloSignReminder(Agreement $agreement)
    {
        try {
            $helloSignService = new \App\Services\HelloSignService;
            $result = $helloSignService->sendReminder(
                $agreement->hellosign_request_id,
                $agreement->driver->email
            );

            if ($result['success']) {
                return redirect()->back()
                    ->with('success', 'Signature reminder sent to driver successfully!');
            }

            // ✅ Handle "already signed" error
            if (isset($result['error']) && strpos($result['error'], 'already signed') !== false) {
                return redirect()->back()
                    ->with('info', 'Driver has already signed! Click "Check Status" button to download the signed document.');
            }

            return redirect()->back()
                ->with('error', 'Failed to send reminder: '.($result['error'] ?? 'Unknown error'));

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error: '.$e->getMessage());
        }
    }

    /**
     * ✅ Resend Custom Signing link
     */
    protected function resendCustomSigningLink(Agreement $agreement)
    {
        try {
            $customSigningService = new \App\Services\CustomSigningService;
            $result = $customSigningService->resendSigningLink($agreement);

            if ($result['success']) {
                return redirect()->back()
                    ->with('success', 'Signing link resent successfully!');
            }

            return redirect()->back()
                ->with('error', 'Failed to resend: '.($result['error'] ?? 'Unknown error'));

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error: '.$e->getMessage());
        }
    }

    /**
     * ✅ View signed document
     */
    public function viewSignedDocument(Agreement $agreement)
    {
        $tenant = Auth::user()->currentTenant();

        if ($agreement->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access');
        }

        if (! $agreement->esign_document_path) {
            abort(404, 'Signed document not found');
        }

        $fullPath = public_path($agreement->esign_document_path);

        if (! file_exists($fullPath)) {
            abort(404, 'Document file not found');
        }

        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="signed_agreement_'.$agreement->id.'.pdf"',
        ]);
    }

    /**
     * ✅ HelloSign Webhook Handler
     */
    public function helloSignWebhook(Request $request)
    {
        try {
            $event = $request->json()->all();

            \Log::info('HelloSign Webhook Received:', $event);

            $eventType = $event['event']['event_type'] ?? null;
            $requestId = $event['signature_request']['signature_request_id'] ?? null;

            if (! $requestId) {
                return response()->json(['error' => 'Invalid webhook data'], 400);
            }

            $agreement = Agreement::where('hellosign_request_id', $requestId)->first();

            if (! $agreement) {
                \Log::error('Agreement not found for HelloSign request: '.$requestId);

                return response()->json(['error' => 'Agreement not found'], 404);
            }

            // Handle events
            switch ($eventType) {
                case 'signature_request_signed':
                case 'signature_request_all_signed':
                    // Download signed PDF
                    $helloSignService = new \App\Services\HelloSignService;
                    $download = $helloSignService->downloadSignedPDF($requestId, $agreement->id);

                    if ($download['success']) {
                        $agreement->update([
                            'hellosign_status' => 'signed',
                            'esign_document_path' => $download['path'],
                            'esign_completed_at' => now(),
                        ]);

                        \Log::info('Agreement signed via webhook: '.$agreement->id);
                    }
                    break;

                case 'signature_request_declined':
                    $agreement->update(['hellosign_status' => 'declined']);
                    \Log::info('Agreement declined: '.$agreement->id);
                    break;

                case 'signature_request_canceled':
                    $agreement->update(['hellosign_status' => 'cancelled']);
                    \Log::info('Agreement cancelled: '.$agreement->id);
                    break;
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            \Log::error('Webhook Error: '.$e->getMessage());

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
