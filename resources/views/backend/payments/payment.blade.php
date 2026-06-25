@extends('layouts.admin', ['title' => 'Payment Details'])

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">{{ $payment->payment_no }}</h4>
                    <div>
                        <a href="{{ route('payments.driver', $payment->driver) }}" class="btn btn-secondary btn-sm">
                            <i class="fa fa-arrow-left"></i> Driver Payments
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <strong>Driver</strong>
                            <p>{{ optional($payment->driver)->full_name }}</p>
                        </div>
                        <div class="col-md-4 mb-2">
                            <strong>Payment Date</strong>
                            <p>{{ optional($payment->payment_date)->format('d M Y') }}</p>
                        </div>
                        <div class="col-md-4 mb-2">
                            <strong>Payment Method</strong>
                            <p>{{ $payment->payment_method }}</p>
                        </div>
                        @if($payment->payment_method === 'Bank Transfer' && $payment->bankAccount)
                        <div class="col-md-4 mb-2">
                            <strong>Bank Account</strong>
                            <p>{{ $payment->bankAccount->bank_name }}</p>
                        </div>
                        @endif
                        <div class="col-md-4 mb-2">
                            <strong>Amount</strong>
                            <p>£{{ number_format($payment->amount, 2) }}</p>
                        </div>
                        <div class="col-md-4 mb-2">
                            <strong>Allocated</strong>
                            <p>£{{ number_format($payment->allocated_amount, 2) }}</p>
                        </div>
                        <div class="col-md-4 mb-2">
                            <strong>Credit</strong>
                            <p>£{{ number_format($payment->unallocated_amount, 2) }}</p>
                        </div>
                        <div class="col-12 mb-2">
                            <strong>Notes</strong>
                            <p>{{ $payment->notes ?: '-' }}</p>
                        </div>
                    </div>

                    <h5>Allocations</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                            <tr>
                                <th>Invoice No</th>
                                <th>Invoice Date</th>
                                <th>Due Date</th>
                                <th>Allocated Amount</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($payment->allocations as $allocation)
                                <tr>
                                    <td>{{ optional($allocation->invoice)->invoice_no }}</td>
                                    <td>{{ optional(optional($allocation->invoice)->invoice_date)->format('d M Y') }}</td>
                                    <td>{{ optional(optional($allocation->invoice)->due_date)->format('d M Y') }}</td>
                                    <td>£{{ number_format($allocation->allocated_amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No invoice allocations. This amount is currently driver credit.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <form action="{{ route('payments.destroy', $payment) }}" method="POST" onsubmit="return confirm('Delete this payment? Invoice balances will be recalculated.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="fa fa-trash"></i> Delete Payment
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
