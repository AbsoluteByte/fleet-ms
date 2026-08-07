<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Invoice No</th>
            <th>Type</th>
            <th>Agreement</th>
            <th>Pays via</th>
            <th>Pay to</th>
            <th>Invoice Date</th>
            <th>Due Date</th>
            <th>Total</th>
            <th>Paid</th>
            <th>Balance</th>
            <th>Status</th>
            <th>Notes</th>
            @if($canManageInvoices ?? false)
                <th>Actions</th>
            @endif
        </tr>
        </thead>
        <tbody>
        @forelse($invoices as $invoice)
            <tr>
                <td><strong>{{ $invoice->invoice_no }}</strong></td>
                <td>{{ ucfirst(str_replace('_', ' ', $invoice->invoice_type)) }}</td>
                <td>
                    @if($agreementId = $invoice->linkedAgreementId())
                        <a href="{{ route('agreements.show', $agreementId) }}">Agreement ID #{{ $agreementId }}</a>
                    @else
                        —
                    @endif
                </td>
                <td>{{ $invoice->payingCompanyNameLabel() ?? '—' }}</td>
                <td>{{ $invoice->sourceAgreement?->paymentBankAccount?->paymentDisplayName() ?: '—' }}</td>
                <td>{{ optional($invoice->invoice_date)->format('d M Y') }}</td>
                <td>{{ optional($invoice->due_date)->format('d M Y') }}</td>
                <td>£{{ number_format($invoice->total_amount, 2) }}</td>
                <td>£{{ number_format($invoice->paid_amount, 2) }}</td>
                <td>£{{ number_format($invoice->balance_amount, 2) }}</td>
                <td>
                    @php
                        $statusClass = match($invoice->status) {
                            'paid' => 'badge-success',
                            'partial' => 'badge-info',
                            'overdue' => 'badge-danger',
                            default => 'badge-warning',
                        };
                    @endphp
                    <span class="badge {{ $statusClass }}">{{ ucfirst($invoice->status) }}</span>
                </td>
                <td>{{ $invoice->notes ?: '-' }}</td>
                @if($canManageInvoices ?? false)
                    <td class="text-nowrap">
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary js-edit-invoice"
                                data-invoice-id="{{ $invoice->id }}"
                                data-total-amount="{{ number_format((float) $invoice->total_amount, 2, '.', '') }}"
                                data-subtotal="{{ number_format((float) $invoice->subtotal, 2, '.', '') }}">
                            <i class="fa fa-pencil"></i>
                        </button>
                        @if($invoice->paymentAllocations->isEmpty())
                            <form action="{{ route('invoices.destroy', $invoice) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Delete this invoice?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </form>
                        @endif
                    </td>
                @endif
            </tr>
        @empty
            <tr>
                <td colspan="{{ ($canManageInvoices ?? false) ? 13 : 12 }}" class="text-center text-muted">No invoices found.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

@if($canManageInvoices ?? false)
    @include('backend.payments.partials.invoice-edit-modal')
@endif
