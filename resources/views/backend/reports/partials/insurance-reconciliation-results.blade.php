@php
    $summary = $reconciliation['summary'] ?? [];
    $pdfMeta = $reconciliation['pdf'] ?? [];
@endphp

<div class="row mb-2">
    <div class="col-6 col-md mb-1">
        <div class="border rounded p-1 h-100">
            <small class="text-muted d-block">Matched</small>
            <strong class="text-success">{{ (int) ($summary['matched'] ?? 0) }}</strong>
        </div>
    </div>
    <div class="col-6 col-md mb-1">
        <div class="border rounded p-1 h-100">
            <small class="text-muted d-block">On PDF only</small>
            <strong class="text-warning">{{ (int) ($summary['pdf_only'] ?? 0) }}</strong>
        </div>
    </div>
    <div class="col-6 col-md mb-1">
        <div class="border rounded p-1 h-100">
            <small class="text-muted d-block">In system only</small>
            <strong class="text-danger">{{ (int) ($summary['system_only'] ?? 0) }}</strong>
        </div>
    </div>
    <div class="col-6 col-md mb-1">
        <div class="border rounded p-1 h-100">
            <small class="text-muted d-block">PDF duplicates</small>
            <strong class="text-info">{{ (int) ($summary['pdf_duplicates'] ?? 0) }}</strong>
        </div>
    </div>
    <div class="col-6 col-md mb-1">
        <div class="border rounded p-1 h-100">
            <small class="text-muted d-block">System duplicates</small>
            <strong class="text-primary">{{ (int) ($summary['system_duplicates'] ?? 0) }}</strong>
        </div>
    </div>
</div>

<p class="text-muted small mb-2">
    Compared period
    <strong>{{ \Carbon\Carbon::parse($reconciliation['from'])->format('d M Y') }}</strong>
    to
    <strong>{{ \Carbon\Carbon::parse($reconciliation['to'])->format('d M Y') }}</strong>.
    PDF rows: {{ (int) ($summary['pdf_vehicle_rows'] ?? 0) }}
    ({{ (int) ($summary['pdf_unique_regs'] ?? 0) }} unique).
    System unique regs in period: {{ (int) ($summary['system_unique_regs'] ?? 0) }}.
    @if(!empty($pdfMeta['policy_number']) || !empty($pdfMeta['inception']) || !empty($pdfMeta['expiry']))
        PDF schedule
        @if(!empty($pdfMeta['policy_number']))
            <strong>{{ $pdfMeta['policy_number'] }}</strong>
        @endif
        @if(!empty($pdfMeta['inception']) || !empty($pdfMeta['expiry']))
            ({{ !empty($pdfMeta['inception']) ? \Carbon\Carbon::parse($pdfMeta['inception'])->format('d M Y') : '—' }}
            –
            {{ !empty($pdfMeta['expiry']) ? \Carbon\Carbon::parse($pdfMeta['expiry'])->format('d M Y') : '—' }})
        @endif.
    @endif
</p>

@foreach([
    'pdf_only' => ['title' => 'On PDF only (missing / not insured in Fleet IQ for this period)', 'class' => 'table-warning'],
    'system_only' => ['title' => 'In system only (on Fleet IQ insurance, not on PDF)', 'class' => 'table-danger'],
    'pdf_duplicates' => ['title' => 'PDF duplicates', 'class' => 'table-info'],
    'system_duplicates' => ['title' => 'System duplicates (multiple overlapping policies)', 'class' => 'table-primary'],
    'matched' => ['title' => 'Matched', 'class' => ''],
] as $key => $meta)
    @php $rows = $reconciliation[$key] ?? []; @endphp
    <div class="mb-2">
        <h5 class="mb-1">{{ $meta['title'] }} ({{ count($rows) }})</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm mb-0">
                <thead>
                <tr>
                    <th>Registration</th>
                    @if($key === 'matched' || $key === 'pdf_only' || $key === 'pdf_duplicates')
                        <th>PDF make/model</th>
                        <th>PDF cover</th>
                        <th>PDF rate</th>
                        <th>PDF date added</th>
                    @endif
                    @if($key === 'pdf_only' || $key === 'pdf_duplicates')
                        <th>Note</th>
                    @endif
                    @if($key === 'matched' || $key === 'system_only')
                        <th>System start</th>
                        <th>System expiry</th>
                        <th>Status</th>
                        <th>Provider</th>
                        <th>Policies</th>
                    @endif
                    @if($key === 'system_duplicates')
                        <th>Overlapping policies</th>
                        <th>Count</th>
                    @endif
                    @if($key === 'system_only')
                        <th>Company</th>
                        <th>Model</th>
                    @endif
                </tr>
                </thead>
                <tbody>
                @forelse($rows as $row)
                    <tr class="{{ $meta['class'] }}">
                        <td>
                            @if(!empty($row['car_id']))
                                <a href="{{ route('cars.show', $row['car_id']) }}">{{ $row['registration'] }}</a>
                            @else
                                {{ $row['registration'] }}
                            @endif
                        </td>
                        @if($key === 'matched' || $key === 'pdf_only' || $key === 'pdf_duplicates')
                            <td>{{ $row['pdf_make_model'] ?? '—' }}</td>
                            <td>{{ $row['pdf_cover'] ?? '—' }}</td>
                            <td>{{ isset($row['pdf_annual_rate']) ? '£'.number_format((float) $row['pdf_annual_rate'], 2) : '—' }}</td>
                            <td>{{ !empty($row['pdf_date_added']) ? \Carbon\Carbon::parse($row['pdf_date_added'])->format('d M Y') : '—' }}</td>
                        @endif
                        @if($key === 'pdf_only' || $key === 'pdf_duplicates')
                            <td>
                                {{ $row['note'] ?? '—' }}
                                @if(($row['pdf_count'] ?? 1) > 1)
                                    (listed {{ $row['pdf_count'] }} times on PDF)
                                @endif
                            </td>
                        @endif
                        @if($key === 'matched' || $key === 'system_only')
                            <td>{{ !empty($row['system_start']) ? \Carbon\Carbon::parse($row['system_start'])->format('d M Y') : '—' }}</td>
                            <td>{{ !empty($row['system_expiry']) ? \Carbon\Carbon::parse($row['system_expiry'])->format('d M Y') : '—' }}</td>
                            <td>{{ $row['system_status'] ?? '—' }}</td>
                            <td>{{ $row['system_provider'] ?? '—' }}</td>
                            <td>{{ (int) ($row['system_policy_count'] ?? 1) }}</td>
                        @endif
                        @if($key === 'system_duplicates')
                            <td>
                                @foreach(($row['policies'] ?? []) as $policy)
                                    #{{ $policy['id'] }}:
                                    {{ $policy['start_date'] ?? '—' }} → {{ $policy['expiry_date'] ?? '—' }}
                                    ({{ $policy['status'] ?? '—' }}{{ !empty($policy['provider']) ? ', '.$policy['provider'] : '' }})
                                    @if(! $loop->last)<br>@endif
                                @endforeach
                            </td>
                            <td>{{ (int) ($row['system_policy_count'] ?? 0) }}</td>
                        @endif
                        @if($key === 'system_only')
                            <td>{{ $row['company'] ?? '—' }}</td>
                            <td>{{ $row['model'] ?? '—' }}</td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted">No rows.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endforeach
