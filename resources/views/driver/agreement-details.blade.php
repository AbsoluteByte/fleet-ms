@extends('layouts.admin', ['title' => 'Agreement Details'])

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <a href="{{ route('driver.agreements') }}" class="btn btn-outline-secondary btn-sm mb-2">
                        <i class="feather icon-arrow-left me-1"></i>
                        Back to Agreements
                    </a>
                    <h1 class="h2 mb-0">
                        Agreement Details - {{ $agreement->car->registration }}
                    </h1>
                </div>
                <div class="status-badge-large">
                <span class="badge" style="background-color: {{ $agreement->status->color }}; font-size: 1rem; padding: 0.5rem 1rem;">
                    {{ $agreement->status->name }}
                </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Agreement Overview -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card agreement-overview">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="feather icon-info me-2"></i>
                        Agreement Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-group">
                                <h6 class="info-label">Vehicle Details</h6>
                                <div class="info-content">
                                    <div class="vehicle-card">
                                        <h4 class="vehicle-reg">{{ $agreement->car->registration }}</h4>
                                        <p class="vehicle-model">{{ $agreement->car->carModel->name }}</p>
                                        <p class="vehicle-color">Color: {{ $agreement->car->color }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="info-group">
                                <h6 class="info-label">Company</h6>
                                <div class="info-content">
                                    <p class="company-name">{{ $agreement->company->name }}</p>
                                    <small class="text-muted">{{ $agreement->company->email }}</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-group">
                                <h6 class="info-label">Agreement Period</h6>
                                <div class="info-content">
                                    <div class="date-range">
                                        <div class="date-item">
                                            <span class="date-label">Start Date</span>
                                            <span class="date-value">{{ $agreement->start_date->format('F j, Y') }}</span>
                                        </div>
                                        <div class="date-separator">
                                            <i class="feather icon-arrow-right"></i>
                                        </div>
                                        <div class="date-item">
                                            <span class="date-label">End Date</span>
                                            <span class="date-value">{{ $agreement->end_date->format('F j, Y') }}</span>
                                        </div>
                                    </div>
                                    <div class="duration-info">
                                        <small class="text-muted">
                                            Duration: {{ $agreement->start_date->diffInDays($agreement->end_date) }} days
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="info-group">
                                <h6 class="info-label">Financial Terms</h6>
                                <div class="info-content">
                                    <div class="financial-grid">
                                        <div class="financial-item">
                                            <span class="financial-label">Rent Amount</span>
                                            <span class="financial-value primary">£{{ number_format($agreement->agreed_rent, 2) }}</span>
                                            <small class="financial-interval">{{ $agreement->rent_interval }}</small>
                                        </div>
                                        <div class="financial-item">
                                            <span class="financial-label">Deposit</span>
                                            <span class="financial-value">£{{ number_format($agreement->deposit_amount, 2) }}</span>
                                        </div>
                                        @if($agreement->security_deposit)
                                            <div class="financial-item">
                                                <span class="financial-label">Security Deposit</span>
                                                <span class="financial-value">£{{ number_format($agreement->security_deposit, 2) }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($agreement->notes)
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="info-group">
                                    <h6 class="info-label">Notes</h6>
                                    <div class="notes-content">
                                        <p>{{ $agreement->notes }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card payment-summary-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="feather icon-credit-card me-2"></i>
                        Payment Summary
                    </h5>
                </div>
                <div class="card-body">
                    @php
                        $totalPaid = $agreement->collections->where('payment_status', 'paid')->sum('amount_paid');
                        $totalPending = $agreement->collections->whereIn('payment_status', ['pending', 'overdue'])->sum('amount');
                        $paidCount = $agreement->collections->where('payment_status', 'paid')->count();
                        $pendingCount = $agreement->collections->where('payment_status', 'pending')->count();
                        $overdueCount = $agreement->collections->where('payment_status', 'overdue')->count();
                    @endphp

                    <div class="summary-stats">
                        <div class="stat-item success">
                            <div class="stat-icon">
                                <i class="feather icon-check-circle"></i>
                            </div>
                            <div class="stat-content">
                                <h4>£{{ number_format($totalPaid, 2) }}</h4>
                                <p>Total Paid ({{ $paidCount }})</p>
                            </div>
                        </div>

                        @if($pendingCount > 0)
                            <div class="stat-item warning">
                                <div class="stat-icon">
                                    <i class="feather icon-clock"></i>
                                </div>
                                <div class="stat-content">
                                    <h4>{{ $pendingCount }}</h4>
                                    <p>Pending Payments</p>
                                </div>
                            </div>
                        @endif

                        @if($overdueCount > 0)
                            <div class="stat-item danger">
                                <div class="stat-icon">
                                    <i class="feather icon-alert-triangle"></i>
                                </div>
                                <div class="stat-content">
                                    <h4>{{ $overdueCount }}</h4>
                                    <p>Overdue Payments</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    @if($overdueCount > 0)
                        <div class="alert alert-danger mt-3">
                            <small>
                                <i class="feather icon-alert-circle me-1"></i>
                                You have {{ $overdueCount }} overdue payment(s). Please contact your fleet manager.
                            </small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Schedule -->
    <div class="row">
        <div class="col-12">
            <div class="card payments-schedule">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="feather icon-calendar me-2"></i>
                        Payment Schedule
                    </h5>
                    <span class="badge bg-info">{{ $agreement->collections->count() }} Collections</span>
                </div>
                <div class="card-body">
                    @if($agreement->collections->count() > 0)
                        <div class="payments-timeline">
                            @foreach($agreement->collections->sortBy('due_date') as $collection)
                                <div class="timeline-item {{ $collection->payment_status }}">
                                    <div class="timeline-marker">
                                        @if($collection->payment_status === 'paid')
                                            <i class="feather icon-check-circle"></i>
                                        @elseif($collection->payment_status === 'overdue')
                                            <i class="feather icon-alert-triangle"></i>
                                        @else
                                            <i class="feather icon-clock"></i>
                                        @endif
                                    </div>
                                    <div class="timeline-content">
                                        <div class="timeline-header">
                                            <h6 class="timeline-title">
                                                Collection #{{ $loop->iteration }}
                                                <span class="status-badge {{ $collection->payment_status }}">
                                                {{ ucfirst($collection->payment_status) }}
                                            </span>
                                            </h6>
                                            <span class="timeline-amount">£{{ number_format($collection->amount, 2) }}</span>
                                        </div>
                                        <div class="timeline-details">
                                            <div class="detail-item">
                                                <span class="detail-label">Due Date:</span>
                                                <span class="detail-value">{{ $collection->due_date->format('M j, Y') }}</span>
                                            </div>
                                            @if($collection->payment_date)
                                                <div class="detail-item">
                                                    <span class="detail-label">Paid Date:</span>
                                                    <span class="detail-value">{{ $collection->payment_date->format('M j, Y') }}</span>
                                                </div>
                                            @endif
                                            @if($collection->payment_status === 'overdue')
                                                <div class="detail-item">
                                                    <span class="detail-label">Days Overdue:</span>
                                                    <span class="detail-value text-danger">{{ $collection->days_overdue }}</span>
                                                </div>
                                            @endif
                                            <div class="detail-item">
                                                <span class="detail-label">Method:</span>
                                                <span class="detail-value">{{ ucfirst(str_replace('_', ' ', $collection->method)) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-payments">
                            <i class="feather icon-calendar fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Payment Schedule</h5>
                            <p class="text-muted">No payment collections have been set up for this agreement.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <style>
        .agreement-overview {
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .info-group {
            margin-bottom: 2rem;
        }

        .info-label {
            color: #374151;
            font-weight: 600;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .vehicle-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
        }

        .vehicle-reg {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .vehicle-model {
            margin-bottom: 0.25rem;
            opacity: 0.9;
        }

        .vehicle-color {
            margin-bottom: 0;
            opacity: 0.8;
            font-size: 0.9rem;
        }

        .company-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .date-range {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.5rem;
        }

        .date-item {
            flex: 1;
            text-align: center;
            padding: 1rem;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 10px;
        }

        .date-label {
            display: block;
            font-size: 0.8rem;
            color: #6b7280;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .date-value {
            display: block;
            font-weight: 600;
            color: #1f2937;
        }

        .date-separator {
            color: #6b7280;
        }

        .financial-grid {
            display: grid;
            gap: 1rem;
        }

        .financial-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: rgba(0,0,0,0.02);
            border-radius: 8px;
        }

        .financial-label {
            font-weight: 500;
            color: #6b7280;
        }

        .financial-value {
            font-weight: 700;
            color: #1f2937;
            font-size: 1.1rem;
        }

        .financial-value.primary {
            color: #059669;
            font-size: 1.25rem;
        }

        .financial-interval {
            color: #6b7280;
            font-size: 0.8rem;
            margin-left: 0.5rem;
        }

        .notes-content {
            background: rgba(0,0,0,0.02);
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .payment-summary-card {
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .summary-stats {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-radius: 12px;
            border-left: 4px solid;
        }

        .stat-item.success {
            background: rgba(16, 185, 129, 0.05);
            border-left-color: #10b981;
        }

        .stat-item.warning {
            background: rgba(245, 158, 11, 0.05);
            border-left-color: #f59e0b;
        }

        .stat-item.danger {
            background: rgba(239, 68, 68, 0.05);
            border-left-color: #ef4444;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .stat-item.success .stat-icon {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .stat-item.warning .stat-icon {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }

        .stat-item.danger .stat-icon {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .stat-content h4 {
            margin-bottom: 0.25rem;
            font-weight: 700;
        }

        .stat-content p {
            margin-bottom: 0;
            color: #6b7280;
            font-size: 0.9rem;
        }

        .payments-schedule {
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .payments-timeline {
            position: relative;
        }

        .payments-timeline::before {
            content: '';
            position: absolute;
            left: 20px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e5e7eb;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
            padding-left: 60px;
        }

        .timeline-marker {
            position: absolute;
            left: 0;
            top: 0;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            z-index: 1;
        }

        .timeline-item.paid .timeline-marker {
            background: #10b981;
            color: white;
        }

        .timeline-item.overdue .timeline-marker {
            background: #ef4444;
            color: white;
        }

        .timeline-item.pending .timeline-marker {
            background: #f59e0b;
            color: white;
        }

        .timeline-content {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .timeline-title {
            margin-bottom: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-badge.paid {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .status-badge.overdue {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .status-badge.pending {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }

        .timeline-amount {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2937;
        }

        .timeline-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .detail-label {
            font-weight: 500;
            color: #6b7280;
            font-size: 0.9rem;
        }

        .detail-value {
            font-weight: 600;
            color: #1f2937;
        }

        .empty-payments {
            text-align: center;
            padding: 3rem;
        }

        @media (max-width: 768px) {
            .date-range {
                flex-direction: column;
            }

            .timeline-details {
                grid-template-columns: 1fr;
            }

            .timeline-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }
    </style>
@endsection
