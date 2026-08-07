@extends('layouts.admin', ['title' => 'Driver Payments'])

@section('content')
    @php
        $creditAmount = $driver->credit_amount;
        $availableCredit = $creditPreview['available_credit'] ?? 0;
        $payingCompanyNames = $invoices
            ->map(fn ($invoice) => $invoice->payingCompanyNameLabel())
            ->filter()
            ->unique()
            ->values();
    @endphp

    <div class="row">
        <div class="col-12">
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-0">{{ $driver->selectOptionLabel() ?: 'Driver' }}</h4>
                        <small class="text-muted">{{ $driver->email }} {{ $driver->phone_number ? ' | '.$driver->phone_number : '' }}</small>
                        @if($payingCompanyNames->isNotEmpty())
                            <small class="text-muted d-block">Pays via: {{ $payingCompanyNames->implode(', ') }}</small>
                        @endif
                    </div>
                    <div>
                        @if($availableCredit > 0 && ($creditPreview['outstanding'] ?? 0) <= 0)
                            <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#refundDriverCreditModal">
                                <i class="fa fa-undo"></i> Refund Credit
                            </button>
                        @elseif($availableCredit > 0 && ($creditPreview['outstanding'] ?? 0) > 0)
                            <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#applyDriverCreditModal">
                                <i class="fa fa-check-circle"></i> Apply Credit to Invoices
                            </button>
                        @endif
                        <a href="{{ route('payments.create', ['driver_id' => $driver->id]) }}" class="btn btn-primary btn-sm">
                            <i class="fa fa-plus"></i> Add Payment
                        </a>
                        <a href="{{ route('payments.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                <div class="card-body" style="margin-top: 85px;">
                @include('alerts')
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
                                <span>Total Paid (Posted)</span>
                                <strong>£{{ number_format($summary['total_paid'], 2) }}</strong>
                            </div>
                        </div>
                    </div>
                    @if(($summary['total_pending'] ?? 0) > 0)
                        <div class="alert alert-info mt-1">
                            £{{ number_format($summary['total_pending'], 2) }} pending daily financial sheet approval.
                        </div>
                    @endif
                    @if($driver->reserved_credit_amount > 0)
                        <div class="alert alert-warning mt-1">
                            £{{ number_format($driver->reserved_credit_amount, 2) }} credit is reserved and pending daily financial sheet approval.
                        </div>
                    @endif

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
                            @include('backend.payments.partials.invoices-table', ['invoices' => $invoices, 'canManageInvoices' => $canManageInvoices ?? false])
                        </div>

                        <div class="tab-pane" id="active-invoices" role="tabpanel">
                            <h5>Active Balance: £{{ number_format($activeInvoices->sum('balance_amount'), 2) }}</h5>
                            @include('backend.payments.partials.invoices-table', ['invoices' => $activeInvoices, 'canManageInvoices' => $canManageInvoices ?? false])
                        </div>

                        <div class="tab-pane" id="due-invoices" role="tabpanel">
                            <h5>Overdue Balance: £{{ number_format($dueInvoices->sum('balance_amount'), 2) }}</h5>
                            @include('backend.payments.partials.invoices-table', ['invoices' => $dueInvoices, 'canManageInvoices' => $canManageInvoices ?? false])
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
                                            <td>
                                                <strong>{{ $payment->payment_no }}</strong>
                                                @if($payment->isPending())
                                                    <span class="badge badge-warning ml-50">Pending approval</span>
                                                @endif
                                            </td>
                                            <td>{{ optional($payment->payment_date)->format('d M Y') }}</td>
                                            <td>
                                                {{ $payment->payment_method }}
                                                @if(\App\Models\Payment::requiresBankAccount($payment->payment_method) && $payment->bankAccount)
                                                    <div class="text-muted small">{{ $payment->bankAccount->bank_name }}</div>
                                                @endif
                                            </td>
                                            <td>£{{ number_format($payment->amount, 2) }}</td>
                                            <td>£{{ number_format($payment->allocated_amount, 2) }}</td>
                                            <td>£{{ number_format($payment->unallocated_amount, 2) }}</td>
                                            <td>
                                                <span class="payment-notes-text">{{ $payment->notes ?: '—' }}</span>
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-secondary ml-50 edit-payment-notes-btn"
                                                        title="{{ $payment->notes ? 'Edit notes' : 'Add notes' }}"
                                                        data-payment-no="{{ $payment->payment_no }}"
                                                        data-notes="{{ e($payment->notes ?? '') }}"
                                                        data-update-url="{{ route('payments.notes.update', $payment) }}"
                                                        data-redirect-to="{{ route('payments.driver', $driver).'#payments' }}">
                                                    <i class="fa fa-edit"></i>
                                                </button>
                                            </td>
                                            <td>
                                                <a href="{{ route('payments.show', $payment) }}" class="btn btn-sm btn-outline-info" title="View payment">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                @if($canManagePayments ?? false)
                                                    <a href="{{ route('payments.edit', $payment) }}" class="btn btn-sm btn-outline-warning" title="Edit payment">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('payments.destroy', $payment) }}" method="POST" style="display: inline;" onsubmit="return confirm('Delete this payment? Invoice balances will be recalculated.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete payment">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
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

    @include('backend.payments.partials.notes-modal')

    <div class="modal fade" id="refundDriverCreditModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" action="{{ route('payments.credit.refund', $driver) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Refund Driver Credit</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            The full available credit will remain reserved until Daily Financial Sheet approval.
                        </div>
                        <div class="form-group">
                            <label>Refund Amount</label>
                            <input type="text" class="form-control" value="£{{ number_format($availableCredit, 2) }}" readonly>
                        </div>
                        <div class="form-group">
                            <label for="credit_refund_method">Payment Method <span class="text-danger">*</span></label>
                            <select name="payment_method" id="credit_refund_method" class="form-control" required>
                                <option value="">Select method</option>
                                @foreach(['Cash', 'Bank Transfer', 'Cheque', 'Card Payment', 'Direct Debit'] as $method)
                                    <option value="{{ $method }}">{{ $method }}</option>
                                @endforeach
                            </select>
                        </div>
                        @include('backend.payments.partials.bank-account-select', [
                            'bankAccounts' => $bankAccounts,
                            'name' => 'bank_account_id',
                            'id' => 'credit_refund_bank_account_id',
                            'wrapperClass' => 'credit-refund-bank-field d-none form-group',
                        ])
                        <div class="form-group">
                            <label for="credit_refund_date">Refund Date <span class="text-danger">*</span></label>
                            <input type="date" name="request_date" id="credit_refund_date" class="form-control"
                                   value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="form-group">
                            <label for="credit_refund_notes">Notes</label>
                            <textarea name="notes" id="credit_refund_notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Submit for Approval</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="applyDriverCreditModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" action="{{ route('payments.credit.apply', $driver) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Apply Credit to Invoices</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="credit-application-preview">
                            <div><span>Available credit</span><strong>£{{ number_format($availableCredit, 2) }}</strong></div>
                            <div><span>Outstanding invoices</span><strong>£{{ number_format($creditPreview['outstanding'], 2) }}</strong></div>
                            <div class="credit-application-preview__total">
                                <span>Apply oldest-first</span><strong>£{{ number_format($creditPreview['application_amount'], 2) }}</strong>
                            </div>
                            <div><span>Remaining credit</span><strong>£{{ number_format($creditPreview['remaining_credit'], 2) }}</strong></div>
                            <div><span>Remaining debt</span><strong>£{{ number_format($creditPreview['remaining_debt'], 2) }}</strong></div>
                        </div>
                        <p class="text-muted mt-1">Invoices will update only after Daily Financial Sheet approval.</p>
                        <div class="form-group">
                            <label for="credit_application_date">Application Date <span class="text-danger">*</span></label>
                            <input type="date" name="request_date" id="credit_application_date" class="form-control"
                                   value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="form-group">
                            <label for="credit_application_notes">Notes</label>
                            <textarea name="notes" id="credit_application_notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Submit for Approval</button>
                    </div>
                </form>
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

        .credit-application-preview {
            padding: 12px 14px;
            border: 1px solid rgba(115, 103, 240, .2);
            border-radius: 8px;
            background: rgba(115, 103, 240, .05);
        }

        .credit-application-preview > div {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
        }

        .credit-application-preview__total {
            margin: 5px 0;
            padding: 9px 0 !important;
            color: #7367f0;
            border-top: 1px solid rgba(115, 103, 240, .2);
            border-bottom: 1px solid rgba(115, 103, 240, .2);
        }
    </style>
@endsection

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function activateTabFromHash() {
                const hash = window.location.hash;
                if (!hash) {
                    return;
                }

                const tabLink = document.querySelector('a[data-toggle="tab"][href="' + hash + '"]');
                if (tabLink && window.jQuery && window.jQuery.fn.tab) {
                    window.jQuery(tabLink).tab('show');
                }
            }

            activateTabFromHash();
            window.addEventListener('hashchange', activateTabFromHash);

            var refundMethod = document.getElementById('credit_refund_method');
            var refundBankField = document.querySelector('.credit-refund-bank-field');
            var refundBankSelect = refundBankField ? refundBankField.querySelector('select') : null;
            function toggleCreditRefundBank() {
                var needsBank = refundMethod && (refundMethod.value === 'Bank Transfer' || refundMethod.value === 'Card Payment');
                if (refundBankField) {
                    refundBankField.classList.toggle('d-none', !needsBank);
                }
                if (refundBankSelect) {
                    refundBankSelect.required = !!needsBank;
                    if (!needsBank) {
                        refundBankSelect.value = '';
                    }
                }
            }
            if (refundMethod) {
                refundMethod.addEventListener('change', toggleCreditRefundBank);
                toggleCreditRefundBank();
            }

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
