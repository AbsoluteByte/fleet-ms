<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Services\PhvlSuspensionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DamagedCarsController extends Controller
{
    public function __construct(private PhvlSuspensionService $phvlSuspensionService)
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

        return view('backend.phvl.damaged-cars', [
            'statusLabels' => PhvlSuspensionService::statusLabels(),
        ]);
    }

    public function data(Request $request)
    {
        $tenant = Auth::user()->currentTenant();
        if (! $tenant) {
            return response()->json(['data' => []], 403);
        }

        $tab = $request->query('tab', 'all');
        if (! in_array($tab, ['all', 'suspended', 'suspension_uplifted', 'licence_revoked', 'active'], true)) {
            $tab = 'all';
        }

        $query = Car::query()
            ->forCurrentTenant()
            ->nonFaultDamaged()
            ->with(['carModel', 'company']);

        match ($tab) {
            'suspended' => $query->where('phvl_suspension_status', PhvlSuspensionService::STATUS_SUSPENDED),
            'suspension_uplifted' => $query->where('phvl_suspension_status', PhvlSuspensionService::STATUS_SUSPENSION_UPLIFTED),
            'licence_revoked' => $query->where('phvl_suspension_status', PhvlSuspensionService::STATUS_LICENCE_REVOKED),
            'active' => $query->where(function ($q) {
                $q->whereNull('phvl_suspension_status')
                    ->orWhere('phvl_suspension_status', PhvlSuspensionService::STATUS_ACTIVE);
            }),
            default => null,
        };

        $rows = $query->orderBy('registration')
            ->get()
            ->map(fn (Car $car) => $this->formatRow($car))
            ->values()
            ->all();

        return response()->json(['data' => $rows]);
    }

    public function updateStatus(Request $request, Car $car)
    {
        $tenant = Auth::user()->currentTenant();
        abort_unless($tenant && (int) $car->tenant_id === (int) $tenant->id, 403);
        abort_unless($car->fleet_status === 'damaged', 422, 'Car is not currently damaged.');

        $validated = $request->validate([
            'phvl_suspension_status' => ['required', Rule::in(PhvlSuspensionService::statuses())],
            'phvl_suspension_status_date' => [
                Rule::requiredIf(fn () => $request->input('phvl_suspension_status') !== PhvlSuspensionService::STATUS_ACTIVE),
                'nullable',
                'date',
            ],
            'phvl_suspension_notes' => 'nullable|string|max:1000',
        ]);

        $this->phvlSuspensionService->applyStatus(
            $car,
            $validated['phvl_suspension_status'],
            ! empty($validated['phvl_suspension_status_date'])
                ? Carbon::parse($validated['phvl_suspension_status_date'])
                : null,
            $validated['phvl_suspension_notes'] ?? null
        );

        return response()->json(['message' => 'PHVL suspension status updated.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatRow(Car $car): array
    {
        $incident = $this->phvlSuspensionService->latestDamagedIncident($car) ?? [];
        $effectiveStatus = $this->phvlSuspensionService->effectiveStatus($car);
        $statusLabel = PhvlSuspensionService::statusLabels()[$effectiveStatus] ?? '—';
        $daysSuspended = $this->phvlSuspensionService->daysSuspended($car);
        $warningLevel = $this->phvlSuspensionService->suspensionWarningLevel($car);
        $warningLabel = $this->phvlSuspensionService->suspensionWarningLabel($car);

        $warningHtml = '—';
        if ($warningLevel && $warningLabel) {
            $warningHtml = '<span class="badge badge-'.$warningLevel.'">'.$warningLabel.'</span>';
        }

        return [
            'id' => $car->id,
            'registration' => e($car->registration),
            'make_model' => e(trim(($car->carModel->name ?? '').' '.($car->color ?? ''))),
            'company' => e($car->company->name ?? '—'),
            'damage_date' => ! empty($incident['damage_date'])
                ? Carbon::parse($incident['damage_date'])->format('d/m/Y')
                : '—',
            'incident_date' => ! empty($incident['incident_date'])
                ? Carbon::parse($incident['incident_date'])->format('d/m/Y')
                : '—',
            'claim_ref' => e($incident['insurance_claim_reference'] ?? '—'),
            'phvl_status' => e($statusLabel),
            'phvl_status_date' => $car->phvl_suspension_status_date?->format('d/m/Y') ?? '—',
            'days_suspended' => $daysSuspended !== null ? (string) $daysSuspended : '—',
            'suspension_warning' => $warningHtml,
            'current_phvl_status' => $effectiveStatus,
            'actions' => '<button type="button" class="btn btn-sm btn-outline-primary damaged-cars-change-status"'
                .' data-car-id="'.$car->id.'"'
                .' data-registration="'.e($car->registration).'"'
                .' data-current-status="'.e($effectiveStatus).'"'
                .' data-current-date="'.e($car->phvl_suspension_status_date?->toDateString() ?? '').'">'
                .'<i class="fa fa-edit"></i> Change PHVL status</button>',
        ];
    }
}
