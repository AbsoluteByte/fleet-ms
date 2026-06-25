<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\MotTestDateImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MotTestDateImportController extends Controller
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

        return view('backend.mot-test-date-import.index');
    }

    public function store(Request $request, MotTestDateImportService $importService)
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found! Please contact administrator.');
        }

        $validated = $request->validate([
            'upload_file' => 'required|file|mimes:csv,xlsx|max:10240',
        ]);

        $tempPath = storage_path('app/mot-test-date-import/'.Str::uuid().'.'.$request->file('upload_file')->getClientOriginalExtension());
        File::ensureDirectoryExists(dirname($tempPath));

        try {
            $request->file('upload_file')->move(dirname($tempPath), basename($tempPath));

            $report = $importService->import($tempPath, $tenant->id);

            session(['mot_test_date_import_report' => $report]);

            return redirect()->route('mot-test-date-import.report');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Import failed: '.$e->getMessage());
        } finally {
            if (isset($tempPath) && is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    public function report()
    {
        $report = session('mot_test_date_import_report');

        if (! $report) {
            return redirect()->route('mot-test-date-import.index')
                ->with('error', 'No import report available. Please run an import first.');
        }

        return view('backend.mot-test-date-import.report', compact('report'));
    }
}
