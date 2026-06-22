<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\InsuranceProvider;
use App\Services\CarInsuranceBulkImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use ZipArchive;

class CarInsuranceImportController extends Controller
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

        $insuranceProviders = InsuranceProvider::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('provider_name')
            ->get();

        return view('backend.car-insurance-import.index', compact('insuranceProviders'));
    }

    public function store(Request $request, CarInsuranceBulkImportService $importService)
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found! Please contact administrator.');
        }

        $validated = $request->validate([
            'insurance_provider_id' => [
                'required',
                Rule::exists('insurance_providers', 'id')->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'notify_before_expiry' => 'required|integer|min:1|max:365',
            'upload_zip' => 'nullable|file|mimes:zip|max:51200',
            'upload_files' => 'nullable|array',
            'upload_files.*' => 'file|mimes:pdf|max:10240',
        ]);

        $pdfPaths = [];
        $tempDirs = [];

        try {
            if ($request->hasFile('upload_zip')) {
                [$paths, $tempDir] = $this->extractPdfPathsFromZip($request->file('upload_zip')->getRealPath());
                $pdfPaths = array_merge($pdfPaths, $paths);
                if ($tempDir) {
                    $tempDirs[] = $tempDir;
                }
            }

            if ($request->hasFile('upload_files')) {
                foreach ($request->file('upload_files') as $file) {
                    $tempPath = storage_path('app/insurance-import/'.Str::uuid().'.pdf');
                    File::ensureDirectoryExists(dirname($tempPath));
                    $file->move(dirname($tempPath), basename($tempPath));
                    $pdfPaths[] = $tempPath;
                    $tempDirs[] = dirname($tempPath);
                }
            }

            if ($pdfPaths === []) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Please upload at least one PDF file or a ZIP containing PDFs.');
            }

            $report = $importService->import(
                $pdfPaths,
                $tenant->id,
                (int) $validated['insurance_provider_id'],
                (int) $validated['notify_before_expiry']
            );

            $provider = InsuranceProvider::query()->find($validated['insurance_provider_id']);
            $report['insurance_provider_name'] = $provider?->provider_name;

            session(['car_insurance_import_report' => $report]);

            return redirect()->route('car-insurance-import.report');
        } finally {
            foreach ($tempDirs as $dir) {
                if (is_dir($dir)) {
                    File::deleteDirectory($dir);
                }
            }
        }
    }

    public function report()
    {
        $report = session('car_insurance_import_report');

        if (! $report) {
            return redirect()->route('car-insurance-import.index')
                ->with('error', 'No import report available. Please run an import first.');
        }

        return view('backend.car-insurance-import.report', compact('report'));
    }

    /**
     * @return array{0: list<string>, 1: string|null}
     */
    private function extractPdfPathsFromZip(string $zipPath): array
    {
        $tempDir = storage_path('app/insurance-import/'.Str::uuid());
        File::ensureDirectoryExists($tempDir);

        $zip = new ZipArchive;

        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Could not open ZIP file.');
        }

        $pdfPaths = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);

            if (! is_string($entry) || str_ends_with($entry, '/')) {
                continue;
            }

            if (! str_ends_with(strtolower($entry), '.pdf')) {
                continue;
            }

            $basename = basename($entry);
            $target = $tempDir.DIRECTORY_SEPARATOR.$basename;

            if (file_exists($target)) {
                $target = $tempDir.DIRECTORY_SEPARATOR.Str::uuid().'_'.$basename;
            }

            copy('zip://'.$zipPath.'#'.$entry, $target);
            $pdfPaths[] = $target;
        }

        $zip->close();

        return [$pdfPaths, $tempDir];
    }
}
