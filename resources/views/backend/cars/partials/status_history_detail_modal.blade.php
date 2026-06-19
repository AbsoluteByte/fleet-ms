@php
    /** @var \App\Models\CarStatusHistory $entry */
    $step2Statuses = ['reserved', 'vehicle_swap', 'damaged', 'written_off', 'stolen', 'for_sale', 'sold'];
@endphp
@if(in_array($entry->new_status, $step2Statuses, true))
    @php
        $sd = $entry->status_data ?? [];
        $writtenOffDisposalLabels = \App\Services\CarStatusChangeService::WRITTEN_OFF_DISPOSAL_OUTCOMES;
    @endphp
    <div class="modal fade" id="carStatusHistoryModal{{ $entry->id }}" tabindex="-1" role="dialog"
         aria-labelledby="carStatusHistoryModalLabel{{ $entry->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="carStatusHistoryModalLabel{{ $entry->id }}">Status change details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        {{ ucwords(str_replace('_', ' ', $entry->previous_status ?? '—')) }}
                        <span>→</span>
                        <strong>{{ ucwords(str_replace('_', ' ', $entry->new_status)) }}</strong>
                        · {{ $entry->created_at?->format('d/m/Y H:i') }}
                    </p>

                    @if(isset($car) && $car->fleet_status === $entry->new_status)
                        <div class="mb-3">
                            <a href="{{ route('car-status.create', ['car_id' => $car->id, 'edit_current_status' => 1]) }}"
                               class="btn btn-sm btn-outline-primary">
                                <i class="fa fa-edit"></i> Edit current status details
                            </a>
                        </div>
                    @endif

                    @if($entry->reservation_id)
                        <div class="mb-3">
                            <a href="{{ route('reservations.edit', $entry->reservation_id) }}"
                               class="btn btn-sm btn-primary">
                                <i class="fa fa-edit"></i> Edit reservation #{{ $entry->reservation_id }}
                            </a>
                        </div>
                    @endif

                    @if($entry->vehicle_swap_id)
                        <div class="mb-3">
                            <a href="{{ route('vehicle-swaps.edit', $entry->vehicle_swap_id) }}"
                               class="btn btn-sm btn-primary">
                                <i class="fa fa-edit"></i> Edit vehicle swap #{{ $entry->vehicle_swap_id }}
                            </a>
                        </div>
                    @endif

                    @php
                        $docs = isset($sd['documents']) && is_array($sd['documents']) ? $sd['documents'] : [];
                        $detailEntries = collect($sd)
                            ->except('documents')
                            ->filter(fn ($v) => $v !== null && $v !== '' && $v !== []);
                    @endphp
                    @if($docs !== [])
                        <h6 class="border-bottom pb-2">Documents</h6>
                        <ul class="mb-3">
                            @foreach($docs as $docPath)
                                @if(is_string($docPath) && $docPath !== '')
                                    <li><a href="{{ document_view_url(asset($docPath)) }}" target="_blank" rel="noopener" class="document-view-link">View file</a></li>
                                @endif
                            @endforeach
                        </ul>
                    @endif

                    @if($detailEntries->isNotEmpty())
                        <h6 class="border-bottom pb-2">Submitted information</h6>
                        <dl class="row small mb-0">
                            @foreach($detailEntries as $key => $value)
                                <dt class="col-sm-4 text-break">
                                    {{ $key === 'driver_id' ? 'Driver' : ucwords(str_replace('_', ' ', (string) $key)) }}
                                </dt>
                                <dd class="col-sm-8">
                                    @if(is_array($value))
                                        <pre class="mb-0 p-2 bg-light rounded border small"
                                             style="max-height:200px;overflow:auto;font-size:0.8rem;">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    @elseif(is_bool($value))
                                        {{ $value ? 'Yes' : 'No' }}
                                    @elseif($key === 'disposal_outcome' && isset($writtenOffDisposalLabels[$value]))
                                        {{ $writtenOffDisposalLabels[$value] }}
                                    @elseif($key === 'driver_id' && $value)
                                        @php
                                            $historyDriver = ($statusHistoryDrivers ?? collect())->get((int) $value);
                                        @endphp
                                        @if($historyDriver)
                                            <a href="{{ route('drivers.edit', $historyDriver) }}">{{ $historyDriver->full_name }}</a>
                                        @else
                                            Driver #{{ $value }}
                                        @endif
                                    @else
                                        {{ $value }}
                                    @endif
                                </dd>
                            @endforeach
                        </dl>
                    @elseif($docs === [] && ! $entry->reservation_id && ! $entry->vehicle_swap_id)
                        <p class="text-muted mb-0">No extra fields were stored for this change.</p>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endif
