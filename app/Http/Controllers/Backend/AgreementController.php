<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Agreement;
use App\Models\Car;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Status;
use App\Models\InsuranceProvider; // Add this
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PDF;
use Carbon\Carbon;
class AgreementController extends Controller
{
    protected $url = 'agreements.';
    protected $dir = 'backend.agreements.';
    protected $name = 'Agreements';

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
        $agreements = Agreement::with(['company', 'driver', 'car', 'status'])
            ->withCount(['collections', 'pendingCollections', 'overdueCollections'])
            ->get();

        return view($this->dir . 'index', compact('agreements'));
    }

    public function create()
    {
        $companies = Company::all();
        $drivers = Driver::all();
        $cars = Car::all();
        $insuranceProviders = InsuranceProvider::all(); // Add this
        $model = new Agreement();
        $statuses = Status::where('type', 'agreement')->get();

        return view($this->dir . 'create', compact('model', 'companies', 'drivers', 'cars', 'statuses', 'insuranceProviders'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'driver_id' => 'required|exists:drivers,id',
            'car_id' => 'required|exists:cars,id',
            'agreed_rent' => 'required|numeric|min:0',
            'rent_interval' => 'required|string',
            'deposit_amount' => 'required|numeric|min:0',
            'mileage_out' => 'nullable|integer|min:0',
            'mileage_in' => 'nullable|integer|min:0',
            'collection_type' => 'required|in:weekly,monthly,static',
            'auto_schedule_collections' => 'boolean',
            'condition_report' => 'nullable|string',
            'notes' => 'nullable|string',
            'status_id' => 'required|exists:statuses,id',
            // New insurance validation
            'using_own_insurance' => 'boolean',
            'insurance_provider_id' => 'required_if:using_own_insurance,0|nullable|exists:insurance_providers,id',
            'own_insurance_provider_name' => 'required_if:using_own_insurance,1|nullable|string|max:255',
            'own_insurance_start_date' => 'required_if:using_own_insurance,1|nullable|date',
            'own_insurance_end_date' => 'required_if:using_own_insurance,1|nullable|date|after:own_insurance_start_date',
            'own_insurance_type' => 'required_if:using_own_insurance,1|nullable|string|max:255',
            'own_insurance_policy_number' => 'required_if:using_own_insurance,1|nullable|string|max:255',
            'own_insurance_proof_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            // Collections (only validate when auto_schedule_collections is false AND collections data exists)
            'collections' => 'array',
            'collections.*.date' => 'required_if:auto_schedule_collections,0|nullable|date',
            'collections.*.due_date' => 'nullable|date',
            'collections.*.method' => 'required_if:auto_schedule_collections,0|nullable|string',
            'collections.*.amount' => 'required_if:auto_schedule_collections,0|nullable|numeric|min:0',
        ]);
        try {
            $agreement = DB::transaction(function () use ($validated, $request) {
                // Handle file upload for insurance proof document
                if ($request->hasFile('own_insurance_proof_document')) {
                    $file = $request->file('own_insurance_proof_document');
                    $filename = time() . '_' . $file->getClientOriginalName();
                    $file->move(public_path('uploads/insurance_documents'), $filename);
                    $validated['own_insurance_proof_document'] = $filename;
                }

                // Create agreement record
                $agreement = Agreement::create($validated);

                // Handle collections based on auto schedule setting
                if ($validated['auto_schedule_collections']) {
                    // Generate automatic collections
                    $agreement->generateCollections();
                } else {
                    // Store manual collections
                    if ($request->has('collections')) {
                        foreach ($request->input('collections') as $collectionData) {
                            $collectionData['payment_status'] = 'pending';
                            $collectionData['is_auto_generated'] = false;
                            $collectionData['due_date'] = $collectionData['due_date'] ?? $collectionData['date'];
                            $agreement->collections()->create($collectionData);
                        }
                    }
                }

                return $agreement;
            });

            return redirect()->route('agreements.index')
                ->with('success', 'Agreement created successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating agreement: ' . $e->getMessage());
        }
    }

    public function show(Agreement $agreement)
    {
        $agreement->load([
            'company', 'driver', 'car', 'status', 'insuranceProvider',
            'collections' => function($query) {
                $query->orderBy('due_date');
            }
        ]);

        // Update overdue collections
        $agreement->updateOverdueCollections();

        return view($this->dir . 'show', compact('agreement'));
    }

    public function edit(Agreement $agreement)
    {
        $model = $agreement->load('collections');
        $companies = Company::all();
        $drivers = Driver::all();
        $cars = Car::all();
        $insuranceProviders = InsuranceProvider::all(); // Add this
        $statuses = Status::where('type', 'agreement')->get();

        return view($this->dir . 'edit', compact('model', 'companies', 'drivers', 'cars', 'statuses', 'insuranceProviders'));
    }

    public function update(Request $request, Agreement $agreement)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'driver_id' => 'required|exists:drivers,id',
            'car_id' => 'required|exists:cars,id',
            'agreed_rent' => 'required|numeric|min:0',
            'rent_interval' => 'required|string',
            'deposit_amount' => 'required|numeric|min:0',
            'mileage_out' => 'nullable|integer|min:0',
            'mileage_in' => 'nullable|integer|min:0',
            'collection_type' => 'required|in:weekly,monthly,static',
            'auto_schedule_collections' => 'boolean',
            'condition_report' => 'nullable|string',
            'notes' => 'nullable|string',
            'status_id' => 'required|exists:statuses,id',

            'using_own_insurance' => 'boolean',
            'insurance_provider_id' => 'required_if:using_own_insurance,0|nullable|exists:insurance_providers,id',
            'own_insurance_provider_name' => 'required_if:using_own_insurance,1|nullable|string|max:255',
            'own_insurance_start_date' => 'required_if:using_own_insurance,1|nullable|date',
            'own_insurance_end_date' => 'required_if:using_own_insurance,1|nullable|date|after:own_insurance_start_date',
            'own_insurance_type' => 'required_if:using_own_insurance,1|nullable|string|max:255',
            'own_insurance_policy_number' => 'required_if:using_own_insurance,1|nullable|string|max:255',
            'own_insurance_proof_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            // Collections (only validate when auto_schedule_collections is false AND collections data exists)
            'collections' => 'array',
            'collections.*.date' => 'required_if:auto_schedule_collections,0|nullable|date',
            'collections.*.due_date' => 'nullable|date',
            'collections.*.method' => 'required_if:auto_schedule_collections,0|nullable|string',
            'collections.*.amount' => 'required_if:auto_schedule_collections,0|nullable|numeric|min:0',
        ]);
        try {
            $updatedAgreement = DB::transaction(function () use ($validated, $request, $agreement) {
                $oldAutoSchedule = $agreement->auto_schedule_collections;

                // Handle file upload for insurance proof document
                if ($request->hasFile('own_insurance_proof_document')) {
                    // Delete old file if exists
                    if ($agreement->own_insurance_proof_document) {
                        $oldFilePath = public_path('uploads/insurance_documents/' . $agreement->own_insurance_proof_document);
                        if (file_exists($oldFilePath)) {
                            unlink($oldFilePath);
                        }
                    }

                    $file = $request->file('own_insurance_proof_document');
                    $filename = time() . '_' . $file->getClientOriginalName();
                    $file->move(public_path('uploads/insurance_documents'), $filename);
                    $validated['own_insurance_proof_document'] = $filename;
                }

                // Update agreement record
                $agreement->update($validated);

                // Handle collections based on auto schedule setting
                if ($validated['auto_schedule_collections']) {
                    // Regenerate collections if auto schedule changed or key fields changed
                    if ($oldAutoSchedule !== $validated['auto_schedule_collections'] ||
                        $agreement->wasChanged(['start_date', 'end_date', 'collection_type', 'agreed_rent'])) {
                        $agreement->generateCollections();
                    }
                } else {
                    // Update manual collections - Delete existing and recreate
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

                return $agreement;
            });

            return redirect()->route('agreements.index')
                ->with('success', 'Agreement updated successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating agreement: ' . $e->getMessage());
        }
    }

    public function destroy(Agreement $agreement)
    {
        try {
            DB::transaction(function () use ($agreement) {
                // Delete insurance document if exists
                if ($agreement->own_insurance_proof_document) {
                    $filePath = public_path('uploads/insurance_documents/' . $agreement->own_insurance_proof_document);
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
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
                ->with('error', 'Error deleting agreement: ' . $e->getMessage());
        }
    }

    public function payCollection(Request $request, Agreement $agreement, $collectionId)
    {
        $collection = $agreement->collections()->findOrFail($collectionId);

        $validated = $request->validate([
            'amount_paid' => 'required|numeric|min:0|max:' . $collection->remaining_amount,
            'payment_date' => 'required|date',
            'notes' => 'nullable|string'
        ]);

        try {
            $collection->markAsPaid($validated['amount_paid'], $validated['payment_date']);

            if ($validated['notes']) {
                $collection->update(['notes' => $validated['notes']]);
            }

            return redirect()->back()->with('success', 'Payment recorded successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error recording payment: ' . $e->getMessage());
        }
    }

    public function generatePDF(Agreement $agreement)
    {
        try {
            $agreement->load([
                'company', 'driver', 'car', 'car.carModel', 'status', 'insuranceProvider'
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

            $filename = 'Agreement_' . $agreement->id . '_' . str_replace(' ', '_', $agreement->driver->full_name) . '.pdf';

            return $pdf->download($filename);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

}
