@extends('layouts.admin', ['title' => 'Dashboard'])

@section('content')
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="dashboard-title">
                    <i class="feather icon-activity me-3"></i>
                    Fleet Dashboard
                </h1>
                <p class="dashboard-subtitle">Welcome back! Here's what's happening with your fleet today.</p>
            </div>
            <div class="col-auto">
                <a href="{{ route('notifications.index') }}" class="btn btn-outline-primary">
                    <i class="feather icon-bell me-2"></i>
                    View All Notifications
                </a>
            </div>
        </div>
    </div>

    <!-- Enhanced Task Bar Notifications -->
    @if($taskBarNotifications->count() > 0)
        <div class="task-bar-notifications mb-4">
            <div class="task-bar-header">
                <h5 class="task-bar-title">
                    <i class="feather icon-bell-ring me-2"></i>
                    Urgent Tasks & Notifications
                    <span class="notification-count-badge">{{ $taskBarNotifications->count() }}</span>
                </h5>
                <a href="{{ route('notifications.index') }}" class="view-all-notifications">
                    View All <i class="feather icon-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="task-bar-list">
                @foreach($taskBarNotifications as $notification)
                    <div class="task-item task-{{ $notification['type'] }}"
                         style="border-left-color: {{ $notification['border_color'] }}; background-color: {{ $notification['bg_color'] }}">
                        <div class="task-icon text-{{ $notification['color'] }}">
                            <i class="feather {{ $notification['icon'] }}"></i>
                        </div>
                        <div class="task-content">
                            <div class="task-header">
                                <h6 class="task-title text-{{ $notification['color'] }}">{{ $notification['title'] }}</h6>
                                <span class="task-priority priority-{{ $notification['priority'] }}">
                                    @if($notification['priority'] <= 2)
                                        Critical
                                    @elseif($notification['priority'] <= 4)
                                        High
                                    @else
                                        Medium
                                    @endif
                                </span>
                            </div>
                            <p class="task-message">{{ $notification['simple_message'] }}</p>
                            <div class="task-meta">
                                @if(isset($notification['vehicle']))
                                    <span class="task-meta-item">
                                        <i class="feather icon-truck"></i>
                                        {{ $notification['vehicle'] }}
                                    </span>
                                @endif
                                @if(isset($notification['amount']))
                                    <span class="task-meta-item amount text-{{ $notification['color'] }}">
                                        <i class="feather icon-pound-sign"></i>
                                        {{ $notification['amount'] }}
                                    </span>
                                @endif
                                @if(isset($notification['driver']))
                                    <span class="task-meta-item">
                                        <i class="feather icon-user"></i>
                                        {{ $notification['driver'] }}
                                    </span>
                                @endif
                                <span class="task-meta-item time">
                                    <i class="feather icon-clock"></i>
                                    {{ $notification['time_ago'] }}
                                </span>
                            </div>
                        </div>
                        <div class="task-actions">
                            @if($notification['action_url'])
                                <a href="{{ $notification['action_url'] }}"
                                   class="btn btn-sm btn-outline-{{ $notification['color'] }} task-btn-view">
                                    <i class="feather icon-external-link"></i>
                                    View Details
                                </a>
                            @endif

                            @if(in_array($notification['type'], ['overdue_payment', 'due_today', 'due_this_week']))
                                @php
                                    $collectionId = explode('_', $notification['id'])[1] ?? null;
                                    $amount = isset($notification['amount']) ? str_replace(['£', ','], '', $notification['amount']) : 0;
                                @endphp
                                @if($collectionId)
                                    <button class="btn btn-sm btn-{{ $notification['color'] }} task-btn-pay"
                                            onclick="quickPayFromTaskBar('{{ $collectionId }}', '{{ $amount }}')">
                                        <i class="feather icon-credit-card"></i>
                                        @if($notification['type'] == 'overdue_payment')
                                            Pay Now
                                        @elseif($notification['type'] == 'due_today')
                                            Pay Today
                                        @else
                                            Pay Early
                                        @endif
                                    </button>
                                @endif
                            @endif

                            <button class="btn btn-sm btn-outline-secondary task-btn-dismiss"
                                    onclick="dismissTaskNotification('{{ $notification['id'] }}')">
                                <i class="feather icon-x"></i>
                                Dismiss
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($taskBarNotifications->count() >= 6)
                <div class="task-bar-footer">
                    <div class="alert alert-info-light">
                        <i class="feather icon-info me-2"></i>
                        Showing top {{ $taskBarNotifications->count() }} urgent notifications.
                        <a href="{{ route('notifications.index') }}" class="alert-link">View all notifications</a>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- Enhanced KPI Cards -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="kpi-card-modern" data-aos="fade-up" data-aos-delay="100">
                <div class="kpi-gradient-bg gradient-primary"></div>
                <div class="kpi-card-content">
                    <div class="kpi-icon-modern">
                        <i class="feather icon-truck"></i>
                    </div>
                    <div class="kpi-info">
                        <h3 class="kpi-value-modern" data-target="{{ $totalCars }}">0</h3>
                        <p class="kpi-label-modern">Total Vehicles</p>
                        <div class="kpi-trend-modern positive">
                            <i class="feather icon-trending-up"></i>
                            <span>+12%</span>
                        </div>
                    </div>
                </div>
                <div class="kpi-sparkline" id="vehicles-sparkline"></div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="kpi-card-modern" data-aos="fade-up" data-aos-delay="200">
                <div class="kpi-gradient-bg gradient-success"></div>
                <div class="kpi-card-content">
                    <div class="kpi-icon-modern">
                        <i class="feather icon-users"></i>
                    </div>
                    <div class="kpi-info">
                        <h3 class="kpi-value-modern" data-target="{{ $totalDrivers }}">0</h3>
                        <p class="kpi-label-modern">Active Drivers</p>
                        <div class="kpi-trend-modern positive">
                            <i class="feather icon-trending-up"></i>
                            <span>+8%</span>
                        </div>
                    </div>
                </div>
                <div class="kpi-sparkline" id="drivers-sparkline"></div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="kpi-card-modern" data-aos="fade-up" data-aos-delay="300">
                <div class="kpi-gradient-bg gradient-warning"></div>
                <div class="kpi-card-content">
                    <div class="kpi-icon-modern">
                        <i class="feather icon-pound-sign"></i>
                    </div>
                    <div class="kpi-info">
                        <h3 class="kpi-value-modern" data-target="{{ $monthlyRevenue }}">0</h3>
                        <p class="kpi-label-modern">Monthly Revenue</p>
                        <div class="kpi-trend-modern positive">
                            <i class="feather icon-trending-up"></i>
                            <span>+15%</span>
                        </div>
                    </div>
                </div>
                <div class="kpi-sparkline" id="revenue-sparkline"></div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="kpi-card-modern" data-aos="fade-up" data-aos-delay="400">
                <div class="kpi-gradient-bg gradient-danger"></div>
                <div class="kpi-card-content">
                    <div class="kpi-icon-modern">
                        <i class="feather icon-alert-circle"></i>
                    </div>
                    <div class="kpi-info">
                        <h3 class="kpi-value-modern" data-target="{{ $totalOutstanding }}">0</h3>
                        <p class="kpi-label-modern">Outstanding (£)</p>
                        <div class="kpi-trend-modern negative">
                            <i class="feather icon-trending-down"></i>
                            <span>-5%</span>
                        </div>
                    </div>
                </div>
                <div class="kpi-sparkline" id="outstanding-sparkline"></div>
            </div>
        </div>
    </div>

    <!-- Enhanced Charts Section -->
    <div class="row g-4 mb-4">
        <!-- Revenue Analytics -->
        <div class="col-xl-8">
            <div class="chart-card-modern" data-aos="fade-up" data-aos-delay="500">
                <div class="chart-card-header">
                    <div class="chart-title-section">
                        <h5 class="chart-title">Revenue Analytics</h5>
                        <p class="chart-subtitle">Monthly performance overview</p>
                    </div>
                    <div class="chart-actions">
                        <div class="chart-legend-pills">
                            <span class="legend-pill revenue-pill">
                                <i class="legend-dot"></i>Revenue
                            </span>
                            <span class="legend-pill expense-pill">
                                <i class="legend-dot"></i>Expenses
                            </span>
                        </div>
                    </div>
                </div>
                <div class="chart-card-body">
                    <div class="chart-container-modern">
                        <canvas id="revenueAnalyticsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Agreement Status -->
        <div class="col-xl-4">
            <div class="chart-card-modern" data-aos="fade-up" data-aos-delay="600">
                <div class="chart-card-header">
                    <div class="chart-title-section">
                        <h5 class="chart-title">Agreement Status</h5>
                        <p class="chart-subtitle">Current distribution</p>
                    </div>
                </div>
                <div class="chart-card-body">
                    <div class="chart-container-modern">
                        <canvas id="agreementStatusChart"></canvas>
                    </div>
                    <div class="agreement-status-legend-modern">
                        @foreach($agreementStatusSummary as $status)
                            <div class="legend-item-modern">
                                <span class="legend-indicator" style="background-color: {{ $status['color'] }}"></span>
                                <span class="legend-text">{{ $status['status'] }}</span>
                                <span class="legend-count">{{ $status['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="row g-4">
        <div class="col-12">
            <div class="activity-card-modern" data-aos="fade-up" data-aos-delay="700">
                <div class="activity-card-header">
                    <div class="activity-header-content">
                        <h5 class="activity-title">
                            <i class="feather icon-activity me-2"></i>
                            Recent Activities
                        </h5>
                        <p class="activity-subtitle">Latest fleet management activities</p>
                    </div>
                    <div class="activity-actions">
                        <a href="{{ route('claims.index') }}" class="btn btn-sm btn-outline-primary">
                            View All Activities
                            <i class="feather icon-external-link ms-1"></i>
                        </a>
                    </div>
                </div>
                <div class="activity-card-body">
                    <div class="table-responsive">
                        <table class="table table-modern">
                            <thead>
                            <tr>
                                <th>Vehicle</th>
                                <th>Case Date</th>
                                <th>Reference</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($recentClaims as $claim)
                                <tr>
                                    <td>
                                        <div class="vehicle-info">
                                            <span class="vehicle-reg">{{ $claim->car->registration }}</span>
                                            <small class="vehicle-model">{{ $claim->car->carModel->name }}</small>
                                        </div>
                                    </td>
                                    <td>{{ $claim->case_date->format('M d, Y') }}</td>
                                    <td>
                                        <code class="reference-code">{{ $claim->our_reference }}</code>
                                    </td>
                                    <td>
                                            <span class="status-badge" style="background-color: {{ $claim->status->color }}">
                                                {{ $claim->status->name }}
                                            </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('claims.show', $claim) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="feather icon-eye"></i>
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <div class="empty-state">
                                            <i class="feather icon-file-text fa-2x text-muted mb-2"></i>
                                            <p class="text-muted">No recent activities to display</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Quick Payment Modal -->
    <div class="modal fade" id="quickPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-modern">
                <form id="quickPaymentForm" method="POST">
                    @csrf
                    <div class="modal-header modal-header-modern">
                        <div class="modal-title-section">
                            <i class="feather icon-credit-card modal-icon"></i>
                            <div>
                                <h5 class="modal-title">Record Payment</h5>
                                <p class="modal-subtitle">Process payment for selected collection</p>
                            </div>
                        </div>
                        <button type="button" class="btn-close-modern" data-dismiss="modal">
                            <i class="feather icon-x"></i>
                        </button>
                    </div>
                    <div class="modal-body modal-body-modern">
                        <div class="payment-details-section mb-4">
                            <div class="payment-summary">
                                <h6 id="payment-summary-title">Payment Summary</h6>
                                <div id="payment-summary-content"></div>
                            </div>
                        </div>

                        <div class="form-group-modern mb-4">
                            <label for="quick_amount_paid" class="form-label-modern">Payment Amount</label>
                            <div class="input-group input-group-modern">
                                <span class="input-group-text">£</span>
                                <input type="number" name="amount_paid" id="quick_amount_paid"
                                       class="form-control form-control-modern" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="form-group-modern mb-4">
                            <label for="quick_payment_date" class="form-label-modern">Payment Date</label>
                            <input type="date" name="payment_date" id="quick_payment_date"
                                   class="form-control form-control-modern" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="form-group-modern">
                            <label for="payment_method" class="form-label-modern">Payment Method</label>
                            <select name="payment_method" id="payment_method" class="form-select form-select-modern">
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cash">Cash</option>
                                <option value="card">Card Payment</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer modal-footer-modern">
                        <button type="button" class="btn btn-secondary-modern" data-dismiss="modal">
                            <i class="feather icon-x me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary-modern">
                            <i class="feather icon-check me-2"></i>Record Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <style>
        /* Modern Dashboard Styles */
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --danger-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --card-shadow: 0 10px 40px rgba(0,0,0,0.1);
            --card-hover-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        /* Dashboard Header */
        .dashboard-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .dashboard-title {
            font-size: 2.5rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .dashboard-subtitle {
            color: #6b7280;
            font-size: 1.1rem;
            margin-bottom: 0;
        }

        /* Enhanced Task Bar Notifications */
        .task-bar-notifications {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.3);
            overflow: hidden;
        }

        .task-bar-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
        }

        .task-bar-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .notification-count-badge {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            min-width: 20px;
            text-align: center;
        }

        .view-all-notifications {
            color: #667eea;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .view-all-notifications:hover {
            color: #5a67d8;
            transform: translateX(5px);
        }

        .task-bar-list {
            padding: 1rem 2rem 2rem;
            max-height: 600px;
            overflow-y: auto;
        }

        .task-item {
            display: flex;
            align-items: flex-start;
            gap: 1.5rem;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
        }

        .task-item:hover {
            transform: translateX(10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .task-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .task-content {
            flex: 1;
        }

        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .task-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0;
        }

        .task-priority {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .priority-1, .priority-2 {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
        }

        .priority-3, .priority-4 {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
        }

        .priority-5, .priority-6, .priority-7, .priority-8, .priority-9 {
            background: rgba(59, 130, 246, 0.1);
            color: #2563eb;
        }

        .task-message {
            color: #6b7280;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .task-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .task-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(0,0,0,0.05);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            color: #6b7280;
            font-weight: 500;
        }

        .task-meta-item.amount {
            font-weight: 700;
            background: rgba(239, 68, 68, 0.1);
        }

        .task-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            flex-shrink: 0;
        }

        .task-btn-view, .task-btn-pay, .task-btn-dismiss {
            border-radius: 10px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .task-btn-view:hover, .task-btn-pay:hover, .task-btn-dismiss:hover {
            transform: translateY(-2px);
        }

        .task-bar-footer {
            padding: 1rem 2rem;
            border-top: 1px solid rgba(0,0,0,0.05);
            background: rgba(0,0,0,0.02);
        }

        .alert-info-light {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
            color: #1e40af;
            border-radius: 12px;
            padding: 1rem;
            margin: 0;
        }

        .alert-link {
            color: #1d4ed8;
            font-weight: 600;
            text-decoration: none;
        }

        .alert-link:hover {
            text-decoration: underline;
        }

        /* Modern KPI Cards */
        .kpi-card-modern {
            position: relative;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.4s ease;
            overflow: hidden;
            height: 200px;
        }

        .kpi-card-modern:hover {
            transform: translateY(-10px);
            box-shadow: var(--card-hover-shadow);
        }

        .kpi-gradient-bg {
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            border-radius: 0 20px 0 100px;
            opacity: 0.1;
        }

        .gradient-primary { background: var(--primary-gradient); }
        .gradient-success { background: var(--success-gradient); }
        .gradient-warning { background: var(--warning-gradient); }
        .gradient-danger { background: var(--danger-gradient); }

        .kpi-card-content {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: flex-start;
            height: 100%;
        }

        .kpi-icon-modern {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            background: rgba(102, 126, 234, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #667eea;
            margin-right: 1.5rem;
            flex-shrink: 0;
        }

        .kpi-info {
            flex: 1;
        }

        .kpi-value-modern {
            font-size: 3rem;
            font-weight: 700;
            color: #1f2937;
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .kpi-label-modern {
            color: #6b7280;
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .kpi-trend-modern {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .kpi-trend-modern.positive {
            color: #10b981;
        }

        .kpi-trend-modern.negative {
            color: #ef4444;
        }

        .kpi-sparkline {
            position: absolute;
            bottom: 1rem;
            right: 1rem;
            width: 80px;
            height: 30px;
        }

        /* Modern Chart Cards */
        .chart-card-modern {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .chart-card-modern:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-hover-shadow);
        }

        .chart-card-header {
            padding: 1.5rem 2rem 1rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chart-title-section {
            flex: 1;
        }

        .chart-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .chart-subtitle {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 0;
        }

        .chart-legend-pills {
            display: flex;
            gap: 1rem;
        }

        .legend-pill {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            background: rgba(0,0,0,0.05);
        }

        .legend-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .revenue-pill .legend-dot { background: #667eea; }
        .expense-pill .legend-dot { background: #f093fb; }

        .chart-container-modern {
            position: relative;
            height: 350px;
            padding: 1rem;
        }

        .agreement-status-legend-modern {
            padding: 1.5rem 2rem;
            border-top: 1px solid rgba(0,0,0,0.05);
        }

        .legend-item-modern {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 0;
        }

        .legend-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 1rem;
        }

        .legend-text {
            flex: 1;
            font-size: 0.9rem;
            color: #4b5563;
            font-weight: 500;
        }

        .legend-count {
            font-weight: 600;
            color: #1f2937;
            font-size: 1rem;
        }

        /* Modern Activity Card */
        .activity-card-modern {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.3);
            overflow: hidden;
        }

        .activity-card-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .activity-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .activity-subtitle {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 0;
        }

        .table-modern {
            margin-bottom: 0;
        }

        .table-modern thead th {
            border: none;
            background: rgba(0,0,0,0.02);
            font-weight: 600;
            color: #4b5563;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem 1.5rem;
        }

        .table-modern tbody td {
            border: none;
            padding: 1.25rem 1.5rem;
            vertical-align: middle;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .vehicle-info {
            display: flex;
            flex-direction: column;
        }

        .vehicle-reg {
            font-weight: 600;
            color: #1f2937;
            font-size: 0.95rem;
        }

        .vehicle-model {
            color: #6b7280;
            font-size: 0.8rem;
        }

        .reference-code {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 0.8rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            color: white;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .empty-state {
            padding: 2rem;
        }

        /* Modern Modal */
        .modal-modern .modal-content {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .modal-header-modern {
            padding: 2rem 2rem 1rem;
            border: none;
            background: rgba(0,0,0,0.02);
        }

        .modal-title-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .modal-icon {
            width: 50px;
            height: 50px;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #667eea;
            font-size: 1.2rem;
        }

        .modal-subtitle {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 0;
        }

        .btn-close-modern {
            background: rgba(0,0,0,0.05);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
        }

        .modal-body-modern {
            padding: 1rem 2rem 2rem;
        }

        .payment-details-section {
            background: rgba(0,0,0,0.02);
            border-radius: 12px;
            padding: 1rem;
        }

        .payment-summary h6 {
            color: #374151;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .form-group-modern {
            margin-bottom: 1.5rem;
        }

        .form-label-modern {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-control-modern, .form-select-modern {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control-modern:focus, .form-select-modern:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .input-group-modern .input-group-text {
            background: rgba(102, 126, 234, 0.1);
            border: 2px solid #e5e7eb;
            border-right: none;
            color: #667eea;
            font-weight: 600;
        }

        .modal-footer-modern {
            padding: 1rem 2rem 2rem;
            border: none;
            background: rgba(0,0,0,0.02);
        }

        .btn-secondary-modern {
            background: rgba(0,0,0,0.05);
            border: none;
            color: #6b7280;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
        }

        .btn-primary-modern {
            background: var(--primary-gradient);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-title {
                font-size: 2rem;
            }

            .kpi-value-modern {
                font-size: 2.5rem;
            }

            .chart-container-modern {
                height: 300px;
            }

            .task-item {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
                padding: 1.5rem;
            }

            .task-header {
                flex-direction: column;
                gap: 0.5rem;
                text-align: center;
            }

            .task-actions {
                justify-content: center;
                flex-direction: column;
                width: 100%;
            }

            .task-meta {
                justify-content: center;
            }
        }

        /* Animation Classes */
        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 1000,
            easing: 'ease-out-cubic',
            once: true,
            offset: 100
        });

        // Enhanced Counter Animation
        function animateValue(element, start, end, duration) {
            const range = end - start;
            const increment = end > start ? 1 : -1;
            const stepTime = Math.abs(Math.floor(duration / range));
            const startTime = Date.now();
            const endTime = startTime + duration;

            function run() {
                const now = Date.now();
                const remaining = Math.max((endTime - now) / duration, 0);
                const value = Math.round(end - (remaining * range));
                element.textContent = value.toLocaleString();

                if (value === end) {
                    return;
                }

                requestAnimationFrame(run);
            }

            run();
        }

        // Animate counters on page load with stagger effect
        document.addEventListener('DOMContentLoaded', function() {
            const counters = document.querySelectorAll('.kpi-value-modern');
            counters.forEach((counter, index) => {
                const target = parseInt(counter.dataset.target);
                setTimeout(() => {
                    animateValue(counter, 0, target, 2000);
                }, index * 200);
            });
        });

        // Enhanced Revenue Analytics Chart
        const revenueCtx = document.getElementById('revenueAnalyticsChart').getContext('2d');

        // Create gradient
        const revenueGradient = revenueCtx.createLinearGradient(0, 0, 0, 350);
        revenueGradient.addColorStop(0, 'rgba(102, 126, 234, 0.3)');
        revenueGradient.addColorStop(1, 'rgba(102, 126, 234, 0.05)');

        const expenseGradient = revenueCtx.createLinearGradient(0, 0, 0, 350);
        expenseGradient.addColorStop(0, 'rgba(240, 147, 251, 0.3)');
        expenseGradient.addColorStop(1, 'rgba(240, 147, 251, 0.05)');

        const revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Revenue',
                    data: @json($monthlyRevenueData),
                    borderColor: '#667eea',
                    backgroundColor: revenueGradient,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 3,
                    pointRadius: 6,
                    pointHoverRadius: 10,
                    pointHoverBackgroundColor: '#667eea',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 3
                }, {
                    label: 'Expenses',
                    data: @json($monthlyExpenseData),
                    borderColor: '#f093fb',
                    backgroundColor: expenseGradient,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#f093fb',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 3,
                    pointRadius: 6,
                    pointHoverRadius: 10,
                    pointHoverBackgroundColor: '#f093fb',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(255, 255, 255, 0.95)',
                        titleColor: '#1f2937',
                        bodyColor: '#6b7280',
                        borderColor: 'rgba(0,0,0,0.1)',
                        borderWidth: 1,
                        cornerRadius: 12,
                        padding: 16,
                        titleFont: {
                            size: 14,
                            weight: '600'
                        },
                        bodyFont: {
                            size: 13
                        },
                        callbacks: {
                            title: function(context) {
                                return context[0].label + ' 2024';
                            },
                            label: function(context) {
                                return context.dataset.label + ': £' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        border: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 12,
                                weight: '500'
                            },
                            color: '#6b7280'
                        }
                    },
                    y: {
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        border: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 12
                            },
                            color: '#6b7280',
                            callback: function(value) {
                                return '£' + (value / 1000) + 'k';
                            },
                            padding: 10
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });

        // Enhanced Agreement Status Chart
        const agreementCtx = document.getElementById('agreementStatusChart').getContext('2d');
        const agreementChart = new Chart(agreementCtx, {
            type: 'doughnut',
            data: {
                labels: @json($agreementStatusSummary->pluck('status')),
                datasets: [{
                    data: @json($agreementStatusSummary->pluck('count')),
                    backgroundColor: @json($agreementStatusSummary->pluck('color')),
                    borderWidth: 0,
                    cutout: '75%',
                    spacing: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.95)',
                        titleColor: '#1f2937',
                        bodyColor: '#6b7280',
                        borderColor: 'rgba(0,0,0,0.1)',
                        borderWidth: 1,
                        cornerRadius: 12,
                        padding: 16,
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.raw / total) * 100).toFixed(1);
                                return context.label + ': ' + context.raw + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });

        // Quick Payment Function
        function quickPay(collectionId, amount) {
            const modal = new bootstrap.Modal(document.getElementById('quickPaymentModal'));
            const form = document.getElementById('quickPaymentForm');
            const amountInput = document.getElementById('quick_amount_paid');
            const summaryContent = document.getElementById('payment-summary-content');

            form.action = `/agreements/collections/${collectionId}/pay`;
            amountInput.value = amount;
            amountInput.max = amount;

            // Update payment summary
            summaryContent.innerHTML = `
                <div class="d-flex justify-content-between">
                    <span>Collection ID:</span>
                    <strong>#${collectionId}</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Amount Due:</span>
                    <strong class="text-danger">£${parseFloat(amount).toLocaleString()}</strong>
                </div>
            `;

            modal.show();
        }

        // Quick Payment from Task Bar
        function quickPayFromTaskBar(collectionId, amount) {
            quickPay(collectionId, amount);
        }

        // Dismiss Task Notification
        function dismissTaskNotification(notificationId) {
            const taskItem = document.querySelector(`[onclick*="${notificationId}"]`).closest('.task-item');

            if (confirm('Dismiss this notification?')) {
                taskItem.style.transition = 'all 0.3s ease';
                taskItem.style.transform = 'translateX(100%)';
                taskItem.style.opacity = '0';

                setTimeout(() => {
                    taskItem.remove();

                    // Update count badge
                    const countBadge = document.querySelector('.notification-count-badge');
                    if (countBadge) {
                        const currentCount = parseInt(countBadge.textContent);
                        if (currentCount > 1) {
                            countBadge.textContent = currentCount - 1;
                        } else {
                            countBadge.parentElement.style.display = 'none';
                        }
                    }

                    // Check if task bar is empty
                    const taskList = document.querySelector('.task-bar-list');
                    if (taskList && taskList.children.length === 0) {
                        document.querySelector('.task-bar-notifications').style.display = 'none';
                    }
                }, 300);

                console.log('Dismissing notification:', notificationId);
            }
        }

        // Add smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add loading states for payment buttons
        document.querySelectorAll('.task-btn-pay').forEach(button => {
            button.addEventListener('click', function() {
                const originalContent = this.innerHTML;
                this.innerHTML = '<i class="feather icon-loader rotating"></i> Processing...';
                this.disabled = true;

                setTimeout(() => {
                    this.innerHTML = originalContent;
                    this.disabled = false;
                }, 2000);
            });
        });

        // Add rotating animation for loader
        const style = document.createElement('style');
        style.textContent = `
            .rotating {
                animation: spin 1s linear infinite;
            }
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    </script>
@endsection
