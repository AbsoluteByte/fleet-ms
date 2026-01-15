<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\CarMot;
use App\Models\CarPhv;
use App\Models\CarRoadTax;
use App\Models\Driver;
use App\Models\Agreement;
use App\Models\AgreementCollection;
use App\Models\InsurancePolicy;
use App\Models\Claim;
use App\Models\Expense;
use App\Models\Penalty;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $dir = 'backend.dashboard.';

    public function __construct()
    {
        $this->middleware('role:admin|superuser');
        view()->share('dir', $this->dir);
    }

    public function index()
    {
        // Basic stats
        $totalCars = Car::count();
        $totalDrivers = Driver::count();
        $activeAgreements = Agreement::whereDate('end_date', '>=', now())->count();
        $totalClaims = Claim::count();

        // Payment notifications and overdue collections
        $overdueCollections = AgreementCollection::with(['agreement.driver', 'agreement.car'])
            ->where('payment_status', 'overdue')
            ->orderBy('due_date')
            ->get();

        $upcomingCollections = AgreementCollection::with(['agreement.driver', 'agreement.car'])
            ->where('payment_status', 'pending')
            ->whereBetween('due_date', [now(), now()->addDays(7)])
            ->orderBy('due_date')
            ->get();

        // Get unified notifications for dashboard
        $notificationData = $this->getUnifiedNotifications();
        $taskBarNotifications = $notificationData['notifications']->take(6);

        // Financial summary
        $monthlyRevenue = AgreementCollection::where('payment_status', 'paid')
            ->whereMonth('payment_date', now()->month)
            ->sum('amount_paid');

        $totalOutstanding = AgreementCollection::whereIn('payment_status', ['pending', 'overdue'])
            ->sum('amount');

        // Monthly trends
        $monthlyRevenueData = [];
        $monthlyExpenseData = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);

            $monthlyRevenueData[] = AgreementCollection::where('payment_status', 'paid')
                ->whereYear('payment_date', $date->year)
                ->whereMonth('payment_date', $date->month)
                ->sum('amount_paid');

            $monthlyExpenseData[] = Expense::whereYear('date', $date->year)
                ->whereMonth('date', $date->month)
                ->sum('amount');
        }

        // Agreement status summary
        $agreementStatusSummary = Agreement::with('status')
            ->selectRaw('status_id, COUNT(*) as count')
            ->groupBy('status_id')
            ->get()
            ->map(function($item) {
                return [
                    'status' => $item->status->name,
                    'count' => $item->count,
                    'color' => $item->status->color
                ];
            });

        // Recent activities
        $recentClaims = Claim::with(['car', 'status'])
            ->latest()
            ->take(5)
            ->get();
        if (auth()->user()->isSuperUser()) {
           return view($this->dir.'superUserDashboard');
        }
        else{
            return view($this->dir.'index', compact(
                'totalCars', 'totalDrivers', 'activeAgreements', 'totalClaims',
                'overdueCollections', 'upcomingCollections', 'taskBarNotifications',
                'monthlyRevenue', 'totalOutstanding', 'monthlyRevenueData',
                'monthlyExpenseData', 'agreementStatusSummary', 'recentClaims'
            ));
        }
    }

    public function getUnifiedNotifications()
    {
        // Update overdue collections first
        AgreementCollection::where('payment_status', 'pending')
            ->where('due_date', '<', now())
            ->update(['payment_status' => 'overdue']);

        $notifications = collect();

        // ==================== 1. OVERDUE PAYMENTS ====================
        $overdueCollections = AgreementCollection::with(['agreement.driver', 'agreement.car'])
            ->where('tenant_id', Auth::user()->currentTenant()->id) // ✅ Filter by tenant
            ->where('payment_status', 'overdue')
            ->orderBy('due_date')
            ->get();

        foreach ($overdueCollections as $collection) {
            $notifications->push([
                'id' => 'overdue_' . $collection->id,
                'type' => 'overdue_payment',
                'priority' => 1,
                'title' => 'Overdue Payment',
                'message' => $collection->agreement->driver->full_name . ' - Overdue by ' . $collection->days_overdue . ' days',
                'simple_message' => $collection->agreement->driver->full_name . ' - Overdue by ' . $collection->days_overdue . ' days',
                'amount' => '£' . number_format($collection->remaining_amount, 2),
                'vehicle' => $collection->agreement->car->registration,
                'time_ago' => 'Due ' . $collection->due_date->diffForHumans(),
                'action_url' => route('agreements.show', $collection->agreement),
                'icon' => 'icon-alert-triangle',
                'color' => 'danger',
                'bg_color' => 'rgba(239, 68, 68, 0.1)',
                'border_color' => '#ef4444',
                'created_at' => $collection->due_date
            ]);
        }

        // ==================== 2. DUE TODAY PAYMENTS ====================
        $dueTodayCollections = AgreementCollection::with(['agreement.driver', 'agreement.car'])
            ->where('tenant_id', Auth::user()->currentTenant()->id) // ✅ Filter by tenant
            ->where('payment_status', 'pending')
            ->whereDate('due_date', now())
            ->get();

        foreach ($dueTodayCollections as $collection) {
            $notifications->push([
                'id' => 'due_today_' . $collection->id,
                'type' => 'due_today',
                'priority' => 2,
                'title' => 'Payment Due Today',
                'message' => $collection->agreement->driver->full_name . ' - Payment due today',
                'simple_message' => $collection->agreement->driver->full_name . ' - Due today',
                'amount' => '£' . number_format($collection->amount, 2),
                'vehicle' => $collection->agreement->car->registration,
                'time_ago' => 'Due Today',
                'action_url' => route('agreements.show', $collection->agreement),
                'icon' => 'icon-clock',
                'color' => 'warning',
                'bg_color' => 'rgba(245, 158, 11, 0.1)',
                'border_color' => '#f59e0b',
                'created_at' => $collection->due_date
            ]);
        }

        // ==================== 3. DUE THIS WEEK PAYMENTS ====================
        $dueThisWeekCollections = AgreementCollection::with(['agreement.driver', 'agreement.car'])
            ->where('tenant_id', Auth::user()->currentTenant()->id) // ✅ Filter by tenant
            ->where('payment_status', 'pending')
            ->whereBetween('due_date', [now()->addDay(), now()->addWeek()])
            ->get();

        foreach ($dueThisWeekCollections as $collection) {
            $daysUntilDue = (int)now()->diffInDays($collection->due_date);
            $notifications->push([
                'id' => 'due_week_' . $collection->id,
                'type' => 'due_this_week',
                'priority' => 3,
                'title' => 'Payment Due Soon',
                'message' => $collection->agreement->driver->full_name . ' - Due in ' . $daysUntilDue . ' days',
                'simple_message' => $collection->agreement->driver->full_name . ' - Due in ' . $daysUntilDue . ' days',
                'amount' => '£' . number_format($collection->amount, 2),
                'vehicle' => $collection->agreement->car->registration,
                'time_ago' => $collection->due_date->diffForHumans(),
                'action_url' => route('agreements.show', $collection->agreement),
                'icon' => 'icon-calendar',
                'color' => 'info',
                'bg_color' => 'rgba(59, 130, 246, 0.1)',
                'border_color' => '#3b82f6',
                'created_at' => $collection->due_date
            ]);
        }

        // ==================== 4. INSURANCE POLICIES (Including Expired) ====================
        $expiringInsurance = InsurancePolicy::with(['car'])
            ->where('tenant_id', Auth::user()->currentTenant()->id) // ✅ Filter by tenant
            ->where('policy_end_date', '<=', now()->addDays(30)) // ✅ Include expired
            ->orderBy('policy_end_date')
            ->get();

        foreach ($expiringInsurance as $policy) {
            $daysDiff = (int)now()->diffInDays($policy->policy_end_date, false);

            // ✅ Better message formatting
            if ($daysDiff > 0) {
                $msg = 'Expires in ' . $daysDiff . ' day' . ($daysDiff > 1 ? 's' : '');
                $color = 'primary';
                $priority = 4;
            } elseif ($daysDiff == 0) {
                $msg = 'Expires today';
                $color = 'warning';
                $priority = 2;
            } else {
                $msg = 'Expired ' . abs($daysDiff) . ' day' . (abs($daysDiff) > 1 ? 's' : '') . ' ago';
                $color = 'danger';
                $priority = 1;
            }

            $notifications->push([
                'id' => 'insurance_' . $policy->id,
                'type' => 'insurance_expiry',
                'priority' => $priority,
                'title' => $daysDiff >= 0 ? 'Insurance Expiring' : 'Insurance Expired',
                'message' => $policy->car->registration . ' - ' . $msg,
                'simple_message' => $policy->car->registration . ' - ' . $msg,
                'vehicle' => $policy->car->registration,
                'time_ago' => $policy->policy_end_date->diffForHumans(),
                'action_url' => route('cars.show', $policy->car_id),
                'icon' => 'icon-shield',
                'color' => $color,
                'bg_color' => $color == 'danger' ? 'rgba(239, 68, 68, 0.1)' : 'rgba(99, 102, 241, 0.1)',
                'border_color' => $color == 'danger' ? '#ef4444' : '#6366f1',
                'created_at' => $policy->policy_end_date
            ]);
        }

        // ==================== 5. PHV LICENSES (Including Expired) ====================
        $expiringPhvs = CarPhv::with(['car'])
            ->whereHas('car', function ($query) {
                $query->where('tenant_id', Auth::user()->currentTenant()->id); // ✅ Filter by tenant
            })
            ->where('expiry_date', '<=', now()->addDays(30)) // ✅ Include expired
            ->orderBy('expiry_date')
            ->get();

        foreach ($expiringPhvs as $phv) {
            $daysDiff = (int)now()->diffInDays($phv->expiry_date, false);

            if ($daysDiff > 0) {
                $msg = 'Expires in ' . $daysDiff . ' day' . ($daysDiff > 1 ? 's' : '');
                $color = 'secondary';
                $priority = 5;
            } elseif ($daysDiff == 0) {
                $msg = 'Expires today';
                $color = 'warning';
                $priority = 2;
            } else {
                $msg = 'Expired ' . abs($daysDiff) . ' day' . (abs($daysDiff) > 1 ? 's' : '') . ' ago';
                $color = 'danger';
                $priority = 1;
            }

            $notifications->push([
                'id' => 'phv_' . $phv->id,
                'type' => 'phv_expiry',
                'priority' => $priority,
                'title' => $daysDiff >= 0 ? 'PHV License Expiring' : 'PHV License Expired',
                'message' => $phv->car->registration . ' - ' . $msg,
                'simple_message' => $phv->car->registration . ' - ' . $msg,
                'vehicle' => $phv->car->registration,
                'time_ago' => $phv->expiry_date->diffForHumans(),
                'action_url' => route('cars.edit', $phv->car_id),
                'icon' => 'icon-award',
                'color' => $color,
                'bg_color' => $color == 'danger' ? 'rgba(239, 68, 68, 0.1)' : 'rgba(107, 114, 128, 0.1)',
                'border_color' => $color == 'danger' ? '#ef4444' : '#6b7280',
                'created_at' => $phv->expiry_date
            ]);
        }

        // ==================== 6. MOT CERTIFICATES (Including Expired) ====================
        $expiringMots = CarMot::with(['car'])
            ->whereHas('car', function ($query) {
                $query->where('tenant_id', Auth::user()->currentTenant()->id); // ✅ Filter by tenant
            })
            ->where('expiry_date', '<=', now()->addDays(30)) // ✅ Include expired
            ->orderBy('expiry_date')
            ->get();

        foreach ($expiringMots as $mot) {
            $daysDiff = (int)now()->diffInDays($mot->expiry_date, false);

            if ($daysDiff > 0) {
                $msg = 'Expires in ' . $daysDiff . ' day' . ($daysDiff > 1 ? 's' : '');
                $color = 'warning';
                $priority = 6;
            } elseif ($daysDiff == 0) {
                $msg = 'Expires today';
                $color = 'warning';
                $priority = 2;
            } else {
                $msg = 'Expired ' . abs($daysDiff) . ' day' . (abs($daysDiff) > 1 ? 's' : '') . ' ago';
                $color = 'danger';
                $priority = 1;
            }

            $notifications->push([
                'id' => 'mot_' . $mot->id,
                'type' => 'mot_expiry',
                'priority' => $priority,
                'title' => $daysDiff >= 0 ? 'MOT Expiring' : 'MOT Expired',
                'message' => $mot->car->registration . ' - ' . $msg,
                'simple_message' => $mot->car->registration . ' - ' . $msg,
                'vehicle' => $mot->car->registration,
                'time_ago' => $mot->expiry_date->diffForHumans(),
                'action_url' => route('cars.edit', $mot->car_id),
                'icon' => 'icon-tool',
                'color' => $color,
                'bg_color' => $color == 'danger' ? 'rgba(239, 68, 68, 0.1)' : 'rgba(245, 158, 11, 0.1)',
                'border_color' => $color == 'danger' ? '#ef4444' : '#f59e0b',
                'created_at' => $mot->expiry_date
            ]);
        }

        // ==================== 7. ROAD TAX (Including Expired) ====================
        $allRoadTaxes = CarRoadTax::with(['car'])
            ->whereHas('car', function ($query) {
                $query->where('tenant_id', Auth::user()->currentTenant()->id); // ✅ Filter by tenant
            })
            ->get();

        $expiringRoadTaxes = $allRoadTaxes->filter(function ($roadTax) {
            $expiryDate = $this->calculateRoadTaxExpiry($roadTax);
            return $expiryDate && $expiryDate <= now()->addDays(30); // ✅ Include expired
        });

        foreach ($expiringRoadTaxes as $roadTax) {
            $expiryDate = $this->calculateRoadTaxExpiry($roadTax);
            $daysDiff = (int)now()->diffInDays($expiryDate, false);

            if ($daysDiff > 0) {
                $msg = 'Expires in ' . $daysDiff . ' day' . ($daysDiff > 1 ? 's' : '');
                $color = 'success';
                $priority = 7;
            } elseif ($daysDiff == 0) {
                $msg = 'Expires today';
                $color = 'warning';
                $priority = 2;
            } else {
                $msg = 'Expired ' . abs($daysDiff) . ' day' . (abs($daysDiff) > 1 ? 's' : '') . ' ago';
                $color = 'danger';
                $priority = 1;
            }

            $notifications->push([
                'id' => 'road_tax_' . $roadTax->id,
                'type' => 'road_tax_expiry',
                'priority' => $priority,
                'title' => $daysDiff >= 0 ? 'Road Tax Expiring' : 'Road Tax Expired',
                'message' => $roadTax->car->registration . ' - ' . $msg,
                'simple_message' => $roadTax->car->registration . ' - ' . $msg,
                'vehicle' => $roadTax->car->registration,
                'time_ago' => $expiryDate->diffForHumans(),
                'action_url' => route('cars.edit', $roadTax->car_id),
                'icon' => 'icon-credit-card',
                'color' => $color,
                'bg_color' => $color == 'danger' ? 'rgba(239, 68, 68, 0.1)' : 'rgba(34, 197, 94, 0.1)',
                'border_color' => $color == 'danger' ? '#ef4444' : '#22c55e',
                'created_at' => $expiryDate
            ]);
        }

        // ==================== 8. DRIVER LICENSES (Including Expired) ====================
        $expiringDriverLicenses = Driver::where('tenant_id', Auth::user()->currentTenant()->id) // ✅ Filter by tenant
        ->where('driver_license_expiry_date', '<=', now()->addDays(30)) // ✅ Include expired
        ->orderBy('driver_license_expiry_date')
            ->get();

        foreach ($expiringDriverLicenses as $driver) {
            $daysDiff = (int)now()->diffInDays($driver->driver_license_expiry_date, false);

            if ($daysDiff > 0) {
                $msg = 'Expires in ' . $daysDiff . ' day' . ($daysDiff > 1 ? 's' : '');
                $color = 'info';
                $priority = 8;
            } elseif ($daysDiff == 0) {
                $msg = 'Expires today';
                $color = 'warning';
                $priority = 2;
            } else {
                $msg = 'Expired ' . abs($daysDiff) . ' day' . (abs($daysDiff) > 1 ? 's' : '') . ' ago';
                $color = 'danger';
                $priority = 1;
            }

            $notifications->push([
                'id' => 'driver_license_' . $driver->id,
                'type' => 'driver_license_expiry',
                'priority' => $priority,
                'title' => $daysDiff >= 0 ? 'Driver License Expiring' : 'Driver License Expired',
                'message' => $driver->full_name . ' - ' . $msg,
                'simple_message' => $driver->full_name . ' - ' . $msg,
                'driver' => $driver->full_name,
                'time_ago' => $driver->driver_license_expiry_date->diffForHumans(),
                'action_url' => route('drivers.edit', $driver->id),
                'icon' => 'icon-user',
                'color' => $color,
                'bg_color' => $color == 'danger' ? 'rgba(239, 68, 68, 0.1)' : 'rgba(59, 130, 246, 0.1)',
                'border_color' => $color == 'danger' ? '#ef4444' : '#3b82f6',
                'created_at' => $driver->driver_license_expiry_date
            ]);
        }

        // ==================== 9. PHD LICENSES (Including Expired) ====================
        $expiringPhdLicenses = Driver::where('tenant_id', Auth::user()->currentTenant()->id) // ✅ Filter by tenant
        ->whereNotNull('phd_license_expiry_date')
            ->where('phd_license_expiry_date', '<=', now()->addDays(30)) // ✅ Include expired
            ->orderBy('phd_license_expiry_date')
            ->get();

        foreach ($expiringPhdLicenses as $driver) {
            $daysDiff = (int)now()->diffInDays($driver->phd_license_expiry_date, false);

            if ($daysDiff > 0) {
                $msg = 'Expires in ' . $daysDiff . ' day' . ($daysDiff > 1 ? 's' : '');
                $color = 'secondary';
                $priority = 9;
            } elseif ($daysDiff == 0) {
                $msg = 'Expires today';
                $color = 'warning';
                $priority = 2;
            } else {
                $msg = 'Expired ' . abs($daysDiff) . ' day' . (abs($daysDiff) > 1 ? 's' : '') . ' ago';
                $color = 'danger';
                $priority = 1;
            }

            $notifications->push([
                'id' => 'phd_license_' . $driver->id,
                'type' => 'phd_license_expiry',
                'priority' => $priority,
                'title' => $daysDiff >= 0 ? 'PHD License Expiring' : 'PHD License Expired',
                'message' => $driver->full_name . ' - ' . $msg,
                'simple_message' => $driver->full_name . ' - ' . $msg,
                'driver' => $driver->full_name,
                'time_ago' => $driver->phd_license_expiry_date->diffForHumans(),
                'action_url' => route('drivers.edit', $driver->id),
                'icon' => 'icon-user-check',
                'color' => $color,
                'bg_color' => $color == 'danger' ? 'rgba(239, 68, 68, 0.1)' : 'rgba(107, 114, 128, 0.1)',
                'border_color' => $color == 'danger' ? '#ef4444' : '#6b7280',
                'created_at' => $driver->phd_license_expiry_date
            ]);
        }

        // Sort notifications by priority and then by creation date
        $sortedNotifications = $notifications->sortBy([
            ['priority', 'asc'],
            ['created_at', 'asc']
        ]);

        // Generate summary counts
        $summary = [
            'overdue_payments' => $overdueCollections->count(),
            'due_today' => $dueTodayCollections->count(),
            'due_this_week' => $dueThisWeekCollections->count(),
            'expiring_insurance' => $expiringInsurance->count(),
            'expiring_phv' => $expiringPhvs->count(),
            'expiring_mot' => $expiringMots->count(),
            'expiring_road_tax' => $expiringRoadTaxes->count(),
            'expiring_driver_licenses' => $expiringDriverLicenses->count(),
            'expiring_phd_licenses' => $expiringPhdLicenses->count(),
            'total_count' => $sortedNotifications->count()
        ];

        return [
            'notifications' => $sortedNotifications,
            'summary' => $summary
        ];
    }

    public function getFleetNotifications()
    {
        $data = $this->getUnifiedNotifications();

        return response()->json([
            'overdue' => $data['summary']['overdue_payments'],
            'due_today' => $data['summary']['due_today'],
            'due_this_week' => $data['summary']['due_this_week'],
            'expiring_insurance' => $data['summary']['expiring_insurance'],
            'expiring_phv' => $data['summary']['expiring_phv'],
            'expiring_mot' => $data['summary']['expiring_mot'],
            'expiring_road_tax' => $data['summary']['expiring_road_tax'],
            'expiring_driver_licenses' => $data['summary']['expiring_driver_licenses'],
            'expiring_phd_licenses' => $data['summary']['expiring_phd_licenses'],
            'notifications' => $data['notifications']->take(15)->values()
        ]);
    }

    public function notificationsIndex(Request $request)
    {
        // If DataTables AJAX request
        if ($request->ajax()) {
            $data = $this->getUnifiedNotifications();
            $notifications = $data['notifications'];

            // Filter by type if requested
            if ($request->has('type') && $request->type) {
                $notifications = $notifications->where('type', $request->type);
            }

            return datatables()->of($notifications)->toJson();
        }

        // Regular page load
        $data = $this->getUnifiedNotifications();
        $summary = $data['summary'];

        return view($this->dir.'notifications', compact('summary'));
    }
    // Helper method to calculate road tax expiry date
    private function calculateRoadTaxExpiry($roadTax)
    {
        if (!$roadTax->start_date || !$roadTax->term) {
            return null;
        }

        $startDate = \Carbon\Carbon::parse($roadTax->start_date);

        switch (strtolower($roadTax->term)) {
            case '6 months':
                return $startDate->copy()->addMonths(6);
            case '12 months':
            case '1 year':
                return $startDate->copy()->addYear();
            default:
                if (preg_match('/(\d+)\s*(month|year)/', strtolower($roadTax->term), $matches)) {
                    $number = (int)$matches[1];
                    $unit = $matches[2];

                    if ($unit === 'month') {
                        return $startDate->copy()->addMonths($number);
                    } elseif ($unit === 'year') {
                        return $startDate->copy()->addYears($number);
                    }
                }
                return null;
        }
    }

    public function fileManager()
    {
        return view('backend.file-manager');
    }
}
