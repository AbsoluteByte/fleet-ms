<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\PhvlArchiveService;
use App\Models\Car;
use App\Models\CarModel;
use App\Models\CarMot;
use App\Models\CarSornHistory;
use App\Models\CarPhv;
use App\Models\CarRoadTax;
use App\Models\Company;
use App\Models\Counsel;
use App\Models\InsuranceProvider;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CarController extends Controller
{
    protected $url = 'cars.';

    protected $dir = 'backend.cars.';

    protected $name = 'Cars';

    public function __construct()
    {
        $this->middleware('role:admin|manager|user');
        view()->share('url', $this->url);
        view()->share('dir', $this->dir);
        view()->share('singular', Str::singular($this->name));
        view()->share('plural', Str::plural($this->name));
    }

    // ✅ Updated Index
    public function index()
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found! Please contact administrator.');
        }

        $cars = Car::where('tenant_id', $tenant->id)
            ->with([
                'company',
                'carModel',
                'phvs.counsel',
                'insurances.status',
                'services',
                'reservations',
                'agreements',
            ])
            ->latest()
            ->get();

        return view($this->dir.'index', compact('cars'));
    }

    // ✅ Updated Create
    public function create()
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }

        $model = new Car;

        // ✅ Filter by tenant
        $companies = Company::where('tenant_id', $tenant->id)->get();
        $carModels = CarModel::where('tenant_id', $tenant->id)->get();
        $counsels = Counsel::where('tenant_id', $tenant->id)->get();
        $insuranceProviders = InsuranceProvider::where('tenant_id', $tenant->id)->get();
        $this->ensureInsuranceAppliedStatus();
        $statuses = Status::where('type', 'insurance')->get();

        return view($this->dir.'create', compact('model', 'companies', 'carModels', 'counsels', 'insuranceProviders', 'statuses'));
    }

    // ✅ Updated Store
    public function store(Request $request)
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->back()
                ->with('error', 'No active company found!');
        }
        $this->ensureInsuranceAppliedStatus();

        // Build validation rules dynamically
        $rules = [
            'company_id' => 'required|exists:companies,id',
            'car_model_id' => 'required|exists:car_models,id',
            'registration' => 'required|string|unique:cars',
            'color' => 'required|string',
            'vin' => 'required|string',
            'v5_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'manufacture_year' => 'required|integer|min:1900|max:'.date('Y'),
            'registration_year' => 'required|integer|min:1900|max:'.date('Y'),
            'purchase_date' => 'required|date',
            'purchase_price' => 'required|numeric|min:0',
            'purchase_type' => 'required|in:imported,uk',
            'seller_name' => 'nullable|string|max:255',
            'seller_notes' => 'nullable|string',
            'damaged_notes' => 'nullable|string',
            'phv_status' => 'nullable|in:need_to_apply,applied,phv_active',
            'phv_applied_date' => 'nullable|date',
            'log_book_applied' => 'nullable|boolean',
            'log_book_applied_date' => 'nullable|date',
            'old_log_book' => 'nullable|array',
            'old_log_book.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
            'available_from_date' => 'nullable|date',
            'reserve_car' => 'nullable|boolean',
            'reservation_customer_name' => 'required_if:reserve_car,1|nullable|string|max:255',
            'reservation_customer_phone' => 'nullable|string|max:50',
            'reservation_customer_email' => 'nullable|email|max:255',
            'reservation_date' => 'nullable|date',
            'reservation_available_from_date' => 'nullable|date',
            'reservation_terms_conditions' => 'nullable|string',

            'mots.*.expiry_date' => 'nullable|date',
            'mots.*.amount' => 'nullable|numeric|min:0',
            'mots.*.term' => 'nullable|string',
            'mots.*.document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',

            'road_taxes.*.start_date' => 'nullable|date',
            'road_taxes.*.term' => 'nullable|string',
            'road_taxes.*.amount' => 'nullable|numeric|min:0',

            'phvs.*.counsel_id' => 'nullable|exists:counsels,id',
            'phvs.*.amount' => 'nullable|numeric|min:0',
            'phvs.*.start_date' => 'nullable|date',
            'phvs.*.expiry_date' => 'nullable|date',
            'phvs.*.notify_before_expiry' => 'nullable|integer|min:1',
            'phvs.*.document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'phvs.*.phv_applied' => 'nullable|boolean',
            'phvs.*.phv_applied_date' => 'nullable|date',
        ];

        if ($request->has('has_insurance')) {
            $activeInsuranceStatusId = $this->insuranceStatusIdByName('Active');
            $appliedInsuranceStatusId = $this->insuranceStatusIdByName('Applied');
            $cancelledInsuranceStatusIds = $this->insuranceCancelledStatusIds();
            $rules = array_merge($rules, [
                'insurance_provider_id' => 'required|exists:insurance_providers,id',
                'insurance_start_date' => [
                    Rule::requiredIf(fn () => (int) $request->input('insurance_status_id') === $activeInsuranceStatusId),
                    'nullable',
                    'date',
                ],
                'insurance_expiry_date' => [
                    Rule::requiredIf(fn () => (int) $request->input('insurance_status_id') === $activeInsuranceStatusId),
                    'nullable',
                    'date',
                ],
                'insurance_document' => [
                    Rule::requiredIf(fn () => in_array((int) $request->input('insurance_status_id'), $cancelledInsuranceStatusIds, true)),
                    'nullable',
                    'file',
                    'mimes:pdf,jpg,jpeg,png',
                    'max:10240',
                ],
                'insurance_notify_before_expiry' => [
                    Rule::requiredIf(fn () => (int) $request->input('insurance_status_id') === $activeInsuranceStatusId),
                    'nullable',
                    'integer',
                    'min:1',
                ],
                'insurance_status_id' => 'required|exists:statuses,id',
                'insurance_applied_date' => [
                    Rule::requiredIf(fn () => (int) $request->input('insurance_status_id') === $appliedInsuranceStatusId),
                    'nullable',
                    'date',
                ],
                'insurance_canceled_date' => [
                    Rule::requiredIf(fn () => in_array((int) $request->input('insurance_status_id'), $cancelledInsuranceStatusIds, true)),
                    'nullable',
                    'date',
                ],
            ]);
        }

        $validated = $request->validate($rules);
        $this->validateMotPhvDocuments($request, null);

        try {
            $car = DB::transaction(function () use ($validated, $request, $tenant) {
                if ($request->hasFile('v5_document')) {
                    $validated['v5_document'] = $this->uploadFile($request->file('v5_document'), 'uploads/cars');
                }

                $carData = $this->carMassAssignmentFromValidated($validated, $request, null);
                $carData = $this->mergeLogBookCarData($request, $carData, null);
                $carData['tenant_id'] = $tenant->id;
                $carData['createdBy'] = Auth::id();
                $car = Car::create($carData);

                // Store MOTs
                if ($request->has('mots')) {
                    foreach ($request->input('mots') as $index => $motData) {
                        if (! $this->historyRowHasValues($motData, ['expiry_date', 'amount', 'term'])) {
                            continue;
                        }

                        if ($request->hasFile("mots.{$index}.document")) {
                            $motData['document'] = $this->uploadFile(
                                $request->file("mots.{$index}.document"),
                                'uploads/cars/mot_documents'
                            );
                        }
                        $car->mots()->create($motData);
                    }
                }

                // Store Road Taxes
                if ($request->has('road_taxes')) {
                    foreach ($request->input('road_taxes') as $roadTaxData) {
                        if (! $this->historyRowHasValues($roadTaxData, ['start_date', 'term', 'amount'])) {
                            continue;
                        }

                        $car->roadTaxes()->create($roadTaxData);
                    }
                }

                // Store PHVs
                $newFuturePhvAdded = false;
                $lastNewPhv = null;
                if ($request->has('phvs')) {
                    foreach ($request->input('phvs') as $index => $phvData) {
                        if (! $this->historyRowHasValues($phvData, ['counsel_id', 'amount', 'start_date', 'expiry_date', 'notify_before_expiry'])) {
                            continue;
                        }

                        if ($request->hasFile("phvs.{$index}.document")) {
                            $phvData['document'] = $this->uploadFile(
                                $request->file("phvs.{$index}.document"),
                                'uploads/cars/phv_documents'
                            );
                        }
                        $phvData = $this->mergePhvAppliedData($phvData, null);
                        $lastNewPhv = $car->phvs()->create($phvData);
                        $newFuturePhvAdded = $newFuturePhvAdded || $this->hasFuturePhvExpiry($phvData);
                    }
                }

                $this->syncCarPhvStatus($request, $car, $newFuturePhvAdded);
                $this->syncReservation($request, $car, $tenant);

                if ($lastNewPhv) {
                    PhvlArchiveService::tryArchiveAfterNewPhv(
                        $car->fresh(['phvlProgress', 'phvs.counsel']),
                        $lastNewPhv
                    );
                }

                // Store Insurance
                if ($request->has('has_insurance')) {
                    $selectedInsuranceStatusId = (int) $validated['insurance_status_id'];
                    $isAppliedInsuranceStatus = $selectedInsuranceStatusId === $this->insuranceStatusIdByName('Applied');
                    $isCancelledInsuranceStatus = in_array($selectedInsuranceStatusId, $this->insuranceCancelledStatusIds(), true);
                    $appliedDate = $validated['insurance_applied_date'] ?? null;
                    $canceledDate = $validated['insurance_canceled_date'] ?? null;
                    $insuranceData = [
                        'tenant_id' => $tenant->id,
                        'insurance_provider_id' => $validated['insurance_provider_id'],
                        'start_date' => $isAppliedInsuranceStatus ? null : ($isCancelledInsuranceStatus ? null : $validated['insurance_start_date']),
                        'expiry_date' => $isAppliedInsuranceStatus ? null : ($isCancelledInsuranceStatus ? null : $validated['insurance_expiry_date']),
                        'applied_date' => $isAppliedInsuranceStatus ? $appliedDate : null,
                        'canceled_date' => $isCancelledInsuranceStatus ? $canceledDate : null,
                        'notify_before_expiry' => ($isAppliedInsuranceStatus || $isCancelledInsuranceStatus) ? null : $validated['insurance_notify_before_expiry'],
                        'status_id' => $validated['insurance_status_id'],
                    ];

                    if ($request->hasFile('insurance_document')) {
                        $insuranceData['insurance_document'] = $this->uploadFile(
                            $request->file('insurance_document'),
                            'uploads/cars/insurance_documents'
                        );
                    }

                    $car->insurances()->create($insuranceData);
                }

                return $car;
            });

            return redirect()->route($this->url.'index')
                ->with('success', 'Car added successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating car: '.$e->getMessage());
        }
    }

    // ✅ Updated Show
    public function show(Car $car)
    {
        $tenant = Auth::user()->currentTenant();

        // ✅ Check ownership
        if ($car->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to this car');
        }

        $car->load([
            'company',
            'carModel',
            'mots',
            'roadTaxes',
            'phvs.counsel',
            'phvs.phvAppliedBy',
            'insurances.insuranceProvider',
            'insurances.status',
            'logBookAppliedBy',
            'services.createdBy',
            'reservations.createdBy',
            'agreements',
            'statusHistories.changedBy',
            'statusHistories.reservation',
            'statusHistories.vehicleSwap',
        ]);
        $this->sortCarHistoryRelations($car);

        return view($this->dir.'show', compact('car'));
    }

    // ✅ Updated Edit
    public function edit($id)
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }

        $model = Car::where('tenant_id', $tenant->id)
            ->with(['mots', 'roadTaxes', 'phvs.counsel', 'phvs.phvAppliedBy', 'insurances.insuranceProvider', 'insurances.status', 'sornAppliedBy', 'sornHistories.startedBy', 'sornHistories.endedBy', 'services', 'reservations'])
            ->findOrFail($id);
        $this->sortCarHistoryRelations($model);

        // ✅ Filter by tenant
        $companies = Company::where('tenant_id', $tenant->id)->get();
        $carModels = CarModel::where('tenant_id', $tenant->id)->get();
        $counsels = Counsel::where('tenant_id', $tenant->id)->get();
        $insuranceProviders = InsuranceProvider::where('tenant_id', $tenant->id)->get();
        $this->ensureInsuranceAppliedStatus();
        $statuses = Status::where('type', 'insurance')->get();

        return view($this->dir.'edit', compact('model', 'companies', 'carModels', 'counsels', 'insuranceProviders', 'statuses'));
    }

    // ✅ Updated Update
    public function update(Request $request, Car $car)
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->back()
                ->with('error', 'No active company found!');
        }

        if ($car->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to this car.');
        }

        $latestInsuranceBeforeUpdate = $car->insurances()
            ->with('status')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();
        $this->ensureInsuranceAppliedStatus();

        $rules = [
            'company_id' => 'required|exists:companies,id',
            'car_model_id' => 'required|exists:car_models,id',
            'registration' => 'required|string|unique:cars,registration,'.$car->id,
            'color' => 'required|string',
            'vin' => 'required|string',
            'v5_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'manufacture_year' => 'required|integer|min:1900|max:'.date('Y'),
            'registration_year' => 'required|integer|min:1900|max:'.date('Y'),
            'purchase_date' => 'required|date',
            'purchase_price' => 'required|numeric|min:0',
            'purchase_type' => 'required|in:imported,uk',
            'seller_name' => 'nullable|string|max:255',
            'seller_notes' => 'nullable|string',
            'damaged_notes' => 'nullable|string',
            'phv_status' => 'nullable|in:need_to_apply,applied,phv_active',
            'phv_applied_date' => 'nullable|date',
            'log_book_applied' => 'nullable|boolean',
            'log_book_applied_date' => 'nullable|date',
            'old_log_book' => 'nullable|array',
            'old_log_book.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
            'available_from_date' => 'nullable|date',
            'reserve_car' => 'nullable|boolean',
            'reservation_customer_name' => 'required_if:reserve_car,1|nullable|string|max:255',
            'reservation_customer_phone' => 'nullable|string|max:50',
            'reservation_customer_email' => 'nullable|email|max:255',
            'reservation_date' => 'nullable|date',
            'reservation_available_from_date' => 'nullable|date',
            'reservation_terms_conditions' => 'nullable|string',

            'mots.*.id' => 'nullable|exists:car_mots,id',
            'mots.*.expiry_date' => 'nullable|date',
            'mots.*.amount' => 'nullable|numeric|min:0',
            'mots.*.term' => 'nullable|string',
            'mots.*.document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',

            'road_taxes.*.start_date' => 'nullable|date',
            'road_taxes.*.term' => 'nullable|string',
            'road_taxes.*.amount' => 'nullable|numeric|min:0',

            'phvs.*.id' => 'nullable|exists:car_phvs,id',
            'phvs.*.counsel_id' => 'nullable|exists:counsels,id',
            'phvs.*.amount' => 'nullable|numeric|min:0',
            'phvs.*.start_date' => 'nullable|date',
            'phvs.*.expiry_date' => 'nullable|date',
            'phvs.*.notify_before_expiry' => 'nullable|integer|min:1',
            'phvs.*.document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'phvs.*.phv_applied' => 'nullable|boolean',
            'phvs.*.phv_applied_date' => 'nullable|date',
        ];

        if ($request->has('has_insurance')) {
            $activeInsuranceStatusId = $this->insuranceStatusIdByName('Active');
            $appliedInsuranceStatusId = $this->insuranceStatusIdByName('Applied');
            $cancelledInsuranceStatusIds = $this->insuranceCancelledStatusIds();
            $rules = array_merge($rules, [
                'insurance_provider_id' => 'required|exists:insurance_providers,id',
                'insurance_start_date' => [
                    Rule::requiredIf(fn () => (int) $request->input('insurance_status_id') === $activeInsuranceStatusId),
                    'nullable',
                    'date',
                ],
                'insurance_expiry_date' => [
                    Rule::requiredIf(fn () => (int) $request->input('insurance_status_id') === $activeInsuranceStatusId),
                    'nullable',
                    'date',
                ],
                'insurance_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
                'insurance_notify_before_expiry' => [
                    Rule::requiredIf(fn () => (int) $request->input('insurance_status_id') === $activeInsuranceStatusId),
                    'nullable',
                    'integer',
                    'min:1',
                ],
                'insurance_status_id' => 'required|exists:statuses,id',
                'insurance_applied_date' => [
                    Rule::requiredIf(fn () => (int) $request->input('insurance_status_id') === $appliedInsuranceStatusId),
                    'nullable',
                    'date',
                ],
                'insurance_canceled_date' => [
                    Rule::requiredIf(fn () => in_array((int) $request->input('insurance_status_id'), $cancelledInsuranceStatusIds, true)),
                    'nullable',
                    'date',
                ],
            ]);
        }

        $validated = $request->validate($rules);
        $currentInsuranceStatusName = strtolower(trim((string) optional(optional($latestInsuranceBeforeUpdate)->status)->name));
        if (
            $request->has('has_insurance')
            && $latestInsuranceBeforeUpdate
            && $currentInsuranceStatusName === 'active'
            && (int) $validated['insurance_status_id'] === $this->insuranceStatusIdByName('Applied')
        ) {
            throw ValidationException::withMessages([
                'insurance_status_id' => 'Applied status is not allowed when current insurance status is Active.',
            ]);
        }
        if (
            $request->has('has_insurance')
            && in_array((int) $validated['insurance_status_id'], $this->insuranceCancelledStatusIds(), true)
            && (
                ! $latestInsuranceBeforeUpdate
                || $currentInsuranceStatusName !== 'active'
            )
        ) {
            throw ValidationException::withMessages([
                'insurance_status_id' => 'Canceled status is only allowed when current insurance status is Active.',
            ]);
        }
        if ($request->has('has_insurance') && in_array((int) $validated['insurance_status_id'], $this->insuranceCancelledStatusIds(), true)) {
            $latestInsuranceWithDocument = $car->insurances()
                ->whereNotNull('insurance_document')
                ->orderByDesc('expiry_date')
                ->orderByDesc('id')
                ->first();
            if (! $request->hasFile('insurance_document') && ! $latestInsuranceWithDocument) {
                throw ValidationException::withMessages([
                    'insurance_document' => 'Insurance document is required before insurance cancelation.',
                ]);
            }
        }

        $this->validateMotPhvDocuments($request, $car);

        try {
            $updatedCar = DB::transaction(function () use ($validated, $request, $car, $tenant) {

                if ($request->hasFile('v5_document')) {
                    $oldV5Document = $car->v5_document;
                    $validated['v5_document'] = $this->uploadFile(
                        $request->file('v5_document'),
                        'uploads/cars'
                    );
                    if ($oldV5Document) {
                        $this->deleteFile($oldV5Document, 'uploads/cars');
                    }
                }

                $carData = $this->carMassAssignmentFromValidated($validated, $request, $car);
                $carData = $this->mergeLogBookCarData($request, $carData, $car);
                $carData['tenant_id'] = $tenant->id;
                $carData['updatedBy'] = Auth::id();
                $car->update($carData);

                $car->load(['mots', 'phvs', 'insurances']);
                $this->sortCarHistoryRelations($car);

                // ==================== Update MOTs ====================
                $existingMots = $car->mots->keyBy('id');
                $processedMotIds = [];

                if ($request->has('mots')) {
                    foreach ($request->input('mots') as $index => $motData) {
                        $motId = $motData['id'] ?? null;
                        $existingMot = $motId ? $existingMots->get($motId) : null;

                        if (! $existingMot && ! $this->historyRowHasValues($motData, ['expiry_date', 'amount', 'term'])) {
                            continue;
                        }

                        if ($request->hasFile("mots.{$index}.document")) {
                            $motData['document'] = $this->uploadFile(
                                $request->file("mots.{$index}.document"),
                                'uploads/cars/mot_documents'
                            );

                            if ($existingMot && $existingMot->document) {
                                $this->deleteFile($existingMot->document, 'uploads/cars/mot_documents');
                            }
                        } elseif ($existingMot && $existingMot->document) {
                            $motData['document'] = $existingMot->document;
                        }

                        unset($motData['id']);

                        if ($existingMot) {
                            $existingMot->update($motData);
                            $processedMotIds[] = $existingMot->id;
                        } else {
                            $newMot = $car->mots()->create($motData);
                            $processedMotIds[] = $newMot->id;
                        }
                    }
                }

                $motsToDelete = $existingMots->keys()->diff($processedMotIds);
                foreach ($motsToDelete as $motId) {
                    $motToDelete = $existingMots->get($motId);
                    if ($motToDelete->document) {
                        $this->deleteFile($motToDelete->document, 'uploads/cars/mot_documents');
                    }
                    $motToDelete->delete();
                }

                // ==================== Update Road Taxes ====================
                $car->roadTaxes()->delete();
                if ($request->has('road_taxes')) {
                    foreach ($request->input('road_taxes') as $roadTaxData) {
                        if (! $this->historyRowHasValues($roadTaxData, ['start_date', 'term', 'amount'])) {
                            continue;
                        }

                        $car->roadTaxes()->create($roadTaxData);
                    }
                }

                $car->refresh();
                $this->syncSornAfterRoadTaxesSaved($car);

                // ==================== Update PHVs ====================
                $existingPhvs = $car->phvs->keyBy('id');
                $processedPhvIds = [];
                $newFuturePhvAdded = false;
                $lastNewPhv = null;

                if ($request->has('phvs')) {
                    foreach ($request->input('phvs') as $index => $phvData) {
                        $phvId = $phvData['id'] ?? null;
                        $existingPhv = $phvId ? $existingPhvs->get($phvId) : null;

                        if (! $existingPhv && ! $this->historyRowHasValues($phvData, ['counsel_id', 'amount', 'start_date', 'expiry_date', 'notify_before_expiry'])) {
                            continue;
                        }

                        if ($request->hasFile("phvs.{$index}.document")) {
                            $phvData['document'] = $this->uploadFile(
                                $request->file("phvs.{$index}.document"),
                                'uploads/cars/phv_documents'
                            );

                            if ($existingPhv && $existingPhv->document) {
                                $this->deleteFile($existingPhv->document, 'uploads/cars/phv_documents');
                            }
                        } elseif ($existingPhv && $existingPhv->document) {
                            $phvData['document'] = $existingPhv->document;
                        }

                        $phvData = $this->mergePhvAppliedData($phvData, $existingPhv);
                        unset($phvData['id']);

                        if ($existingPhv) {
                            $existingPhv->update($phvData);
                            $processedPhvIds[] = $existingPhv->id;
                        } else {
                            $lastNewPhv = $car->phvs()->create($phvData);
                            $processedPhvIds[] = $lastNewPhv->id;
                            $newFuturePhvAdded = $newFuturePhvAdded || $this->hasFuturePhvExpiry($phvData);
                        }
                    }
                }

                $phvsToDelete = $existingPhvs->keys()->diff($processedPhvIds);
                foreach ($phvsToDelete as $phvId) {
                    $phvToDelete = $existingPhvs->get($phvId);
                    if ($phvToDelete->document) {
                        $this->deleteFile($phvToDelete->document, 'uploads/cars/phv_documents');
                    }
                    $phvToDelete->delete();
                }

                $this->syncCarPhvStatus($request, $car, $newFuturePhvAdded);
                $this->syncReservation($request, $car, $tenant);

                if ($lastNewPhv) {
                    PhvlArchiveService::tryArchiveAfterNewPhv(
                        $car->fresh(['phvlProgress', 'phvs.counsel']),
                        $lastNewPhv
                    );
                }

                // ==================== Update Insurance ====================
                $latestInsurance = $car->insurances
                    ->sortByDesc(fn ($insurance) => [optional($insurance->created_at)->timestamp ?? 0, $insurance->id])
                    ->first();

                if ($request->has('has_insurance')) {
                    $selectedInsuranceStatusId = (int) $validated['insurance_status_id'];
                    $isAppliedInsuranceStatus = $selectedInsuranceStatusId === $this->insuranceStatusIdByName('Applied');
                    $isCancelledInsuranceStatus = in_array($selectedInsuranceStatusId, $this->insuranceCancelledStatusIds(), true);
                    $appliedDate = $validated['insurance_applied_date'] ?? null;
                    $canceledDate = $validated['insurance_canceled_date'] ?? null;
                    $carriedStartDate = $latestInsurance && $latestInsurance->start_date
                        ? $latestInsurance->start_date->format('Y-m-d')
                        : null;
                    $carriedExpiryDate = $latestInsurance && $latestInsurance->expiry_date
                        ? $latestInsurance->expiry_date->format('Y-m-d')
                        : null;
                    $startDate = $isAppliedInsuranceStatus
                        ? null
                        : ($isCancelledInsuranceStatus ? $carriedStartDate : $validated['insurance_start_date']);
                    $expiryDate = $isCancelledInsuranceStatus
                        ? $carriedExpiryDate
                        : ($isAppliedInsuranceStatus ? null : $validated['insurance_expiry_date']);
                    $insuranceData = [
                        'tenant_id' => $tenant->id,
                        'insurance_provider_id' => $validated['insurance_provider_id'],
                        'start_date' => $startDate,
                        'expiry_date' => $expiryDate,
                        'applied_date' => $isAppliedInsuranceStatus
                            ? $appliedDate
                            : ($latestInsurance ? $latestInsurance->applied_date?->format('Y-m-d') : null),
                        'canceled_date' => $isCancelledInsuranceStatus ? $canceledDate : null,
                        'notify_before_expiry' => ($isAppliedInsuranceStatus || $isCancelledInsuranceStatus) ? null : $validated['insurance_notify_before_expiry'],
                        'status_id' => $validated['insurance_status_id'],
                    ];

                    $latestStatusName = strtolower(trim((string) optional(optional($latestInsurance)->status)->name));
                    $isLatestClosedCycle = in_array($latestStatusName, ['cancelled', 'canceled'], true);
                    $startingNewCycle = $latestInsurance
                        && $isLatestClosedCycle
                        && ! $isCancelledInsuranceStatus;
                    $updatingSameCycleRow = $latestInsurance && ! $startingNewCycle;

                    if ($request->hasFile('insurance_document')) {
                        $insuranceData['insurance_document'] = $this->uploadFile(
                            $request->file('insurance_document'),
                            'uploads/cars/insurance_documents'
                        );
                        if ($updatingSameCycleRow && $latestInsurance->insurance_document) {
                            $this->deleteFile($latestInsurance->insurance_document, 'uploads/cars/insurance_documents');
                        }
                    } elseif (! $startingNewCycle && $latestInsurance && $latestInsurance->insurance_document) {
                        $insuranceData['insurance_document'] = $latestInsurance->insurance_document;
                    }

                    if (! $latestInsurance || $startingNewCycle) {
                        $car->insurances()->create($insuranceData);
                    } else {
                        // Keep one row per insurance lifecycle; mutate the current row through status changes.
                        $latestInsurance->update($insuranceData);
                    }
                } else {
                    // Keep historical insurance records when insurance section is unchecked.
                    // New insurance can be added later by re-checking the checkbox.
                }

                return $car;
            });

            return redirect()
                ->route($this->url.'edit', $updatedCar)
                ->with('success', 'Car updated successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating car: '.$e->getMessage());
        }
    }

    // ✅ Updated Destroy
    public function destroy(Car $car)
    {
        $tenant = Auth::user()->currentTenant();

        // ✅ Check ownership
        if ($car->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access');
        }

        try {
            DB::transaction(function () use ($car) {
                $car->load(['mots', 'phvs', 'insurances', 'services']);
                $this->deleteCarFiles($car);
                $car->mots()->delete();
                $car->roadTaxes()->delete();
                $car->phvs()->delete();
                $car->insurances()->delete();
                $car->services()->delete();
                $car->loadMissing(['reservations', 'vehicleSwapsAsOld', 'vehicleSwapsAsNew']);
                foreach ($car->vehicleSwapsAsOld as $swap) {
                    $swap->delete();
                }
                foreach ($car->vehicleSwapsAsNew as $swap) {
                    $swap->delete();
                }
                foreach ($car->reservations as $reservation) {
                    $reservation->delete();
                }
                $car->delete();
            });

            return redirect()->route($this->url.'index')
                ->with('success', 'Car deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error deleting car: '.$e->getMessage());
        }
    }

    /**
     * Most recent first: MOT and PHV by expiry date, road tax by start date.
     */
    private function sortCarHistoryRelations(Car $car): void
    {
        $mots = $car->mots
            ->sortByDesc(function ($m) {
                return optional($m->expiry_date)->timestamp ?? 0;
            })
            ->values();
        $car->setRelation('mots', $mots);

        $roadTaxes = $car->roadTaxes
            ->sortByDesc(function ($r) {
                return optional($r->start_date)->timestamp ?? 0;
            })
            ->values();
        $car->setRelation('roadTaxes', $roadTaxes);

        $phvs = $car->phvs
            ->sortByDesc(function ($p) {
                return optional($p->expiry_date)->timestamp ?? 0;
            })
            ->values();
        $car->setRelation('phvs', $phvs);

        $insurances = $car->insurances
            ->sortByDesc(function ($i) {
                return [optional($i->expiry_date)->timestamp ?? 0, $i->id];
            })
            ->values();
        $car->setRelation('insurances', $insurances);
    }

    public function applySorn(Request $request, Car $car)
    {
        $tenant = Auth::user()->currentTenant();
        if (! $tenant || $car->tenant_id !== $tenant->id) {
            abort(403);
        }
        if ($car->sorn_applied) {
            return response()->json([
                'ok' => false,
                'message' => 'SORN is already applied for this car.',
            ], 422);
        }

        $request->validate([
            'sorn_proof' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $data = [
            'sorn_applied' => true,
            'sorn_applied_at' => now(),
            'sorn_applied_by' => Auth::id(),
            'fleet_status' => 'sorn',
            'available_from_date' => null,
            'updatedBy' => Auth::id(),
        ];

        if ($request->hasFile('sorn_proof')) {
            $data['sorn_document'] = $this->uploadFile(
                $request->file('sorn_proof'),
                'uploads/cars/sorn_documents'
            );
        }

        $car->update($data);
        $car->refresh();

        CarSornHistory::create([
            'tenant_id' => $car->tenant_id,
            'car_id' => $car->id,
            'sorn_started_at' => $car->sorn_applied_at,
            'sorn_started_by' => Auth::id(),
            'sorn_document' => $car->sorn_document,
        ]);

        return response()->json([
            'ok' => true,
            'gov_sorn_url' => 'https://www.gov.uk/make-a-sorn',
            'sorn_applied_by_name' => Auth::user()?->name,
            'sorn_applied_at_formatted' => $car->sorn_applied_at?->format('d M Y').' at '.$car->sorn_applied_at?->format('h:i A'),
            'sorn_proof_url' => $car->sorn_document
                ? asset('uploads/cars/sorn_documents/'.$car->sorn_document)
                : null,
        ]);
    }

    public function endSorn(Car $car)
    {
        $tenant = Auth::user()->currentTenant();
        if (! $tenant || $car->tenant_id !== $tenant->id) {
            abort(403);
        }
        if (! $car->sorn_applied) {
            return response()->json([
                'ok' => false,
                'message' => 'SORN is not applied for this car.',
            ], 422);
        }

        $this->clearSornState($car);

        return response()->json(['ok' => true]);
    }

    public function destroyV5Document(Car $car)
    {
        $this->authorizeCarTenant($car);

        if ($car->v5_document) {
            $this->deleteFile($car->v5_document, 'uploads/cars');
            $car->update([
                'v5_document' => null,
                'updatedBy' => Auth::id(),
            ]);
        }

        return response()->json(['ok' => true]);
    }

    public function destroyMotDocument(Car $car, int $car_mot)
    {
        $this->authorizeCarTenant($car);

        $record = CarMot::where('car_id', $car->id)->where('id', $car_mot)->firstOrFail();
        if ($record->document) {
            $this->deleteFile($record->document, 'uploads/cars/mot_documents');
            $record->update(['document' => null]);
        }

        return response()->json(['ok' => true]);
    }

    public function destroyMot(Car $car, int $car_mot)
    {
        $this->authorizeCarTenant($car);

        $record = CarMot::where('car_id', $car->id)->where('id', $car_mot)->firstOrFail();
        if ($record->document) {
            $this->deleteFile($record->document, 'uploads/cars/mot_documents');
        }
        $record->delete();

        return response()->json(['ok' => true]);
    }

    public function destroyRoadTax(Car $car, int $car_road_tax)
    {
        $tenant = Auth::user()->currentTenant();
        if (! $tenant || $car->tenant_id !== $tenant->id) {
            abort(403);
        }
        $record = CarRoadTax::where('car_id', $car->id)->where('id', $car_road_tax)->firstOrFail();
        $record->delete();

        return response()->json(['ok' => true]);
    }

    public function destroyPhvDocument(Car $car, int $car_phv)
    {
        $this->authorizeCarTenant($car);

        $record = CarPhv::where('car_id', $car->id)->where('id', $car_phv)->firstOrFail();
        if ($record->document) {
            $this->deleteFile($record->document, 'uploads/cars/phv_documents');
            $record->update(['document' => null]);
        }

        return response()->json(['ok' => true]);
    }

    public function destroyPhv(Car $car, int $car_phv)
    {
        $this->authorizeCarTenant($car);

        $record = CarPhv::where('car_id', $car->id)->where('id', $car_phv)->firstOrFail();
        if ($record->document) {
            $this->deleteFile($record->document, 'uploads/cars/phv_documents');
        }
        $record->delete();

        return response()->json(['ok' => true]);
    }

    public function destroyInsuranceDocument(Car $car)
    {
        $this->authorizeCarTenant($car);

        $car->load('insurances');
        $insurance = $car->insurances
            ->sortByDesc(fn ($row) => [optional($row->created_at)->timestamp ?? 0, $row->id])
            ->first();

        if ($insurance?->insurance_document) {
            $this->deleteFile($insurance->insurance_document, 'uploads/cars/insurance_documents');
            $insurance->update(['insurance_document' => null]);
        }

        return response()->json(['ok' => true]);
    }

    public function destroySornDocument(Car $car)
    {
        $this->authorizeCarTenant($car);

        if ($car->sorn_document) {
            $this->deleteFile($car->sorn_document, 'uploads/cars/sorn_documents');
            $car->update([
                'sorn_document' => null,
                'updatedBy' => Auth::id(),
            ]);
        }

        return response()->json(['ok' => true]);
    }

    private function authorizeCarTenant(Car $car): void
    {
        $tenant = Auth::user()->currentTenant();
        abort_unless($tenant && (int) $car->tenant_id === (int) $tenant->id, 403);
    }

    public function statusReport(string $status)
    {
        $tenant = Auth::user()->currentTenant();
        $statuses = $this->fleetStatuses();

        abort_unless($tenant && array_key_exists($status, $statuses), 404);

        $cars = Car::where('tenant_id', $tenant->id)
            ->where('fleet_status', $status)
            ->with(['company', 'carModel', 'phvs.counsel', 'reservations'])
            ->latest()
            ->get();

        return view($this->dir.'status-report', [
            'cars' => $cars,
            'status' => $status,
            'statusLabel' => $statuses[$status],
        ]);
    }

    public function availableByPhv()
    {
        $tenant = Auth::user()->currentTenant();
        abort_unless($tenant, 403);

        $cars = Car::where('tenant_id', $tenant->id)
            ->with(['company', 'carModel', 'phvs.counsel', 'insurances.status', 'services', 'reservations', 'agreements'])
            ->get()
            ->filter(fn (Car $car) => $car->isAvailableForRent())
            ->groupBy(fn (Car $car) => $car->latestPhvCounselName() ?: 'No PHV Council');

        return view($this->dir.'available-by-phv', compact('cars'));
    }

    public function awaitingPhv()
    {
        $tenant = Auth::user()->currentTenant();
        abort_unless($tenant, 403);

        $cars = Car::where('tenant_id', $tenant->id)
            ->doesntHave('phvs')
            ->with(['company', 'carModel', 'reservations'])
            ->latest()
            ->get();

        return view($this->dir.'status-report', [
            'cars' => $cars,
            'status' => 'awaiting_phv',
            'statusLabel' => 'Awaiting PHV',
        ]);
    }

    public function viewV5(Car $car)
    {
        return $this->viewCarFileInline($car, 'uploads/cars', $car->v5_document);
    }

    public function downloadV5(Car $car)
    {
        return $this->downloadCarFile($car, 'uploads/cars', $car->v5_document, 'v5');
    }

    public function downloadMot(Car $car, int $car_mot)
    {
        $record = CarMot::where('car_id', $car->id)->where('id', $car_mot)->firstOrFail();

        return $this->viewCarFileInline($car, 'uploads/cars/mot_documents', $record->document);
    }

    public function downloadPhv(Car $car, int $car_phv)
    {
        $record = CarPhv::where('car_id', $car->id)->where('id', $car_phv)->firstOrFail();

        return $this->viewCarFileInline($car, 'uploads/cars/phv_documents', $record->document);
    }

    /**
     * Only pass real car columns to create/update (not nested mots/phvs/insurance keys from validate()).
     * Fleet status is not editable on the car form: new cars default to available_for_rent; updates keep the DB value
     * (changed only via Car Status wizard, reservations, swaps, etc.).
     */
    private function carMassAssignmentFromValidated(array $validated, Request $request, ?Car $forUpdate = null): array
    {
        $keys = [
            'company_id', 'car_model_id', 'registration', 'color', 'vin', 'v5_document',
            'manufacture_year', 'registration_year', 'purchase_date', 'purchase_price',
            'purchase_type', 'seller_name', 'seller_notes', 'damaged_notes',
            'phv_status', 'phv_applied_date', 'available_from_date',
        ];

        $data = array_intersect_key($validated, array_flip($keys));

        if (! array_key_exists('seller_name', $data) && $request->has('seller_name')) {
            $data['seller_name'] = $request->string('seller_name')->value();
        }

        if (! array_key_exists('seller_notes', $data) && $request->has('seller_notes')) {
            $data['seller_notes'] = $request->string('seller_notes')->value();
        }

        if (! array_key_exists('damaged_notes', $data) && $request->has('damaged_notes')) {
            $data['damaged_notes'] = $request->string('damaged_notes')->value();
        }

        if ($forUpdate === null) {
            $data['fleet_status'] = 'available_for_rent';
        }

        if (($data['available_from_date'] ?? '') === '') {
            $data['available_from_date'] = null;
        }

        return $data;
    }

    private function mergePhvAppliedData(array $phvData, ?CarPhv $existing): array
    {
        if ($existing && ! array_key_exists('phv_applied', $phvData) && ! array_key_exists('phv_applied_date', $phvData)) {
            $phvData['phv_applied'] = $existing->phv_applied;
            $phvData['phv_applied_date'] = $existing->phv_applied_date?->format('Y-m-d');
            $phvData['phv_applied_by'] = $existing->phv_applied_by;

            return $phvData;
        }

        $isApplied = (bool) ($phvData['phv_applied'] ?? false);
        $phvData['phv_applied'] = $isApplied;
        $phvData['phv_applied_date'] = $isApplied ? ($phvData['phv_applied_date'] ?? null) : null;

        if ($isApplied) {
            $phvData['phv_applied_by'] = $existing && $existing->phv_applied
                ? $existing->phv_applied_by
                : Auth::id();
        } else {
            $phvData['phv_applied_by'] = null;
        }

        return $phvData;
    }

    private function validateMotPhvDocuments(Request $request, ?Car $car): void
    {
        if ($car && (! $car->relationLoaded('mots') || ! $car->relationLoaded('phvs'))) {
            $car->load(['mots', 'phvs']);
        }

        $existingMots = $car ? $car->mots->keyBy('id') : collect();
        $existingPhvs = $car ? $car->phvs->keyBy('id') : collect();
        $errors = [];

        if ($request->has('mots')) {
            foreach ($request->input('mots') as $index => $motData) {
                if (! $this->historyRowHasValues($motData, ['expiry_date', 'amount', 'term'])) {
                    continue;
                }

                $existingMot = ! empty($motData['id']) ? $existingMots->get($motData['id']) : null;
                $hasDocument = $request->hasFile("mots.{$index}.document")
                    || ($existingMot && $existingMot->document);

                if (! $hasDocument) {
                    $errors["mots.{$index}.document"] = 'MOT document is required when MOT details are provided.';
                }
            }
        }

        if ($request->has('phvs')) {
            foreach ($request->input('phvs') as $index => $phvData) {
                if (! $this->historyRowHasValues($phvData, ['counsel_id', 'amount', 'start_date', 'expiry_date', 'notify_before_expiry'])) {
                    continue;
                }

                $existingPhv = ! empty($phvData['id']) ? $existingPhvs->get($phvData['id']) : null;
                $hasDocument = $request->hasFile("phvs.{$index}.document")
                    || ($existingPhv && $existingPhv->document);

                if (! $hasDocument) {
                    $errors["phvs.{$index}.document"] = 'PHV document is required when PHV details are provided.';
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function historyRowHasValues(array $row, array $keys): bool
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
                return true;
            }
        }

        return false;
    }

    private function hasFuturePhvExpiry(array $phvData): bool
    {
        if (empty($phvData['expiry_date'])) {
            return false;
        }

        return Carbon::parse($phvData['expiry_date'])->startOfDay()->gte(now()->startOfDay());
    }

    private function syncCarPhvStatus(Request $request, Car $car, bool $newFuturePhvAdded): void
    {
        if ($newFuturePhvAdded) {
            $car->update([
                'phv_status' => 'phv_active',
                'phv_applied_date' => null,
                'phv_applied_by' => null,
            ]);

            return;
        }

        $status = $request->input('phv_status', $car->phv_status ?: 'need_to_apply');
        $appliedDate = $status === 'applied' ? ($request->input('phv_applied_date') ?: null) : null;

        $car->update([
            'phv_status' => $status,
            'phv_applied_date' => $appliedDate,
            'phv_applied_by' => $status === 'applied'
                ? ($car->phv_status === 'applied' && $car->phv_applied_by ? $car->phv_applied_by : Auth::id())
                : null,
        ]);
    }

    private function syncReservation(Request $request, Car $car, $tenant): void
    {
        $activeReservation = $car->reservations()->where('status', 'active')->latest()->first();

        if ($car->fleet_status === 'damaged') {
            if ($activeReservation) {
                $activeReservation->update(['status' => 'cancelled']);
            }

            return;
        }

        if (! $request->boolean('reserve_car')) {
            if ($activeReservation) {
                $activeReservation->update(['status' => 'cancelled']);
            }
            if ($car->fleet_status === 'reserved') {
                $car->update(['fleet_status' => 'available_for_rent']);
            }

            return;
        }

        $reservationData = [
            'tenant_id' => $tenant->id,
            'customer_name' => $request->input('reservation_customer_name'),
            'customer_phone' => $request->input('reservation_customer_phone'),
            'customer_email' => $request->input('reservation_customer_email'),
            'reservation_date' => $request->input('reservation_date') ?: now()->toDateString(),
            'available_from_date' => $request->input('reservation_available_from_date'),
            'terms_conditions' => $request->input('reservation_terms_conditions'),
            'status' => 'active',
            'created_by' => Auth::id(),
        ];

        if ($activeReservation) {
            $activeReservation->update($reservationData);
        } else {
            $car->reservations()->create($reservationData);
        }

        $car->update([
            'fleet_status' => 'reserved',
            'available_from_date' => $reservationData['available_from_date'],
        ]);
    }

    /**
     * Log book fields, optional file, and who first enabled "log book applied".
     */
    private function mergeLogBookCarData(Request $request, array $carData, ?Car $existing): array
    {
        $isApplied = $request->boolean('log_book_applied');
        $carData['log_book_applied'] = $isApplied;

        if (array_key_exists('seller_notes', $carData) && $carData['seller_notes'] === '') {
            $carData['seller_notes'] = null;
        }

        if (! $isApplied) {
            $carData['log_book_applied_date'] = null;
            $carData['log_book_applied_by'] = null;
            if ($existing) {
                foreach ($existing->oldLogBookFileNames() as $name) {
                    $this->deleteFile($name, 'uploads/cars/log_book');
                }
            }
            $carData['old_log_book'] = null;

            return $carData;
        }

        $rawDate = $request->input('log_book_applied_date');
        $carData['log_book_applied_date'] = ($rawDate !== null && $rawDate !== '') ? $rawDate : null;

        if ($existing === null || ! $existing->log_book_applied) {
            $carData['log_book_applied_by'] = Auth::id();
        } else {
            $carData['log_book_applied_by'] = $existing->log_book_applied_by;
        }

        $names = $existing?->oldLogBookFileNames() ?? [];

        foreach ($this->collectOldLogBookUploads($request) as $file) {
            $names[] = $this->uploadFile($file, 'uploads/cars/log_book');
        }

        $carData['old_log_book'] = $names === [] ? null : array_values(array_unique($names));

        return $carData;
    }

    /**
     * @return list<\Illuminate\Http\UploadedFile>
     */
    private function collectOldLogBookUploads(Request $request): array
    {
        if (! $request->hasFile('old_log_book')) {
            return [];
        }

        $files = $request->file('old_log_book');

        return collect(is_array($files) ? $files : [$files])
            ->filter(fn ($file) => $file && $file->isValid())
            ->values()
            ->all();
    }

    // ✅ Keep your existing helper methods
    private function uploadFile($file, $directory)
    {
        $mimeType = $file->getMimeType();

        if (str_starts_with($mimeType, 'image/')) {
            $dims = getimagesize($file);
            $width = $dims[0];
            $height = $dims[1];
            $name = time().'-'.uniqid().'-'.$width.'-'.$height.'.'.$file->extension();
        } else {
            $name = time().'-'.uniqid().'.'.$file->extension();
        }

        $path = public_path($directory);

        if (! file_exists($path)) {
            mkdir($path, 0755, true);
        }

        if ($file->move($path, $name)) {
            return $name;
        }

        throw new \Exception('Failed to upload file');
    }

    private function deleteFile($filename, $directory)
    {
        if ($filename) {
            $filePath = public_path($directory.'/'.$filename);
            if (File::exists($filePath)) {
                File::delete($filePath);
            }
        }
    }

    private function fleetStatuses(): array
    {
        return [
            'available_for_rent' => 'Available for rent',
            'damaged' => 'Damaged',
            'written_off' => 'Written off',
            'stolen' => 'Stolen',
            'for_sale' => 'For sale',
            'sold' => 'Sold',
            'reserved' => 'Reserved',
            'vehicle_swap' => 'Vehicle swap',
            'sorn' => 'SORN',
        ];
    }

    private function downloadCarFile(Car $car, string $directory, ?string $filename, string $type)
    {
        $tenant = Auth::user()->currentTenant();
        abort_unless($tenant && $car->tenant_id === $tenant->id, 403);
        abort_unless($filename, 404);

        $path = public_path($directory.'/'.$filename);
        abort_unless(File::exists($path), 404);

        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $registration = preg_replace('/[^A-Za-z0-9]/', '', $car->registration);

        return response()->download($path, $registration.'-'.$type.'.'.$extension);
    }

    /**
     * Serve a car file for viewing in the browser (Content-Disposition: inline).
     */
    private function viewCarFileInline(Car $car, string $directory, ?string $filename)
    {
        $tenant = Auth::user()->currentTenant();
        abort_unless($tenant && $car->tenant_id === $tenant->id, 403);
        abort_unless($filename, 404);

        $path = public_path($directory.'/'.$filename);
        abort_unless(File::exists($path), 404);

        return response()->file($path);
    }

    /**
     * Remove SORN flags, proof file on disk, and null related columns.
     */
    private function clearSornState(Car $car): void
    {
        $activeHistory = CarSornHistory::where('car_id', $car->id)
            ->whereNull('sorn_ended_at')
            ->latest('sorn_started_at')
            ->first();

        if ($activeHistory) {
            $activeHistory->update([
                'sorn_ended_at' => now(),
                'sorn_ended_by' => Auth::id(),
            ]);
        }

        if ($car->sorn_document) {
            $this->deleteFile($car->sorn_document, 'uploads/cars/sorn_documents');
        }

        $car->update([
            'sorn_applied' => false,
            'sorn_applied_at' => null,
            'sorn_applied_by' => null,
            'sorn_document' => null,
            'fleet_status' => 'available_for_rent',
            'updatedBy' => Auth::id(),
        ]);
    }

    /**
     * When road tax is renewed (latest period starts on or after SORN was applied), clear SORN automatically.
     */
    private function syncSornAfterRoadTaxesSaved(Car $car): void
    {
        if (! $car->sorn_applied) {
            return;
        }

        $sornAt = $car->sorn_applied_at;
        if (! $sornAt) {
            return;
        }

        $car->load('roadTaxes');
        $latest = $car->roadTaxes
            ->sortByDesc(fn ($rt) => [optional($rt->start_date)->timestamp ?? 0, $rt->id])
            ->first();

        if (! $latest || ! $latest->start_date) {
            return;
        }

        $row = [
            'start_date' => $latest->start_date->format('Y-m-d'),
            'term' => $latest->term,
            'amount' => (string) $latest->amount,
        ];

        if (! $this->historyRowHasValues($row, ['start_date', 'term', 'amount'])) {
            return;
        }

        if ((float) $latest->amount <= 0) {
            return;
        }

        if ($latest->start_date->copy()->startOfDay()->lt($sornAt->copy()->startOfDay())) {
            return;
        }

        $this->clearSornState($car);
    }

    private function deleteCarFiles($car)
    {
        $filesToDelete = [
            $car->v5_document ? public_path('uploads/cars/'.$car->v5_document) : null,
        ];
        foreach ($car->oldLogBookFileNames() as $lbName) {
            $filesToDelete[] = public_path('uploads/cars/log_book/'.$lbName);
        }

        foreach ($car->mots as $mot) {
            if ($mot->document) {
                $filesToDelete[] = public_path('uploads/cars/mot_documents/'.$mot->document);
            }
        }

        foreach ($car->phvs as $phv) {
            if ($phv->document) {
                $filesToDelete[] = public_path('uploads/cars/phv_documents/'.$phv->document);
            }
        }

        foreach ($car->insurances as $insurance) {
            if ($insurance->insurance_document) {
                $filesToDelete[] = public_path('uploads/cars/insurance_documents/'.$insurance->insurance_document);
            }
        }

        foreach ($car->services as $service) {
            if ($service->document) {
                $filesToDelete[] = public_path('uploads/cars/service_documents/'.$service->document);
            }
        }

        if ($car->sorn_document) {
            $filesToDelete[] = public_path('uploads/cars/sorn_documents/'.$car->sorn_document);
        }

        foreach (array_filter($filesToDelete) as $filePath) {
            if (File::exists($filePath)) {
                File::delete($filePath);
            }
        }
    }

    private function ensureInsuranceAppliedStatus(): void
    {
        Status::firstOrCreate(
            ['type' => 'insurance', 'name' => 'Applied'],
            ['color' => '#17a2b8']
        );
    }

    private function insuranceStatusIdByName(string $statusName): int
    {
        $status = Status::firstOrCreate(
            ['type' => 'insurance', 'name' => $statusName],
            ['color' => $statusName === 'Applied' ? '#17a2b8' : '#28a745']
        );

        return (int) $status->id;
    }

    /**
     * Support both "Cancelled" and "Canceled" naming.
     *
     * @return int[]
     */
    private function insuranceCancelledStatusIds(): array
    {
        $statuses = Status::where('type', 'insurance')
            ->whereIn('name', ['Cancelled', 'Canceled'])
            ->get();

        if ($statuses->isEmpty()) {
            return [(int) $this->insuranceStatusIdByName('Cancelled')];
        }

        return $statuses->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
}
