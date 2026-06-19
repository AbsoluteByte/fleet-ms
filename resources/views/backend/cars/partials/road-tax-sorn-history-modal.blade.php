<div class="modal fade" id="roadTaxSornHistoryModal" tabindex="-1" role="dialog" aria-labelledby="roadTaxSornHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header py-75">
                <h5 class="modal-title mb-0" id="roadTaxSornHistoryModalLabel">Road tax &amp; SORN history</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <ul class="nav nav-tabs nav-fill border-bottom px-1 pt-1 mb-0" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link {{ $roadTaxSornActiveTab === 'road-tax' ? 'active' : '' }}"
                           id="rt-sorn-tab-road-tax"
                           data-toggle="tab"
                           href="#rtSornTabRoadTax"
                           role="tab"
                           aria-controls="rtSornTabRoadTax"
                           aria-selected="{{ $roadTaxSornActiveTab === 'road-tax' ? 'true' : 'false' }}">
                            Road tax
                            @if($hasRoadTaxHistory)
                                <span class="badge badge-light ml-50">{{ $roadTaxesOlder->count() }}</span>
                            @endif
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $roadTaxSornActiveTab === 'sorn' ? 'active' : '' }}"
                           id="rt-sorn-tab-sorn"
                           data-toggle="tab"
                           href="#rtSornTabSorn"
                           role="tab"
                           aria-controls="rtSornTabSorn"
                           aria-selected="{{ $roadTaxSornActiveTab === 'sorn' ? 'true' : 'false' }}">
                            SORN
                            @if($hasSornHistory)
                                <span class="badge badge-light ml-50">{{ $model->sornHistories->count() }}</span>
                            @endif
                        </a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade {{ $roadTaxSornActiveTab === 'road-tax' ? 'show active' : '' }}"
                         id="rtSornTabRoadTax"
                         role="tabpanel"
                         aria-labelledby="rt-sorn-tab-road-tax">
                        @if($hasRoadTaxHistory)
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0">
                                    <thead class="thead-light">
                                    <tr>
                                        <th>Start Date</th>
                                        <th>Term</th>
                                        <th>Expiry Date</th>
                                        <th>Amount</th>
                                        <th class="text-right" style="width:80px">Action</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($roadTaxesOlder as $rtH)
                                        <tr data-hist-rt-id="{{ $rtH->id }}">
                                            <td>{{ $rtH->start_date->format('d M, Y') }}</td>
                                            <td>{{ $rtH->term }}</td>
                                            <td>
                                                @if($rtExpiry = $rtH->expiryDate())
                                                    {{ $rtExpiry->format('d M, Y') }}
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>£{{ number_format($rtH->amount, 2) }}</td>
                                            <td class="text-right">
                                                <x-car-record-delete-button
                                                    :delete-url="route('cars.road-taxes.destroy', [$model, $rtH->id])"
                                                    label="Road tax record"
                                                />
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted text-center py-3 mb-0">No previous road tax records.</p>
                        @endif
                    </div>
                    <div class="tab-pane fade {{ $roadTaxSornActiveTab === 'sorn' ? 'show active' : '' }}"
                         id="rtSornTabSorn"
                         role="tabpanel"
                         aria-labelledby="rt-sorn-tab-sorn">
                        @if($hasSornHistory)
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0">
                                    <thead class="thead-light">
                                    <tr>
                                        <th>SORN Started</th>
                                        <th>Started By</th>
                                        <th>SORN Ended</th>
                                        <th>Ended By</th>
                                        <th>Duration</th>
                                        <th>Proof</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($model->sornHistories as $sornH)
                                        <tr>
                                            <td>{{ $sornH->sorn_started_at->format('d M Y, h:i A') }}</td>
                                            <td>{{ $sornH->startedBy?->name ?? '—' }}</td>
                                            <td>
                                                @if($sornH->sorn_ended_at)
                                                    {{ $sornH->sorn_ended_at->format('d M Y, h:i A') }}
                                                @else
                                                    <span class="badge badge-success">Active</span>
                                                @endif
                                            </td>
                                            <td>{{ $sornH->endedBy?->name ?? '—' }}</td>
                                            <td>
                                                @php
                                                    $end = $sornH->sorn_ended_at ?? now();
                                                    $diff = $sornH->sorn_started_at->diff($end);
                                                    $parts = [];
                                                    if ($diff->y) $parts[] = $diff->y . 'y';
                                                    if ($diff->m) $parts[] = $diff->m . 'mo';
                                                    if ($diff->d) $parts[] = $diff->d . 'd';
                                                    if (empty($parts)) $parts[] = 'less than a day';
                                                @endphp
                                                {{ implode(' ', $parts) }}
                                            </td>
                                            <td>
                                                @if($sornH->sorn_document)
                                                    <a href="{{ document_view_url(asset('uploads/cars/sorn_documents/'.$sornH->sorn_document)) }}" target="_blank" rel="noopener noreferrer" class="document-view-link" title="View proof"><i class="fa fa-file"></i></a>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted text-center py-3 mb-0">No SORN history yet.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
