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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PDF;
use Carbon\Carbon;
use App\Services\HelloSignService;
use Illuminate\Support\Facades\File;
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
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found! Please contact administrator.');
        }
        $agreements = Agreement::where('tenant_id', $tenant->id)->with(['company', 'driver', 'car', 'status'])
            ->withCount(['collections', 'pendingCollections', 'overdueCollections'])
            ->get();

        return view($this->dir . 'index', compact('agreements'));
    }

    public function create()
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }
        $companies = Company::where('tenant_id', $tenant->id)->get();
        $drivers = Driver::where('tenant_id', $tenant->id)->get();
        $cars = Car::where('tenant_id', $tenant->id)->get();
        $insuranceProviders = InsuranceProvider::where('tenant_id', $tenant->id)->get(); // Add this
        $model = new Agreement();
        $statuses = Status::where('type', 'agreement')->get();

        return view($this->dir . 'create', compact('model', 'companies', 'drivers', 'cars', 'statuses', 'insuranceProviders'));
    }

    public function store(Request $request)
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->back()
                ->with('error', 'No active company found!');
        }
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
            $agreement = DB::transaction(function () use ($validated, $request, $tenant) {
                // Handle file upload for insurance proof document
                if ($request->hasFile('own_insurance_proof_document')) {
                    $file = $request->file('own_insurance_proof_document');
                    $filename = time() . '_' . $file->getClientOriginalName();
                    $file->move(public_path('uploads/insurance_documents'), $filename);
                    $validated['own_insurance_proof_document'] = $filename;
                }

                // Create agreement record
                $validated['tenant_id'] = $tenant->id;
                $validated['createdBy'] = Auth::id();
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
        $tenant = Auth::user()->currentTenant();

        // ✅ Check ownership
        if ($agreement->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to this car');
        }
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
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }
        $model = $agreement->load('collections');
        $companies = Company::where('tenant_id', $tenant->id)->get();
        $drivers = Driver::where('tenant_id', $tenant->id)->get();
        $cars = Car::where('tenant_id', $tenant->id)->get();
        $insuranceProviders = InsuranceProvider::where('tenant_id', $tenant->id)->get(); // Add this
        $statuses = Status::where('type', 'agreement')->get();

        return view($this->dir . 'edit', compact('model', 'companies', 'drivers', 'cars', 'statuses', 'insuranceProviders'));
    }

    public function update(Request $request, Agreement $agreement)
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->back()
                ->with('error', 'No active company found!');
        }
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
            $updatedAgreement = DB::transaction(function () use ($validated, $request, $agreement, $tenant) {
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
                $validated['tenant_id'] = $tenant->id;
                $validated['updatedBy'] = Auth::id();
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
            $tenant = Auth::user()->currentTenant();

            // ✅ Check ownership
            if ($agreement->tenant_id !== $tenant->id) {
                abort(403, 'Unauthorized access');
            }
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

    /**
     * ✅ Send agreement for e-signature
     */
    public function sendForESignature(Agreement $agreement)
    {
        $tenant = Auth::user()->currentTenant();

        if ($agreement->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access');
        }

        if ($agreement->hellosign_request_id) {
            return redirect()->back()
                ->with('warning', 'Agreement already sent for signature.');
        }

        if (!$agreement->driver || !$agreement->driver->email) {
            return redirect()->back()
                ->with('error', 'Driver email is required for e-signature.');
        }

        try {
            // Generate PDF
            $pdfPath = $this->generatePDFForESign($agreement);

            if (!$pdfPath) {
                throw new \Exception('Failed to generate PDF');
            }

            // Send to HelloSign
            $helloSignService = new \App\Services\HelloSignService();
            $result = $helloSignService->sendAgreementForSignature($agreement, $pdfPath);

            if ($result['success']) {
                $agreement->update([
                    'hellosign_request_id' => $result['request_id'],
                    'hellosign_status' => 'pending',
                    'esign_sent_at' => now(),
                ]);

                return redirect()->route('agreements.show', $agreement)
                    ->with('success', 'Agreement sent for e-signature successfully! Driver will receive an email from HelloSign.');
            }

            return redirect()->back()
                ->with('error', 'Failed: ' . ($result['error'] ?? 'Unknown error'));

        } catch (\Exception $e) {
            \Log::error('E-Signature Error: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error: ' . $e->getMessage());
        }
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
            if (!file_exists($directory)) {
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
            \Log::error('PDF Generation Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * ✅ Check e-signature status - IMPROVED
     */
    public function checkESignStatus(Agreement $agreement)
    {
        $tenant = Auth::user()->currentTenant();

        if ($agreement->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access');
        }

        if (!$agreement->hellosign_request_id) {
            return redirect()->back()
                ->with('error', 'No signature request found.');
        }

        try {
            $helloSignService = new \App\Services\HelloSignService();

            \Log::info('Checking signature status', [
                'agreement_id' => $agreement->id,
                'request_id' => $agreement->hellosign_request_id
            ]);

            $status = $helloSignService->getSignatureStatus($agreement->hellosign_request_id);

            if (!$status['success']) {
                return redirect()->back()
                    ->with('error', 'Failed to check status: ' . $status['error']);
            }

            // ✅ Update status
            $agreement->update(['hellosign_status' => $status['status']]);

            // ✅ If complete and no document yet, download it
            if ($status['is_complete'] && !$agreement->esign_document_path) {

                \Log::info('Document is complete, downloading...', [
                    'agreement_id' => $agreement->id
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
                        ->with('success', '✅ Agreement is fully signed! Signed document downloaded successfully. You can now view it below.');
                } else {
                    return redirect()->back()
                        ->with('warning', 'Agreement is signed but failed to download PDF: ' . $download['error']);
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
                ->with('info', 'Current Status: ' . ucfirst($status['status']));

        } catch (\Exception $e) {
            \Log::error('Status Check Error: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Error checking status: ' . $e->getMessage());
        }
    }

    /**
     * ✅ Resend signature reminder - IMPROVED
     */
    public function resendESignature(Agreement $agreement)
    {
        $tenant = Auth::user()->currentTenant();

        if ($agreement->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access');
        }

        if (!$agreement->hellosign_request_id) {
            return redirect()->back()
                ->with('error', 'No signature request found.');
        }

        // ✅ Check if already signed
        if ($agreement->hellosign_status === 'signed') {
            return redirect()->back()
                ->with('warning', 'This agreement is already signed. Click "Check Status" to download the signed document.');
        }

        if ($agreement->hellosign_status !== 'pending') {
            return redirect()->back()
                ->with('error', 'Cannot send reminder for this agreement.');
        }

        try {
            $helloSignService = new \App\Services\HelloSignService();
            $result = $helloSignService->sendReminder(
                $agreement->hellosign_request_id,
                $agreement->driver->email
            );

            if ($result['success']) {
                return redirect()->back()
                    ->with('success', 'Signature reminder sent to driver successfully!');
            }

            // ✅ Handle "already signed" error
            if (strpos($result['error'], 'already signed') !== false) {
                return redirect()->back()
                    ->with('info', 'Driver has already signed! Click "Check Status" button to download the signed document.');
            }

            return redirect()->back()
                ->with('error', 'Failed to send reminder: ' . ($result['error'] ?? 'Unknown error'));

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error: ' . $e->getMessage());
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

        if (!$agreement->esign_document_path) {
            abort(404, 'Signed document not found');
        }

        $fullPath = public_path($agreement->esign_document_path);

        if (!file_exists($fullPath)) {
            abort(404, 'Document file not found');
        }

        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="signed_agreement_' . $agreement->id . '.pdf"'
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

            if (!$requestId) {
                return response()->json(['error' => 'Invalid webhook data'], 400);
            }

            $agreement = Agreement::where('hellosign_request_id', $requestId)->first();

            if (!$agreement) {
                \Log::error('Agreement not found for HelloSign request: ' . $requestId);
                return response()->json(['error' => 'Agreement not found'], 404);
            }

            // Handle events
            switch ($eventType) {
                case 'signature_request_signed':
                case 'signature_request_all_signed':
                    // Download signed PDF
                    $helloSignService = new \App\Services\HelloSignService();
                    $download = $helloSignService->downloadSignedPDF($requestId, $agreement->id);

                    if ($download['success']) {
                        $agreement->update([
                            'hellosign_status' => 'signed',
                            'esign_document_path' => $download['path'],
                            'esign_completed_at' => now(),
                        ]);

                        \Log::info('Agreement signed: ' . $agreement->id);
                    }
                    break;

                case 'signature_request_declined':
                    $agreement->update(['hellosign_status' => 'declined']);
                    \Log::info('Agreement declined: ' . $agreement->id);
                    break;

                case 'signature_request_canceled':
                    $agreement->update(['hellosign_status' => 'cancelled']);
                    \Log::info('Agreement cancelled: ' . $agreement->id);
                    break;
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            \Log::error('Webhook Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}
