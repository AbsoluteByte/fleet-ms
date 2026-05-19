<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Agreement;
use App\Models\AgreementCollection;
use App\Models\Car;
use App\Models\CarInsurance;
use App\Models\CarMot;
use App\Models\CarPhv;
use App\Models\CarRoadTax;
use App\Models\CarService;
use App\Models\Claim;
use App\Models\Driver;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected $dir = 'backend.dashboard.';

    public function __construct()
    {
        $this->middleware('role:admin|superuser|user|manager');
        view()->share('dir', $this->dir);
    }

    public function index()
    {
        // ✅ Check if superuser
        if (auth()->user()->isSuperUser()) {
            return view($this->dir.'superUserDashboard');
        }

        // ✅ Get current tenant
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }

        // ✅ Basic stats with DYNAMIC GROWTH
        $totalCars = Car::where('tenant_id', $tenant->id)->count();
        $totalDrivers = Driver::where('tenant_id', $tenant->id)->count();
        $activeAgreements = Agreement::where('tenant_id', $tenant->id)
            ->whereDate('end_date', '>=', now())
            ->count();
        $totalClaims = Claim::where('tenant_id', $tenant->id)->count();

        // ✅ DYNAMIC GROWTH CALCULATION - Last Month vs This Month
        $lastMonthCars = Car::where('tenant_id', $tenant->id)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        $lastMonthDrivers = Driver::where('tenant_id', $tenant->id)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        $lastMonthRevenue = AgreementCollection::whereHas('agreement', function ($query) use ($tenant) {
            $query->where('tenant_id', $tenant->id);
        })
            ->where('payment_status', 'paid')
            ->whereMonth('payment_date', now()->subMonth()->month)
            ->sum('amount_paid');

        $lastMonthOutstanding = AgreementCollection::whereHas('agreement', function ($query) use ($tenant) {
            $query->where('tenant_id', $tenant->id);
        })
            ->whereIn('payment_status', ['pending', 'overdue'])
            ->whereMonth('created_at', now()->subMonth()->month)
            ->sum('amount');

        // Calculate growth percentages
        $carsGrowth = $lastMonthCars > 0 ? round((($totalCars - $lastMonthCars) / $lastMonthCars) * 100, 1) : 0;
        $driversGrowth = $lastMonthDrivers > 0 ? round((($totalDrivers - $lastMonthDrivers) / $lastMonthDrivers) * 100, 1) : 0;

        // ✅ Get unified notifications
        $notificationData = $this->getUnifiedNotifications();
        $allNotifications = $notificationData['notifications'];

        // ✅ SEPARATE PAYMENT NOTIFICATIONS
        $paymentNotifications = $allNotifications->filter(function ($notification) {
            return in_array($notification['type'], ['overdue_payment', 'due_today', 'due_this_week']);
        })->take(10);

        // ✅ SEPARATE FLEET NOTIFICATIONS (prioritized by expiry)
        $fleetNotifications = $allNotifications->filter(function ($notification) {
            return ! in_array($notification['type'], ['overdue_payment', 'due_today', 'due_this_week']);
        })->sortBy(function ($notification) {
            // Sort: Expired first (priority 1), then by created_at
            return [$notification['priority'], $notification['created_at']];
        })->take(10);

        // ✅ Financial summary
        $monthlyRevenue = AgreementCollection::whereHas('agreement', function ($query) use ($tenant) {
            $query->where('tenant_id', $tenant->id);
        })
            ->where('payment_status', 'paid')
            ->whereMonth('payment_date', now()->month)
            ->sum('amount_paid');

        $totalOutstanding = AgreementCollection::whereHas('agreement', function ($query) use ($tenant) {
            $query->where('tenant_id', $tenant->id);
        })
            ->whereIn('payment_status', ['pending', 'overdue'])
            ->sum('amount');

        // Calculate revenue growth
        $revenueGrowth = $lastMonthRevenue > 0 ? round((($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1) : 0;
        $outstandingGrowth = $lastMonthOutstanding > 0 ? round((($totalOutstanding - $lastMonthOutstanding) / $lastMonthOutstanding) * 100, 1) : 0;

        // ✅ Monthly trends
        $monthlyRevenueData = [];
        $monthlyExpenseData = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);

            $monthlyRevenueData[] = AgreementCollection::whereHas('agreement', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id);
            })
                ->where('payment_status', 'paid')
                ->whereYear('payment_date', $date->year)
                ->whereMonth('payment_date', $date->month)
                ->sum('amount_paid');

            $monthlyExpenseData[] = Expense::where('tenant_id', $tenant->id)
                ->whereYear('date', $date->year)
                ->whereMonth('date', $date->month)
                ->sum('amount');
        }

        // ✅ Agreement status summary
        $agreementStatusSummary = Agreement::with('status')
            ->where('tenant_id', $tenant->id)
            ->selectRaw('status_id, COUNT(*) as count')
            ->groupBy('status_id')
            ->get()
            ->map(function ($item) {
                return [
                    'status' => $item->status->name,
                    'count' => $item->count,
                    'color' => $item->status->color,
                ];
            });

        // ✅ Recent activities
        $recentClaims = Claim::with(['car', 'status'])
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->take(5)
            ->get();

        return view($this->dir.'index', compact(
            'totalCars', 'totalDrivers', 'activeAgreements', 'totalClaims',
            'carsGrowth', 'driversGrowth', 'revenueGrowth', 'outstandingGrowth',
            'paymentNotifications', 'fleetNotifications',
            'monthlyRevenue', 'totalOutstanding', 'monthlyRevenueData',
            'monthlyExpenseData', 'agreementStatusSummary', 'recentClaims'
        ));
    }

    // ✅ UNIFIED METHOD: Get all notifications (tenant filtered)
    public function getUnifiedNotifications()
    {
        $tenant = Auth::user()->currentTenant();
        $fleetNotificationExcludedStatuses = ['written_off', 'stolen', 'sold'];
        $nonRoadTaxNotificationExcludedStatuses = array_merge($fleetNotificationExcludedStatuses, ['for_sale']);

        if (! $tenant) {
            return [
                'notifications' => collect(),
                'summary' => [
                    'overdue_payments' => 0,
                    'due_today' => 0,
                    'due_this_week' => 0,
                    'insurance_applied' => 0,
                    'expiring_insurance' => 0,
                    'expiring_phv' => 0,
                    'expiring_mot' => 0,
                    'expiring_road_tax' => 0,
                    'expiring_driver_licenses' => 0,
                    'expiring_phd_licenses' => 0,
                    'total_count' => 0,
                ],
            ];
        }

        // ✅ Update overdue collections
        AgreementCollection::whereHas('agreement', function ($query) use ($tenant) {
            $query->where('tenant_id', $tenant->id);
        })
            ->where('payment_status', 'pending')
            ->where('due_date', '<', now())
            ->update(['payment_status' => 'overdue']);

        $notifications = collect();

        // ==================== 1. OVERDUE PAYMENTS ====================
        $overdueCollections = AgreementCollection::with(['agreement.driver', 'agreement.car'])
            ->whereHas('agreement', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id);
            })
            ->where('payment_status', 'overdue')
            ->orderBy('due_date')
            ->get();

        foreach ($overdueCollections as $collection) {
            $notifications->push([
                'id' => 'overdue_'.$collection->id,
                'type' => 'overdue_payment',
                'priority' => 1,
                'title' => 'Overdue Payment',
                'message' => $collection->agreement->driver->full_name.' - Overdue by '.$collection->days_overdue.' days',
                'simple_message' => $collection->agreement->driver->full_name.' - Overdue by '.$collection->days_overdue.' days',
                'amount' => '£'.number_format($collection->remaining_amount, 2),
                'vehicle' => $collection->agreement->car->registration,
                'time_ago' => 'Due '.$collection->due_date->diffForHumans(),
                'action_url' => route('agreements.show', $collection->agreement),
                'icon' => 'icon-alert-triangle',
                'color' => 'danger',
                'bg_color' => 'rgba(239, 68, 68, 0.1)',
                'border_color' => '#ef4444',
                'created_at' => $collection->due_date,
                'sort_key' => $collection->due_date->timestamp,
            ]);
        }

        // ==================== 2. DUE TODAY PAYMENTS ====================
        $dueTodayCollections = AgreementCollection::with(['agreement.driver', 'agreement.car'])
            ->whereHas('agreement', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id);
            })
            ->where('payment_status', 'pending')
            ->whereDate('due_date', now())
            ->get();

        foreach ($dueTodayCollections as $collection) {
            $notifications->push([
                'id' => 'due_today_'.$collection->id,
                'type' => 'due_today',
                'priority' => 2,
                'title' => 'Payment Due Today',
                'message' => $collection->agreement->driver->full_name.' - Payment due today',
                'simple_message' => $collection->agreement->driver->full_name.' - Due today',
                'amount' => '£'.number_format($collection->amount, 2),
                'vehicle' => $collection->agreement->car->registration,
                'time_ago' => 'Due Today',
                'action_url' => route('agreements.show', $collection->agreement),
                'icon' => 'icon-clock',
                'color' => 'warning',
                'bg_color' => 'rgba(245, 158, 11, 0.1)',
                'border_color' => '#f59e0b',
                'created_at' => $collection->due_date,
                'sort_key' => $collection->due_date->timestamp,
            ]);
        }

        // ==================== 3. DUE THIS WEEK PAYMENTS ====================
        $dueThisWeekCollections = AgreementCollection::with(['agreement.driver', 'agreement.car'])
            ->whereHas('agreement', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id);
            })
            ->where('payment_status', 'pending')
            ->whereBetween('due_date', [now()->addDay(), now()->addWeek()])
            ->get();

        foreach ($dueThisWeekCollections as $collection) {
            $daysUntilDue = (int) now()->diffInDays($collection->due_date);
            $notifications->push([
                'id' => 'due_week_'.$collection->id,
                'type' => 'due_this_week',
                'priority' => 3,
                'title' => 'Payment Due Soon',
                'message' => $collection->agreement->driver->full_name.' - Due in '.$daysUntilDue.' days',
                'simple_message' => $collection->agreement->driver->full_name.' - Due in '.$daysUntilDue.' days',
                'amount' => '£'.number_format($collection->amount, 2),
                'vehicle' => $collection->agreement->car->registration,
                'time_ago' => $collection->due_date->diffForHumans(),
                'action_url' => route('agreements.show', $collection->agreement),
                'icon' => 'icon-calendar',
                'color' => 'info',
                'bg_color' => 'rgba(59, 130, 246, 0.1)',
                'border_color' => '#3b82f6',
                'created_at' => $collection->due_date,
                'sort_key' => $collection->due_date->timestamp,
            ]);
        }

        // ==================== 4. INSURANCE POLICIES (latest policy per car only) ====================
        $insuranceRows = CarInsurance::with(['car', 'status'])
            ->whereHas('car', function ($query) use ($tenant, $nonRoadTaxNotificationExcludedStatuses) {
                $query->where('tenant_id', $tenant->id)
                    ->whereNotIn('fleet_status', $nonRoadTaxNotificationExcludedStatuses);
            })
            ->get();
        $activeInsuranceStatusId = (int) optional(\App\Models\Status::where('type', 'insurance')->where('name', 'Active')->first())->id;
        $latestInsuranceRows = $this->latestInsurancePerCar($insuranceRows);
        $appliedInsurance = $latestInsuranceRows
            ->filter(function ($policy) {
                return strcasecmp((string) optional($policy->status)->name, 'Applied') === 0;
            })
            ->values();
        foreach ($appliedInsurance as $policy) {
            $appliedDate = $policy->applied_date ?? $policy->start_date;
            $appliedOn = $appliedDate ? $appliedDate->format('d M, Y') : 'N/A';
            $notifications->push([
                'id' => 'insurance_applied_'.$policy->id,
                'type' => 'insurance_applied',
                'priority' => 4,
                'title' => 'Insurance Applied',
                'message' => $policy->car->registration.' - Applied on '.$appliedOn,
                'simple_message' => $policy->car->registration.' - Applied on '.$appliedOn,
                'vehicle' => $policy->car->registration,
                'time_ago' => $appliedDate ? $appliedDate->diffForHumans() : 'Pending',
                'action_url' => route('cars.edit', $policy->car_id),
                'icon' => 'icon-shield',
                'color' => 'warning',
                'bg_color' => 'rgba(245, 158, 11, 0.1)',
                'border_color' => '#f59e0b',
                'created_at' => $appliedDate ?? $policy->updated_at,
                'sort_key' => ($appliedDate ?? $policy->updated_at)->timestamp,
            ]);
        }

        $expiringInsurance = $latestInsuranceRows
            ->filter(function ($policy) use ($activeInsuranceStatusId) {
                if (! $activeInsuranceStatusId || (int) $policy->status_id !== $activeInsuranceStatusId) {
                    return false;
                }
                $days = (int) ($policy->notify_before_expiry ?? 0);

                return $policy->expiry_date && $policy->expiry_date <= now()->addDays($days);
            })
            ->sortBy('expiry_date')
            ->values();

        foreach ($expiringInsurance as $policy) {
            $daysDiff = (int) now()->diffInDays($policy->expiry_date, false);

            if ($daysDiff > 0) {
                $msg = 'Expires in '.$daysDiff.' day'.($daysDiff > 1 ? 's' : '');
                $color = 'primary';
                $priority = 4;
            } elseif ($daysDiff == 0) {
                $msg = 'Expires today';
                $color = 'warning';
                $priority = 2;
            } else {
                $msg = 'Expired '.abs($daysDiff).' day'.(abs($daysDiff) > 1 ? 's' : '').' ago';
                $color = 'danger';
                $priority = 1;
            }

            $notifications->push([
                'id' => 'insurance_'.$policy->id,
                'type' => 'insurance_expiry',
                'priority' => $priority,
                'title' => $daysDiff >= 0 ? 'Insurance Expiring' : 'Insurance Expired',
                'message' => $policy->car->registration.' - '.$msg,
                'simple_message' => $policy->car->registration.' - '.$msg,
                'vehicle' => $policy->car->registration,
                'time_ago' => $policy->expiry_date->diffForHumans(),
                'action_url' => route('cars.show', $policy->car_id),
                'icon' => 'icon-shield',
                'color' => $color,
                'bg_color' => $color == 'danger' ? 'rgba(239, 68, 68, 0.1)' : 'rgba(99, 102, 241, 0.1)',
                'border_color' => $color == 'danger' ? '#ef4444' : '#6366f1',
                'created_at' => $policy->expiry_date,
                'sort_key' => $policy->expiry_date->timestamp,
            ]);
        }

        // ==================== 5. PHV LICENSES (latest PHV per car only) ====================
        $phvRows = CarPhv::with(['car'])
            ->whereHas('car', function ($query) use ($tenant, $nonRoadTaxNotificationExcludedStatuses) {
                $query->where('tenant_id', $tenant->id)
                    ->whereNotIn('fleet_status', $nonRoadTaxNotificationExcludedStatuses);
            })
            ->get();
        $expiringPhvs = $this->latestPhvPerCar($phvRows)
            ->filter(function ($phv) {
                $days = (int) ($phv->notify_before_expiry ?? 0);

                return $phv->expiry_date <= now()->addDays($days);
            })
            ->sortBy('expiry_date')
            ->values();

        foreach ($expiringPhvs as $phv) {
            $daysDiff = (int) now()->diffInDays($phv->expiry_date, false);

            if ($daysDiff > 0) {
                $msg = 'Expires in '.$daysDiff.' day'.($daysDiff > 1 ? 's' : '');
                $color = 'secondary';
                $priority = 5;
            } elseif ($daysDiff == 0) {
                $msg = 'Expires today';
                $color = 'warning';
                $priority = 2;
            } else {
                $msg = 'Expired '.abs($daysDiff).' day'.(abs($daysDiff) > 1 ? 's' : '').' ago';
                $color = 'danger';
                $priority = 1;
            }

            $notifications->push([
                'id' => 'phv_'.$phv->id,
                'type' => 'phv_expiry',
                'priority' => $priority,
                'title' => $daysDiff >= 0 ? 'PHV License Expiring' : 'PHV License Expired',
                'message' => $phv->car->registration.' - '.$msg,
                'simple_message' => $phv->car->registration.' - '.$msg,
                'vehicle' => $phv->car->registration,
                'time_ago' => $phv->expiry_date->diffForHumans(),
                'action_url' => route('cars.edit', $phv->car_id),
                'icon' => 'icon-award',
                'color' => $color,
                'bg_color' => $color == 'danger' ? 'rgba(239, 68, 68, 0.1)' : 'rgba(107, 114, 128, 0.1)',
                'border_color' => $color == 'danger' ? '#ef4444' : '#6b7280',
                'created_at' => $phv->expiry_date,
                'sort_key' => $phv->expiry_date->timestamp,
            ]);
        }

        // ==================== 6. MOT CERTIFICATES (latest MOT per car only) ====================
        $motRows = CarMot::with(['car'])
            ->whereHas('car', function ($query) use ($tenant, $nonRoadTaxNotificationExcludedStatuses) {
                $query->where('tenant_id', $tenant->id)
                    ->whereNotIn('fleet_status', $nonRoadTaxNotificationExcludedStatuses);
            })
            ->get();
        $expiringMots = $this->latestMotPerCar($motRows)
            ->filter(function ($mot) {
                return $mot->expiry_date <= now()->addDays(30);
            })
            ->sortBy('expiry_date')
            ->values();

        foreach ($expiringMots as $mot) {
            $daysDiff = (int) now()->diffInDays($mot->expiry_date, false);

            if ($daysDiff > 0) {
                $msg = 'Expires in '.$daysDiff.' day'.($daysDiff > 1 ? 's' : '');
                $color = 'warning';
                $priority = 6;
            } elseif ($daysDiff == 0) {
                $msg = 'Expires today';
                $color = 'warning';
                $priority = 2;
            } else {
                $msg = 'Expired '.abs($daysDiff).' day'.(abs($daysDiff) > 1 ? 's' : '').' ago';
                $color = 'danger';
                $priority = 1;
            }

            $notifications->push([
                'id' => 'mot_'.$mot->id,
                'type' => 'mot_expiry',
                'priority' => $priority,
                'title' => $daysDiff >= 0 ? 'MOT Expiring' : 'MOT Expired',
                'message' => $mot->car->registration.' - '.$msg,
                'simple_message' => $mot->car->registration.' - '.$msg,
                'vehicle' => $mot->car->registration,
                'time_ago' => $mot->expiry_date->diffForHumans(),
                'action_url' => route('cars.edit', $mot->car_id),
                'icon' => 'icon-tool',
                'color' => $color,
                'bg_color' => $color == 'danger' ? 'rgba(239, 68, 68, 0.1)' : 'rgba(245, 158, 11, 0.1)',
                'border_color' => $color == 'danger' ? '#ef4444' : '#f59e0b',
                'created_at' => $mot->expiry_date,
                'sort_key' => $mot->expiry_date->timestamp,
            ]);
        }

        // ==================== 7. ROAD TAX — latest period per car (exclude SORN: vehicle off the road) ====================
        $allRoadTaxes = CarRoadTax::with(['car'])
            ->whereHas('car', function ($query) use ($tenant, $fleetNotificationExcludedStatuses) {
                $query->where('tenant_id', $tenant->id)
                    ->where('sorn_applied', false)
                    ->whereNotIn('fleet_status', $fleetNotificationExcludedStatuses);
            })
            ->get();

        $expiringRoadTaxes = $this->latestRoadTaxPerCar($allRoadTaxes)
            ->filter(function ($roadTax) {
                $expiryDate = $this->calculateRoadTaxExpiry($roadTax);

                return $expiryDate && $expiryDate <= now()->addDays(30);
            })
            ->values();

        foreach ($expiringRoadTaxes as $roadTax) {
            $expiryDate = $this->calculateRoadTaxExpiry($roadTax);
            $daysDiff = (int) now()->diffInDays($expiryDate, false);

            if ($daysDiff > 0) {
                $msg = 'Expires in '.$daysDiff.' day'.($daysDiff > 1 ? 's' : '');
                $color = 'success';
                $priority = 7;
            } elseif ($daysDiff == 0) {
                $msg = 'Expires today';
                $color = 'warning';
                $priority = 2;
            } else {
                $msg = 'Expired '.abs($daysDiff).' day'.(abs($daysDiff) > 1 ? 's' : '').' ago';
                $color = 'danger';
                $priority = 1;
            }

            $notifications->push([
                'id' => 'road_tax_'.$roadTax->id,
                'type' => 'road_tax_expiry',
                'priority' => $priority,
                'title' => $daysDiff >= 0 ? 'Road Tax Expiring' : 'Road Tax Expired',
                'message' => $roadTax->car->registration.' - '.$msg,
                'simple_message' => $roadTax->car->registration.' - '.$msg,
                'vehicle' => $roadTax->car->registration,
                'time_ago' => $expiryDate->diffForHumans(),
                'action_url' => route('cars.edit', $roadTax->car_id),
                'icon' => 'icon-credit-card',
                'color' => $color,
                'bg_color' => $color == 'danger' ? 'rgba(239, 68, 68, 0.1)' : 'rgba(34, 197, 94, 0.1)',
                'border_color' => $color == 'danger' ? '#ef4444' : '#22c55e',
                'created_at' => $expiryDate,
                'sort_key' => $expiryDate->timestamp,
            ]);
        }

        $carsMissingRoadTax = $this->carsMissingRoadTax($tenant, $allRoadTaxes, $fleetNotificationExcludedStatuses);

        foreach ($carsMissingRoadTax as $car) {
            $notifications->push([
                'id' => 'road_tax_missing_'.$car->id,
                'type' => 'road_tax_missing',
                'priority' => 2,
                'title' => 'Road Tax Not Added',
                'message' => $car->registration.' - No road tax details on file',
                'simple_message' => $car->registration.' - No road tax details on file',
                'vehicle' => $car->registration,
                'time_ago' => 'Not on file',
                'action_url' => route('cars.edit', $car->id),
                'icon' => 'icon-credit-card',
                'color' => 'warning',
                'bg_color' => 'rgba(245, 158, 11, 0.1)',
                'border_color' => '#f59e0b',
                'created_at' => $car->created_at ?? now(),
                'sort_key' => now()->timestamp,
            ]);
        }

        // ==================== 8. CAR SERVICES (latest service per car, every 3 months) ====================
        $serviceRows = CarService::with(['car'])
            ->whereHas('car', function ($query) use ($tenant, $nonRoadTaxNotificationExcludedStatuses) {
                $query->where('tenant_id', $tenant->id)
                    ->whereNotIn('fleet_status', $nonRoadTaxNotificationExcludedStatuses);
            })
            ->get();
        $serviceNotifications = $this->latestServicePerCar($serviceRows)
            ->map(function ($service) {
                $service->due_date = $service->service_date->copy()->addMonths(3);

                return $service;
            })
            ->filter(function ($service) {
                return $service->due_date <= now()->addDays(30);
            })
            ->sortBy('due_date')
            ->values();

        foreach ($serviceNotifications as $service) {
            $daysDiff = (int) now()->diffInDays($service->due_date, false);

            if ($daysDiff > 0) {
                $msg = 'Service due in '.$daysDiff.' day'.($daysDiff > 1 ? 's' : '');
                $color = 'info';
                $priority = 7;
            } elseif ($daysDiff == 0) {
                $msg = 'Service due today';
                $color = 'warning';
                $priority = 2;
            } else {
                $msg = 'Service overdue by '.abs($daysDiff).' day'.(abs($daysDiff) > 1 ? 's' : '');
                $color = 'danger';
                $priority = 1;
            }

            $notifications->push([
                'id' => 'car_service_'.$service->id,
                'type' => 'car_service_due',
                'priority' => $priority,
                'title' => $daysDiff >= 0 ? 'Car Service Due' : 'Car Service Overdue',
                'message' => $service->car->registration.' - '.$msg,
                'simple_message' => $service->car->registration.' - '.$msg,
                'vehicle' => $service->car->registration,
                'time_ago' => $service->due_date->diffForHumans(),
                'action_url' => route('cars.edit', $service->car_id),
                'icon' => 'icon-settings',
                'color' => $color,
                'bg_color' => $color == 'danger' ? 'rgba(239, 68, 68, 0.1)' : 'rgba(59, 130, 246, 0.1)',
                'border_color' => $color == 'danger' ? '#ef4444' : '#3b82f6',
                'created_at' => $service->due_date,
                'sort_key' => $service->due_date->timestamp,
            ]);
        }

        // ==================== 9. AGREEMENT TERMINATION NOTICES ====================
        $terminationNotices = Agreement::with(['driver', 'car'])
            ->where('tenant_id', $tenant->id)
            ->whereHas('car', function ($query) use ($nonRoadTaxNotificationExcludedStatuses) {
                $query->whereNotIn('fleet_status', $nonRoadTaxNotificationExcludedStatuses);
            })
            ->whereNotNull('termination_notice_date')
            ->where(function ($query) {
                $query->whereNull('termination_available_from_date')
                    ->orWhere('termination_available_from_date', '<=', now()->addDays(30));
            })
            ->orderBy('termination_available_from_date')
            ->get();

        foreach ($terminationNotices as $agreement) {
            $availableDate = $agreement->termination_available_from_date ?: $agreement->termination_notice_date;
            $daysDiff = (int) now()->diffInDays($availableDate, false);
            $msg = $daysDiff >= 0
                ? 'Available in '.$daysDiff.' day'.($daysDiff === 1 ? '' : 's')
                : 'Available since '.abs($daysDiff).' day'.(abs($daysDiff) === 1 ? '' : 's').' ago';

            $notifications->push([
                'id' => 'agreement_termination_'.$agreement->id,
                'type' => 'agreement_termination',
                'priority' => $daysDiff < 0 ? 1 : 6,
                'title' => 'Rent Agreement Termination',
                'message' => ($agreement->car->registration ?? 'Vehicle').' - '.$msg,
                'simple_message' => ($agreement->car->registration ?? 'Vehicle').' - '.$msg,
                'vehicle' => $agreement->car->registration ?? 'N/A',
                'time_ago' => $availableDate->diffForHumans(),
                'action_url' => route('agreements.show', $agreement),
                'icon' => 'icon-file-text',
                'color' => $daysDiff < 0 ? 'danger' : 'info',
                'bg_color' => $daysDiff < 0 ? 'rgba(239, 68, 68, 0.1)' : 'rgba(59, 130, 246, 0.1)',
                'border_color' => $daysDiff < 0 ? '#ef4444' : '#3b82f6',
                'created_at' => $availableDate,
                'sort_key' => $availableDate->timestamp,
            ]);
        }

        // ==================== 10. DRIVER LICENSES ====================
        $expiringDriverLicenses = Driver::where('tenant_id', $tenant->id)
            ->where('driver_license_expiry_date', '<=', now()->addDays(30))
            ->orderBy('driver_license_expiry_date')
            ->get();

        foreach ($expiringDriverLicenses as $driver) {
            $daysDiff = (int) now()->diffInDays($driver->driver_license_expiry_date, false);

            if ($daysDiff > 0) {
                $msg = 'Expires in '.$daysDiff.' day'.($daysDiff > 1 ? 's' : '');
                $color = 'info';
                $priority = 8;
            } elseif ($daysDiff == 0) {
                $msg = 'Expires today';
                $color = 'warning';
                $priority = 2;
            } else {
                $msg = 'Expired '.abs($daysDiff).' day'.(abs($daysDiff) > 1 ? 's' : '').' ago';
                $color = 'danger';
                $priority = 1;
            }

            $notifications->push([
                'id' => 'driver_license_'.$driver->id,
                'type' => 'driver_license_expiry',
                'priority' => $priority,
                'title' => $daysDiff >= 0 ? 'Driver License Expiring' : 'Driver License Expired',
                'message' => $driver->full_name.' - '.$msg,
                'simple_message' => $driver->full_name.' - '.$msg,
                'driver' => $driver->full_name,
                'time_ago' => $driver->driver_license_expiry_date->diffForHumans(),
                'action_url' => route('drivers.edit', $driver->id),
                'icon' => 'icon-user',
                'color' => $color,
                'bg_color' => $color == 'danger' ? 'rgba(239, 68, 68, 0.1)' : 'rgba(59, 130, 246, 0.1)',
                'border_color' => $color == 'danger' ? '#ef4444' : '#3b82f6',
                'created_at' => $driver->driver_license_expiry_date,
                'sort_key' => $driver->driver_license_expiry_date->timestamp,
            ]);
        }

        // ==================== 11. PHD LICENSES ====================
        $expiringPhdLicenses = Driver::where('tenant_id', $tenant->id)
            ->whereNotNull('phd_license_expiry_date')
            ->where('phd_license_expiry_date', '<=', now()->addDays(30))
            ->orderBy('phd_license_expiry_date')
            ->get();

        foreach ($expiringPhdLicenses as $driver) {
            $daysDiff = (int) now()->diffInDays($driver->phd_license_expiry_date, false);

            if ($daysDiff > 0) {
                $msg = 'Expires in '.$daysDiff.' day'.($daysDiff > 1 ? 's' : '');
                $color = 'secondary';
                $priority = 9;
            } elseif ($daysDiff == 0) {
                $msg = 'Expires today';
                $color = 'warning';
                $priority = 2;
            } else {
                $msg = 'Expired '.abs($daysDiff).' day'.(abs($daysDiff) > 1 ? 's' : '').' ago';
                $color = 'danger';
                $priority = 1;
            }

            $notifications->push([
                'id' => 'phd_license_'.$driver->id,
                'type' => 'phd_license_expiry',
                'priority' => $priority,
                'title' => $daysDiff >= 0 ? 'PHD License Expiring' : 'PHD License Expired',
                'message' => $driver->full_name.' - '.$msg,
                'simple_message' => $driver->full_name.' - '.$msg,
                'driver' => $driver->full_name,
                'time_ago' => $driver->phd_license_expiry_date->diffForHumans(),
                'action_url' => route('drivers.edit', $driver->id),
                'icon' => 'icon-user-check',
                'color' => $color,
                'bg_color' => $color == 'danger' ? 'rgba(239, 68, 68, 0.1)' : 'rgba(107, 114, 128, 0.1)',
                'border_color' => $color == 'danger' ? '#ef4444' : '#6b7280',
                'created_at' => $driver->phd_license_expiry_date,
                'sort_key' => $driver->phd_license_expiry_date->timestamp,
            ]);
        }

        // Sort by actual expiry/due instant: past dates first (oldest expiry first), then future (soonest first)
        $sortedNotifications = $notifications->sortBy([
            ['sort_key', 'asc'],
            ['id', 'asc'],
        ]);

        // Generate summary counts
        $summary = [
            'overdue_payments' => $overdueCollections->count(),
            'due_today' => $dueTodayCollections->count(),
            'due_this_week' => $dueThisWeekCollections->count(),
            'insurance_applied' => $appliedInsurance->count(),
            'expiring_insurance' => $expiringInsurance->count(),
            'expiring_phv' => $expiringPhvs->count(),
            'expiring_mot' => $expiringMots->count(),
            'expiring_road_tax' => $expiringRoadTaxes->count() + $carsMissingRoadTax->count(),
            'car_service_due' => $serviceNotifications->count(),
            'agreement_terminations' => $terminationNotices->count(),
            'expiring_driver_licenses' => $expiringDriverLicenses->count(),
            'expiring_phd_licenses' => $expiringPhdLicenses->count(),
            'total_count' => $sortedNotifications->count(),
        ];

        return [
            'notifications' => $sortedNotifications,
            'summary' => $summary,
        ];
    }

    // ✅ API Endpoint: Get notifications for header bell
    public function getFleetNotifications()
    {
        $data = $this->getUnifiedNotifications();

        return response()->json([
            'notifications' => $data['notifications']->take(15)->values(),
            'summary' => $data['summary'],
        ]);
    }

    // ✅ Notifications Index Page
    public function notificationsIndex(Request $request)
    {
        // If DataTables AJAX request
        if ($request->ajax()) {
            $data = $this->getUnifiedNotifications();

            // ✅ Filter OUT payment notifications - ONLY FLEET NOTIFICATIONS
            $fleetNotifications = $data['notifications']->filter(function ($notification) {
                return ! in_array($notification['type'], ['overdue_payment', 'due_today', 'due_this_week']);
            });

            // ✅ Filter by type if requested
            if ($request->has('type') && $request->type) {
                if ($request->type === 'road_tax_expiry') {
                    $fleetNotifications = $fleetNotifications->whereIn('type', ['road_tax_expiry', 'road_tax_missing']);
                } else {
                    $fleetNotifications = $fleetNotifications->where('type', $request->type);
                }
            }

            // ✅ Chronological by expiry/due date (expired oldest-first, then upcoming soonest-first)
            $fleetNotifications = $fleetNotifications->sortBy([
                ['sort_key', 'asc'],
                ['id', 'asc'],
            ])->values();

            return datatables()->of($fleetNotifications)->toJson();
        }

        // ✅ Regular page load
        $data = $this->getUnifiedNotifications();
        $summary = $data['summary'];

        return view($this->dir.'notifications', compact('summary'));
    }

    public function paymentsIndex(Request $request)
    {
        // If DataTables AJAX request
        if ($request->ajax()) {
            $data = $this->getUnifiedNotifications();

            // ✅ Filter ONLY payment notifications
            $paymentNotifications = $data['notifications']->filter(function ($notification) {
                return in_array($notification['type'], ['overdue_payment', 'due_today', 'due_this_week']);
            });

            // ✅ Filter by type if requested
            if ($request->has('type') && $request->type) {
                $paymentNotifications = $paymentNotifications->where('type', $request->type);
            }

            $paymentNotifications = $paymentNotifications->sortBy([
                ['sort_key', 'asc'],
                ['id', 'asc'],
            ])->values();

            // ✅ Transform for DataTable
            $transformed = $paymentNotifications->map(function ($notification) {
                $collectionId = explode('_', $notification['id'])[1] ?? null;
                $amountRaw = isset($notification['amount']) ? str_replace(['£', ','], '', $notification['amount']) : 0;

                return [
                    'priority' => $notification['priority'],
                    'driver_name' => $notification['simple_message'] ? explode(' - ', $notification['simple_message'])[0] : 'N/A',
                    'vehicle' => $notification['vehicle'] ?? 'N/A',
                    'amount' => $notification['amount'] ?? '£0.00',
                    'amount_raw' => $amountRaw,
                    'due_date' => $notification['created_at']->format('d M, Y'),
                    'time_ago' => $notification['time_ago'],
                    'action_url' => $notification['action_url'] ?? '#',
                    'collection_id' => $collectionId,
                    'color' => $notification['color'],
                ];
            });

            return datatables()->of($transformed)->toJson();
        }

        // ✅ Regular page load
        $data = $this->getUnifiedNotifications();
        $summary = $data['summary'];

        return view($this->dir.'payments', compact('summary'));
    }

    /**
     * One row per car: same "current" record as car edit (latest expiry / latest start for tax).
     */
    private function latestMotPerCar(Collection $mots): Collection
    {
        return $mots->groupBy('car_id')->map(function ($group) {
            return $group->sortByDesc(function ($m) {
                return [optional($m->expiry_date)->timestamp ?? 0, $m->id];
            })->first();
        })->values();
    }

    private function latestPhvPerCar(Collection $phvs): Collection
    {
        return $phvs->groupBy('car_id')->map(function ($group) {
            return $group->sortByDesc(function ($p) {
                return [optional($p->expiry_date)->timestamp ?? 0, $p->id];
            })->first();
        })->values();
    }

    private function latestRoadTaxPerCar(Collection $roadTaxes): Collection
    {
        return $roadTaxes->groupBy('car_id')->map(function ($group) {
            return $group->sortByDesc(function ($r) {
                return [optional($r->start_date)->timestamp ?? 0, $r->id];
            })->first();
        })->values();
    }

    /**
     * Cars that need a road tax record (none on file, or latest period cannot be calculated).
     * Same eligibility as expiring road tax: tenant fleet, not SORN, not written off/stolen/sold.
     */
    private function carsMissingRoadTax($tenant, Collection $allRoadTaxes, array $fleetNotificationExcludedStatuses): Collection
    {
        $carIdsWithValidRoadTax = $this->latestRoadTaxPerCar($allRoadTaxes)
            ->filter(fn ($roadTax) => $this->calculateRoadTaxExpiry($roadTax) !== null)
            ->pluck('car_id')
            ->unique();

        return Car::query()
            ->where('tenant_id', $tenant->id)
            ->where('sorn_applied', false)
            ->whereNotIn('fleet_status', $fleetNotificationExcludedStatuses)
            ->whereNotIn('id', $carIdsWithValidRoadTax)
            ->orderBy('registration')
            ->get();
    }

    private function latestInsurancePerCar(Collection $policies): Collection
    {
        return $policies->groupBy('car_id')->map(function ($group) {
            return $group->sortByDesc(function ($p) {
                return [optional($p->created_at)->timestamp ?? 0, $p->id];
            })->first();
        })->values();
    }

    private function latestServicePerCar(Collection $services): Collection
    {
        return $services->groupBy('car_id')->map(function ($group) {
            return $group->sortByDesc(function ($service) {
                return [optional($service->service_date)->timestamp ?? 0, $service->id];
            })->first();
        })->values();
    }

    // ✅ Helper method: Calculate road tax expiry
    private function calculateRoadTaxExpiry($roadTax)
    {
        if (! $roadTax->start_date || ! $roadTax->term) {
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
                    $number = (int) $matches[1];
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
}
