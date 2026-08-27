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
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentAllocationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

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
        $complianceNotificationExcludedStatuses = Car::fleetStatusesExcludedFromComplianceNotifications();

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
                    'expiring_agreement_end_dates' => 0,
                    'expiring_agreement_termination_notices' => 0,
                    'expired_agreements' => 0,
                    'agreement_notifications' => 0,
                    'total_count' => 0,
                ],
            ];
        }

        $notifications = collect();

        $invoiceDriverFilter = function ($query) use ($tenant) {
            $query->where('tenant_id', $tenant->id)->where('is_active', true);
        };

        $baseInvoiceQuery = fn () => Invoice::query()
            ->with('driver')
            ->where('balance_amount', '>', 0)
            ->whereHas('driver', $invoiceDriverFilter);

        // ==================== 1. GENERATED TODAY INVOICES ====================
        $dueTodayInvoices = $baseInvoiceQuery()
            ->whereDate('invoice_date', now())
            ->orderByDesc('invoice_date')
            ->get();

        $generatedTodayIds = $dueTodayInvoices->pluck('id');

        // ==================== 2. OVERDUE INVOICES (Due Invoices tab match) ====================
        $overdueInvoices = $baseInvoiceQuery()
            ->whereDate('due_date', '<', now())
            ->when($generatedTodayIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $generatedTodayIds))
            ->orderBy('due_date')
            ->get();

        // ==================== 3. DUE THIS WEEK INVOICES ====================
        $dueThisWeekInvoices = $baseInvoiceQuery()
            ->whereBetween('due_date', [now()->addDay()->startOfDay(), now()->addWeek()->endOfDay()])
            ->when($generatedTodayIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $generatedTodayIds))
            ->orderBy('due_date')
            ->get();

        $agreementsById = $this->agreementsByIdForInvoices(
            $overdueInvoices->merge($dueTodayInvoices)->merge($dueThisWeekInvoices)
        );

        foreach ($overdueInvoices as $invoice) {
            $daysOverdue = (int) $invoice->due_date->startOfDay()->diffInDays(now()->startOfDay());
            $driverName = $invoice->driver?->selectOptionLabel() ?? 'Driver';

            $notifications->push($this->invoicePaymentNotificationPayload(
                $invoice,
                'overdue_payment',
                'overdue_'.$invoice->id,
                1,
                'Overdue Payment',
                $driverName.' - Overdue by '.$daysOverdue.' day'.($daysOverdue === 1 ? '' : 's'),
                'danger',
                'icon-alert-triangle',
                'rgba(239, 68, 68, 0.1)',
                '#ef4444',
                'Due '.$invoice->due_date->diffForHumans(),
                $agreementsById
            ));
        }

        foreach ($dueTodayInvoices as $invoice) {
            $driverName = $invoice->driver?->selectOptionLabel() ?? 'Driver';

            $notifications->push($this->invoicePaymentNotificationPayload(
                $invoice,
                'due_today',
                'due_today_'.$invoice->id,
                2,
                'Invoice Generated Today',
                $driverName.' - Generated today',
                'warning',
                'icon-clock',
                'rgba(245, 158, 11, 0.1)',
                '#f59e0b',
                'Generated today',
                $agreementsById
            ));
        }

        foreach ($dueThisWeekInvoices as $invoice) {
            $daysUntilDue = (int) now()->startOfDay()->diffInDays($invoice->due_date->startOfDay());
            $driverName = $invoice->driver?->selectOptionLabel() ?? 'Driver';

            $notifications->push($this->invoicePaymentNotificationPayload(
                $invoice,
                'due_this_week',
                'due_week_'.$invoice->id,
                3,
                'Payment Due Soon',
                $driverName.' - Due in '.$daysUntilDue.' day'.($daysUntilDue === 1 ? '' : 's'),
                'info',
                'icon-calendar',
                'rgba(59, 130, 246, 0.1)',
                '#3b82f6',
                $invoice->due_date->diffForHumans(),
                $agreementsById
            ));
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

        $clientInsuranceAgreements = Agreement::with(['driver', 'car'])
            ->where('tenant_id', $tenant->id)
            ->where('using_own_insurance', true)
            ->whereNotNull('own_insurance_end_date')
            ->whereDate('own_insurance_end_date', '<=', now()->addDays(3))
            ->whereDate('end_date', '>=', now())
            ->whereHas('status', fn ($query) => $query->whereIn('name', ['Active', 'Swap', 'Replacement Vehicle']))
            ->whereHas('driver', fn ($query) => $query->where('is_active', true))
            ->whereHas('car', function ($query) use ($nonRoadTaxNotificationExcludedStatuses) {
                $query->whereNotIn('fleet_status', $nonRoadTaxNotificationExcludedStatuses);
            })
            ->orderBy('own_insurance_end_date')
            ->get();

        foreach ($clientInsuranceAgreements as $agreement) {
            $expiryDate = $agreement->own_insurance_end_date;
            $daysDiff = (int) now()->diffInDays($expiryDate, false);
            $registration = $agreement->car->registration ?? 'Vehicle';

            if ($daysDiff > 0) {
                $msg = 'Client insurance expires in '.$daysDiff.' day'.($daysDiff > 1 ? 's' : '');
                $color = 'primary';
                $priority = 4;
            } elseif ($daysDiff == 0) {
                $msg = 'Client insurance expires today';
                $color = 'warning';
                $priority = 2;
            } else {
                $msg = 'Client insurance expired '.abs($daysDiff).' day'.(abs($daysDiff) > 1 ? 's' : '').' ago';
                $color = 'danger';
                $priority = 1;
            }

            $notifications->push([
                'id' => 'agreement_client_insurance_'.$agreement->id,
                'type' => 'insurance_expiry',
                'priority' => $priority,
                'title' => $daysDiff >= 0 ? 'Client Insurance Expiring' : 'Client Insurance Expired',
                'message' => $registration.' - '.$msg,
                'simple_message' => $registration.' - '.$msg,
                'vehicle' => $registration,
                'time_ago' => $expiryDate->diffForHumans(),
                'action_url' => route('agreements.show', $agreement),
                'icon' => 'icon-shield',
                'color' => $color,
                'bg_color' => $color == 'danger' ? 'rgba(239, 68, 68, 0.1)' : 'rgba(99, 102, 241, 0.1)',
                'border_color' => $color == 'danger' ? '#ef4444' : '#6366f1',
                'created_at' => $expiryDate,
                'sort_key' => $expiryDate->timestamp,
            ]);
        }

        // ==================== 5. PHV LICENSES (latest PHV per car only) ====================
        $phvRows = CarPhv::with(['car'])
            ->whereHas('car', function ($query) use ($tenant, $complianceNotificationExcludedStatuses) {
                $query->where('tenant_id', $tenant->id)
                    ->whereNotIn('fleet_status', $complianceNotificationExcludedStatuses);
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
            ->whereHas('car', function ($query) use ($tenant, $complianceNotificationExcludedStatuses) {
                $query->where('tenant_id', $tenant->id)
                    ->whereNotIn('fleet_status', $complianceNotificationExcludedStatuses);
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
            ->whereHas('car', function ($query) use ($tenant, $complianceNotificationExcludedStatuses) {
                $query->where('tenant_id', $tenant->id)
                    ->where('sorn_applied', false)
                    ->whereNotIn('fleet_status', $complianceNotificationExcludedStatuses);
            })
            ->get();

        $expiringRoadTaxes = $this->latestRoadTaxPerCar($allRoadTaxes)
            ->filter(function ($roadTax) {
                $expiryDate = $roadTax->expiryDate();

                return $expiryDate && $expiryDate <= now()->addDays(30);
            })
            ->values();

        foreach ($expiringRoadTaxes as $roadTax) {
            $expiryDate = $roadTax->expiryDate();
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

        $carsMissingRoadTax = $this->carsMissingRoadTax($tenant, $allRoadTaxes, $complianceNotificationExcludedStatuses);

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

        // ==================== 9. AGREEMENT END / TERMINATION NOTICE (10-DAY WINDOW) ====================
        $agreementUpcomingNotifications = $this->buildAgreementFleetNotifications($tenant, $nonRoadTaxNotificationExcludedStatuses);
        $agreementExpiredNotifications = $this->buildExpiredAgreementFleetNotifications($tenant, $nonRoadTaxNotificationExcludedStatuses);

        foreach ($agreementUpcomingNotifications as $agreementNotification) {
            $notifications->push($agreementNotification);
        }

        foreach ($agreementExpiredNotifications as $agreementNotification) {
            $notifications->push($agreementNotification);
        }

        // ==================== 10. DRIVER LICENSES ====================
        $expiringDriverLicenses = Driver::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->where('driver_license_expiry_date', '<=', now()->addDays(30))
            ->orderBy('driver_license_expiry_date')
            ->get();

        // ==================== 11. PHD LICENSES ====================
        $expiringPhdLicenses = Driver::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->whereNotNull('phd_license_expiry_date')
            ->where('phd_license_expiry_date', '<=', now()->addDays(30))
            ->orderBy('phd_license_expiry_date')
            ->get();

        $latestAgreementsByDriver = Agreement::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('driver_id', $expiringDriverLicenses->pluck('id')->merge($expiringPhdLicenses->pluck('id'))->unique())
            ->with('car')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get()
            ->unique('driver_id')
            ->keyBy('driver_id');

        foreach ($expiringDriverLicenses as $driver) {
            $daysDiff = (int) now()->diffInDays($driver->driver_license_expiry_date, false);
            $driverLabel = $driver->selectOptionLabel();
            $latestAgreement = $latestAgreementsByDriver->get($driver->id);

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
                'message' => $driverLabel.' - '.$msg,
                'simple_message' => $driverLabel.' - '.$msg,
                'driver' => $driverLabel,
                'last_car_registration' => $latestAgreement?->car?->registration,
                'last_car_agreement_url' => $latestAgreement ? route('agreements.show', $latestAgreement) : null,
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

        foreach ($expiringPhdLicenses as $driver) {
            $daysDiff = (int) now()->diffInDays($driver->phd_license_expiry_date, false);
            $driverLabel = $driver->selectOptionLabel();
            $latestAgreement = $latestAgreementsByDriver->get($driver->id);

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
                'message' => $driverLabel.' - '.$msg,
                'simple_message' => $driverLabel.' - '.$msg,
                'driver' => $driverLabel,
                'last_car_registration' => $latestAgreement?->car?->registration,
                'last_car_agreement_url' => $latestAgreement ? route('agreements.show', $latestAgreement) : null,
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
            'overdue_payments' => $overdueInvoices->count(),
            'due_today' => $dueTodayInvoices->count(),
            'due_this_week' => $dueThisWeekInvoices->count(),
            'insurance_applied' => $appliedInsurance->count(),
            'expiring_insurance' => $expiringInsurance->count() + $clientInsuranceAgreements->count(),
            'expiring_phv' => $expiringPhvs->count(),
            'expiring_mot' => $expiringMots->count(),
            'expiring_road_tax' => $expiringRoadTaxes->count() + $carsMissingRoadTax->count(),
            'car_service_due' => $serviceNotifications->count(),
            'expiring_agreement_end_dates' => $agreementUpcomingNotifications->where('type', 'agreement_end_date')->count(),
            'expiring_agreement_termination_notices' => $agreementUpcomingNotifications->where('type', 'agreement_termination_notice')->count(),
            'expired_agreements' => $agreementExpiredNotifications->count(),
            'agreement_notifications' => $agreementUpcomingNotifications->count() + $agreementExpiredNotifications->count(),
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

    public function getCarNotifications(Car $car)
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant || (int) $car->tenant_id !== (int) $tenant->id) {
            return response()->json(['notifications' => []], 403);
        }

        $data = $this->getUnifiedNotifications();

        $notifications = collect($data['notifications'])
            ->filter(function ($notification) use ($car) {
                return isset($notification['vehicle'])
                    && (string) $notification['vehicle'] === (string) $car->registration;
            })
            ->values()
            ->map(function ($notification) {
                return [
                    'id' => $notification['id'] ?? null,
                    'title' => $notification['title'] ?? 'Notification',
                    'message' => $notification['message'] ?? '',
                    'time_ago' => $notification['time_ago'] ?? '',
                    'color' => $notification['color'] ?? 'info',
                    'action_url' => $notification['action_url'] ?? null,
                ];
            });

        return response()->json([
            'car_registration' => $car->registration,
            'notifications' => $notifications,
        ]);
    }

    public function getCarNotificationCounts(): array
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return [];
        }

        return Cache::remember(
            "tenant:{$tenant->id}:car_notification_counts",
            now()->addMinutes(2),
            fn () => $this->buildCarNotificationCounts()
        );
    }

    public function getCarNotificationCountsJson()
    {
        return response()->json([
            'counts' => $this->getCarNotificationCounts(),
        ]);
    }

    private function buildCarNotificationCounts(): array
    {
        $data = $this->getUnifiedNotifications();

        return collect($data['notifications'])
            ->filter(function ($notification) {
                return isset($notification['vehicle'])
                    && (string) $notification['vehicle'] !== ''
                    && (string) $notification['vehicle'] !== 'N/A';
            })
            ->groupBy(fn ($notification) => (string) $notification['vehicle'])
            ->map(fn ($group) => $group->count())
            ->all();
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
                } elseif ($request->type === 'agreement_notifications') {
                    $fleetNotifications = $fleetNotifications->whereIn('type', [
                        'agreement_end_date',
                        'agreement_termination_notice',
                        'agreement_expired',
                    ]);
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

    public function paymentsIndex(Request $request, PaymentAllocationService $paymentAllocationService)
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
            } elseif ($request->filled('invoice_date_from') || $request->filled('invoice_date_to')) {
                $paymentNotifications = $this->filterPaymentNotificationsByInvoiceDateRange(
                    $paymentNotifications,
                    $request->input('invoice_date_from'),
                    $request->input('invoice_date_to')
                );
            }

            $paymentNotifications = $paymentNotifications->sortBy([
                ['invoice_date_sort', 'desc'],
                ['invoice_id', 'desc'],
            ])->values();

            $driverIds = $paymentNotifications
                ->pluck('driver_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $driversById = $driverIds === []
                ? collect()
                : Driver::query()
                    ->where('tenant_id', Auth::user()->currentTenant()?->id)
                    ->whereIn('id', $driverIds)
                    ->get()
                    ->keyBy('id');

            $pendingDfsByInvoice = [];
            $invoiceIds = $paymentNotifications
                ->pluck('invoice_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($invoiceIds !== []) {
                $notificationInvoices = Invoice::query()
                    ->whereIn('id', $invoiceIds)
                    ->get(['id', 'driver_id', 'source_id', 'invoice_type', 'balance_amount', 'invoice_date', 'due_date']);

                $pendingDfsByInvoice = $paymentAllocationService->pendingDfsAmountsForInvoices($notificationInvoices);
            }

            // ✅ Transform for DataTable
            $transformed = $paymentNotifications->map(function ($notification) use ($driversById, $pendingDfsByInvoice) {
                $amountRaw = isset($notification['amount']) ? str_replace(['£', ','], '', $notification['amount']) : 0;
                $driverId = $notification['driver_id'] ?? null;
                $driver = $driverId ? $driversById->get($driverId) : null;
                $invoiceId = isset($notification['invoice_id']) ? (int) $notification['invoice_id'] : null;
                $pendingDfsAmount = $invoiceId ? (float) ($pendingDfsByInvoice[$invoiceId] ?? 0) : 0.0;
                $amountColor = 'danger';
                $amountTooltip = null;
                $hasPendingDfs = $pendingDfsAmount > 0;

                if ($hasPendingDfs) {
                    $amountColor = 'warning';
                    $amountTooltip = '£'.number_format($pendingDfsAmount, 2).' pending daily financial sheet approval.';
                }

                return [
                    'priority' => $notification['priority'],
                    'driver_name' => $notification['simple_message'] ? explode(' - ', $notification['simple_message'])[0] : 'N/A',
                    'vehicle' => $notification['vehicle'] ?? 'N/A',
                    'paying_company' => $notification['paying_company'] ?? null,
                    'amount' => $notification['amount'] ?? '£0.00',
                    'amount_raw' => $amountRaw,
                    'invoice_generated_date' => isset($notification['invoice_date'])
                        ? $notification['invoice_date']->format('d M, Y')
                        : '—',
                    'due_date' => isset($notification['due_date_value'])
                        ? $notification['due_date_value']->format('d M, Y')
                        : '—',
                    'time_ago' => $notification['time_ago'],
                    'action_url' => $notification['action_url'] ?? '#',
                    'driver_id' => $driverId,
                    'invoice_id' => $invoiceId,
                    'color' => $amountColor,
                    'amount_color' => $amountColor,
                    'amount_tooltip' => $amountTooltip,
                    'has_pending_dfs' => $hasPendingDfs,
                    'follow_up_notes' => $driver?->payment_follow_up_notes,
                    'follow_up_remind_at' => $driver?->payment_remind_at?->toIso8601String(),
                    'follow_up_has_note' => $driver?->hasPaymentFollowUpNote() ?? false,
                    'follow_up_has_reminder' => $driver?->hasPaymentReminder() ?? false,
                    'follow_up_update_url' => $driver ? route('payments.follow-up.update', $driver) : null,
                    'pay_to_bank' => $notification['pay_to_bank'] ?? null,
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
     * Same eligibility as expiring road tax: tenant fleet, not SORN, not damaged/stolen/for sale/written off/sold.
     */
    private function carsMissingRoadTax($tenant, Collection $allRoadTaxes, array $fleetNotificationExcludedStatuses): Collection
    {
        $carIdsWithValidRoadTax = $this->latestRoadTaxPerCar($allRoadTaxes)
            ->filter(fn ($roadTax) => $roadTax->expiryDate() !== null)
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

    private function agreementsByIdForInvoices(Collection $invoices): Collection
    {
        $sourceIds = $invoices
            ->filter(function ($invoice) {
                return in_array($invoice->invoice_type, ['agreement', 'agreement_deposit', 'agreement_additional_charge'], true) && $invoice->source_id;
            })
            ->pluck('source_id')
            ->unique()
            ->values();

        if ($sourceIds->isEmpty()) {
            return collect();
        }

        return Agreement::with([
            'car',
            'paymentBankAccount',
            'replacementVehicleAgreements' => fn ($query) => $query->currentlyActiveReplacement()->with('car'),
        ])->whereIn('id', $sourceIds)->get()->keyBy('id');
    }

    private function invoiceVehicleRegistration(Invoice $invoice, Collection $agreementsById): string
    {
        if (! in_array($invoice->invoice_type, ['agreement', 'agreement_deposit', 'agreement_additional_charge'], true) || ! $invoice->source_id) {
            return 'N/A';
        }

        $agreement = $agreementsById->get($invoice->source_id);

        return $agreement
            ? $agreement->vehicleRegistrationsLabel('N/A')
            : 'N/A';
    }

    /**
     * Active/Swap agreements with end_date or termination_notice_date within the next 10 days.
     * One notification per agreement, keyed by the nearest qualifying date.
     */
    private function buildAgreementFleetNotifications($tenant, array $excludedCarStatuses): Collection
    {
        $windowStart = now()->startOfDay();
        $windowEnd = now()->addDays(10)->endOfDay();

        $agreements = Agreement::with(['driver', 'car', 'status'])
            ->where('tenant_id', $tenant->id)
            ->currentlyActive()
            ->whereHas('car', function ($query) use ($excludedCarStatuses) {
                $query->whereNotIn('fleet_status', $excludedCarStatuses);
            })
            ->get();

        $notifications = collect();

        foreach ($agreements as $agreement) {
            $qualifyingDates = [];

            if ($agreement->end_date) {
                $endDate = $agreement->end_date->copy()->startOfDay();
                if ($endDate->gte($windowStart) && $endDate->lte($windowEnd)) {
                    $qualifyingDates['end_date'] = $endDate;
                }
            }

            if ($agreement->termination_notice_date) {
                $terminationDate = $agreement->termination_notice_date->copy()->startOfDay();
                if ($terminationDate->gte($windowStart) && $terminationDate->lte($windowEnd)) {
                    $qualifyingDates['termination_notice'] = $terminationDate;
                }
            }

            if ($qualifyingDates === []) {
                continue;
            }

            $nearestKey = array_key_first($qualifyingDates);
            $nearestDate = $qualifyingDates[$nearestKey];

            foreach ($qualifyingDates as $key => $date) {
                if ($date->lt($nearestDate)) {
                    $nearestKey = $key;
                    $nearestDate = $date;
                }
            }

            $daysDiff = (int) $windowStart->diffInDays($nearestDate, false);
            $registration = $agreement->car->registration ?? 'Vehicle';
            $driverLabel = $agreement->driver?->selectOptionLabel() ?? 'Driver';

            if ($nearestKey === 'end_date') {
                $type = 'agreement_end_date';
                $title = 'Agreement Ending Soon';
                $msg = $daysDiff === 0
                    ? 'Ends today'
                    : 'Ends in '.$daysDiff.' day'.($daysDiff === 1 ? '' : 's');

                if (isset($qualifyingDates['termination_notice'])) {
                    $termDays = (int) $windowStart->diffInDays($qualifyingDates['termination_notice'], false);
                    $msg .= ' (termination notice in '.$termDays.' day'.($termDays === 1 ? '' : 's').')';
                }
            } else {
                $type = 'agreement_termination_notice';
                $title = 'Termination Notice Due';
                $msg = $daysDiff === 0
                    ? 'Termination notice today'
                    : 'Termination notice in '.$daysDiff.' day'.($daysDiff === 1 ? '' : 's');

                if (isset($qualifyingDates['end_date'])) {
                    $endDays = (int) $windowStart->diffInDays($qualifyingDates['end_date'], false);
                    $msg .= ' (agreement ends in '.$endDays.' day'.($endDays === 1 ? '' : 's').')';
                }
            }

            if ($daysDiff === 0) {
                $color = 'warning';
                $priority = 2;
                $bgColor = 'rgba(245, 158, 11, 0.1)';
                $borderColor = '#f59e0b';
            } else {
                $color = 'secondary';
                $priority = 6;
                $bgColor = 'rgba(107, 114, 128, 0.1)';
                $borderColor = '#6b7280';
            }

            $fullMessage = $registration.' - '.$driverLabel.' - '.$msg;
            $payingCompany = trim((string) ($agreement->paying_company_name ?? ''));
            if ($payingCompany !== '') {
                $fullMessage .= ' - Pays via: '.$payingCompany;
            }

            $notifications->push([
                'id' => 'agreement_upcoming_'.$agreement->id,
                'type' => $type,
                'priority' => $priority,
                'title' => $title,
                'message' => $fullMessage,
                'simple_message' => $fullMessage,
                'vehicle' => $registration,
                'driver' => $driverLabel,
                'paying_company' => $payingCompany !== '' ? $payingCompany : null,
                'time_ago' => $nearestDate->diffForHumans(),
                'action_url' => route('agreements.show', $agreement),
                'icon' => 'icon-file-text',
                'color' => $color,
                'bg_color' => $bgColor,
                'border_color' => $borderColor,
                'created_at' => $nearestDate,
                'sort_key' => $nearestDate->timestamp,
            ]);
        }

        return $notifications;
    }

    /**
     * Agreements whose end_date passed within the last 10 days (excludes Terminated).
     */
    private function buildExpiredAgreementFleetNotifications($tenant, array $excludedCarStatuses): Collection
    {
        $windowStart = now()->startOfDay();
        $expiredWindowStart = $windowStart->copy()->subDays(10);

        $agreements = Agreement::with(['driver', 'car', 'status'])
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('end_date')
            ->whereDate('end_date', '>=', $expiredWindowStart)
            ->whereDate('end_date', '<', $windowStart)
            ->whereHas('status', function ($query) {
                $query->whereNotIn('name', ['Terminated']);
            })
            ->whereHas('car', function ($query) use ($excludedCarStatuses) {
                $query->whereNotIn('fleet_status', $excludedCarStatuses);
            })
            ->get();

        $notifications = collect();

        foreach ($agreements as $agreement) {
            $endDate = $agreement->end_date->copy()->startOfDay();
            $daysSinceExpiry = (int) $endDate->diffInDays($windowStart, false);

            if ($daysSinceExpiry <= 0) {
                continue;
            }

            $registration = $agreement->car->registration ?? 'Vehicle';
            $driverLabel = $agreement->driver?->selectOptionLabel() ?? 'Driver';

            $msg = $daysSinceExpiry === 1
                ? 'Agreement expired yesterday'
                : 'Agreement expired '.$daysSinceExpiry.' days ago';

            $fullMessage = $registration.' - '.$driverLabel.' - '.$msg;
            $payingCompany = trim((string) ($agreement->paying_company_name ?? ''));
            if ($payingCompany !== '') {
                $fullMessage .= ' - Pays via: '.$payingCompany;
            }

            $notifications->push([
                'id' => 'agreement_expired_'.$agreement->id,
                'type' => 'agreement_expired',
                'priority' => 1,
                'title' => 'Agreement Expired',
                'message' => $fullMessage,
                'simple_message' => $fullMessage,
                'vehicle' => $registration,
                'driver' => $driverLabel,
                'paying_company' => $payingCompany !== '' ? $payingCompany : null,
                'time_ago' => $endDate->diffForHumans(),
                'action_url' => route('agreements.show', $agreement),
                'icon' => 'icon-file-text',
                'color' => 'danger',
                'bg_color' => 'rgba(239, 68, 68, 0.1)',
                'border_color' => '#ef4444',
                'created_at' => $endDate,
                'sort_key' => $endDate->timestamp,
            ]);
        }

        return $notifications;
    }

    /**
     * @return array<string, mixed>
     */
    private function invoicePaymentNotificationPayload(
        Invoice $invoice,
        string $type,
        string $id,
        int $priority,
        string $title,
        string $simpleMessage,
        string $color,
        string $icon,
        string $bgColor,
        string $borderColor,
        string $timeAgo,
        Collection $agreementsById
    ): array {
        return [
            'id' => $id,
            'type' => $type,
            'priority' => $priority,
            'title' => $title,
            'message' => $simpleMessage,
            'simple_message' => $simpleMessage,
            'amount' => '£'.number_format((float) $invoice->balance_amount, 2),
            'vehicle' => $this->invoiceVehicleRegistration($invoice, $agreementsById),
            'paying_company' => $this->invoicePayingCompanyName($invoice, $agreementsById),
            'pay_to_bank' => $this->invoicePayToBankName($invoice, $agreementsById),
            'driver_id' => $invoice->driver_id,
            'invoice_id' => $invoice->id,
            'time_ago' => $timeAgo,
            'action_url' => route('payments.driver', $invoice->driver_id).'#due-invoices',
            'icon' => $icon,
            'color' => $color,
            'bg_color' => $bgColor,
            'border_color' => $borderColor,
            'created_at' => $invoice->due_date,
            'sort_key' => $invoice->due_date->timestamp,
            'invoice_date' => $invoice->invoice_date,
            'due_date_value' => $invoice->due_date,
            'invoice_date_sort' => $invoice->invoice_date?->timestamp ?? $invoice->id,
        ];
    }

    private function filterPaymentNotificationsByInvoiceDateRange(
        Collection $paymentNotifications,
        ?string $from,
        ?string $to
    ): Collection {
        $fromDate = null;
        $toDate = null;

        try {
            if (filled($from)) {
                $fromDate = Carbon::parse($from)->startOfDay();
            }
        } catch (\Throwable) {
            $fromDate = null;
        }

        try {
            if (filled($to)) {
                $toDate = Carbon::parse($to)->endOfDay();
            }
        } catch (\Throwable) {
            $toDate = null;
        }

        if ($fromDate === null && $toDate === null) {
            return $paymentNotifications;
        }

        return $paymentNotifications->filter(function (array $notification) use ($fromDate, $toDate) {
            $invoiceDate = $notification['invoice_date'] ?? null;

            if (! $invoiceDate) {
                return false;
            }

            $invoiceDay = $invoiceDate->copy()->startOfDay();

            if ($fromDate !== null && $invoiceDay->lt($fromDate)) {
                return false;
            }

            if ($toDate !== null && $invoiceDay->gt($toDate->copy()->startOfDay())) {
                return false;
            }

            return true;
        })->values();
    }

    private function invoicePayingCompanyName(Invoice $invoice, Collection $agreementsById): ?string
    {
        if (! in_array($invoice->invoice_type, ['agreement', 'agreement_deposit', 'agreement_additional_charge'], true) || ! $invoice->source_id) {
            return null;
        }

        $name = trim((string) ($agreementsById->get($invoice->source_id)?->paying_company_name ?? ''));

        return $name !== '' ? $name : null;
    }

    private function invoicePayToBankName(Invoice $invoice, Collection $agreementsById): ?string
    {
        if (! in_array($invoice->invoice_type, ['agreement', 'agreement_deposit', 'agreement_additional_charge'], true) || ! $invoice->source_id) {
            return null;
        }

        $bankAccount = $agreementsById->get($invoice->source_id)?->paymentBankAccount;
        $displayName = trim((string) ($bankAccount?->paymentDisplayName() ?? ''));

        return $displayName !== '' ? $displayName : null;
    }
}
