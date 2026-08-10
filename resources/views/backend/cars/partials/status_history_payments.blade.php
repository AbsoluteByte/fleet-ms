@php
    $paymentRows = collect($paymentRows ?? [])
        ->filter(fn ($row) => is_array($row))
        ->values();
    $paymentTotal = $paymentRows->sum(fn ($row) => (float) ($row['amount'] ?? 0));
@endphp

@if($paymentRows->isEmpty())
    <span class="text-muted">—</span>
@else
    <div class="table-responsive">
        <table class="table table-sm table-bordered mb-2">
            <thead class="thead-light">
            <tr>
                <th>Date</th>
                <th>Method</th>
                <th>Bank account</th>
                <th class="text-right">Amount</th>
                <th>Notes</th>
            </tr>
            </thead>
            <tbody>
            @foreach($paymentRows as $paymentRow)
                @php
                    $bankAccountId = (int) ($paymentRow['bank_account_id'] ?? 0);
                    $historyBankAccount = $bankAccountId > 0
                        ? ($statusHistoryBankAccounts ?? collect())->get($bankAccountId)
                        : null;
                    $paymentDate = $paymentRow['payment_date'] ?? null;
                @endphp
                <tr>
                    <td class="text-nowrap">
                        @if(is_string($paymentDate) && $paymentDate !== '')
                            {{ \Carbon\Carbon::parse($paymentDate)->format('d M Y') }}
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $paymentRow['payment_method'] ?? '—' }}</td>
                    <td>
                        @if($historyBankAccount)
                            {{ $historyBankAccount->paymentDisplayName() }}
                            <small class="text-muted d-block">{{ $historyBankAccount->account_number }}</small>
                        @elseif($bankAccountId > 0)
                            Bank account #{{ $bankAccountId }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-right text-nowrap">£{{ number_format((float) ($paymentRow['amount'] ?? 0), 2) }}</td>
                    <td>{{ filled($paymentRow['notes'] ?? null) ? $paymentRow['notes'] : '—' }}</td>
                </tr>
            @endforeach
            </tbody>
            @if($paymentRows->count() > 1)
                <tfoot>
                <tr>
                    <th colspan="3" class="text-right">Total</th>
                    <th class="text-right">£{{ number_format($paymentTotal, 2) }}</th>
                    <th></th>
                </tr>
                </tfoot>
            @endif
        </table>
    </div>
@endif
