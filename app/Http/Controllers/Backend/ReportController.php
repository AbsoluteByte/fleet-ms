<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\CarMot;
use App\Models\CarPhv;
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
            $motExpiry = $latestMot?->expiry_date;
            $car->report_latest_mot = $latestMot;
            $car->report_mot_expiry = $motExpiry;
            $car->report_mot_missing = ! $motExpiry;
            $car->report_mot_status = $this->expiryStatusLabel($motExpiry);

            $latestPhv = $this->latestPhvForCar($car);
            $phvExpiry = $latestPhv?->expiry_date;
            $car->report_latest_phv = $latestPhv;
            $car->report_phv_expiry = $phvExpiry;
            $car->report_phv_missing = ! $phvExpiry;
            $car->report_phv_status = $this->expiryStatusLabel($phvExpiry);

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

    private function latestPhvForCar(Car $car): ?CarPhv
    {
        return $car->phvs
            ->sortByDesc(fn (CarPhv $p) => [optional($p->expiry_date)->timestamp ?? 0, $p->id])
            ->first();
    }

    private function expiryStatusLabel(?Carbon $expiry): string
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
