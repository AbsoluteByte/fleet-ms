<div class="table-responsive">
    <table class="table table-bordered table-striped">
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
        </tr>
        </thead>
        <tbody>
        @forelse($invoices as $invoice)
            <tr>
                <td><strong>{{ $invoice->invoice_no }}</strong></td>
                <td>{{ ucfirst(str_replace('_', ' ', $invoice->invoice_type)) }}</td>
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
            </tr>
        @empty
            <tr>
                <td colspan="9" class="text-center text-muted">No invoices found.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
