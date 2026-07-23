<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Daily Financial Sheet — {{ \Carbon\Carbon::parse($sheetDate)->format('d M Y') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 16px 0 6px; }
        .meta { margin-bottom: 12px; color: #555; }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        .badge-success { background: #e8f8ef; color: #28c76f; }
        .badge-warning { background: #fff4e5; color: #ff9f43; }
        .totals { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .totals td {
            border: 1px solid #ddd;
            padding: 8px;
            width: 25%;
            vertical-align: top;
        }
        .totals small { color: #777; display: block; margin-bottom: 2px; }
        .totals strong { font-size: 13px; }
        .alert {
            border: 1px solid #ffcf8b;
            background: #fff8ee;
            padding: 8px 10px;
            margin-bottom: 12px;
        }
        .alert-success {
            border-color: #a8e6c1;
            background: #eefbf3;
        }
        table.entries { width: 100%; border-collapse: collapse; }
        table.entries th, table.entries td {
            border: 1px solid #ccc;
            padding: 5px 6px;
            text-align: left;
            vertical-align: top;
        }
        table.entries th { background: #f3f3f3; font-size: 10px; }
        .text-success { color: #28c76f; }
        .text-danger { color: #ea5455; }
        .text-info { color: #00cfe8; }
        .text-muted { color: #888; }
        .del { text-decoration: line-through; color: #888; }
        ul.banks { margin: 4px 0 12px 16px; padding: 0; }
        .footer { margin-top: 16px; font-size: 9px; color: #888; }
    </style>
</head>
<body>
    <h1>Daily Financial Sheet</h1>
    <div class="meta">
        <strong>{{ \Carbon\Carbon::parse($sheetDate)->format('d M Y') }}</strong>
        @if($isApproved)
            <span class="badge badge-success">Approved</span>
            @if($hasPending)
                <span class="badge badge-warning">New entries pending</span>
            @endif
        @else
            <span class="badge badge-warning">Pending Approval</span>
        @endif
    </div>

    <table class="totals">
        <tr>
            <td>
                <small>{{ $isApproved ? 'Approved Cash In' : 'Cash In' }}</small>
                <strong>£{{ number_format($totals['cash_in'], 2) }}</strong>
            </td>
            <td>
                <small>{{ $isApproved ? 'Approved Cash Out' : 'Cash Out' }}</small>
                <strong>£{{ number_format($totals['cash_out'], 2) }}</strong>
            </td>
            <td>
                <small>{{ $isApproved ? 'Approved Net Cash' : 'Net Cash' }}</small>
                <strong>£{{ number_format($totals['net_cash'], 2) }}</strong>
            </td>
            <td>
                <small>{{ $isApproved ? 'Approved Bank In' : 'Bank In (total)' }}</small>
                <strong>£{{ number_format(collect($totals['bank_in'])->sum('total'), 2) }}</strong>
            </td>
        </tr>
    </table>

    @if(!empty($totals['bank_in']))
        <h2>{{ $isApproved ? 'Approved Bank In breakdown' : 'Bank In breakdown' }}</h2>
        <ul class="banks">
            @foreach($totals['bank_in'] as $bankRow)
                <li>{{ $bankRow['bank_name'] }} ({{ $bankRow['account_number'] }}): £{{ number_format($bankRow['total'], 2) }}</li>
            @endforeach
        </ul>
    @endif

    @if(!empty($totals['bank_out'] ?? []))
        <h2>{{ $isApproved ? 'Approved Bank Out breakdown' : 'Bank Out breakdown' }}</h2>
        <ul class="banks">
            @foreach($totals['bank_out'] as $bankRow)
                <li>{{ $bankRow['bank_name'] }} ({{ $bankRow['account_number'] }}): £{{ number_format($bankRow['total'], 2) }}</li>
            @endforeach
        </ul>
    @endif

    @if($pendingTotals)
        <div class="alert">
            <strong>Pending batch</strong>
            — Cash in £{{ number_format($pendingTotals['cash_in'], 2) }},
            Cash out £{{ number_format($pendingTotals['cash_out'], 2) }},
            Bank in £{{ number_format(collect($pendingTotals['bank_in'])->sum('total'), 2) }},
            Bank out £{{ number_format(collect($pendingTotals['bank_out'] ?? [])->sum('total'), 2) }}
        </div>
    @endif

    @if($isApproved && $sheet)
        <div class="alert alert-success">
            Approved by {{ $sheet->approvedByUser?->name ?? '—' }}
            on {{ optional($sheet->approved_at)->format('d M Y H:i') }}.
            @if($sheet->approval_notes)
                <div><strong>Notes:</strong> {{ $sheet->approval_notes }}</div>
            @endif
        </div>
    @endif

    <h2>Entries</h2>
    <table class="entries">
        <thead>
        <tr>
            <th>Direction</th>
            <th>Employee</th>
            <th>Description</th>
            <th>Category</th>
            <th>Vehicle</th>
            <th>Method</th>
            <th>Bank</th>
            <th>Amount</th>
            <th>Status</th>
        </tr>
        </thead>
        <tbody>
        @forelse($entries as $entry)
            <tr>
                <td>
                    @if($entry['direction'] === 'in')
                        <span class="text-success">IN</span>
                    @elseif($entry['direction'] === 'internal')
                        <span class="text-info">INTERNAL</span>
                    @else
                        <span class="text-danger">OUT</span>
                    @endif
                </td>
                <td>{{ $entry['employee'] }}</td>
                <td>
                    @if(!empty($entry['is_adjustment']) && ($entry['adjustment_event_type'] ?? '') === 'reversal')
                        <span class="del">{{ $entry['description'] }}</span>
                    @else
                        {{ $entry['description'] }}
                    @endif
                </td>
                <td>
                    {{ $entry['category'] }}
                    @if(!empty($entry['agreement_id']))
                        <div class="text-muted">Agreement #{{ $entry['agreement_id'] }}</div>
                    @endif
                    @if(!empty($entry['paying_company_name']))
                        <div class="text-muted">Pays via: {{ $entry['paying_company_name'] }}</div>
                    @endif
                </td>
                <td>{{ $entry['car_registration'] ?? '—' }}</td>
                <td>{{ $entry['payment_method'] }}</td>
                <td>
                    @if($entry['bank_name'])
                        {{ $entry['bank_name'] }}
                        @if($entry['account_number'])
                            <div class="text-muted">{{ $entry['account_number'] }}</div>
                        @endif
                    @else
                        —
                    @endif
                </td>
                <td>£{{ number_format($entry['amount'], 2) }}</td>
                <td>
                    @if(($entry['posting_status'] ?? '') === 'adjustment')
                        Adjustment
                    @elseif($entry['posting_status'] === 'pending')
                        Pending
                    @else
                        Posted
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="text-muted">No entries for this date.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generated {{ now()->format('d M Y H:i') }}
    </div>
</body>
</html>
