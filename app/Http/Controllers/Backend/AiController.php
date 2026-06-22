<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\RoadTaxBulkImportService;
use App\Services\RoadTaxSlipExtractionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use ZipArchive;

class AiController extends Controller
{
    protected $dir = 'backend.ai.';

    /** @var list<string> */
    private const SLIP_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

    public function __construct()
    {
        $this->middleware('role:admin|manager|user');
        view()->share('dir', $this->dir);
    }

    public function index()
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found! Please contact administrator.');
        }

        return view($this->dir.'index');
    }

    public function analyzeRoadTax(Request $request, RoadTaxSlipExtractionService $extractionService)
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found! Please contact administrator.');
        }

        $validated = $request->validate([
            'upload_zip' => 'nullable|file|mimes:zip|max:51200',
            'upload_files' => 'nullable|array',
            'upload_files.*' => 'file|mimes:jpeg,jpg,png,webp,pdf|max:10240',
        ]);

        /** @var list<array{path: string, display: string}> */
        $slipFiles = [];
        $batchId = (string) Str::uuid();
        $batchDir = storage_path('app/road-tax-import/'.$batchId);
        File::ensureDirectoryExists($batchDir);

        try {
            if ($request->hasFile('upload_zip')) {
                $zipFiles = $this->extractSlipFilesFromZip(
                    $request->file('upload_zip')->getRealPath(),
                    $batchDir
                );
                $slipFiles = array_merge($slipFiles, $zipFiles);
            }

            if ($request->hasFile('upload_files')) {
                foreach ($request->file('upload_files') as $file) {
                    $originalName = $file->getClientOriginalName();
                    $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
                    $target = $batchDir.DIRECTORY_SEPARATOR.Str::uuid().'.'.$extension;
                    $file->move(dirname($target), basename($target));
                    $slipFiles[] = [
                        'path' => $target,
                        'display' => $originalName,
                    ];
                }
            }

            if ($slipFiles === []) {
                File::deleteDirectory($batchDir);

                return redirect()->to(route('ai.index').'#add-road-tax')
                    ->with('error', 'Please upload at least one image or PDF file, or a ZIP containing slip files.');
            }

            $rows = [];
            $analyzeIndex = 0;

            foreach ($slipFiles as $slipFile) {
                if ($analyzeIndex > 0) {
                    sleep(1);
                }

                $slipPath = $slipFile['path'];
                $displayName = $slipFile['display'];
                $isPdf = strtolower(pathinfo($slipPath, PATHINFO_EXTENSION)) === 'pdf';
                $extractions = $extractionService->extractFromSlipFile($slipPath);

                foreach ($extractions as $pageIndex => $extracted) {
                    $filename = $displayName;

                    if ($isPdf && count($extractions) > 1) {
                        $filename = pathinfo($displayName, PATHINFO_FILENAME).'.pdf (page '.($pageIndex + 1).')';
                    }

                    $rows[] = [
                        'row_id' => (string) Str::uuid(),
                        'filename' => $filename,
                        'file_path' => 'road-tax-import/'.$batchId.'/'.basename($slipPath),
                        'registration' => $extracted['registration'],
                        'start_date' => $extracted['start_date'],
                        'term' => $extracted['term'],
                        'amount' => $extracted['amount'],
                        'confidence' => $extracted['confidence'],
                        'notes' => $extracted['notes'],
                        'needs_review' => $extracted['needs_review'],
                        'extraction_status' => $extracted['extraction_status'],
                        'message' => $extracted['message'],
                    ];
                }

                $analyzeIndex++;
            }

            $accountWarning = $this->detectAccountWarning($rows);

            session([
                'road_tax_import_review' => [
                    'batch_id' => $batchId,
                    'analyzed_at' => now()->format('d M Y H:i'),
                    'tenant_id' => $tenant->id,
                    'rows' => $rows,
                    'account_warning' => $accountWarning,
                ],
            ]);

            $redirect = redirect()->route('ai.road-tax.review');

            if ($accountWarning) {
                return $redirect->with('warning', $accountWarning);
            }

            return $redirect;
        } catch (\Throwable $e) {
            if (is_dir($batchDir)) {
                File::deleteDirectory($batchDir);
            }

            return redirect()->to(route('ai.index').'#add-road-tax')
                ->with('error', 'Could not analyze road tax slips: '.$e->getMessage());
        }
    }

    public function reviewRoadTax()
    {
        $review = session('road_tax_import_review');

        if (! $review || empty($review['rows'])) {
            return redirect()->to(route('ai.index').'#add-road-tax')
                ->with('error', 'No road tax analysis available. Please upload slips first.');
        }

        $tenant = Auth::user()->currentTenant();

        if (! $tenant || (int) ($review['tenant_id'] ?? 0) !== (int) $tenant->id) {
            session()->forget('road_tax_import_review');

            return redirect()->to(route('ai.index').'#add-road-tax')
                ->with('error', 'Analysis session expired or belongs to another company.');
        }

        return view($this->dir.'road-tax-review', compact('review'));
    }

    public function applyRoadTax(Request $request, RoadTaxBulkImportService $importService)
    {
        $review = session('road_tax_import_review');

        if (! $review || empty($review['rows'])) {
            return redirect()->to(route('ai.index').'#add-road-tax')
                ->with('error', 'No road tax analysis available. Please upload slips first.');
        }

        $tenant = Auth::user()->currentTenant();

        if (! $tenant || (int) ($review['tenant_id'] ?? 0) !== (int) $tenant->id) {
            session()->forget('road_tax_import_review');

            return redirect()->to(route('ai.index').'#add-road-tax')
                ->with('error', 'Analysis session expired or belongs to another company.');
        }

        $validated = $request->validate([
            'rows' => 'required|array|min:1',
            'rows.*.row_id' => 'required|string',
            'rows.*.include' => 'nullable|in:0,1',
            'rows.*.registration' => 'nullable|string|max:20',
            'rows.*.start_date' => 'nullable|date',
            'rows.*.term' => ['nullable', Rule::in(['6 months', '12 months'])],
            'rows.*.amount' => 'nullable|numeric|min:0',
        ]);

        $reviewRowsById = collect($review['rows'])->keyBy('row_id');
        $applyRows = [];
        $errors = [];

        foreach ($validated['rows'] as $index => $inputRow) {
            if (empty($inputRow['include']) || (string) $inputRow['include'] !== '1') {
                continue;
            }

            $rowId = $inputRow['row_id'];
            $sourceRow = $reviewRowsById->get($rowId);

            if (! $sourceRow) {
                $errors[] = 'Row '.($index + 1).': unknown slip reference.';

                continue;
            }

            $registration = trim((string) ($inputRow['registration'] ?? ''));
            $startDate = $inputRow['start_date'] ?? null;
            $term = $inputRow['term'] ?? null;
            $amount = $inputRow['amount'] ?? null;

            if ($registration === '' || ! $startDate || ! $term || $amount === null || $amount === '') {
                $errors[] = ($sourceRow['filename'] ?? 'Slip').': please complete registration, start date, term, and amount.';

                continue;
            }

            $applyRows[] = [
                'row_id' => $rowId,
                'filename' => $sourceRow['filename'],
                'registration' => $registration,
                'start_date' => $startDate,
                'term' => $term,
                'amount' => $amount,
            ];
        }

        if ($errors !== []) {
            return redirect()->back()
                ->withInput()
                ->with('error', implode(' ', $errors));
        }

        if ($applyRows === []) {
            return redirect()->back()
                ->with('error', 'Please include at least one slip to save.');
        }

        $report = $importService->apply($tenant->id, $applyRows);

        $batchId = $review['batch_id'] ?? null;
        if ($batchId) {
            $batchDir = storage_path('app/road-tax-import/'.$batchId);
            if (is_dir($batchDir)) {
                File::deleteDirectory($batchDir);
            }
        }

        session()->forget('road_tax_import_review');
        session(['road_tax_import_report' => $report]);

        return redirect()->route('ai.road-tax.report');
    }

    public function roadTaxReport()
    {
        $report = session('road_tax_import_report');

        if (! $report) {
            return redirect()->to(route('ai.index').'#add-road-tax')
                ->with('error', 'No road tax import report available.');
        }

        return view($this->dir.'road-tax-report', compact('report'));
    }

    public function roadTaxPreview(string $batchId, string $filename)
    {
        $review = session('road_tax_import_review');

        if (! $review || ($review['batch_id'] ?? '') !== $batchId) {
            abort(404);
        }

        $tenant = Auth::user()->currentTenant();

        if (! $tenant || (int) ($review['tenant_id'] ?? 0) !== (int) $tenant->id) {
            abort(403);
        }

        $safeName = basename($filename);
        $path = storage_path('app/road-tax-import/'.$batchId.'/'.$safeName);

        if (! is_file($path)) {
            abort(404);
        }

        return response()->file($path);
    }

    /**
     * @return list<array{path: string, display: string}>
     */
    private function extractSlipFilesFromZip(string $zipPath, string $targetDir): array
    {
        $zip = new ZipArchive;

        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Could not open ZIP file.');
        }

        $slipFiles = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);

            if (! is_string($entry) || str_ends_with($entry, '/')) {
                continue;
            }

            $extension = strtolower(pathinfo($entry, PATHINFO_EXTENSION));

            if (! in_array($extension, self::SLIP_EXTENSIONS, true)) {
                continue;
            }

            $basename = basename($entry);
            $target = $targetDir.DIRECTORY_SEPARATOR.$basename;

            if (file_exists($target)) {
                $target = $targetDir.DIRECTORY_SEPARATOR.Str::uuid().'_'.$basename;
            }

            copy('zip://'.$zipPath.'#'.$entry, $target);
            $slipFiles[] = [
                'path' => $target,
                'display' => $basename,
            ];
        }

        $zip->close();

        return $slipFiles;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function detectAccountWarning(array $rows): ?string
    {
        if ($rows === []) {
            return null;
        }

        $failed = array_filter($rows, fn ($row) => ($row['extraction_status'] ?? '') !== 'ok');

        if (count($failed) !== count($rows)) {
            return null;
        }

        $messages = array_filter(array_map(fn ($row) => strtolower((string) ($row['message'] ?? '')), $failed));

        foreach ($messages as $message) {
            if (str_contains($message, 'limit: 0') || str_contains($message, 'unavailable on your account')) {
                return 'Gemini model is unavailable. Set GEMINI_MODEL=gemini-2.5-flash in .env, run php artisan config:clear, then try again.';
            }

            if (str_contains($message, 'quota') || str_contains($message, 'rate limit') || str_contains($message, 'resource exhausted')) {
                return 'Gemini API quota or rate limit reached — AI could not read the slips. Wait and try again, or enter slip details manually below.';
            }

            if (str_contains($message, 'invalid') && str_contains($message, 'api key')) {
                return 'Gemini API key is invalid. Update GEMINI_API_KEY in .env, or enter slip details manually below.';
            }
        }

        return 'AI could not read the uploaded slips. Please enter registration, start date, term, and amount manually for each row below.';
    }
}
