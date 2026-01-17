@extends('layouts.admin', ['title' => 'Invoices'])

@section('content')
    <div class="content-header row">
        <div class="content-header-left col-12 mb-2">
            <h2 class="content-header-title">
                <i class="fa fa-file-invoice"></i> Billing History
            </h2>
            <p class="text-muted">View your invoices</p>
        </div>
    </div>

    <div class="content-body">
        @include('alerts')

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        @if($invoices->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Invoice Number</th>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($invoices as $invoice)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <strong>{{ $invoice->invoice_number }}</strong>
                                            </td>
                                            <td>
                                                {{ $invoice->created_at->format('d M, Y') }}
                                            </td>
                                            <td>
                                                @if($invoice->subscription)
                                                    {{ $invoice->subscription->package->name }}
                                                    <br>
                                                    <small class="text-muted">
                                                        {{ ucfirst($invoice->subscription->package->billing_period) }}
                                                        Subscription
                                                    </small>
                                                @else
                                                    Subscription Payment
                                                @endif
                                            </td>
                                            <td>
                                                <strong class="text-primary">
                                                    £{{ number_format($invoice->total, 2) }}
                                                </strong>
                                                @if($invoice->tax > 0)
                                                    <br>
                                                    <small class="text-muted">
                                                        (incl. £{{ number_format($invoice->tax, 2) }} tax)
                                                    </small>
                                                @endif
                                            </td>
                                            <td>
                                                @if($invoice->status == 'paid')
                                                    <span class="badge badge-success">
                                                                <i class="fa fa-check-circle"></i> Paid
                                                            </span>
                                                    @if($invoice->paid_at)
                                                        <br>
                                                        <small class="text-muted">
                                                            {{ $invoice->paid_at->format('d M, Y') }}
                                                        </small>
                                                    @endif
                                                @elseif($invoice->status == 'pending')
                                                    <span class="badge badge-warning">
                                                                <i class="fa fa-clock"></i> Pending
                                                            </span>
                                                @elseif($invoice->status == 'failed')
                                                    <span class="badge badge-danger">
                                                                <i class="fa fa-times-circle"></i> Failed
                                                            </span>
                                                @else
                                                    <span class="badge badge-secondary">
                                                                {{ ucfirst($invoice->status) }}
                                                            </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>

                            {{-- Pagination --}}
                            <div class="mt-3">
                                {{ $invoices->links() }}
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fa fa-file-invoice fa-4x text-muted mb-3"></i>
                                <h4>No Invoices Yet</h4>
                                <p class="text-muted">Your billing history will appear here once you make your first
                                    payment</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Billing Summary --}}
        @if($invoices->count() > 0)
            <div class="row">
                <div class="col-lg-4 col-md-6">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6 class="mb-0">Total Paid</h6>
                            <h2 class="mb-0">
                                £{{ number_format($invoices->where('status', 'paid')->sum('total'), 2) }}
                            </h2>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h6 class="mb-0">Pending</h6>
                            <h2 class="mb-0">
                                £{{ number_format($invoices->where('status', 'pending')->sum('total'), 2) }}
                            </h2>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h6 class="mb-0">Total Invoices</h6>
                            <h2 class="mb-0">{{ $invoices->total() }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
