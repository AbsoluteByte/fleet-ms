@extends('layouts.admin', ['title' => 'My Payments'])

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2">
                    <i class="feather icon-credit-card me-2"></i>
                    My Payments
                </h1>
                <div class="payment-stats">
                    @php
                        $totalPaid = $payments->where('payment_status', 'paid')->count();
                        $totalPending = $payments->where('payment_status', 'pending')->count();
                        $totalOverdue = $payments->where('payment_status', 'overdue')->count();
                    @endphp
                    <span class="badge bg-success me-1">{{ $totalPaid }} Paid</span>
                    @if($totalPending > 0)
                        <span class="badge bg-warning me-1">{{ $totalPending }} Pending</span>
                    @endif
                    @if($totalOverdue > 0)
                        <span class="badge bg-danger">{{ $totalOverdue }} Overdue</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="payment-filters">
                <button class="filter-btn active" data-filter="all">
                    <i class="feather icon-list me-1"></i>
                    All Payments
                </button>
                <button class="filter-btn" data-filter="paid">
                    <i class="feather icon-check-circle me-1"></i>
                    Paid
                </button>
                <button class="filter-btn" data-filter="pending">
                    <i class="feather icon-clock me-1"></i>
                    Pending
                </button>
                <button class="filter-btn" data-filter="overdue">
                    <i class="feather icon-alert-triangle me-1"></i>
                    Overdue
                </button>
            </div>
        </div>
    </div>

    <!-- Payments List -->
    <div class="row">
        <div class="col-12">
            <div class="payments-container">
                @forelse($payments as $payment)
                    <div class="payment-card {{ $payment->payment_status }}" data-status="{{ $payment->payment_status }}">
                        <div class="payment-header">
                            <div class="payment-info">
                                <div class="vehicle-badge">
                                    <i class="feather icon-truck"></i>
                                    <span>{{ $payment->agreement->car->registration }}</span>
                                </div>
                                <div class="payment-date">
                                    <span class="date-label">Due Date</span>
                                    <span class="date-value">{{ $payment->due_date->format('M j, Y') }}</span>
                                </div>
                            </div>
                            <div class="payment-status">
                            <span class="status-badge {{ $payment->payment_status }}">
                                @if($payment->payment_status === 'paid')
                                    <i class="feather icon-check-circle me-1"></i>
                                @elseif($payment->payment_status === 'overdue')
                                    <i class="feather icon-alert-triangle me-1"></i>
                                @else
                                    <i class="feather icon-clock me-1"></i>
                                @endif
                                {{ ucfirst($payment->payment_status) }}
                            </span>
                            </div>
                        </div>

                        <div class="payment-body">
                            <div class="payment-amount-section">
                                <div class="amount-info">
                                    <span class="amount-label">Payment Amount</span>
                                    <span class="amount-value">£{{ number_format($payment->amount, 2) }}</span>
                                </div>

                                @if($payment->payment_status === 'paid')
                                    <div class="paid-info">
                                        <span class="paid-label">Paid Amount</span>
                                        <span class="paid-value">£{{ number_format($payment->amount_paid, 2) }}</span>
                                        @if($payment->payment_date)
                                            <small class="paid-date">on {{ $payment->payment_date->format('M j, Y') }}</small>
                                        @endif
                                    </div>
                                @elseif($payment->payment_status === 'overdue')
                                    <div class="overdue-info">
                                        <span class="overdue-label">Days Overdue</span>
                                        <span class="overdue-value">{{ $payment->days_overdue }} days</span>
                                    </div>
                                @endif
                            </div>

                            <div class="payment-details">
                                <div class="detail-row">
                                    <span class="detail-label">Vehicle:</span>
                                    <span class="detail-value">{{ $payment->agreement->car->registration }} - {{ $payment->agreement->car->carModel->name }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Payment Method:</span>
                                    <span class="detail-value">{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Collection Date:</span>
                                    <span class="detail-value">{{ $payment->date->format('M j, Y') }}</span>
                                </div>
                                @if($payment->notes)
                                    <div class="detail-row">
                                        <span class="detail-label">Notes:</span>
                                        <span class="detail-value">{{ $payment->notes }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if($payment->payment_status === 'overdue')
                            <div class="payment-footer overdue-footer">
                                <div class="alert alert-danger mb-0">
                                    <i class="feather icon-alert-circle me-2"></i>
                                    <strong>Action Required:</strong> This payment is overdue. Please contact your fleet manager immediately.
                                </div>
                            </div>
                        @elseif($payment->payment_status === 'pending')
                            <div class="payment-footer pending-footer">
                                <div class="alert alert-warning mb-0">
                                    <i class="feather icon-clock me-2"></i>
                                    <strong>Upcoming Payment:</strong> Due {{ $payment->due_date->diffForHumans() }}
                                </div>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="empty-payments">
                        <i class="feather icon-credit-card fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">No Payments Found</h4>
                        <p class="text-muted">You don't have any payment records yet.</p>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if($payments->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $payments->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Payment Summary Modal -->
    <div class="modal fade" id="paymentSummaryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Payment Summary</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @php
                        $totalAmount = $payments->sum('amount');
                        $totalPaidAmount = $payments->where('payment_status', 'paid')->sum('amount_paid');
                        $totalPendingAmount = $payments->whereIn('payment_status', ['pending', 'overdue'])->sum('amount');
                    @endphp

                    <div class="summary-grid">
                        <div class="summary-card">
                            <h3 class="summary-value text-primary">£{{ number_format($totalAmount, 2) }}</h3>
                            <p class="summary-label">Total Amount</p>
                        </div>
                        <div class="summary-card">
                            <h3 class="summary-value text-success">£{{ number_format($totalPaidAmount, 2) }}</h3>
                            <p class="summary-label">Amount Paid</p>
                        </div>
                        <div class="summary-card">
                            <h3 class="summary-value text-warning">£{{ number_format($totalPendingAmount, 2) }}</h3>
                            <p class="summary-label">Amount Pending</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <style>
        .payment-filters {
            background: white;
            padding: 1rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            background: transparent;
            border: 2px solid #e5e7eb;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            color: #6b7280;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .filter-btn:hover, .filter-btn.active {
            background: #667eea;
            border-color: #667eea;
            color: white;
            transform: translateY(-2px);
        }

        .payments-container {
            display: grid;
            gap: 1.5rem;
        }

        .payment-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            overflow: hidden;
            border-left: 5px solid;
        }

        .payment-card.paid {
            border-left-color: #10b981;
        }

        .payment-card.pending {
            border-left-color: #f59e0b;
        }

        .payment-card.overdue {
            border-left-color: #ef4444;
        }

        .payment-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .payment-header {
            padding: 1.5rem 1.5rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            background: rgba(0,0,0,0.01);
        }

        .payment-info {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .vehicle-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            width: fit-content;
        }

        .payment-date {
            display: flex;
            flex-direction: column;
        }

        .date-label {
            font-size: 0.8rem;
            color: #6b7280;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .date-value {
            font-weight: 600;
            color: #1f2937;
            font-size: 1rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            width: fit-content;
        }

        .status-badge.paid {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .status-badge.pending {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }

        .status-badge.overdue {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .payment-body {
            padding: 0 1.5rem 1.5rem;
        }

        .payment-amount-section {
            background: rgba(0,0,0,0.02);
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .amount-info, .paid-info, .overdue-info {
            display: flex;
            flex-direction: column;
        }

        .amount-label, .paid-label, .overdue-label {
            font-size: 0.8rem;
            color: #6b7280;
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .amount-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1f2937;
        }

        .paid-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: #10b981;
        }

        .overdue-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: #ef4444;
        }

        .paid-date {
            color: #6b7280;
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }

        .payment-details {
            display: grid;
            gap: 0.75rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 500;
            color: #6b7280;
            font-size: 0.9rem;
        }

        .detail-value {
            font-weight: 600;
            color: #1f2937;
            text-align: right;
            max-width: 60%;
        }

        .payment-footer {
            padding: 1rem 1.5rem;
            background: rgba(0,0,0,0.02);
        }

        .empty-payments {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .summary-card {
            text-align: center;
            padding: 1.5rem;
            background: rgba(0,0,0,0.02);
            border-radius: 12px;
        }

        .summary-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .summary-label {
            color: #6b7280;
            font-weight: 500;
            margin-bottom: 0;
        }

        @media (max-width: 768px) {
            .payment-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .payment-amount-section {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .detail-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.25rem;
            }

            .detail-value {
                text-align: left;
                max-width: 100%;
            }

            .filter-btn {
                flex: 1;
                justify-content: center;
            }
        }

        /* Hide/Show based on filter */
        .payment-card.hidden {
            display: none;
        }
    </style>
@endsection

@section('js')
    <script>
        // Payment filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('.filter-btn');
            const paymentCards = document.querySelectorAll('.payment-card');

            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const filter = this.dataset.filter;

                    // Update active button
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');

                    // Filter payment cards
                    paymentCards.forEach(card => {
                        if (filter === 'all' || card.dataset.status === filter) {
                            card.classList.remove('hidden');
                        } else {
                            card.classList.add('hidden');
                        }
                    });
                });
            });
        });

        // Show payment summary modal
        function showPaymentSummary() {
            new bootstrap.Modal(document.getElementById('paymentSummaryModal')).show();
        }
    </script>
@endsection
