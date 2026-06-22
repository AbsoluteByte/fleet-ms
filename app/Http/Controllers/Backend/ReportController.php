<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\CarMot;
use App\Models\CarPhv;
use App\Models\Company;
use App\Models\InsuranceProvider;
use App\Services\InsuranceDateRangeReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    protected $dir = 'backend.reports.';

    public function __construct(
        private readonly InsuranceDateRangeReportService $insuranceReportService,
    ) {
        $this->middleware('role:admin|manager|user');
        view()->share('dir', $this->dir);
    }

    public function index(Request $request)
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

        $insuranceFrom = $request->query('insurance_from');
        $insuranceTo = $request->query('insurance_to');
        $insuranceCompanyId = $request->filled('insurance_company_id')
            ? (int) $request->query('insurance_company_id')
            : null;
        $insuranceProviderId = $request->filled('insurance_provider_id')
            ? (int) $request->query('insurance_provider_id')
            : null;
        $insuranceDateError = null;
        $insuranceRemovedInRange = collect();
        $insuranceActivatedStillActive = collect();
        $insuranceActivatedAndEnded = collect();
        $insurancePreExisting = collect();

        $reportCompanies = Company::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $reportInsuranceProviders = InsuranceProvider::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('provider_name')
            ->get(['id', 'provider_name']);

        if ($insuranceCompanyId && ! $reportCompanies->contains('id', $insuranceCompanyId)) {
            $insuranceCompanyId = null;
        }

        if ($insuranceProviderId && ! $reportInsuranceProviders->contains('id', $insuranceProviderId)) {
            $insuranceProviderId = null;
        }

        if ($insuranceFrom !== null || $insuranceTo !== null) {
            $parsedRange = $this->insuranceReportService->parseDateRange($insuranceFrom, $insuranceTo);

            if ($parsedRange === null) {
                $insuranceDateError = 'Please select a valid date range (From must be on or before To).';
            } else {
                [$from, $to] = $parsedRange;
                $insuranceRemovedInRange = $this->insuranceReportService->removedInRange(
                    $tenant->id, $from, $to, $insuranceCompanyId, $insuranceProviderId
                );
                $insuranceActivatedStillActive = $this->insuranceReportService->activatedStillActive(
                    $tenant->id, $from, $to, $insuranceCompanyId, $insuranceProviderId
                );
                $insuranceActivatedAndEnded = $this->insuranceReportService->activatedAndEndedInRange(
                    $tenant->id, $from, $to, $insuranceCompanyId, $insuranceProviderId
                );
                $insurancePreExisting = $this->insuranceReportService->preExistingPolicies(
                    $tenant->id, $from, $to, $insuranceCompanyId, $insuranceProviderId
                );
            }
        }

        $selectedInsuranceCompany = $insuranceCompanyId
            ? $reportCompanies->firstWhere('id', $insuranceCompanyId)
            : null;
        $selectedInsuranceProvider = $insuranceProviderId
            ? $reportInsuranceProviders->firstWhere('id', $insuranceProviderId)
            : null;

        return view($this->dir.'index', compact(
            'cars',
            'insuranceFrom',
            'insuranceTo',
            'insuranceCompanyId',
            'insuranceProviderId',
            'insuranceDateError',
            'insuranceRemovedInRange',
            'insuranceActivatedStillActive',
            'insuranceActivatedAndEnded',
            'insurancePreExisting',
            'reportCompanies',
            'reportInsuranceProviders',
            'selectedInsuranceCompany',
            'selectedInsuranceProvider',
        ));
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
