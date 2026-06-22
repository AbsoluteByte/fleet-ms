@php
    $statusLabel = $row->current_status ?? 'Inactive';
@endphp
<tr>
    <td><strong>{{ $row->registration }}</strong></td>
    <td>{{ $row->company }}</td>
    <td>{{ $row->model }}</td>
    <td>{{ $row->provider }}</td>
    <td>
        @if($row->start_date)
            {{ $row->start_date->format('d M, Y') }}
        @else
            <span class="text-muted">—</span>
        @endif
    </td>
    <td>
        @if($row->expiry_date)
            {{ $row->expiry_date->format('d M, Y') }}
        @else
            <span class="text-muted">—</span>
        @endif
    </td>
    <td>
        @if($row->canceled_date)
            {{ $row->canceled_date->format('d M, Y') }}
        @else
            <span class="text-muted">—</span>
        @endif
    </td>
    <td>
        @if($statusLabel === 'Active')
            <span class="insurance-status">
                <span class="insurance-status-dot insurance-status-dot--active" aria-hidden="true"></span>
                <span class="insurance-status-label">Active</span>
            </span>
        @elseif($statusLabel === 'Applied')
            <span class="insurance-status">
                <span class="insurance-status-dot insurance-status-dot--pending" aria-hidden="true"></span>
                <span class="insurance-status-label">Applied</span>
            </span>
        @else
            <span class="insurance-status">
                <span class="insurance-status-dot insurance-status-dot--inactive" aria-hidden="true"></span>
                <span class="insurance-status-label">Inactive</span>
            </span>
        @endif
    </td>
    <td>
        @if($row->car_id)
            <a href="{{ route('cars.show', $row->car_id) }}" class="btn btn-sm btn-outline-info" title="View">
                <i class="fa fa-eye"></i>
            </a>
        @else
            <span class="text-muted">—</span>
        @endif
    </td>
</tr>
