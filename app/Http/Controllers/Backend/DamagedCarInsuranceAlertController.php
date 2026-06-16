<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\DamagedCarActiveInsuranceAlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DamagedCarInsuranceAlertController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin|manager|user');
    }

    public function index(DamagedCarActiveInsuranceAlertService $alertService): JsonResponse
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return response()->json(['cars' => []]);
        }

        return response()->json([
            'cars' => $alertService->alertableCars($tenant->id)->all(),
        ]);
    }

    public function dismiss(Request $request, DamagedCarActiveInsuranceAlertService $alertService): JsonResponse
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return response()->json(['ok' => false], 403);
        }

        $validated = $request->validate([
            'car_ids' => 'required|array|min:1',
            'car_ids.*' => 'integer',
        ]);

        $allowedIds = $alertService
            ->alertableCars($tenant->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $toDismiss = collect($validated['car_ids'])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => in_array($id, $allowedIds, true))
            ->values()
            ->all();

        if ($toDismiss !== []) {
            $alertService->dismissForSession($toDismiss);
        }

        return response()->json(['ok' => true]);
    }
}
