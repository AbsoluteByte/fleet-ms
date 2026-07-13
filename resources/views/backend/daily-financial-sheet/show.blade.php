@extends('layouts.admin', ['title' => 'Daily Financial Sheet'])

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center" style="position: static; z-index: unset; width: 100%;">
                    <div>
                        <h4 class="card-title mb-0">Sheet: {{ \Carbon\Carbon::parse($sheetDate)->format('d M Y') }}</h4>
                        @if($isApproved)
                            <span class="badge badge-success">Approved</span>
                        @else
                            <span class="badge badge-warning">Pending Approval</span>
                        @endif
                    </div>
                    <a href="{{ route('daily-financial-sheet.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fa fa-arrow-left"></i> Back
                    </a>
                </div>
                <div class="card-body" style="margin-top: 0;">
                    @include('alerts')

                    <div class="row mb-2">
                        <div class="col-md-3 mb-1">
                            <div class="border rounded p-1">
                                <small class="text-muted">Cash In</small>
                                <div><strong>£{{ number_format($totals['cash_in'], 2) }}</strong></div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-1">
                            <div class="border rounded p-1">
                                <small class="text-muted">Cash Out</small>
                                <div><strong>£{{ number_format($totals['cash_out'], 2) }}</strong></div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-1">
                            <div class="border rounded p-1">
                                <small class="text-muted">Net Cash</small>
                                <div><strong>£{{ number_format($totals['net_cash'], 2) }}</strong></div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-1">
                            <div class="border rounded p-1">
                                <small class="text-muted">Bank In (total)</small>
                                <div><strong>£{{ number_format(collect($totals['bank_in'])->sum('total'), 2) }}</strong></div>
                            </div>
                        </div>
                    </div>

                    @if(!empty($totals['bank_in']))
                        <div class="mb-2">
                            <h6>Bank In breakdown</h6>
                            <ul class="mb-0">
                                @foreach($totals['bank_in'] as $bankRow)
                                    <li>{{ $bankRow['bank_name'] }} ({{ $bankRow['account_number'] }}): £{{ number_format($bankRow['total'], 2) }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(!empty($totals['bank_out'] ?? []))
                        <div class="mb-2">
                            <h6>Bank Out breakdown</h6>
                            <ul class="mb-0">
                                @foreach($totals['bank_out'] as $bankRow)
                                    <li>{{ $bankRow['bank_name'] }} ({{ $bankRow['account_number'] }}): £{{ number_format($bankRow['total'], 2) }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if($isApproved && $sheet)
                        <div class="alert alert-success">
                            Approved by {{ $sheet->approvedByUser?->name ?? '—' }}
                            on {{ optional($sheet->approved_at)->format('d M Y H:i') }}.
                            @if($sheet->approval_notes)
                                <div class="mt-50"><strong>Notes:</strong> {{ $sheet->approval_notes }}</div>
                            @endif
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
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
                                        @else
                                            <span class="text-danger">OUT</span>
                                        @endif
                                    </td>
                                    <td>{{ $entry['employee'] }}</td>
                                    <td>{{ $entry['description'] }}</td>
                                    <td>
                                        {{ $entry['category'] }}
                                        @if(!empty($entry['agreement_url']))
                                            <div><a href="{{ $entry['agreement_url'] }}">Agreement #{{ $entry['agreement_id'] }}</a></div>
                                        @endif
                                    </td>
                                    <td>{{ $entry['car_registration'] ?? '—' }}</td>
                                    <td>{{ $entry['payment_method'] }}</td>
                                    <td>
                                        @if($entry['bank_name'])
                                            {{ $entry['bank_name'] }}
                                            @if($entry['account_number'])
                                                <div class="text-muted small">{{ $entry['account_number'] }}</div>
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>£{{ number_format($entry['amount'], 2) }}</td>
                                    <td>
                                        @if($entry['posting_status'] === 'pending')
                                            <span class="badge badge-warning">Pending</span>
                                        @else
                                            <span class="badge badge-success">Posted</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted">No entries for this date.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($canApprove)
                        <hr>
                        <form method="POST" action="{{ route('daily-financial-sheet.approve', $sheetDate) }}" onsubmit="return confirm('Approve all pending entries for this day? Payments will be posted to invoices.');">
                            @csrf
                            <div class="form-group">
                                <label for="approval_notes">Approval notes (optional)</label>
                                <textarea name="approval_notes" id="approval_notes" class="form-control" rows="3" placeholder="e.g. Cash counted £500, bank matches statement">{{ old('approval_notes') }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-success">
                                Approve Daily Sheet
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <style>
        .navbar-floating .header-navbar-shadow {
            height: 90px !important;
        }
    </style>
@endsection
