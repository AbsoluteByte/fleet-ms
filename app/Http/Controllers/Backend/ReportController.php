<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\CarMot;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    protected $dir = 'backend.reports.';

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

        $cars = Car::where('tenant_id', $tenant->id)
            ->with([
                'company',
                'carModel',
                'phvs.counsel',
                'insurances.status',
                'mots',
            ])
            ->latest()
            ->get();

        $cars = $cars->map(function (Car $car) {
            $latestMot = $this->latestMotForCar($car);
            $expiry = $latestMot?->expiry_date;
            $car->report_latest_mot = $latestMot;
            $car->report_mot_expiry = $expiry;
            $car->report_mot_missing = ! $expiry;
            $car->report_mot_status = $this->motStatusLabel($expiry);

            return $car;
        });

        return view($this->dir.'index', compact('cars'));
    }

    private function latestMotForCar(Car $car): ?CarMot
    {
        return $car->mots
            ->sortByDesc(fn (CarMot $m) => [optional($m->expiry_date)->timestamp ?? 0, $m->id])
            ->first();
    }

    private function motStatusLabel(?Carbon $expiry): string
    {
        if (! $expiry) {
            return 'Missing';
        }

        $today = now()->startOfDay();
        $expiryDay = $expiry->copy()->startOfDay();

        if ($expiryDay->lt($today)) {
            return 'Expired';
        }

        if ($expiryDay->lte($today->copy()->addDays(30))) {
            return 'Expiring';
        }

        return 'OK';
    }
}
