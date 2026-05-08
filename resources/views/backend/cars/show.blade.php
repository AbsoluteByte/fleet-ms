@extends('layouts.admin', ['title' => 'Car Details'])

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">Car Details - {{ $car->registration }}</h3>
                        <div>
                            <a href="{{ route($url . 'edit', $car->id) }}" class="btn btn-primary btn-sm">
                                <i class="fa fa-edit"></i> Edit
                            </a>
                            <a href="{{ route($url . 'index') }}" class="btn btn-secondary btn-sm">
                                <i class="fa fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>

                    <div class="card-body">
                        <!-- Basic Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h4 class="border-bottom pb-2 mb-3">Basic Information</h4>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Company:</strong>
                                <p class="mb-0">{{ $car->company->name ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Car Model:</strong>
                                <p class="mb-0">{{ $car->carModel->name ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Registration:</strong>
                                <p class="mb-0">{{ $car->registration }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Color:</strong>
                                <p class="mb-0">{{ $car->color }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>VIN:</strong>
                                <p class="mb-0">{{ $car->vin }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Manufacture Year:</strong>
                                <p class="mb-0">{{ $car->manufacture_year }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Registration Year:</strong>
                                <p class="mb-0">{{ $car->registration_year }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Purchase Date:</strong>
                                <p class="mb-0">{{ \Carbon\Carbon::parse($car->purchase_date)->format('d M, Y') }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Purchase Price:</strong>
                                <p class="mb-0">£{{ number_format($car->purchase_price, 2) }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Purchase Type:</strong>
                                <p class="mb-0">
                                <span class="badge badge-{{ $car->purchase_type == 'imported' ? 'info' : 'success' }}">
                                    {{ ucfirst($car->purchase_type) }}
                                </span>
                                </p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Seller Name:</strong>
                                <p class="mb-0">{{ $car->seller_name ?? '—' }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Fleet Status:</strong>
                                <p class="mb-0">{{ ucwords(str_replace('_', ' ', $car->fleet_status ?? 'available_for_rent')) }}</p>
                            </div>
                            @if($car->statusHistories->isNotEmpty())
                                @php
                                    $historyStep2Statuses = ['reserved', 'vehicle_swap', 'damaged', 'written_off', 'stolen', 'for_sale', 'sold'];
                                    $historyTotalCount = $car->statusHistories->count();
                                    $historyExtraCount = max(0, $historyTotalCount - 2);
                                @endphp
                                <div class="col-12 mb-4">
                                    <h4 class="border-bottom pb-2 mb-3 mt-2">Fleet status history</h4>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead class="thead-light">
                                            <tr>
                                                <th>When</th>
                                                <th>Change</th>
                                                <th>By</th>
                                                <th>Details</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($car->statusHistories as $entry)
                                                @php
                                                    $historyShowDetailsBtn = in_array($entry->new_status, $historyStep2Statuses, true);
                                                    $historyRowExtra = $loop->iteration > 2;
                                                @endphp
                                                <tr class="{{ $historyRowExtra ? 'fleet-status-history-row-extra d-none' : '' }}">
                                                    <td class="text-nowrap">{{ $entry->created_at?->format('d/m/Y H:i') }}</td>
                                                    <td>
                                                        {{ ucwords(str_replace('_', ' ', $entry->previous_status ?? '—')) }}
                                                        <span class="text-muted">→</span>
                                                        <strong>{{ ucwords(str_replace('_', ' ', $entry->new_status)) }}</strong>
                                                    </td>
                                                    <td>{{ $entry->changedBy->name ?? '—' }}</td>
                                                    <td>
                                                        @if($historyShowDetailsBtn)
                                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                                    data-toggle="modal"
                                                                    data-target="#carStatusHistoryModal{{ $entry->id }}">
                                                                View details
                                                            </button>
                                                        @else
                                                            <span class="text-muted">—</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @if($historyExtraCount > 0)
                                        <p class="mb-0 mt-2 text-center">
                                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                                    id="fleetStatusHistoryToggle"
                                                    data-expanded="0"
                                                    data-label-expand="Show full history ({{ $historyExtraCount }} more)"
                                                    data-label-collapse="Show latest only">
                                                Show full history ({{ $historyExtraCount }} more)
                                            </button>
                                        </p>
                                    @endif
                                    @foreach($car->statusHistories as $entry)
                                        @include('backend.cars.partials.status_history_detail_modal', ['entry' => $entry])
                                    @endforeach
                                </div>
                            @endif
                            @if($car->seller_notes)
                                <div class="col-12 mb-3">
                                    <strong>Seller Notes:</strong>
                                    <p class="mb-0" style="white-space: pre-wrap;">{{ $car->seller_notes }}</p>
                                </div>
                            @endif
                            @php
                                $hasV5Doc = filled($car->v5_document);
                                $oldLogBookFiles = $car->oldLogBookFileNames();
                                $hasOldLogBookFile = $oldLogBookFiles !== [];
                                $logBookApplied = $car->log_book_applied;

                                $logBookReceivedLayout = $hasV5Doc && $hasOldLogBookFile;
                                $logBookAppliedOnlyLayout = ! $hasV5Doc && $logBookApplied;
                                $logBookMissingNotice = ! $hasV5Doc && ! $logBookApplied && ! $hasOldLogBookFile;
                            @endphp

                            @if($logBookReceivedLayout)
                                <div class="col-md-6 mb-3">
                                    <strong>Log book status:</strong>
                                    <p class="mb-0"><span class="badge badge-success">Received</span></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Log book applied:</strong>
                                    <p class="mb-0">
                                        @foreach($oldLogBookFiles as $lbName)
                                            <a href="{{ asset('uploads/cars/log_book/' . $lbName) }}" target="_blank" class="btn btn-sm btn-outline-primary mr-1 mb-1">
                                                <i class="fa fa-file"></i> View file @if(count($oldLogBookFiles) > 1)#{{ $loop->iteration }}@endif
                                            </a>
                                        @endforeach
                                    </p>
                                </div>
                            @elseif($logBookAppliedOnlyLayout)
                                <div class="col-md-6 mb-3">
                                    <strong>Log book status:</strong>
                                    <p class="mb-0"><span class="badge badge-success">Applied</span></p>
                                    @if($car->logBookAppliedBy)
                                        <strong class="d-block mt-3">Log book applied by:</strong>
                                        <p class="mb-0">{{ $car->logBookAppliedBy->name ?? '—' }}</p>
                                    @endif
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Applied Date:</strong>
                                    <p class="mb-0">{{ $car->log_book_applied_date ? $car->log_book_applied_date->format('d M, Y') : '—' }}</p>
                                    @if($hasOldLogBookFile)
                                        <strong class="d-block mt-3">Old log book:</strong>
                                        <p class="mb-0">
                                            @foreach($oldLogBookFiles as $lbName)
                                                <a href="{{ asset('uploads/cars/log_book/' . $lbName) }}" target="_blank" class="btn btn-sm btn-outline-primary mr-1 mb-1">
                                                    <i class="fa fa-file"></i> View file @if(count($oldLogBookFiles) > 1)#{{ $loop->iteration }}@endif
                                                </a>
                                            @endforeach
                                        </p>
                                    @endif
                                </div>
                            @elseif($logBookMissingNotice)
                                <div class="col-12 mb-3">
                                    <div class="alert alert-danger mb-0" role="alert">
                                        Log book missing
                                    </div>
                                </div>
                            @else
                                {{-- Partial states e.g. V5 without old file, old file without applied, etc. --}}
                                @if($logBookApplied)
                                    <div class="col-md-6 mb-3">
                                        <strong>Log book applied:</strong>
                                        <p class="mb-0"><span class="badge badge-success">Yes</span></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <strong>Applied Date:</strong>
                                        <p class="mb-0">{{ $car->log_book_applied_date ? $car->log_book_applied_date->format('d M, Y') : '—' }}</p>
                                    </div>
                                    @if($car->logBookAppliedBy)
                                        <div class="col-md-6 mb-3">
                                            <strong>Log book applied by:</strong>
                                            <p class="mb-0">{{ $car->logBookAppliedBy->name ?? '—' }}</p>
                                        </div>
                                    @endif
                                @endif
                                @if($hasOldLogBookFile)
                                    <div class="col-md-6 mb-3">
                                        <strong>Old log book:</strong>
                                        <p class="mb-0">
                                            @foreach($oldLogBookFiles as $lbName)
                                                <a href="{{ asset('uploads/cars/log_book/' . $lbName) }}" target="_blank" class="btn btn-sm btn-outline-primary mr-1 mb-1">
                                                    <i class="fa fa-file"></i> View file @if(count($oldLogBookFiles) > 1)#{{ $loop->iteration }}@endif
                                                </a>
                                            @endforeach
                                        </p>
                                    </div>
                                @endif
                            @endif
                            @if($hasV5Doc)
                                <div class="col-md-6 mb-3">
                                    <strong>V5 Document:</strong>
                                    <p class="mb-0">
                                        <a href="{{ route('cars.view.v5', $car) }}" target="_blank" class="btn btn-sm btn-outline-primary" rel="noopener">
                                            <i class="fa fa-file"></i> View Document
                                        </a>
                                    </p>
                                </div>
                            @endif
                        </div>

                        <!-- MOT Information -->
                        @php
                            $motsSorted = $car->mots;
                            $latestMot = $motsSorted->count() > 0 ? $motsSorted->first() : null;
                            $olderMots = $motsSorted->count() > 1 ? $motsSorted->slice(1) : collect();
                        @endphp
                        <div class="row mb-4">
                            <div class="col-12 d-flex flex-wrap justify-content-between align-items-center border-bottom pb-2 mb-3">
                                <h4 class="mb-0">MOT Information</h4>
                                @if($olderMots->isNotEmpty())
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#showMotHistoryModal">View All</button>
                                @endif
                            </div>
                            @if($latestMot)
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead class="thead-light">
                                            <tr>
                                                <th>Expiry Date</th>
                                                <th>Amount</th>
                                                <th>Term</th>
                                                <th>Document</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>{{ $latestMot->expiry_date->format('d M, Y') }}</td>
                                                    <td>£{{ number_format($latestMot->amount, 2) }}</td>
                                                    <td>{{ $latestMot->term }}</td>
                                                    <td>
                                                        @if($latestMot->document)
                                                            <a href="{{ route('cars.mots.download', [$car, $latestMot->id]) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                <i class="fa fa-file"></i> View
                                                            </a>
                                                        @else
                                                            <span class="text-muted">No Document</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @else
                                <div class="col-12">
                                    <p class="text-muted">No MOT records available</p>
                                </div>
                            @endif
                        </div>
                        @if($olderMots->isNotEmpty())
                        <div class="modal fade" id="showMotHistoryModal" tabindex="-1" role="dialog" aria-labelledby="showMotHistoryModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="showMotHistoryModalLabel">Previous MOT records</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    </div>
                                    <div class="modal-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-bordered mb-0">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>Expiry Date</th>
                                                        <th>Amount</th>
                                                        <th>Term</th>
                                                        <th>Document</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($olderMots as $mot)
                                                    <tr>
                                                        <td>{{ $mot->expiry_date->format('d M, Y') }}</td>
                                                        <td>£{{ number_format($mot->amount, 2) }}</td>
                                                        <td>{{ $mot->term }}</td>
                                                        <td>
                                                            @if($mot->document)
                                                                <a href="{{ route('cars.mots.download', [$car, $mot->id]) }}" target="_blank" class="btn btn-sm btn-outline-primary">View</a>
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Road Tax Information -->
                        @php
                            $rtsSorted = $car->roadTaxes;
                            $latestRt = $rtsSorted->count() > 0 ? $rtsSorted->first() : null;
                            $olderRts = $rtsSorted->count() > 1 ? $rtsSorted->slice(1) : collect();
                        @endphp
                        <div class="row mb-4">
                            <div class="col-12 d-flex flex-wrap justify-content-between align-items-center border-bottom pb-2 mb-3">
                                <h4 class="mb-0">Road Tax Information</h4>
                                @if($olderRts->isNotEmpty())
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#showRoadTaxHistoryModal">View All</button>
                                @endif
                            </div>
                            @if($latestRt)
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead class="thead-light">
                                            <tr>
                                                <th>Start Date</th>
                                                <th>Term</th>
                                                <th>Amount</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>{{ $latestRt->start_date->format('d M, Y') }}</td>
                                                    <td>{{ $latestRt->term }}</td>
                                                    <td>£{{ number_format($latestRt->amount, 2) }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @else
                                <div class="col-12">
                                    <p class="text-muted">No Road Tax records available</p>
                                </div>
                            @endif
                        </div>
                        @if($olderRts->isNotEmpty())
                        <div class="modal fade" id="showRoadTaxHistoryModal" tabindex="-1" role="dialog" aria-labelledby="showRoadTaxHistoryModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="showRoadTaxHistoryModalLabel">Previous road tax records</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    </div>
                                    <div class="modal-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-bordered mb-0">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>Start Date</th>
                                                        <th>Term</th>
                                                        <th>Amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($olderRts as $roadTax)
                                                    <tr>
                                                        <td>{{ $roadTax->start_date->format('d M, Y') }}</td>
                                                        <td>{{ $roadTax->term }}</td>
                                                        <td>£{{ number_format($roadTax->amount, 2) }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- PHV License Information -->
                        @php
                            $phvsSorted = $car->phvs;
                            $latestPhv = $phvsSorted->count() > 0 ? $phvsSorted->first() : null;
                            $olderPhvs = $phvsSorted->count() > 1 ? $phvsSorted->slice(1) : collect();
                        @endphp
                        <div class="row mb-4">
                            <div class="col-12 d-flex flex-wrap justify-content-between align-items-center border-bottom pb-2 mb-3">
                                <h4 class="mb-0">PHV License Information</h4>
                                @if($olderPhvs->isNotEmpty())
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#showPhvHistoryModal">View All</button>
                                @endif
                            </div>
                            @if($latestPhv)
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead class="thead-light">
                                            <tr>
                                                <th>Council</th>
                                                <th>Start Date</th>
                                                <th>Expiry Date</th>
                                                <th>Amount</th>
                                                <th>Notify Before</th>
                                                <th>Applied</th>
                                                <th>Document</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>{{ $latestPhv->counsel->name ?? 'N/A' }}</td>
                                                    <td>{{ $latestPhv->start_date->format('d M, Y') }}</td>
                                                    <td>{{ $latestPhv->expiry_date->format('d M, Y') }}</td>
                                                    <td>£{{ number_format($latestPhv->amount, 2) }}</td>
                                                    <td>{{ $latestPhv->notify_before_expiry }} days</td>
                                                    <td>
                                                        {{ $latestPhv->phv_applied ? 'Yes' : 'No' }}
                                                        @if($latestPhv->phv_applied_date)
                                                            <br><small>{{ $latestPhv->phv_applied_date->format('d M, Y') }}</small>
                                                        @endif
                                                        @if($latestPhv->phvAppliedBy)
                                                            <br><small>By {{ $latestPhv->phvAppliedBy->name }}</small>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($latestPhv->document)
                                                            <a href="{{ route('cars.phvs.download', [$car, $latestPhv->id]) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                <i class="fa fa-file"></i> View
                                                            </a>
                                                        @else
                                                            <span class="text-muted">No Document</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @else
                                <div class="col-12">
                                    <p class="text-muted">No PHV records available</p>
                                </div>
                            @endif
                        </div>
                        @if($olderPhvs->isNotEmpty())
                        <div class="modal fade" id="showPhvHistoryModal" tabindex="-1" role="dialog" aria-labelledby="showPhvHistoryModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="showPhvHistoryModalLabel">Previous PHV records</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    </div>
                                    <div class="modal-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-bordered mb-0">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>Council</th>
                                                        <th>Start</th>
                                                        <th>Expiry</th>
                                                        <th>Amount</th>
                                                        <th>Notify</th>
                                                        <th>Applied</th>
                                                        <th>Document</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($olderPhvs as $phv)
                                                    <tr>
                                                        <td>{{ $phv->counsel->name ?? 'N/A' }}</td>
                                                        <td>{{ $phv->start_date->format('d M, Y') }}</td>
                                                        <td>{{ $phv->expiry_date->format('d M, Y') }}</td>
                                                        <td>£{{ number_format($phv->amount, 2) }}</td>
                                                        <td>{{ $phv->notify_before_expiry }} days</td>
                                                        <td>
                                                            {{ $phv->phv_applied ? 'Yes' : 'No' }}
                                                            @if($phv->phv_applied_date)
                                                                <br><small>{{ $phv->phv_applied_date->format('d M, Y') }}</small>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($phv->document)
                                                                <a href="{{ route('cars.phvs.download', [$car, $phv->id]) }}" target="_blank" class="btn btn-sm btn-outline-primary">View</a>
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Insurance Information -->
                        @php
                            $insurancesSorted = $car->insurances;
                            $latestInsurance = $insurancesSorted->count() > 0 ? $insurancesSorted->first() : null;
                            $olderInsurances = $insurancesSorted->count() > 1 ? $insurancesSorted->slice(1) : collect();
                        @endphp
                        <div class="row mb-4">
                            <div class="col-12 d-flex flex-wrap justify-content-between align-items-center border-bottom pb-2 mb-3">
                                <h4 class="mb-0">Insurance Information</h4>
                                @if($olderInsurances->isNotEmpty())
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#showInsuranceHistoryModal">View All</button>
                                @endif
                            </div>
                            @if($latestInsurance)
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead class="thead-light">
                                            <tr>
                                                <th>Provider</th>
                                                <th>Start Date</th>
                                                <th>Expiry Date</th>
                                                <th>Canceled Date</th>
                                                <th>Notify Before</th>
                                                <th>Status</th>
                                                <th>Document</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>{{ $latestInsurance->insuranceProvider->provider_name ?? 'N/A' }}</td>
                                                    <td>{{ $latestInsurance->start_date->format('d M, Y') }}</td>
                                                    <td>{{ $latestInsurance->expiry_date->format('d M, Y') }}</td>
                                                    <td>{{ $latestInsurance->canceled_date ? $latestInsurance->canceled_date->format('d M, Y') : '—' }}</td>
                                                    <td>{{ $latestInsurance->notify_before_expiry }} days</td>
                                                    <td>
                                                        <span class="badge badge-{{ $latestInsurance->status && $latestInsurance->status->name == 'Active' ? 'success' : 'warning' }}">
                                                            {{ $latestInsurance->status->name ?? 'N/A' }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if($latestInsurance->insurance_document)
                                                            <a href="{{ asset('uploads/cars/insurance_documents/' . $latestInsurance->insurance_document) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                <i class="fa fa-file"></i> View
                                                            </a>
                                                        @else
                                                            <span class="text-muted">No Document</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @else
                                <div class="col-12">
                                    <p class="text-muted">No Insurance records available</p>
                                </div>
                            @endif
                        </div>
                        @if($olderInsurances->isNotEmpty())
                        <div class="modal fade" id="showInsuranceHistoryModal" tabindex="-1" role="dialog" aria-labelledby="showInsuranceHistoryModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="showInsuranceHistoryModalLabel">Previous insurance records</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    </div>
                                    <div class="modal-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-bordered mb-0">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>Provider</th>
                                                        <th>Start</th>
                                                        <th>Expiry</th>
                                                        <th>Canceled</th>
                                                        <th>Document</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($olderInsurances as $insurance)
                                                    @php
                                                        $insuranceStatusName = strtolower(trim((string) optional($insurance->status)->name));
                                                    @endphp
                                                    @if($insuranceStatusName !== 'applied')
                                                    <tr>
                                                        <td>{{ $insurance->insuranceProvider->provider_name ?? 'N/A' }}</td>
                                                        <td>{{ $insurance->start_date->format('d M, Y') }}</td>
                                                        <td>{{ $insurance->expiry_date->format('d M, Y') }}</td>
                                                        <td>{{ $insurance->canceled_date ? $insurance->canceled_date->format('d M, Y') : '—' }}</td>
                                                        <td>
                                                            @if($insurance->insurance_document)
                                                                <a href="{{ asset('uploads/cars/insurance_documents/' . $insurance->insurance_document) }}" target="_blank" class="btn btn-sm btn-outline-primary">View</a>
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @endif
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Service and Reservation Information -->
                        @php
                            $latestService = $car->latestService();
                            $activeReservation = $car->activeReservation();
                        @endphp
                        <div class="row mb-4">
                            <div class="col-12">
                                <h4 class="border-bottom pb-2 mb-3">Service Information</h4>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Latest Service:</strong>
                                <p class="mb-0">{{ $latestService ? $latestService->service_date->format('d M, Y') : '—' }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Next Service Due:</strong>
                                <p class="mb-0">{{ $latestService ? $latestService->service_date->copy()->addMonths(3)->format('d M, Y') : '—' }}</p>
                            </div>
                            @if($latestService && $latestService->notes)
                                <div class="col-12 mb-3">
                                    <strong>Service Notes:</strong>
                                    <p class="mb-0" style="white-space: pre-wrap;">{{ $latestService->notes }}</p>
                                </div>
                            @endif
                        </div>

                        <div class="row mb-4">
                            <div class="col-12">
                                <h4 class="border-bottom pb-2 mb-3">Reservation Information</h4>
                            </div>
                            @if($activeReservation)
                                <div class="col-md-6 mb-3">
                                    <strong>Customer:</strong>
                                    <p class="mb-0">{{ $activeReservation->customer_name }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Reservation Date:</strong>
                                    <p class="mb-0">{{ $activeReservation->reservation_date->format('d M, Y') }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Phone:</strong>
                                    <p class="mb-0">{{ $activeReservation->customer_phone ?? '—' }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Email:</strong>
                                    <p class="mb-0">{{ $activeReservation->customer_email ?? '—' }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Available From:</strong>
                                    <p class="mb-0">{{ $activeReservation->available_from_date ? $activeReservation->available_from_date->format('d M, Y') : '—' }}</p>
                                </div>
                                @if($activeReservation->terms_conditions)
                                    <div class="col-12 mb-3">
                                        <strong>Terms & Conditions:</strong>
                                        <p class="mb-0" style="white-space: pre-wrap;">{{ $activeReservation->terms_conditions }}</p>
                                    </div>
                                @endif
                            @else
                                <div class="col-12">
                                    <p class="text-muted">No active reservation.</p>
                                </div>
                            @endif
                        </div>

                        <!-- Timestamps -->
                        <div class="row">
                            <div class="col-12">
                                <h4 class="border-bottom pb-2 mb-3">Record Information</h4>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Created At:</strong>
                                <p class="mb-0">{{ $car->created_at->format('d M, Y h:i A') }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Last Updated:</strong>
                                <p class="mb-0">{{ $car->updated_at->format('d M, Y h:i A') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function () {
            const $histBtn = $('#fleetStatusHistoryToggle');
            if (!$histBtn.length) {
                return;
            }
            $histBtn.on('click', function () {
                const expanded = $histBtn.attr('data-expanded') === '1';
                if (expanded) {
                    $('.fleet-status-history-row-extra').addClass('d-none');
                    $histBtn.attr('data-expanded', '0').text($histBtn.attr('data-label-expand'));
                } else {
                    $('.fleet-status-history-row-extra').removeClass('d-none');
                    $histBtn.attr('data-expanded', '1').text($histBtn.attr('data-label-collapse'));
                }
            });
        });
    </script>
@endsection
