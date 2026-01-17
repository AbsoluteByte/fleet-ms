@extends('layouts.admin', ['title' => 'Customer Details'])

@section('content')
    <div class="content-header row">
        <div class="content-header-left col-md-9 col-12 mb-2">
            <h2 class="content-header-title">
                <i class="fa fa-building"></i> {{ $tenant->company_name }}
            </h2>
            <div class="breadcrumb-wrapper">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route($url . 'index') }}">Customers</a></li>
                    <li class="breadcrumb-item active">{{ $tenant->company_name }}</li>
                </ol>
            </div>
        </div>
        <div class="content-header-right text-md-right col-md-3 col-12">
            <a href="{{ route($url . 'edit', $tenant->id) }}" class="btn btn-primary">
                <i class="fa fa-edit"></i> Edit Customer
            </a>
        </div>
    </div>

    <div class="content-body">
        @include('alerts')

        {{-- Customer Overview --}}
        <div class="row">
            <div class="col-lg-8">
                {{-- Company Information --}}
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fa fa-info-circle"></i> Company Information
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-item mb-3">
                                    <strong>Company Name:</strong>
                                    <p>{{ $tenant->company_name }}</p>
                                </div>
                                <div class="info-item mb-3">
                                    <strong>Status:</strong>
                                    <p>
                                        @if($tenant->isActive())
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-danger">Suspended</span>
                                            @if($tenant->suspension_reason)
                                                <br><small
                                                    class="text-muted">Reason: {{ $tenant->suspension_reason }}</small>
                                            @endif
                                        @endif
                                    </p>
                                </div>
                                <div class="info-item mb-3">
                                    <strong>Registered On:</strong>
                                    <p>{{ $tenant->created_at->format('d M, Y h:i A') }}</p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="info-item mb-3">
                                    <strong>Admin User:</strong>
                                    <p>
                                        {{ $tenant->users->first()->name ?? 'N/A' }}<br>
                                        <small class="text-muted">{{ $tenant->users->first()->email ?? '' }}</small>
                                    </p>
                                </div>
                                <div class="info-item mb-3">
                                    <strong>Stripe Customer ID:</strong>
                                    <p>
                                        @if($tenant->stripe_customer_id)
                                            <code>{{ $tenant->stripe_customer_id }}</code>
                                            <a href="https://dashboard.stripe.com/customers/{{ $tenant->stripe_customer_id }}"
                                               target="_blank" class="btn btn-sm btn-outline-primary ml-2">
                                                <i class="fab fa-stripe"></i> View on Stripe
                                            </a>
                                        @else
                                            <span class="text-muted">Not set</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Subscription Details --}}
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fa fa-crown text-warning"></i> Subscription Details
                        </h4>
                    </div>
                    <div class="card-body">
                        @if($subscription)
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item mb-3">
                                        <strong>Current Package:</strong>
                                        <p>
                                            <span
                                                class="badge badge-info badge-lg">{{ $subscription->package->name }}</span><br>
                                            <small class="text-muted">{{ $subscription->package->description }}</small>
                                        </p>
                                    </div>
                                    <div class="info-item mb-3">
                                        <strong>Price:</strong>
                                        <p class="text-primary">
                                            <strong>{{ $subscription->package->getPriceFormatted() }}</strong>
                                            / {{ $subscription->package->billing_period }}
                                        </p>
                                    </div>
                                    <div class="info-item mb-3">
                                        <strong>Status:</strong>
                                        <p>
                                            @if($subscription->isTrialing())
                                                <span class="badge badge-warning">Trial - {{ $subscription->trialDaysRemaining() }} days left</span>
                                            @elseif($subscription->isActive())
                                                <span class="badge badge-success">Active</span>
                                            @elseif($subscription->isCancelled())
                                                <span class="badge badge-danger">Cancelled</span>
                                            @elseif($subscription->isSuspended())
                                                <span class="badge badge-danger">Suspended</span>
                                            @else
                                                <span
                                                    class="badge badge-secondary">{{ ucfirst($subscription->status) }}</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    @if($subscription->isTrialing())
                                        <div class="info-item mb-3">
                                            <strong>Trial Ends:</strong>
                                            <p>{{ $subscription->trial_ends_at->format('d M, Y') }}</p>
                                        </div>
                                    @endif
                                    <div class="info-item mb-3">
                                        <strong>Current Period:</strong>
                                        <p>
                                            {{ $subscription->current_period_start->format('d M, Y') }} -
                                            {{ $subscription->current_period_end->format('d M, Y') }}
                                        </p>
                                    </div>
                                    <div class="info-item mb-3">
                                        <strong>Next Billing Date:</strong>
                                        <p>{{ $subscription->current_period_end->format('d M, Y') }}</p>
                                    </div>
                                    @if($subscription->stripe_subscription_id)
                                        <div class="info-item mb-3">
                                            <strong>Stripe Subscription:</strong>
                                            <p>
                                                <a href="https://dashboard.stripe.com/subscriptions/{{ $subscription->stripe_subscription_id }}"
                                                   target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fab fa-stripe"></i> View on Stripe
                                                </a>
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Package Features --}}
                            <hr class="my-3">
                            <h6 class="mb-3">Package Features:</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <ul class="list-unstyled">
                                        <li>
                                            <i class="fa fa-check text-success"></i> {{ $subscription->package->getUsersLimit() }}
                                            Users
                                        </li>
                                        <li>
                                            <i class="fa fa-check text-success"></i> {{ $subscription->package->getVehiclesLimit() }}
                                            Vehicles
                                        </li>
                                        <li>
                                            <i class="fa fa-check text-success"></i> {{ $subscription->package->getDriversLimit() }}
                                            Drivers
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-md-4">
                                    <ul class="list-unstyled">
                                        @if($subscription->package->has_notifications)
                                            <li><i class="fa fa-check text-success"></i> Notifications</li>
                                        @endif
                                        @if($subscription->package->has_reports)
                                            <li><i class="fa fa-check text-success"></i> Advanced Reports</li>
                                        @endif
                                        @if($subscription->package->has_api_access)
                                            <li><i class="fa fa-check text-success"></i> API Access</li>
                                        @endif
                                    </ul>
                                </div>
                                <div class="col-md-4">
                                    @foreach($subscription->package->features ?? [] as $feature)
                                        <li><i class="fa fa-check text-success"></i> {{ $feature }}</li>
                                    @endforeach
                                </div>
                            </div>

                        @else
                            <div class="text-center py-4">
                                <i class="fa fa-box-open fa-3x text-muted mb-3"></i>
                                <h5>No Active Subscription</h5>
                                <p class="text-muted">This customer hasn't subscribed to any package yet.</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Payment Methods --}}
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fa fa-credit-card"></i> Payment Methods
                        </h4>
                    </div>
                    <div class="card-body">
                        @if($tenant->paymentMethods->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                    <tr>
                                        <th>Card</th>
                                        <th>Expires</th>
                                        <th>Status</th>
                                        <th>Default</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($tenant->paymentMethods as $method)
                                        <tr>
                                            <td>
                                                <i class="fab fa-cc-{{ strtolower($method->card_brand) }}"></i>
                                                •••• {{ $method->card_last_four }}
                                            </td>
                                            <td>{{ $method->getExpiryDisplay() }}</td>
                                            <td>
                                                @if($method->isExpired())
                                                    <span class="badge badge-danger">Expired</span>
                                                @elseif($method->isExpiringSoon())
                                                    <span class="badge badge-warning">Expiring Soon</span>
                                                @else
                                                    <span class="badge badge-success">Active</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($method->is_default)
                                                    <span class="badge badge-primary">Default</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted text-center mb-0">No payment methods added</p>
                        @endif
                    </div>
                </div>

                {{-- Recent Invoices --}}
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fa fa-file-invoice"></i> Recent Invoices
                        </h4>
                    </div>
                    <div class="card-body">
                        @if($tenant->invoices->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($tenant->invoices as $invoice)
                                        <tr>
                                            <td>{{ $invoice->invoice_number }}</td>
                                            <td>{{ $invoice->created_at->format('d M, Y') }}</td>
                                            <td>£{{ number_format($invoice->total, 2) }}</td>
                                            <td>
                                                @if($invoice->status == 'paid')
                                                    <span class="badge badge-success">Paid</span>
                                                @elseif($invoice->status == 'pending')
                                                    <span class="badge badge-warning">Pending</span>
                                                @else
                                                    <span class="badge badge-danger">Failed</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted text-center mb-0">No invoices yet</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="col-lg-4">
                {{-- Quick Stats --}}
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fa fa-chart-pie"></i> Usage Statistics
                        </h4>
                    </div>
                    <div class="card-body">
                        @if($subscription)
                            {{-- Users --}}
                            <div class="usage-item mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span><i class="fa fa-users"></i> Users</span>
                                    <span>{{ $stats['total_users'] }} / {{ $subscription->package->getUsersLimit() }}</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-primary"
                                         style="width: {{ min($stats['users_percentage'] ?? 0, 100) }}%">
                                    </div>
                                </div>
                            </div>

                            {{-- Vehicles --}}
                            <div class="usage-item mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span><i class="fa fa-car"></i> Vehicles</span>
                                    <span>{{ $stats['total_vehicles'] }} / {{ $subscription->package->getVehiclesLimit() }}</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success"
                                         style="width: {{ min($stats['vehicles_percentage'] ?? 0, 100) }}%">
                                    </div>
                                </div>
                            </div>

                            {{-- Drivers --}}
                            <div class="usage-item mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span><i class="fa fa-user"></i> Drivers</span>
                                    <span>{{ $stats['total_drivers'] }} / {{ $subscription->package->getDriversLimit() }}</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-warning"
                                         style="width: {{ min($stats['drivers_percentage'] ?? 0, 100) }}%">
                                    </div>
                                </div>
                            </div>
                        @else
                            <p class="text-muted text-center">No active subscription</p>
                        @endif
                    </div>
                </div>

                {{-- Billing Summary --}}
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fa fa-money-bill-wave"></i> Billing Summary
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="billing-item mb-3">
                            <strong>Total Invoices:</strong>
                            <p class="text-primary mb-0">{{ $stats['total_invoices'] }}</p>
                        </div>
                        <div class="billing-item mb-3">
                            <strong>Total Paid:</strong>
                            <p class="text-success mb-0">£{{ number_format($stats['total_paid'], 2) }}</p>
                        </div>
                        @if($subscription)
                            <div class="billing-item mb-3">
                                <strong>Current MRR:</strong>
                                <p class="text-info mb-0">
                                    @if($subscription->package->billing_period == 'monthly')
                                        £{{ number_format($subscription->package->price, 2) }}
                                    @elseif($subscription->package->billing_period == 'quarterly')
                                        £{{ number_format($subscription->package->price / 3, 2) }}
                                    @else
                                        £{{ number_format($subscription->package->price / 12, 2) }}
                                    @endif
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fa fa-bolt"></i> Quick Actions
                        </h4>
                    </div>
                    <div class="card-body">
                        @if($tenant->isActive())
                            <button class="btn btn-danger btn-block mb-2" onclick="suspendTenant({{ $tenant->id }})">
                                <i class="fa fa-ban"></i> Suspend Customer
                            </button>
                        @else
                            <button class="btn btn-success btn-block mb-2" onclick="activateTenant({{ $tenant->id }})">
                                <i class="fa fa-check"></i> Activate Customer
                            </button>
                        @endif

                        <a href="{{ route($url . 'edit', $tenant->id) }}" class="btn btn-primary btn-block mb-2">
                            <i class="fa fa-edit"></i> Edit Details
                        </a>

                        @if($tenant->stripe_customer_id)
                            <a href="https://dashboard.stripe.com/customers/{{ $tenant->stripe_customer_id }}"
                               target="_blank" class="btn btn-outline-primary btn-block mb-2">
                                <i class="fab fa-stripe"></i> View on Stripe
                            </a>
                        @endif

                        <form action="{{ route($url . 'destroy', $tenant->id) }}"
                              method="POST"
                              onsubmit="return confirm('Are you sure? This will delete all customer data!')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-block">
                                <i class="fa fa-trash"></i> Delete Customer
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <style>
        .info-item strong {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .info-item p {
            margin-top: 5px;
            margin-bottom: 0;
        }

        .badge-lg {
            font-size: 1rem;
            padding: 8px 15px;
        }

        .usage-item {
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }
    </style>
@endsection

@section('js')
    <script>
        function suspendTenant(tenantId) {
            const reason = prompt('Enter suspension reason:');
            if (reason) {
                // Add suspend route later
                alert('Suspend functionality to be implemented');
            }
        }

        function activateTenant(tenantId) {
            if (confirm('Activate this customer?')) {
                // Add activate route later
                alert('Activate functionality to be implemented');
            }
        }
    </script>
@endsection
