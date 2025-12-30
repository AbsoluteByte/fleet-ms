<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\CarModel;
use App\Models\Company;
use App\Models\Counsel;
use App\Models\InsuranceProvider;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CarController extends Controller
{
    protected $url = 'cars.';
    protected $dir = 'backend.cars.';
    protected $name = 'Cars';

    public function __construct()
    {
        $this->middleware('role:admin');
        view()->share('url', $this->url);
        view()->share('dir', $this->dir);
        view()->share('singular', Str::singular($this->name));
        view()->share('plural', Str::plural($this->name));
    }

    public function index()
    {
        $cars = Car::with(['company', 'carModel'])->get();
        return view($this->dir . 'index', compact('cars'));
    }

    public function create()
    {
        $model = new Car();
        $companies = Company::all();
        $carModels = CarModel::all();
        $counsels = Counsel::all();
        $insuranceProviders = InsuranceProvider::all();
        $statuses = Status::where('type', 'insurance')->get();

        return view($this->dir . 'create', compact('model', 'companies', 'carModels', 'counsels', 'insuranceProviders', 'statuses'));
    }

    public function store(Request $request)
    {
        // Build validation rules dynamically
        $rules = [
            'company_id' => 'required|exists:companies,id',
            'car_model_id' => 'required|exists:car_models,id',
            'registration' => 'required|string|unique:cars',
            'color' => 'required|string',
            'vin' => 'required|string',
            'v5_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'manufacture_year' => 'required|integer|min:1900|max:' . date('Y'),
            'registration_year' => 'required|integer|min:1900|max:' . date('Y'),
            'purchase_date' => 'required|date',
            'purchase_price' => 'required|numeric|min:0',
            'purchase_type' => 'required|in:imported,uk',

            // MOTs
            'mots.*.expiry_date' => 'required|date',
            'mots.*.amount' => 'required|numeric|min:0',
            'mots.*.term' => 'required|string',
            'mots.*.document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',

            // Road Taxes
            'road_taxes.*.start_date' => 'required|date',
            'road_taxes.*.term' => 'required|string',
            'road_taxes.*.amount' => 'required|numeric|min:0',

            // PHVs
            'phvs.*.counsel_id' => 'required|exists:counsels,id',
            'phvs.*.amount' => 'required|numeric|min:0',
            'phvs.*.start_date' => 'required|date',
            'phvs.*.expiry_date' => 'required|date',
            'phvs.*.notify_before_expiry' => 'required|integer|min:1',
            'phvs.*.document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ];

        // ✅ Add insurance validation only if checkbox is checked
        if ($request->has('has_insurance')) {
            $rules = array_merge($rules, [
                'insurance_provider_id' => 'required|exists:insurance_providers,id',
                'insurance_start_date' => 'required|date',
                'insurance_expiry_date' => 'required|date',
                'insurance_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
                'insurance_notify_before_expiry' => 'required|integer|min:1',
                'insurance_status_id' => 'required|exists:statuses,id',
            ]);
        }

        $validated = $request->validate($rules);

        try {
            $car = DB::transaction(function () use ($validated, $request) {
                // Handle main V5 document upload
                if ($request->hasFile('v5_document')) {
                    $validated['v5_document'] = $this->uploadFile($request->file('v5_document'), 'uploads/cars');
                }

                // Create car record
                $car = Car::create($validated);

                // Store MOTs
                if ($request->has('mots')) {
                    foreach ($request->input('mots') as $index => $motData) {
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
                        $car->roadTaxes()->create($roadTaxData);
                    }
                }

                // Store PHVs
                if ($request->has('phvs')) {
                    foreach ($request->input('phvs') as $index => $phvData) {
                        if ($request->hasFile("phvs.{$index}.document")) {
                            $phvData['document'] = $this->uploadFile(
                                $request->file("phvs.{$index}.document"),
                                'uploads/cars/phv_documents'
                            );
                        }
                        $car->phvs()->create($phvData);
                    }
                }

                // ✅ Store Insurance ONLY if checkbox is checked
                if ($request->has('has_insurance')) {
                    $insuranceData = [
                        'car_id' => $car->id,
                        'insurance_provider_id' => $validated['insurance_provider_id'],
                        'start_date' => $validated['insurance_start_date'],
                        'expiry_date' => $validated['insurance_expiry_date'],
                        'notify_before_expiry' => $validated['insurance_notify_before_expiry'],
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

            return redirect()->route($this->url . 'index')
                ->with('success', 'Car added successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating car: ' . $e->getMessage());
        }
    }

    public function show(Car $car)
    {
        $car->load(['company', 'carModel', 'mots', 'roadTaxes', 'phvs.counsel', 'insurances.insuranceProvider', 'insurances.status']);
        return view($this->dir . 'show', compact('car'));
    }

    public function edit($id)
    {
        $model = Car::with(['mots', 'roadTaxes', 'phvs', 'insurances'])->findOrFail($id);
        $companies = Company::all();
        $carModels = CarModel::all();
        $counsels = Counsel::all();
        $insuranceProviders = InsuranceProvider::all();
        $statuses = Status::where('type', 'insurance')->get();

        return view($this->dir . 'edit', compact('model', 'companies', 'carModels', 'counsels', 'insuranceProviders', 'statuses'));
    }

    public function update(Request $request, $id)
    {
        $car = Car::findOrFail($id);

        // Build validation rules dynamically
        $rules = [
            'company_id' => 'required|exists:companies,id',
            'car_model_id' => 'required|exists:car_models,id',
            'registration' => 'required|string|unique:cars,registration,' . $car->id,
            'color' => 'required|string',
            'vin' => 'required|string',
            'v5_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'manufacture_year' => 'required|integer|min:1900|max:' . date('Y'),
            'registration_year' => 'required|integer|min:1900|max:' . date('Y'),
            'purchase_date' => 'required|date',
            'purchase_price' => 'required|numeric|min:0',
            'purchase_type' => 'required|in:imported,uk',

            // MOTs
            'mots.*.id' => 'nullable|exists:car_mots,id',
            'mots.*.expiry_date' => 'required|date',
            'mots.*.amount' => 'required|numeric|min:0',
            'mots.*.term' => 'required|string',
            'mots.*.document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',

            // Road Taxes
            'road_taxes.*.start_date' => 'required|date',
            'road_taxes.*.term' => 'required|string',
            'road_taxes.*.amount' => 'required|numeric|min:0',

            // PHVs
            'phvs.*.id' => 'nullable|exists:car_phvs,id',
            'phvs.*.counsel_id' => 'required|exists:counsels,id',
            'phvs.*.amount' => 'required|numeric|min:0',
            'phvs.*.start_date' => 'required|date',
            'phvs.*.expiry_date' => 'required|date',
            'phvs.*.notify_before_expiry' => 'required|integer|min:1',
            'phvs.*.document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ];

        // ✅ Add insurance validation only if checkbox is checked
        if ($request->has('has_insurance')) {
            $rules = array_merge($rules, [
                'insurance_provider_id' => 'required|exists:insurance_providers,id',
                'insurance_start_date' => 'required|date',
                'insurance_expiry_date' => 'required|date',
                'insurance_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
                'insurance_notify_before_expiry' => 'required|integer|min:1',
                'insurance_status_id' => 'required|exists:statuses,id',
            ]);
        }

        $validated = $request->validate($rules);

        try {
            $updatedCar = DB::transaction(function () use ($validated, $request, $car) {

                // ==================== V5 DOCUMENT HANDLING ====================
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

                // Update car record
                $car->update($validated);

                // ==================== Update MOTs ====================
                $existingMots = $car->mots->keyBy('id');
                $processedMotIds = [];

                if ($request->has('mots')) {
                    foreach ($request->input('mots') as $index => $motData) {
                        $motId = $motData['id'] ?? null;
                        $existingMot = $motId ? $existingMots->get($motId) : null;

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
                        $car->roadTaxes()->create($roadTaxData);
                    }
                }

                // ==================== Update PHVs ====================
                $existingPhvs = $car->phvs->keyBy('id');
                $processedPhvIds = [];

                if ($request->has('phvs')) {
                    foreach ($request->input('phvs') as $index => $phvData) {
                        $phvId = $phvData['id'] ?? null;
                        $existingPhv = $phvId ? $existingPhvs->get($phvId) : null;

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

                        unset($phvData['id']);

                        if ($existingPhv) {
                            $existingPhv->update($phvData);
                            $processedPhvIds[] = $existingPhv->id;
                        } else {
                            $newPhv = $car->phvs()->create($phvData);
                            $processedPhvIds[] = $newPhv->id;
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

                // ==================== Update Insurance ====================
                $existingInsurance = $car->insurances->first();

                if ($request->has('has_insurance')) {
                    // ✅ User wants insurance
                    $insuranceData = [
                        'car_id' => $car->id,
                        'insurance_provider_id' => $validated['insurance_provider_id'],
                        'start_date' => $validated['insurance_start_date'],
                        'expiry_date' => $validated['insurance_expiry_date'],
                        'notify_before_expiry' => $validated['insurance_notify_before_expiry'],
                        'status_id' => $validated['insurance_status_id'],
                    ];

                    if ($request->hasFile('insurance_document')) {
                        $insuranceData['insurance_document'] = $this->uploadFile(
                            $request->file('insurance_document'),
                            'uploads/cars/insurance_documents'
                        );

                        if ($existingInsurance && $existingInsurance->insurance_document) {
                            $this->deleteFile($existingInsurance->insurance_document, 'uploads/cars/insurance_documents');
                        }
                    } elseif ($existingInsurance && $existingInsurance->insurance_document) {
                        $insuranceData['insurance_document'] = $existingInsurance->insurance_document;
                    }

                    if ($existingInsurance) {
                        $existingInsurance->update($insuranceData);
                    } else {
                        $car->insurances()->create($insuranceData);
                    }
                } else {
                    // ✅ User doesn't want insurance - Delete existing
                    if ($existingInsurance) {
                        if ($existingInsurance->insurance_document) {
                            $this->deleteFile($existingInsurance->insurance_document, 'uploads/cars/insurance_documents');
                        }
                        $existingInsurance->delete();
                    }
                }

                return $car;
            });

            return redirect()->route($this->url . 'index')
                ->with('success', 'Car updated successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating car: ' . $e->getMessage());
        }
    }

    private function uploadFile($file, $directory)
    {
        $mimeType = $file->getMimeType();

        if (str_starts_with($mimeType, 'image/')) {
            $dims = getimagesize($file);
            $width = $dims[0];
            $height = $dims[1];
            $name = time() . '-' . uniqid() . '-' . $width . '-' . $height . '.' . $file->extension();
        } else {
            $name = time() . '-' . uniqid() . '.' . $file->extension();
        }

        $path = public_path($directory);

        if (!file_exists($path)) {
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
            $filePath = public_path($directory . '/' . $filename);
            if (File::exists($filePath)) {
                File::delete($filePath);
            }
        }
    }

    public function destroy(Car $car)
    {
        try {
            DB::transaction(function () use ($car) {
                $car->mots()->delete();
                $car->roadTaxes()->delete();
                $car->phvs()->delete();
                $car->insurances()->delete();
                $car->delete();
            });

            $this->deleteCarFiles($car);

            return redirect()->route($this->url . 'index')
                ->with('success', 'Car deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error deleting car: ' . $e->getMessage());
        }
    }

    private function deleteCarFiles($car)
    {
        $filesToDelete = [
            $car->v5_document ? public_path('uploads/cars/' . $car->v5_document) : null,
        ];

        foreach ($car->mots as $mot) {
            if ($mot->document) {
                $filesToDelete[] = public_path('uploads/cars/mot_documents/' . $mot->document);
            }
        }

        foreach ($car->phvs as $phv) {
            if ($phv->document) {
                $filesToDelete[] = public_path('uploads/cars/phv_documents/' . $phv->document);
            }
        }

        foreach ($car->insurances as $insurance) {
            if ($insurance->insurance_document) {
                $filesToDelete[] = public_path('uploads/cars/insurance_documents/' . $insurance->insurance_document);
            }
        }

        foreach (array_filter($filesToDelete) as $filePath) {
            if (File::exists($filePath)) {
                File::delete($filePath);
            }
        }
    }
}
