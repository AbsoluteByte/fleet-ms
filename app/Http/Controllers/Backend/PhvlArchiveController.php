<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\CarPhvlArchive;
use App\Support\PhvlWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PhvlArchiveController extends Controller
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

        return view('backend.phvl.archive');
    }

    public function data(Request $request)
    {
        $tenant = Auth::user()->currentTenant();
        if (! $tenant) {
            return response()->json(['data' => []], 403);
        }

        $archives = CarPhvlArchive::query()
            ->where('tenant_id', $tenant->id)
            ->with(['car.company', 'car.carModel', 'carPhv.counsel', 'completedByUser'])
            ->orderByDesc('completed_at')
            ->get();

        $rows = $archives->map(fn (CarPhvlArchive $a) => $this->formatRow($a))->values()->all();

        return response()->json(['data' => $rows]);
    }

    public function timeline(CarPhvlArchive $archive)
    {
        $tenant = Auth::user()->currentTenant();
        abort_unless($tenant && (int) $archive->tenant_id === (int) $tenant->id, 403);

        $events = $archive->events()
            ->with('user')
            ->orderBy('created_at')
            ->get()
            ->map(fn ($e) => [
                'field' => PhvlWorkflow::fieldLabels()[$e->field] ?? $e->field,
                'old_value' => $e->old_value ?? '—',
                'new_value' => $e->new_value ?? '—',
                'user' => $e->user?->name ?? '—',
                'at' => $e->created_at?->format('d M, Y H:i') ?? '—',
            ]);

        return response()->json(['events' => $events]);
    }

    private function formatRow(CarPhvlArchive $archive): array
    {
        $car = $archive->car;
        $phv = $archive->phv_summary ?? [];
        $motLabel = $archive->mot_status === 'done' ? 'Done' : 'Pending';
        $appLabel = $archive->application_status === 'applied' ? 'Applied' : 'Pending';
        $apptLabels = [
            'pending' => 'Pending',
            'additional_documents' => 'Additional Documents required',
            'approved' => 'Approved',
            'confirmed' => 'Approved',
        ];
        $apptLabel = $apptLabels[$archive->appointment_confirmation] ?? $archive->appointment_confirmation;
        $phvlLabel = match ($archive->phvl_result_status) {
            'pass' => 'Pass',
            'fail' => 'Fail',
            default => '—',
        };

        $phvSummary = '—';
        if (! empty($phv)) {
            $phvSummary = sprintf(
                '%s · %s – %s',
                $phv['counsel'] ?? '—',
                isset($phv['start_date']) ? date('d M, Y', strtotime($phv['start_date'])) : '—',
                isset($phv['expiry_date']) ? date('d M, Y', strtotime($phv['expiry_date'])) : '—'
            );
        }

        return [
            'id' => $archive->id,
            'make_model' => e($car?->carModel?->name ?? '—'),
            'registration' => e($car?->registration ?? '—'),
            'company' => e($car?->company?->name ?? '—'),
            'renewal_context' => e($archive->renewal_context ?? '—'),
            'mot_status' => e($motLabel),
            'application_status' => e($appLabel),
            'applied_date' => e($archive->applied_date?->format('d M, Y') ?? '—'),
            'appointment_confirmation' => e($apptLabel),
            'appointment_at' => e($archive->appointment_at?->format('d M, Y H:i') ?? '—'),
            'phvl_result_status' => e($phvlLabel),
            'phv_summary' => e($phvSummary),
            'completed_at' => e($archive->completed_at?->format('d M, Y H:i') ?? '—'),
            'completed_by' => e($archive->completedByUser?->name ?? '—'),
            'actions' => '<button type="button" class="btn btn-sm btn-outline-primary phvl-archive-timeline-btn" data-archive-id="'.$archive->id.'">View timeline</button>',
        ];
    }
}
