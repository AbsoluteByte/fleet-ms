@extends('layouts.admin', ['title' => 'Invoice'])
@section('content')
        <div class="content-body">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card">
                        <div class="card-body p-5">
                            {{-- Invoice Header --}}
                            <div class="row mb-4">
                                <div class="col-6">
                                    <h2>INVOICE</h2>
                                    <p class="mb-1"><strong>Invoice #:</strong> {{ $invoice->invoice_number }}</p>
                                    <p class="mb-1"><strong>Date:</strong> {{ $invoice->created_at->format('d M, Y') }}</p>
                                    @if($invoice->due_date)
                                        <p class="mb-1"><strong>Due Date:</strong> {{ $invoice->due_date->format('d M, Y') }}</p>
                                    @endif
                                </div>
                                <div class="col-6 text-right">
                                    <img src="{{ asset('app-assets/images/logo/app-logo.png') }}"
                                         alt="Logo" height="50">
                                    <p class="mt-3 mb-0">
                                        Fleet Management System<br>
                                        contact@example.com<br>
                                        +44 123 456 7890
                                    </p>
                                </div>
                            </div>

                            <hr>

                            {{-- Bill To --}}
                            <div class="row mb-4">
                                <div class="col-6">
                                    <h5>Bill To:</h5>
                                    <p class="mb-0">
                                        <strong>{{ $invoice->tenant->company_name }}</strong><br>
                                        {{ $invoice->tenant->users->first()->email ?? '' }}
                                    </p>
                                </div>
                                <div class="col-6">
                                    <h5>Status:</h5>
                                    <p>
                                        @if($invoice->isPaid())
                                            <span class="badge badge-success badge-lg">PAID</span>
                                            @if($invoice->paid_at)
                                                <br><small>Paid on {{ $invoice->paid_at->format('d M, Y') }}</small>
                                            @endif
                                        @else
                                            <span class="badge badge-warning badge-lg">{{ strtoupper($invoice->status) }}</span>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            {{-- Invoice Items --}}
                            <table class="table table-bordered">
                                <thead class="bg-light">
                                <tr>
                                    <th>Description</th>
                                    <th class="text-right">Amount</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td>
                                        @if($invoice->subscription)
                                            <strong>{{ $invoice->subscription->package->name }}</strong><br>
                                            <small class="text-muted">
                                                {{ ucfirst($invoice->subscription->package->billing_period) }} Subscription
                                            </small>
                                        @else
                                            Subscription Payment
                                        @endif
                                    </td>
                                    <td class="text-right">{{ $invoice->getFormattedAmount() }}</td>
                                </tr>
                                </tbody>
                                <tfoot>
                                @if($invoice->tax > 0)
                                    <tr>
                                        <th class="text-right">Subtotal:</th>
                                        <th class="text-right">{{ $invoice->getFormattedAmount() }}</th>
                                    </tr>
                                    <tr>
                                        <th class="text-right">Tax (20%):</th>
                                        <th class="text-right">£{{ number_format($invoice->tax, 2) }}</th>
                                    </tr>
                                @endif
                                <tr class="bg-light">
                                    <th class="text-right">Total:</th>
                                    <th class="text-right">{{ $invoice->getFormattedTotal() }}</th>
                                </tr>
                                </tfoot>
                            </table>

                            {{-- Footer --}}
                            <div class="text-center mt-5 pt-4 border-top">
                                <p class="text-muted mb-1">Thank you for your business!</p>
                                <p class="text-muted small">
                                    For questions about this invoice, contact us at support@example.com
                                </p>
                            </div>

                            {{-- Action Buttons --}}
                            <div class="text-center mt-4 no-print">
                                <button onclick="window.print()" class="btn btn-primary mr-2">
                                    <i class="fa fa-print"></i> Print Invoice
                                </button>
                                <a href="{{ route('admin.subscription.invoices') }}" class="btn btn-secondary">
                                    <i class="fa fa-arrow-left"></i> Back to Invoices
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
@endsection

@section('css')
    <style>
        @media print {
            .content-header,
            .no-print,
            .main-menu,
            .navbar,
            .footer {
                display: none !important;
            }

            .card {
                box-shadow: none;
                border: none;
            }
        }

        .badge-lg {
            font-size: 1.2rem;
            padding: 10px 20px;
        }
    </style>
@endsection
