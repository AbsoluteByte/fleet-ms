@extends('layouts.admin', ['title' => 'Dashboard'])

@section('content')
    <div class="row">
        <div class="col-12">
            <h1 class="h2 mb-4">
                <i class="feather icon-home me-2"></i>
                Welcome back, {{ $driver->first_name }}!
            </h1>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3>{{ $activeAgreements }}</h3>
                            <p class="mb-0">Active Agreements</p>
                        </div>
                        <div class="align-self-center">
                            <i class="feather icon-file-text fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3>{{ $pendingPayments }}</h3>
                            <p class="mb-0">Pending Payments</p>
                        </div>
                        <div class="align-self-center">
                            <i class="feather icon-clock fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3>{{ $overduePayments->count(); }}</h3>
                            <p class="mb-0">Overdue Payments</p>
                        </div>
                        <div class="align-self-center">
                            <i class="feather icon-alert-triangle fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3>{{ $totalAgreements }}</h3>
                            <p class="mb-0">Total Agreements</p>
                        </div>
                        <div class="align-self-center">
                            <i class="feather icon-briefcase fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Agreements -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="feather icon-file-text me-2"></i>
                        Recent Agreements
                    </h5>
                </div>
                <div class="card-body">
                    @forelse($recentAgreements as $agreement)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-3">
                            <div>
                                <h6 class="mb-1">{{ $agreement->car->registration }}
                                    - {{ $agreement->car->carModel->name }}</h6>
                                <p class="text-muted mb-0">
                                    {{ $agreement->company->name }} •
                                    {{ $agreement->start_date->format('M d, Y') }}
                                    - {{ $agreement->end_date->format('M d, Y') }}
                                </p>
                            </div>
                            <div class="text-end">
                            <span class="badge" style="background-color: {{ $agreement->status->color }}">
                                {{ $agreement->status->name }}
                            </span>
                                <p class="mb-0 text-muted">£{{ number_format($agreement->agreed_rent, 2) }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted text-center py-4">No agreements found.</p>
                    @endforelse

                    @if($recentAgreements->count() > 0)
                        <div class="text-center mt-3">
                            <a href="{{ route('driver.agreements') }}" class="btn btn-outline-primary">
                                View All Agreements
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Upcoming Payments -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="feather icon-calendar me-2"></i>
                        Upcoming Payments
                    </h5>
                </div>
                <div class="card-body">
                    @forelse($upcomingPayments as $payment)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <div>
                                <h6 class="mb-1">{{ $payment->agreement->car->registration }}</h6>
                                <small class="text-muted">Due: {{ $payment->due_date->format('M d, Y') }}</small>
                            </div>
                            <span class="badge bg-warning">£{{ number_format($payment->amount, 2) }}</span>
                        </div>
                    @empty
                        <p class="text-muted text-center py-4">No upcoming payments.</p>
                    @endforelse

                    @if($upcomingPayments->count() > 0)
                        <div class="text-center mt-3">
                            <a href="{{ route('driver.payments') }}" class="btn btn-outline-warning btn-sm">
                                View All Payments
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Overdue Payments Alert -->
    @if($overduePayments->count() > 0)
        <div class="row">
            <div class="col-12">
                <div class="alert alert-danger">
                    <h5><i class="feather icon-alert-triangle me-2"></i>Overdue Payments</h5>
                    <p>You have {{ $overduePayments->count() }} overdue payment(s). Please contact your fleet manager or
                        make payments as soon as possible.</p>
                    <a href="{{ route('driver.payments') }}" class="btn btn-outline-danger">
                        View Overdue Payments
                    </a>
                </div>
            </div>
        </div>
    @endif
@endsection
