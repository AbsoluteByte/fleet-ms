@extends('layouts.admin', ['title' => 'My Subscription'])

@section('content')
        <div class="content-header row">
            <div class="content-header-left col-12 mb-2">
                <h2 class="content-header-title">My Subscription</h2>
            </div>
        </div>

        <div class="content-body">
            @include('alerts')

            {{-- Subscription Status Card --}}
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="fa fa-crown text-warning"></i> Current Plan
                            </h4>
                        </div>
                        <div class="card-body">
                            @if($subscription)
                                <div class="subscription-info">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h3 class="mb-3">{{ $subscription->package->name }}</h3>
                                            <p class="text-muted">{{ $subscription->package->description }}</p>

                                            <div class="price-tag mt-3 mb-3">
                                                <h2 class="text-primary mb-0">
                                                    {{ $subscription->package->getPriceFormatted() }}
                                                    <small class="text-muted">/{{ $subscription->package->billing_period }}</small>
                                                </h2>
                                            </div>

                                            <div class="status-badges mb-3">
                                                @if($subscription->isTrialing())
                                                    <span class="badge badge-warning badge-lg">
                                                        <i class="fa fa-clock"></i> Trial Period
                                                    </span>
                                                @elseif($subscription->isActive())
                                                    <span class="badge badge-success badge-lg">
                                                        <i class="fa fa-check-circle"></i> Active
                                                    </span>
                                                @elseif($subscription->isCancelled())
                                                    <span class="badge badge-danger badge-lg">
                                                        <i class="fa fa-times-circle"></i> Cancelled
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <h5 class="mb-3">Plan Includes:</h5>
                                            <ul class="feature-list">
                                                <li>
                                                    <i class="fa fa-users text-primary"></i>
                                                    <strong>{{ $subscription->package->getUsersLimit() }}</strong> Users
                                                </li>
                                                <li>
                                                    <i class="fa fa-car text-primary"></i>
                                                    <strong>{{ $subscription->package->getVehiclesLimit() }}</strong> Vehicles
                                                </li>
                                                <li>
                                                    <i class="fa fa-user text-primary"></i>
                                                    <strong>{{ $subscription->package->getDriversLimit() }}</strong> Drivers
                                                </li>
                                                @if($subscription->package->has_notifications)
                                                    <li>
                                                        <i class="fa fa-bell text-success"></i>
                                                        Email & SMS Notifications
                                                    </li>
                                                @endif
                                                @if($subscription->package->has_reports)
                                                    <li>
                                                        <i class="fa fa-chart-bar text-success"></i>
                                                        Advanced Reports
                                                    </li>
                                                @endif
                                                @if($subscription->package->has_api_access)
                                                    <li>
                                                        <i class="fa fa-code text-success"></i>
                                                        API Access
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </div>

                                    <hr class="my-4">

                                    {{-- Subscription Details --}}
                                    <div class="row">
                                        <div class="col-md-6">
                                            @if($subscription->isTrialing())
                                                <div class="detail-item">
                                                    <strong>Trial Ends:</strong>
                                                    <span class="text-warning">
                                                        {{ $subscription->trial_ends_at->format('d M, Y') }}
                                                        ({{ $subscription->trialDaysRemaining() }} days left)
                                                    </span>
                                                </div>
                                            @else
                                                <div class="detail-item">
                                                    <strong>Billing Cycle:</strong>
                                                    <span>{{ ucfirst($subscription->package->billing_period) }}</span>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="col-md-6">
                                            <div class="detail-item">
                                                <strong>Next Billing Date:</strong>
                                                <span>{{ $subscription->current_period_end->format('d M, Y') }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Action Buttons --}}
                                    <div class="mt-4">
                                        @if($subscription->isTrialing() || $subscription->package->name === 'Free Trial')
                                            <a href="{{ route($url . 'packages') }}" class="btn btn-warning btn-lg">
                                                <i class="fa fa-rocket"></i> Upgrade Now
                                            </a>
                                        @else
                                            <a href="{{ route($url . 'packages') }}" class="btn btn-primary">
                                                <i class="fa fa-exchange-alt"></i> Change Plan
                                            </a>

                                            @if($subscription->isCancelled())
                                                <form action="{{ route($url . 'resume') }}" method="POST" style="display: inline;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success">
                                                        <i class="fa fa-play"></i> Resume Subscription
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route($url . 'cancel') }}" method="POST" style="display: inline;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-danger"
                                                            onclick="return confirm('Are you sure you want to cancel your subscription?')">
                                                        <i class="fa fa-times"></i> Cancel Subscription
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="fa fa-box-open fa-3x text-muted mb-3"></i>
                                    <h4>No Active Subscription</h4>
                                    <p class="text-muted">Choose a plan to get started</p>
                                    <a href="{{ route($url . 'packages') }}" class="btn btn-primary btn-lg mt-3">
                                        <i class="fa fa-shopping-cart"></i> Browse Packages
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Quick Stats Sidebar --}}
                <div class="col-lg-4">
                    {{-- Usage Stats --}}
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="fa fa-chart-pie"></i> Usage Statistics
                            </h4>
                        </div>
                        <div class="card-body">
                            @if($subscription)
                                @php
                                    $package = $subscription->package;
                                    $usersCount = $tenant->users()->count();
                                    $vehiclesCount = $tenant->cars()->count();
                                    $driversCount = $tenant->drivers()->count();
                                @endphp

                                <div class="usage-item mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span><i class="fa fa-users"></i> Users</span>
                                        <span>{{ $usersCount }} / {{ $package->getUsersLimit() }}</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-primary"
                                             style="width: {{ $package->max_users > 0 ? ($usersCount / $package->max_users * 100) : 0 }}%">
                                        </div>
                                    </div>
                                </div>

                                <div class="usage-item mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span><i class="fa fa-car"></i> Vehicles</span>
                                        <span>{{ $vehiclesCount }} / {{ $package->getVehiclesLimit() }}</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-success"
                                             style="width: {{ $package->max_vehicles > 0 ? ($vehiclesCount / $package->max_vehicles * 100) : 0 }}%">
                                        </div>
                                    </div>
                                </div>

                                <div class="usage-item mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span><i class="fa fa-user"></i> Drivers</span>
                                        <span>{{ $driversCount }} / {{ $package->getDriversLimit() }}</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-warning"
                                             style="width: {{ $package->max_drivers > 0 ? ($driversCount / $package->max_drivers * 100) : 0 }}%">
                                        </div>
                                    </div>
                                </div>
                            @else
                                <p class="text-muted text-center">No active subscription</p>
                            @endif
                        </div>
                    </div>

                    {{-- Payment Method --}}
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="fa fa-credit-card"></i> Payment Method
                            </h4>
                        </div>
                        <div class="card-body">
                            @if($paymentMethods->count() > 0)
                                @foreach($paymentMethods as $method)
                                    <div class="payment-method-item mb-2">
                                        <i class="fa fa-cc-{{ strtolower($method->card_brand) }} fa-2x"></i>
                                        <span class="ml-2">
                                            {{ $method->getCardDisplay() }}
                                            <br>
                                            <small class="text-muted">Expires {{ $method->getExpiryDisplay() }}</small>
                                        </span>
                                        @if($method->is_default)
                                            <span class="badge badge-success ml-2">Default</span>
                                        @endif
                                    </div>
                                @endforeach
                                <a href="{{ route($url . 'payment-methods') }}" class="btn btn-sm btn-outline-primary btn-block mt-3">
                                    Manage Cards
                                </a>
                            @else
                                <p class="text-muted text-center mb-3">No payment method added</p>
                                <a href="{{ route($url . 'payment-methods') }}" class="btn btn-primary btn-block">
                                    <i class="fa fa-plus"></i> Add Payment Method
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
@endsection

@section('css')
    <style>
        .feature-list {
            list-style: none;
            padding: 0;
        }
        .feature-list li {
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .feature-list li:last-child {
            border-bottom: none;
        }
        .detail-item {
            margin-bottom: 15px;
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
