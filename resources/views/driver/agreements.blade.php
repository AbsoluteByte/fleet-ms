@extends('layouts.admin', ['title' => 'My Agreements'])
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2">
                    <i class="feather icon-file-text me-2"></i>
                    My Agreements
                </h1>
                <div class="d-flex gap-2">
                    <span class="badge bg-primary">{{ $agreements->total() }} Total</span>
                    <span class="badge bg-success">{{ $agreements->where('status.name', 'Active')->count() }} Active</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Agreements Grid -->
    <div class="row g-4">
        @forelse($agreements as $agreement)
            <div class="col-lg-6 col-xl-4">
                <div class="agreement-card">
                    <div class="agreement-header">
                        <div class="vehicle-info">
                            <h5 class="vehicle-registration">{{ $agreement->car->registration }}</h5>
                            <p class="vehicle-model">{{ $agreement->car->carModel->name }}</p>
                        </div>
                        <div class="status-badge">
                        <span class="badge" style="background-color: {{ $agreement->status->color }}">
                            {{ $agreement->status->name }}
                        </span>
                        </div>
                    </div>

                    <div class="agreement-body">
                        <div class="company-info">
                            <i class="feather icon-briefcase me-2"></i>
                            <span>{{ $agreement->company->name }}</span>
                        </div>

                        <div class="agreement-dates">
                            <div class="date-item">
                                <small class="text-muted">Start Date</small>
                                <p class="mb-0">{{ $agreement->start_date->format('M d, Y') }}</p>
                            </div>
                            <div class="date-item">
                                <small class="text-muted">End Date</small>
                                <p class="mb-0">{{ $agreement->end_date->format('M d, Y') }}</p>
                            </div>
                        </div>

                        <div class="financial-info">
                            <div class="rent-amount">
                                <span class="amount">£{{ number_format($agreement->agreed_rent, 2) }}</span>
                                <small class="interval">{{ $agreement->rent_interval }}</small>
                            </div>
                            <div class="deposit-amount">
                                <small class="text-muted">Deposit: £{{ number_format($agreement->deposit_amount, 2) }}</small>
                            </div>
                        </div>

                        <!-- Payment Summary -->
                        @if($agreement->collections->count() > 0)
                            <div class="payment-summary">
                                <div class="summary-item">
                                    <span class="summary-label">Total Collections</span>
                                    <span class="summary-value">{{ $agreement->collections->count() }}</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Paid</span>
                                    <span class="summary-value text-success">{{ $agreement->collections->where('payment_status', 'paid')->count() }}</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Pending</span>
                                    <span class="summary-value text-warning">{{ $agreement->collections->where('payment_status', 'pending')->count() }}</span>
                                </div>
                                @if($agreement->collections->where('payment_status', 'overdue')->count() > 0)
                                    <div class="summary-item">
                                        <span class="summary-label">Overdue</span>
                                        <span class="summary-value text-danger">{{ $agreement->collections->where('payment_status', 'overdue')->count() }}</span>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="agreement-footer">
                        <a href="{{ route('driver.agreements.show', $agreement) }}" class="btn btn-primary btn-sm">
                            <i class="feather icon-eye me-1"></i>
                            View Details
                        </a>
                        @if($agreement->collections->whereIn('payment_status', ['pending', 'overdue'])->count() > 0)
                            <button class="btn btn-outline-warning btn-sm" onclick="showPaymentAlert()">
                                <i class="feather icon-alert-circle me-1"></i>
                                {{ $agreement->collections->whereIn('payment_status', ['pending', 'overdue'])->count() }} Due
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="empty-state">
                    <i class="feather icon-file-text fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">No Agreements Found</h4>
                    <p class="text-muted">You don't have any agreements yet. Contact your fleet manager for more information.</p>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($agreements->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $agreements->links() }}
        </div>
    @endif
@endsection

@section('css')
    <style>
        .agreement-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .agreement-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .agreement-header {
            padding: 1.5rem 1.5rem 1rem;
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .vehicle-registration {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .vehicle-model {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 0;
        }

        .agreement-body {
            padding: 1.5rem;
        }

        .company-info {
            display: flex;
            align-items: center;
            color: #4b5563;
            font-weight: 500;
            margin-bottom: 1.5rem;
            padding: 0.75rem;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 10px;
        }

        .agreement-dates {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: rgba(0,0,0,0.02);
            border-radius: 10px;
        }

        .date-item small {
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.75rem;
        }

        .date-item p {
            font-weight: 600;
            color: #1f2937;
            font-size: 0.95rem;
        }

        .financial-info {
            margin-bottom: 1.5rem;
        }

        .rent-amount {
            display: flex;
            align-items: baseline;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .amount {
            font-size: 1.5rem;
            font-weight: 700;
            color: #059669;
        }

        .interval {
            color: #6b7280;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.8rem;
        }

        .deposit-amount {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .payment-summary {
            background: rgba(0,0,0,0.02);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .summary-item:last-child {
            margin-bottom: 0;
        }

        .summary-label {
            font-size: 0.85rem;
            color: #6b7280;
            font-weight: 500;
        }

        .summary-value {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .agreement-footer {
            padding: 1rem 1.5rem;
            background: rgba(0,0,0,0.02);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.5rem;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        @media (max-width: 768px) {
            .agreement-dates {
                grid-template-columns: 1fr;
            }

            .agreement-footer {
                flex-direction: column;
                gap: 1rem;
            }

            .agreement-footer .btn {
                width: 100%;
            }
        }
    </style>
@endsection

@section('js')
    <script>
        function showPaymentAlert() {
            alert('You have pending payments. Please check the agreement details or contact your fleet manager.');
        }
    </script>
@endsection
