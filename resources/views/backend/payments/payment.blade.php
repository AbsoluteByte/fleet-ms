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
                        @if(\App\Models\Payment::requiresBankAccount($payment->payment_method) && $payment->bankAccount)
                        <div class="col-md-4 mb-2">
                            <strong>Bank Account</strong>
                            <p>{{ $payment->bankAccount->bank_name }}</p>
                        </div>
                        @endif
                        @if(filled($payment->sourceAgreement?->paying_company_name))
                        <div class="col-md-4 mb-2">
                            <strong>Paying company</strong>
                            <p>{{ $payment->sourceAgreement->paying_company_name }}</p>
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
                            <div class="d-flex align-items-center justify-content-between">
                                <strong>Notes</strong>
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary edit-payment-notes-btn"
                                        data-payment-no="{{ $payment->payment_no }}"
                                        data-notes="{{ e($payment->notes ?? '') }}"
                                        data-update-url="{{ route('payments.notes.update', $payment) }}"
                                        data-redirect-to="{{ route('payments.show', $payment) }}">
                                    <i class="fa fa-edit"></i> {{ $payment->notes ? 'Edit Notes' : 'Add Notes' }}
                                </button>
                            </div>
                            <p class="mb-0 mt-50">{{ $payment->notes ?: '—' }}</p>
                        </div>
                    </div>

                    <h5>Allocations</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                            <tr>
                                <th>Invoice No</th>
                                <th>Pays via</th>
                                <th>Invoice Date</th>
                                <th>Due Date</th>
                                <th>Allocated Amount</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($payment->allocations as $allocation)
                                <tr>
                                    <td>{{ optional($allocation->invoice)->invoice_no }}</td>
                                    <td>{{ optional($allocation->invoice)->payingCompanyNameLabel() ?? '—' }}</td>
                                    <td>{{ optional(optional($allocation->invoice)->invoice_date)->format('d M Y') }}</td>
                                    <td>{{ optional(optional($allocation->invoice)->due_date)->format('d M Y') }}</td>
                                    <td>£{{ number_format($allocation->allocated_amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No invoice allocations. This amount is currently driver credit.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($canManagePayments ?? false)
                        <div class="d-flex flex-wrap" style="gap: 0.5rem;">
                            <a href="{{ route('payments.edit', $payment) }}" class="btn btn-outline-warning">
                                <i class="fa fa-edit"></i> Edit Payment
                            </a>
                            <form action="{{ route('payments.destroy', $payment) }}" method="POST" onsubmit="return confirm('Delete this payment? Invoice balances will be recalculated.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger">
                                    <i class="fa fa-trash"></i> Delete Payment
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('backend.payments.partials.notes-modal')
@endsection

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.edit-payment-notes-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    var form = document.getElementById('paymentNotesForm');
                    var notesInput = document.getElementById('paymentNotesInput');
                    var subtitle = document.getElementById('paymentNotesModalSubtitle');
                    var redirectInput = document.getElementById('paymentNotesRedirectTo');

                    form.action = button.getAttribute('data-update-url');
                    redirectInput.value = button.getAttribute('data-redirect-to') || '';
                    notesInput.value = button.getAttribute('data-notes') || '';
                    subtitle.textContent = button.getAttribute('data-payment-no') || 'Payment';

                    if (window.jQuery) {
                        window.jQuery('#paymentNotesModal').modal('show');
                    }
                });
            });
        });
    </script>
@endsection
