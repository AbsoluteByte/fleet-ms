@extends('layouts.admin', ['title' => 'Driver Payments'])

@section('content')
    @php
        $creditAmount = max(($summary['total_paid'] ?? 0) - ($summary['total_allocated'] ?? 0), 0);
    @endphp

    <div class="row">
        <div class="col-12">
            @include('alerts')
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-0">{{ $driver->full_name ?: 'Driver' }}</h4>
                        <small class="text-muted">{{ $driver->email }} {{ $driver->phone_number ? ' | '.$driver->phone_number : '' }}</small>
                    </div>
                    <div>
                        <a href="{{ route('payments.create', ['driver_id' => $driver->id]) }}" class="btn btn-primary btn-sm">
                            <i class="fa fa-plus"></i> Add Payment
                        </a>
                        <a href="{{ route('payments.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-1">
                            <div class="payment-summary-card border-danger">
                                <span>Total Due</span>
                                <strong>£{{ number_format($summary['total_due'], 2) }}</strong>
                            </div>
                        </div>
                        <div class="col-md-3 mb-1">
                            <div class="payment-summary-card border-warning">
                                <span>Overdue</span>
                                <strong>£{{ number_format($summary['overdue_due'], 2) }}</strong>
                            </div>
                        </div>
                        <div class="col-md-3 mb-1">
                            <div class="payment-summary-card border-success">
                                <span>Driver Credit</span>
                                <strong>£{{ number_format($creditAmount, 2) }}</strong>
                            </div>
                        </div>
                        <div class="col-md-3 mb-1">
                            <div class="payment-summary-card border-primary">
                                <span>Total Paid</span>
                                <strong>£{{ number_format($summary['total_paid'], 2) }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="alert {{ $summary['total_due'] > 0 ? 'alert-warning' : ($creditAmount > 0 ? 'alert-success' : 'alert-info') }} mb-0">
                        @if($summary['total_due'] > 0)
                            Driver has £{{ number_format($summary['total_due'], 2) }} outstanding.
                        @elseif($creditAmount > 0)
                            Driver has £{{ number_format($creditAmount, 2) }} credit with the company.
                        @else
                            Driver balance is clear.
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#all-invoices" role="tab">Invoices</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#active-invoices" role="tab">Active Invoices</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#due-invoices" role="tab">Due Invoices</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#payments" role="tab">Payments</a>
                        </li>
                    </ul>

                    <div class="tab-content pt-2">
                        <div class="tab-pane active" id="all-invoices" role="tabpanel">
                            <h5>Total Invoices: £{{ number_format($invoices->sum('total_amount'), 2) }}</h5>
                            @include('backend.payments.partials.invoices-table', ['invoices' => $invoices])
                        </div>

                        <div class="tab-pane" id="active-invoices" role="tabpanel">
                            <h5>Active Balance: £{{ number_format($activeInvoices->sum('balance_amount'), 2) }}</h5>
                            @include('backend.payments.partials.invoices-table', ['invoices' => $activeInvoices])
                        </div>

                        <div class="tab-pane" id="due-invoices" role="tabpanel">
                            <h5>Overdue Balance: £{{ number_format($dueInvoices->sum('balance_amount'), 2) }}</h5>
                            @include('backend.payments.partials.invoices-table', ['invoices' => $dueInvoices])
                        </div>

                        <div class="tab-pane" id="payments" role="tabpanel">
                            <h5>Total Payments: £{{ number_format($payments->sum('amount'), 2) }}</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th>Payment No</th>
                                        <th>Date</th>
                                        <th>Method</th>
                                        <th>Amount</th>
                                        <th>Allocated</th>
                                        <th>Credit</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($payments as $payment)
                                        <tr>
                                            <td><strong>{{ $payment->payment_no }}</strong></td>
                                            <td>{{ optional($payment->payment_date)->format('d M Y') }}</td>
                                            <td>{{ $payment->payment_method }}</td>
                                            <td>£{{ number_format($payment->amount, 2) }}</td>
                                            <td>£{{ number_format($payment->allocated_amount, 2) }}</td>
                                            <td>£{{ number_format($payment->unallocated_amount, 2) }}</td>
                                            <td>{{ $payment->notes ?: '-' }}</td>
                                            <td>
                                                <a href="{{ route('payments.show', $payment) }}" class="btn btn-sm btn-outline-info">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">No payments found.</td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <style>
        .payment-summary-card {
            border-left: 4px solid #7367f0;
            border-radius: .25rem;
            padding: 1rem;
            background: #fff;
            box-shadow: 0 2px 8px rgba(34, 41, 47, .08);
        }

        .payment-summary-card span {
            display: block;
            color: #6e6b7b;
            font-size: .85rem;
        }

        .payment-summary-card strong {
            display: block;
            margin-top: .25rem;
            font-size: 1.25rem;
        }
    </style>
@endsection
