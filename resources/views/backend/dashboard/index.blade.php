{{-- resources/views/backend/dashboard/index.blade.php --}}

@extends('layouts.admin', ['title' => 'Dashboard'])

@section('content')

    {{-- Subscription Status Widget --}}
    @php
        $tenant = auth()->user()->currentTenant();
        $subscription = $tenant->subscription;
    @endphp

    @if($subscription && $subscription->isTrialing())
        <div class="alert alert-warning alert-dismissible mb-2" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">×</span>
            </button>
            <div class="d-flex align-items-center">
                <i class="feather icon-clock mr-1"></i>
                <strong>Trial Period:</strong>&nbsp;{{ $subscription->trialDaysRemaining() }} days remaining
                <a href="{{ route('subscription.packages') }}" class="btn btn-sm btn-warning ml-auto">
                    <i class="feather icon-zap mr-50"></i>Upgrade Now
                </a>
            </div>
        </div>
    @endif

    {{-- Dashboard Analytics Section --}}
    <section id="dashboard-analytics">

        {{-- KPI Cards Row --}}
        <div class="row">
            {{-- Total Vehicles --}}
            <div class="col-lg-3 col-sm-6 col-12">
                <div class="card">
                    <div class="card-header d-flex flex-column align-items-start pb-0">
                        <div class="avatar bg-rgba-primary p-50 m-0">
                            <div class="avatar-content">
                                <i class="feather icon-truck text-primary font-medium-5"></i>
                            </div>
                        </div>
                        <h2 class="text-bold-700 mt-1 mb-25" data-counter="{{ $totalCars }}">0</h2>
                        <p class="mb-0">Total Vehicles</p>
                    </div>
                    <div class="card-content">
                        <div class="card-body px-1 pb-0">
                            <p class="mb-2">
                                <span class="text-{{ $carsGrowth >= 0 ? 'success' : 'danger' }}">
                                    <i class="feather icon-{{ $carsGrowth >= 0 ? 'trending-up' : 'trending-down' }}"></i>
                                    {{ $carsGrowth >= 0 ? '+' : '' }}{{ $carsGrowth }}%
                                </span>
                                <span class="text-muted ml-50">vs last month</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Active Drivers --}}
            <div class="col-lg-3 col-sm-6 col-12">
                <div class="card">
                    <div class="card-header d-flex flex-column align-items-start pb-0">
                        <div class="avatar bg-rgba-success p-50 m-0">
                            <div class="avatar-content">
                                <i class="feather icon-users text-success font-medium-5"></i>
                            </div>
                        </div>
                        <h2 class="text-bold-700 mt-1 mb-25" data-counter="{{ $totalDrivers }}">0</h2>
                        <p class="mb-0">Active Drivers</p>
                    </div>
                    <div class="card-content">
                        <div class="card-body px-1 pb-0">
                            <p class="mb-2">
                                <span class="text-{{ $driversGrowth >= 0 ? 'success' : 'danger' }}">
                                    <i class="feather icon-{{ $driversGrowth >= 0 ? 'trending-up' : 'trending-down' }}"></i>
                                    {{ $driversGrowth >= 0 ? '+' : '' }}{{ $driversGrowth }}%
                                </span>
                                <span class="text-muted ml-50">vs last month</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Monthly Revenue --}}
            <div class="col-lg-3 col-sm-6 col-12">
                <div class="card">
                    <div class="card-header d-flex flex-column align-items-start pb-0">
                        <div class="avatar bg-rgba-warning p-50 m-0">
                            <div class="avatar-content">
                                <i class="feather icon-pound-sign text-warning font-medium-5"></i>
                            </div>
                        </div>
                        <h2 class="text-bold-700 mt-1 mb-25" data-counter="{{ $monthlyRevenue }}">0</h2>
                        <p class="mb-0">Monthly Revenue</p>
                    </div>
                    <div class="card-content">
                        <div class="card-body px-1 pb-0">
                            <p class="mb-2">
                                <span class="text-{{ $revenueGrowth >= 0 ? 'success' : 'danger' }}">
                                    <i class="feather icon-{{ $revenueGrowth >= 0 ? 'trending-up' : 'trending-down' }}"></i>
                                    {{ $revenueGrowth >= 0 ? '+' : '' }}{{ $revenueGrowth }}%
                                </span>
                                <span class="text-muted ml-50">vs last month</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Outstanding --}}
            <div class="col-lg-3 col-sm-6 col-12">
                <div class="card">
                    <div class="card-header d-flex flex-column align-items-start pb-0">
                        <div class="avatar bg-rgba-danger p-50 m-0">
                            <div class="avatar-content">
                                <i class="feather icon-alert-circle text-danger font-medium-5"></i>
                            </div>
                        </div>
                        <h2 class="text-bold-700 mt-1 mb-25" data-counter="{{ $totalOutstanding }}">0</h2>
                        <p class="mb-0">Outstanding</p>
                    </div>
                    <div class="card-content">
                        <div class="card-body px-1 pb-0">
                            <p class="mb-2">
                                <span class="text-{{ $outstandingGrowth <= 0 ? 'success' : 'danger' }}">
                                    <i class="feather icon-{{ $outstandingGrowth <= 0 ? 'trending-down' : 'trending-up' }}"></i>
                                    {{ $outstandingGrowth >= 0 ? '+' : '' }}{{ $outstandingGrowth }}%
                                </span>
                                <span class="text-muted ml-50">vs last month</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts and Notifications Row --}}
        <div class="row match-height">

            {{-- Revenue Analytics Chart --}}
            <div class="col-lg-8 col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-start pb-1">
                        <div>
                            <h4 class="card-title mb-25">Revenue Analytics</h4>
                            <p class="text-muted mb-0">Monthly performance overview</p>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="mr-1"><i class="fa fa-circle text-primary"></i> Revenue</span>
                            <span><i class="fa fa-circle text-warning"></i> Expenses</span>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="card-body pb-0">
                            <div id="revenue-chart" style="height: 300px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Agreement Status --}}
            <div class="col-lg-4 col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-start">
                        <div>
                            <h4 class="card-title">Agreement Status</h4>
                            <p class="text-muted mb-0">Current distribution</p>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="card-body pt-0">
                            <div id="agreement-status-chart" style="height: 200px;"></div>
                            <div class="mt-2">
                                @foreach($agreementStatusSummary as $status)
                                    <div class="d-flex justify-content-between mb-1">
                                        <div class="d-flex align-items-center">
                                            <i class="fa fa-circle font-small-2 mr-50" style="color: {{ $status['color'] }}"></i>
                                            <span class="font-weight-bold">{{ $status['status'] }}</span>
                                        </div>
                                        <span class="text-bold-600">{{ $status['count'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Payment & Fleet Notifications Row --}}
        <div class="row match-height">

            {{-- Payment Notifications --}}
            <div class="col-lg-6 col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center pb-1">
                        <h4 class="card-title mb-0">
                            <i class="feather icon-credit-card mr-50"></i>
                            Payment Notifications
                        </h4>
                        <span class="badge badge-pill badge-danger">{{ $paymentNotifications->count() }}</span>
                    </div>
                    <div class="card-content">
                        <div class="card-body p-0">
                            <ul class="list-unstyled mb-0" style="max-height: 400px; overflow-y: auto;">
                                @forelse($paymentNotifications as $notification)
                                    <li class="d-flex justify-content-between notification-item-vuexy border-bottom p-1"
                                        style="border-left: 3px solid {{ $notification['border_color'] }}; background: {{ $notification['bg_color'] }}">
                                        <div class="media d-flex align-items-start w-100">
                                            <div class="media-left mr-1">
                                                <div class="avatar bg-light-{{ $notification['color'] }}">
                                                    <div class="avatar-content">
                                                        <i class="feather {{ $notification['icon'] }} font-medium-2 text-{{ $notification['color'] }}"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="media-body flex-grow-1">
                                                <h6 class="text-{{ $notification['color'] }} font-weight-bold mb-50">
                                                    {{ $notification['title'] }}
                                                </h6>
                                                <p class="font-small-3 mb-50">{{ $notification['simple_message'] }}</p>
                                                <div class="d-flex flex-wrap">
                                                    @if(isset($notification['vehicle']))
                                                        <span class="badge badge-light-secondary mr-50 mb-50">
                                                            <i class="feather icon-truck font-small-2"></i>
                                                            {{ $notification['vehicle'] }}
                                                        </span>
                                                    @endif
                                                    @if(isset($notification['amount']))
                                                        <span class="badge badge-light-{{ $notification['color'] }} mr-50 mb-50 font-weight-bold">
                                                            <i class="feather icon-pound-sign font-small-2"></i>
                                                            {{ $notification['amount'] }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <small class="text-muted">
                                                    <i class="feather icon-clock font-small-2"></i>
                                                    {{ $notification['time_ago'] }}
                                                </small>
                                            </div>
                                            <div class="media-right d-flex flex-column ml-1">
                                                @if($notification['action_url'])
                                                    <a href="{{ $notification['action_url'] }}"
                                                       class="btn btn-sm btn-outline-{{ $notification['color'] }} btn-icon mb-50">
                                                        <i class="feather icon-eye"></i>
                                                    </a>
                                                @endif
                                                @php
                                                    $collectionId = explode('_', $notification['id'])[1] ?? null;
                                                    $amount = isset($notification['amount']) ? str_replace(['£', ','], '', $notification['amount']) : 0;
                                                @endphp
                                                @if($collectionId)
                                                    <button class="btn btn-sm btn-{{ $notification['color'] }} btn-icon"
                                                            onclick="quickPayFromNotif('{{ $collectionId }}', '{{ $amount }}')">
                                                        <i class="feather icon-credit-card"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </li>
                                @empty
                                    <li class="text-center p-2">
                                        <div class="py-3">
                                            <i class="feather icon-check-circle font-large-2 text-success mb-1"></i>
                                            <p class="text-muted mb-0">No payment notifications</p>
                                        </div>
                                    </li>
                                @endforelse
                            </ul>
                            @if($paymentNotifications->count() >= 10)
                                <div class="card-footer text-center border-top">
                                    <a href="{{ route('notifications.index') }}" class="text-primary">
                                        View All Notifications <i class="feather icon-arrow-right"></i>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Fleet Notifications --}}
            <div class="col-lg-6 col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center pb-1">
                        <h4 class="card-title mb-0">
                            <i class="feather icon-bell mr-50"></i>
                            Fleet Notifications
                        </h4>
                        <span class="badge badge-pill badge-warning">{{ $fleetNotifications->count() }}</span>
                    </div>
                    <div class="card-content">
                        <div class="card-body p-0">
                            <ul class="list-unstyled mb-0" style="max-height: 400px; overflow-y: auto;">
                                @forelse($fleetNotifications as $notification)
                                    <li class="d-flex justify-content-between notification-item-vuexy border-bottom p-1"
                                        style="border-left: 3px solid {{ $notification['border_color'] }}; background: {{ $notification['bg_color'] }}">
                                        <div class="media d-flex align-items-start w-100">
                                            <div class="media-left mr-1">
                                                <div class="avatar bg-light-{{ $notification['color'] }}">
                                                    <div class="avatar-content">
                                                        <i class="feather {{ $notification['icon'] }} font-medium-2 text-{{ $notification['color'] }}"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="media-body flex-grow-1">
                                                <h6 class="text-{{ $notification['color'] }} font-weight-bold mb-50">
                                                    {{ $notification['title'] }}
                                                </h6>
                                                <p class="font-small-3 mb-50">{{ $notification['simple_message'] }}</p>
                                                <div class="d-flex flex-wrap">
                                                    @if(isset($notification['vehicle']))
                                                        <span class="badge badge-light-secondary mr-50 mb-50">
                                                            <i class="feather icon-truck font-small-2"></i>
                                                            {{ $notification['vehicle'] }}
                                                        </span>
                                                    @endif
                                                    @if(isset($notification['driver']))
                                                        <span class="badge badge-light-info mr-50 mb-50">
                                                            <i class="feather icon-user font-small-2"></i>
                                                            {{ $notification['driver'] }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <small class="text-muted">
                                                    <i class="feather icon-clock font-small-2"></i>
                                                    {{ $notification['time_ago'] }}
                                                </small>
                                            </div>
                                            <div class="media-right ml-1">
                                                @if($notification['action_url'])
                                                    <a href="{{ $notification['action_url'] }}"
                                                       class="btn btn-sm btn-outline-{{ $notification['color'] }} btn-icon">
                                                        <i class="feather icon-external-link"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </li>
                                @empty
                                    <li class="text-center p-2">
                                        <div class="py-3">
                                            <i class="feather icon-check-circle font-large-2 text-success mb-1"></i>
                                            <p class="text-muted mb-0">No fleet notifications</p>
                                        </div>
                                    </li>
                                @endforelse
                            </ul>
                            @if($fleetNotifications->count() >= 10)
                                <div class="card-footer text-center border-top">
                                    <a href="{{ route('notifications.index') }}" class="text-primary">
                                        View All Notifications <i class="feather icon-arrow-right"></i>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Activities Table --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Recent Activities</h4>
                        <a href="{{ route('claims.index') }}" class="btn btn-sm btn-primary">
                            View All <i class="feather icon-arrow-right"></i>
                        </a>
                    </div>
                    <div class="card-content">
                        <div class="table-responsive mt-1">
                            <table class="table table-hover-animation mb-0">
                                <thead>
                                <tr>
                                    <th>VEHICLE</th>
                                    <th>CASE DATE</th>
                                    <th>REFERENCE</th>
                                    <th>STATUS</th>
                                    <th>ACTIONS</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($recentClaims as $claim)
                                    <tr>
                                        <td>
                                            <div class="font-weight-bold">{{ $claim->car->registration }}</div>
                                            <small class="text-muted">{{ $claim->car->carModel->name }}</small>
                                        </td>
                                        <td>{{ $claim->case_date->format('d M, Y') }}</td>
                                        <td>
                                            <span class="badge badge-light-primary">{{ $claim->our_reference }}</span>
                                        </td>
                                        <td>
                                            <i class="fa fa-circle font-small-3 mr-50" style="color: {{ $claim->status->color }}"></i>
                                            {{ $claim->status->name }}
                                        </td>
                                        <td>
                                            <a href="{{ route('claims.show', $claim) }}"
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="feather icon-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-2">
                                            <i class="feather icon-file-text font-large-2 text-muted d-block mb-1"></i>
                                            <p class="text-muted mb-0">No recent activities</p>
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

    </section>

    {{-- Quick Payment Modal --}}
    <div class="modal fade" id="quickPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="quickPaymentForm" method="POST">
                    @csrf
                    <div class="modal-header bg-primary">
                        <h5 class="modal-title text-white">
                            <i class="feather icon-credit-card mr-50"></i>
                            Record Payment
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info" id="payment-summary-content"></div>

                        <div class="form-group">
                            <label>Payment Amount (£)</label>
                            <input type="number" name="amount_paid" id="quick_amount_paid"
                                   class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Payment Date</label>
                            <input type="date" name="payment_date" id="quick_payment_date"
                                   class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="form-group">
                            <label>Payment Method</label>
                            <select name="payment_method" class="form-control">
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cash">Cash</option>
                                <option value="card">Card Payment</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="feather icon-check mr-50"></i>Record Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('css')
    <style>
        .notification-item-vuexy {
            transition: all 0.3s ease;
        }
        .notification-item-vuexy:hover {
            transform: translateX(5px);
        }
        .card-header .badge-pill {
            font-size: 0.85rem;
        }
    </style>
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        // Counter Animation
        document.querySelectorAll('[data-counter]').forEach(el => {
            const target = parseInt(el.dataset.counter);
            let current = 0;
            const increment = target / 50;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    el.textContent = target.toLocaleString();
                    clearInterval(timer);
                } else {
                    el.textContent = Math.floor(current).toLocaleString();
                }
            }, 20);
        });

        // Revenue Chart
        var revenueOptions = {
            series: [{
                name: 'Revenue',
                data: @json($monthlyRevenueData)
            }, {
                name: 'Expenses',
                data: @json($monthlyExpenseData)
            }],
            chart: {
                type: 'line',
                height: 300,
                toolbar: { show: false }
            },
            colors: ['#7367F0', '#FFA500'],
            stroke: {
                curve: 'smooth',
                width: 3
            },
            dataLabels: { enabled: false },
            xaxis: {
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
            },
            yaxis: {
                labels: {
                    formatter: function(val) {
                        return '£' + (val / 1000) + 'k';
                    }
                }
            },
            grid: {
                borderColor: '#e7e7e7'
            }
        };
        var revenueChart = new ApexCharts(document.querySelector("#revenue-chart"), revenueOptions);
        revenueChart.render();

        // Agreement Status Chart
        var statusOptions = {
            series: @json($agreementStatusSummary->pluck('count')),
            chart: {
                type: 'donut',
                height: 200
            },
            labels: @json($agreementStatusSummary->pluck('status')),
            colors: @json($agreementStatusSummary->pluck('color')),
            legend: { show: false },
            dataLabels: { enabled: false }
        };
        var statusChart = new ApexCharts(document.querySelector("#agreement-status-chart"), statusOptions);
        statusChart.render();

        // Quick Payment Function
        function quickPayFromNotif(collectionId, amount) {
            const modal = $('#quickPaymentModal');
            const form = $('#quickPaymentForm');

            form.attr('action', `/admin/agreements/collections/${collectionId}/pay`);
            $('#quick_amount_paid').val(amount).attr('max', amount);

            $('#payment-summary-content').html(`
        <strong>Collection ID:</strong> #${collectionId}<br>
        <strong>Amount Due:</strong> £${parseFloat(amount).toLocaleString()}
    `);

            modal.modal('show');
        }
    </script>
@endsection
