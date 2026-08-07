<div class="col-12 mt-4">
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between"
             style="position: static; width: 100%; z-index: unset;">
            <h5 class="card-title mb-0">
                <i class="fa fa-file-text-o me-2"></i>
                Agreement Invoices
            </h5>
            <div class="text-right">
                @if($agreement->paying_company_name)
                    <small class="text-muted d-block">Pays via: {{ $agreement->paying_company_name }}</small>
                @endif
                <span class="badge badge-light-primary">{{ $agreement->invoices->count() }} invoices</span>
            </div>
        </div>
        <div class="card-body p-0" style="margin-top: 0;">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                    <tr>
                        <th>Invoice No</th>
                        <th>Type</th>
                        <th>Invoice Date</th>
                        <th>Due Date</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th class="text-right">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($agreement->invoices as $invoice)
                        @php
                            $invoiceStatusClass = match($invoice->status) {
                                'paid' => 'badge-success',
                                'partial' => 'badge-info',
                                'overdue' => 'badge-danger',
                                default => 'badge-warning',
                            };
                            $invoiceTypeLabel = match($invoice->invoice_type) {
                                'agreement_deposit' => 'Deposit',
                                'agreement_additional_charge' => 'Damage',
                                default => 'Rent',
                            };
                        @endphp
                        <tr>
                            <td><strong>{{ $invoice->invoice_no }}</strong></td>
                            <td>{{ $invoiceTypeLabel }}</td>
                            <td>{{ optional($invoice->invoice_date)->format('d M Y') ?: '—' }}</td>
                            <td>{{ optional($invoice->due_date)->format('d M Y') ?: '—' }}</td>
                            <td>£{{ number_format((float) $invoice->total_amount, 2) }}</td>
                            <td class="text-success">£{{ number_format((float) $invoice->paid_amount, 2) }}</td>
                            <td class="{{ (float) $invoice->balance_amount > 0 ? 'text-danger' : 'text-success' }}">
                                £{{ number_format((float) $invoice->balance_amount, 2) }}
                            </td>
                            <td>
                                <span class="badge {{ $invoiceStatusClass }}">{{ ucfirst($invoice->status) }}</span>
                            </td>
                            <td>{{ $invoice->notes ?: '—' }}</td>
                            <td class="text-right text-nowrap">
                                @if($canManageInvoices ?? false)
                                    <button type="button"
                                            class="btn btn-sm btn-outline-secondary js-edit-invoice"
                                            data-invoice-id="{{ $invoice->id }}"
                                            data-total-amount="{{ number_format((float) $invoice->total_amount, 2, '.', '') }}"
                                            data-subtotal="{{ number_format((float) $invoice->subtotal, 2, '.', '') }}"
                                            title="Edit invoice">
                                        <i class="fa fa-pencil"></i>
                                    </button>
                                    @if($invoice->paymentAllocations->isEmpty())
                                        <form action="{{ route('invoices.destroy', $invoice) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete this invoice?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete invoice">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                @endif
                                @if((float) $invoice->balance_amount > 0)
                                    <a href="{{ route('payments.create', ['driver_id' => $agreement->driver_id]) }}"
                                       class="btn btn-sm btn-primary">
                                        <i class="fa fa-plus me-1"></i> Add Payment
                                    </a>
                                @elseif(! ($canManageInvoices ?? false))
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4">
                                <i class="fa fa-file-text-o d-block mb-2 text-muted" style="font-size: 24px;"></i>
                                <span class="text-muted">No invoices found for this agreement.</span>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@if($canManageInvoices ?? false)
    @include('backend.payments.partials.invoice-edit-modal')
@endif
