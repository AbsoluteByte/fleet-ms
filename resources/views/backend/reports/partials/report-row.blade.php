@php
    $carStatusLabel = ucwords(str_replace('_', ' ', $car->fleet_status ?? 'available_for_rent'));
    if ($carStatusLabel === 'Sorn') {
        $carStatusLabel = 'SORN';
    }
    $latestInsurance = $car->insurances
        ->sortByDesc(fn (\App\Models\CarInsurance $i) => [optional($i->created_at)->timestamp ?? 0, $i->id])
        ->first();
    $latestInsuranceStatusName = trim((string) optional(optional($latestInsurance)->status)->name);
    $insuranceStatusLabel = strcasecmp($latestInsuranceStatusName, 'Applied') === 0
        ? 'Applied'
        : (strcasecmp($latestInsuranceStatusName, 'Active') === 0 ? 'Active' : 'Inactive');
    $phvCounselLabel = $car->latestPhvCounselName() ?? '—';

    if ($reportType === 'phvl') {
        $status = $car->report_phv_status;
        $expiry = $car->report_phv_expiry;
        $expiryIso = $expiry ? $expiry->format('Y-m-d') : '';
        $isMissing = $car->report_phv_missing;
        $expiryAttr = 'data-phv-expiry';
        $missingAttr = 'data-phv-missing';
    } else {
        $status = $car->report_mot_status;
        $expiry = $car->report_mot_expiry;
        $expiryIso = $expiry ? $expiry->format('Y-m-d') : '';
        $isMissing = $car->report_mot_missing;
        $expiryAttr = 'data-mot-expiry';
        $missingAttr = 'data-mot-missing';
    }

    $statusClass = match ($status) {
        'Missing' => 'badge-light-warning',
        'Expired' => 'badge-light-danger',
        'Expiring' => 'badge-light-warning',
        default => 'badge-light-success',
    };
@endphp
<tr
    data-company="{{ $car->company->name ?? '' }}"
    {{ $expiryAttr }}="{{ $expiryIso }}"
    {{ $missingAttr }}="{{ $isMissing ? '1' : '0' }}"
>
    <td><strong>{{ $car->registration }}</strong></td>
    <td>{{ $car->company->name ?? '—' }}</td>
    <td>{{ $car->carModel->name ?? '—' }}</td>
    <td>
        <span class="badge bg-secondary">{{ $car->color }}</span>
    </td>
    <td>{{ $carStatusLabel }}</td>
    <td>{{ $phvCounselLabel }}</td>
    <td>
        @if($insuranceStatusLabel === 'Active')
            <span class="insurance-status">
                <span class="insurance-status-dot insurance-status-dot--active" aria-hidden="true"></span>
                <span class="insurance-status-label">Active</span>
            </span>
        @elseif($insuranceStatusLabel === 'Applied')
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
        @if($expiry)
            {{ $expiry->format('d M, Y') }}
        @else
            <span class="text-muted">—</span>
        @endif
    </td>
    <td>
        <span class="badge {{ $statusClass }}">{{ $status }}</span>
    </td>
    <td>
        <div class="btn-group" role="group">
            <a href="{{ route('cars.show', $car) }}" class="btn btn-sm btn-outline-info" title="View">
                <i class="fa fa-eye"></i>
            </a>
            <a href="{{ route('cars.edit', $car) }}" class="btn btn-sm btn-outline-warning" title="Edit">
                <i class="fa fa-edit"></i>
            </a>
        </div>
    </td>
</tr>
