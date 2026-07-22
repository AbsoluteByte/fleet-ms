@extends('layouts.admin', ['title' => 'Reports'])

@section('content')
    <section id="reports-page">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Reports</h4>
                        <div class="btn-group mt-25 mt-md-0" id="reportsExportGroup">
                            <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle" id="reportsExportDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fa fa-download mr-50"></i> Export
                            </button>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="reportsExportDropdown">
                                <button type="button" class="dropdown-item" id="reportsExportCsv">Export CSV</button>
                                <button type="button" class="dropdown-item" id="reportsExportPdf">Export PDF</button>
                            </div>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="card-body card-dashboard">
                            @include('alerts')

                            @php
                                $insuranceReportRequested = filled($insuranceFrom) || filled($insuranceTo);
                                $ticketReportRequested = filled($ticketCarId ?? null) || filled($ticketAt ?? null);
                                $activeMainTab = $ticketReportRequested
                                    ? 'ticket'
                                    : ($insuranceReportRequested ? 'insurance' : 'mots');
                                $ticketAtInputValue = filled($ticketAt ?? null)
                                    ? \Carbon\Carbon::parse($ticketAt)->format('Y-m-d\TH:i')
                                    : '';
                                $insuranceReportReady = filled($insuranceFrom) && filled($insuranceTo) && ! $insuranceDateError;
                            @endphp

                            <ul class="nav nav-pills mb-2" id="reports-tabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link {{ $activeMainTab === 'mots' ? 'active' : '' }}" id="reports-mots-tab" data-toggle="pill" href="#reports-mots-pane" role="tab" aria-controls="reports-mots-pane" aria-selected="{{ $activeMainTab === 'mots' ? 'true' : 'false' }}">MOTs</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ $activeMainTab === 'phvl' ? 'active' : '' }}" id="reports-phvl-tab" data-toggle="pill" href="#reports-phvl-pane" role="tab" aria-controls="reports-phvl-pane" aria-selected="{{ $activeMainTab === 'phvl' ? 'true' : 'false' }}">PHVL</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ $activeMainTab === 'insurance' ? 'active' : '' }}" id="reports-insurance-tab" data-toggle="pill" href="#reports-insurance-pane" role="tab" aria-controls="reports-insurance-pane" aria-selected="{{ $activeMainTab === 'insurance' ? 'true' : 'false' }}">Insurance</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ $activeMainTab === 'ticket' ? 'active' : '' }}" id="reports-ticket-tab" data-toggle="pill" href="#reports-ticket-pane" role="tab" aria-controls="reports-ticket-pane" aria-selected="{{ $activeMainTab === 'ticket' ? 'true' : 'false' }}">Ticket Tracking</a>
                                </li>
                            </ul>

                            <div class="tab-content" id="reports-tab-content">
                                <div class="tab-pane fade {{ $activeMainTab === 'mots' ? 'show active' : '' }}" id="reports-mots-pane" role="tabpanel" aria-labelledby="reports-mots-tab">
                                    <div class="table-responsive">
                                        <table id="reportsMotsTable" class="table datatable table-bordered table-striped">
                                            <thead>
                                            <tr>
                                                <th>Registration</th>
                                                <th>Company</th>
                                                <th>Model</th>
                                                <th>Color</th>
                                                <th>Status</th>
                                                <th>PHV Council</th>
                                                <th>Insurance Status</th>
                                                <th>MOT Expiry</th>
                                                <th>MOT Status</th>
                                                <th>Actions</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @forelse($cars as $car)
                                                @include('backend.reports.partials.report-row', ['car' => $car, 'reportType' => 'mot'])
                                            @empty
                                                <tr>
                                                    <td colspan="10" class="text-center text-muted py-4">No cars found.</td>
                                                </tr>
                                            @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="tab-pane fade {{ $activeMainTab === 'phvl' ? 'show active' : '' }}" id="reports-phvl-pane" role="tabpanel" aria-labelledby="reports-phvl-tab">
                                    <div class="table-responsive">
                                        <table id="reportsPhvlTable" class="table datatable table-bordered table-striped">
                                            <thead>
                                            <tr>
                                                <th>Registration</th>
                                                <th>Company</th>
                                                <th>Model</th>
                                                <th>Color</th>
                                                <th>Status</th>
                                                <th>PHV Council</th>
                                                <th>Insurance Status</th>
                                                <th>PHVL Expiry</th>
                                                <th>PHVL Status</th>
                                                <th>Actions</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @forelse($cars as $car)
                                                @include('backend.reports.partials.report-row', ['car' => $car, 'reportType' => 'phvl'])
                                            @empty
                                                <tr>
                                                    <td colspan="10" class="text-center text-muted py-4">No cars found.</td>
                                                </tr>
                                            @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="tab-pane fade {{ $activeMainTab === 'insurance' ? 'show active' : '' }}" id="reports-insurance-pane" role="tabpanel" aria-labelledby="reports-insurance-tab">
                                    <form method="GET" action="{{ route('reports.index') }}" class="mb-2" id="reportsInsuranceDateForm">
                                        <div class="form-row align-items-end">
                                            <div class="form-group col-md-3 col-lg-2 mb-1">
                                                <label class="small text-muted mb-25 d-block" for="insurance_from">From</label>
                                                <input type="date" name="insurance_from" id="insurance_from" class="form-control" value="{{ $insuranceFrom }}" required>
                                            </div>
                                            <div class="form-group col-md-3 col-lg-2 mb-1">
                                                <label class="small text-muted mb-25 d-block" for="insurance_to">To</label>
                                                <input type="date" name="insurance_to" id="insurance_to" class="form-control" value="{{ $insuranceTo }}" required>
                                            </div>
                                            <div class="form-group col-md-3 col-lg-2 mb-1">
                                                <label class="small text-muted mb-25 d-block" for="insurance_company_id">Company <span class="text-muted">(optional)</span></label>
                                                <select name="insurance_company_id" id="insurance_company_id" class="form-control">
                                                    <option value="">All companies</option>
                                                    @foreach($reportCompanies as $company)
                                                        <option value="{{ $company->id }}" @selected((int) ($insuranceCompanyId ?? 0) === (int) $company->id)>{{ $company->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group col-md-3 col-lg-3 mb-1">
                                                <label class="small text-muted mb-25 d-block" for="insurance_provider_id">Insurance provider <span class="text-muted">(optional)</span></label>
                                                <select name="insurance_provider_id" id="insurance_provider_id" class="form-control">
                                                    <option value="">All providers</option>
                                                    @foreach($reportInsuranceProviders as $provider)
                                                        <option value="{{ $provider->id }}" @selected((int) ($insuranceProviderId ?? 0) === (int) $provider->id)>
                                                            {{ $provider->provider_name }}@if($provider->isExpired()) (Expired)@endif
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group col-md-3 col-lg-2 mb-1">
                                                <button type="submit" class="btn btn-primary btn-block">Run report</button>
                                            </div>
                                        </div>
                                    </form>

                                    @if($insuranceDateError)
                                        <div class="alert alert-danger">{{ $insuranceDateError }}</div>
                                    @elseif(! $insuranceReportReady)
                                        <p class="text-muted mb-2">Select a date range and submit to view insurance results.</p>
                                    @else
                                        <p class="text-muted small mb-1">
                                            Showing insurance activity from {{ \Carbon\Carbon::parse($insuranceFrom)->format('d M, Y') }}
                                            to {{ \Carbon\Carbon::parse($insuranceTo)->format('d M, Y') }}.
                                            The <strong>Active on insurance</strong> tab lists currently active policies (date range not applied).
                                            @if($selectedInsuranceCompany)
                                                Company: <strong>{{ $selectedInsuranceCompany->name }}</strong>.
                                            @endif
                                            @if($selectedInsuranceProvider)
                                                Provider: <strong>{{ $selectedInsuranceProvider->provider_name }}@if($selectedInsuranceProvider->isExpired()) (Expired)@endif</strong>.
                                            @endif
                                        </p>

                                        <ul class="nav nav-pills mb-2" id="reports-insurance-subtabs" role="tablist">
                                            <li class="nav-item">
                                                <a class="nav-link active" id="reports-insurance-removed-tab" data-toggle="pill" href="#reports-insurance-removed-pane" role="tab">
                                                    Removed in range
                                                    <span class="badge badge-light ml-25">{{ $insuranceRemovedInRange->count() }}</span>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" id="reports-insurance-active-tab" data-toggle="pill" href="#reports-insurance-active-pane" role="tab">
                                                    Activated in range
                                                    <span class="badge badge-light ml-25">{{ $insuranceActivatedInRange->count() }}</span>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" id="reports-insurance-ended-tab" data-toggle="pill" href="#reports-insurance-ended-pane" role="tab">
                                                    Activated &amp; ended in range
                                                    <span class="badge badge-light ml-25">{{ $insuranceActivatedOrRemovedInRange->count() }}</span>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" id="reports-insurance-preexisting-tab" data-toggle="pill" href="#reports-insurance-preexisting-pane" role="tab">
                                                    Active on insurance
                                                    <span class="badge badge-light ml-25">{{ $insuranceActiveOnInsurance->count() }}</span>
                                                </a>
                                            </li>
                                        </ul>

                                        <div class="tab-content" id="reports-insurance-subtab-content">
                                            @php
                                                $insuranceSubTabs = [
                                                    'removed' => ['id' => 'reportsInsuranceRemovedTable', 'pane' => 'reports-insurance-removed-pane', 'tab' => 'reports-insurance-removed-tab', 'rows' => $insuranceRemovedInRange, 'active' => true],
                                                    'active' => ['id' => 'reportsInsuranceActiveTable', 'pane' => 'reports-insurance-active-pane', 'tab' => 'reports-insurance-active-tab', 'rows' => $insuranceActivatedInRange, 'active' => false],
                                                    'ended' => ['id' => 'reportsInsuranceEndedTable', 'pane' => 'reports-insurance-ended-pane', 'tab' => 'reports-insurance-ended-tab', 'rows' => $insuranceActivatedOrRemovedInRange, 'active' => false],
                                                    'preexisting' => ['id' => 'reportsInsurancePreExistingTable', 'pane' => 'reports-insurance-preexisting-pane', 'tab' => 'reports-insurance-preexisting-tab', 'rows' => $insuranceActiveOnInsurance, 'active' => false],
                                                ];
                                            @endphp

                                            @foreach($insuranceSubTabs as $subTab)
                                                <div class="tab-pane fade {{ $subTab['active'] ? 'show active' : '' }}" id="{{ $subTab['pane'] }}" role="tabpanel" aria-labelledby="{{ $subTab['tab'] }}">
                                                    <div class="table-responsive">
                                                        <table id="{{ $subTab['id'] }}" class="table datatable table-bordered table-striped reports-insurance-table">
                                                            <thead>
                                                            <tr>
                                                                <th>Registration</th>
                                                                <th>Company</th>
                                                                <th>Model</th>
                                                                <th>Provider</th>
                                                                <th>Start Date</th>
                                                                <th>Expiry</th>
                                                                <th>Cancelled Date</th>
                                                                <th>Current Status</th>
                                                                <th>Actions</th>
                                                            </tr>
                                                            </thead>
                                                            <tbody>
                                                            @forelse($subTab['rows'] as $row)
                                                                @include('backend.reports.partials.insurance-report-row', ['row' => $row])
                                                            @empty
                                                                <tr>
                                                                    <td colspan="9" class="text-center text-muted py-4">No records found for this category.</td>
                                                                </tr>
                                                            @endforelse
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <div class="tab-pane fade {{ $activeMainTab === 'ticket' ? 'show active' : '' }}" id="reports-ticket-pane" role="tabpanel" aria-labelledby="reports-ticket-tab">
                                    <form method="GET" action="{{ route('reports.index') }}" class="mb-2" id="reportsTicketTrackingForm">
                                        <div class="form-row align-items-end">
                                            <div class="form-group col-md-4 col-lg-3 mb-1">
                                                <label class="small text-muted mb-25 d-block" for="ticket_car_id">Vehicle</label>
                                                <select name="ticket_car_id" id="ticket_car_id" class="form-control select-search" required>
                                                    <option value="">— Select vehicle —</option>
                                                    @foreach($ticketCars as $ticketCar)
                                                        <option value="{{ $ticketCar->id }}" @selected((int) ($ticketCarId ?? 0) === (int) $ticketCar->id)>
                                                            {{ $ticketCar->registration }} — {{ $ticketCar->carModel->name ?? 'Unknown model' }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group col-md-4 col-lg-3 mb-1">
                                                <label class="small text-muted mb-25 d-block" for="ticket_at">Date &amp; time</label>
                                                <input type="datetime-local" name="ticket_at" id="ticket_at" class="form-control" value="{{ $ticketAtInputValue }}" required>
                                            </div>
                                            <div class="form-group col-md-3 col-lg-2 mb-1">
                                                <button type="submit" class="btn btn-primary btn-block">Next</button>
                                            </div>
                                        </div>
                                    </form>

                                    @if($ticketTrackingError ?? false)
                                        <div class="alert alert-danger">{{ $ticketTrackingError }}</div>
                                    @elseif(! ($ticketTrackingReady ?? false))
                                        <p class="text-muted mb-2">Select a vehicle and date &amp; time, then click Next to see who was driving.</p>
                                    @else
                                        @php
                                            $queriedCar = $ticketCars->firstWhere('id', $ticketCarId);
                                        @endphp
                                        <p class="text-muted small mb-1">
                                            Lookup for
                                            <strong>{{ $queriedCar?->registration ?? '—' }}</strong>
                                            at
                                            <strong>{{ $ticketTrackingQueriedAt?->format('d M Y, H:i') }}</strong>.
                                        </p>

                                        @if($ticketTrackingResult)
                                            @php
                                                $ticketDriver = $ticketTrackingResult->driver;
                                                $ticketAgreement = $ticketTrackingResult;
                                            @endphp
                                            <div class="card border">
                                                <div class="card-body">
                                                    <h5 class="card-title mb-1">Driver found</h5>
                                                    <dl class="row mb-0">
                                                        <dt class="col-sm-3">Vehicle</dt>
                                                        <dd class="col-sm-9">{{ $queriedCar?->registration }} — {{ $queriedCar?->carModel?->name ?? '—' }}</dd>

                                                        <dt class="col-sm-3">Driver</dt>
                                                        <dd class="col-sm-9">{{ $ticketDriver?->full_name ?: '—' }}</dd>

                                                        <dt class="col-sm-3">Phone</dt>
                                                        <dd class="col-sm-9">{{ $ticketDriver?->phone_number ?: '—' }}</dd>

                                                        <dt class="col-sm-3">Licence</dt>
                                                        <dd class="col-sm-9">{{ $ticketDriver?->driver_license_number ?: '—' }}</dd>

                                                        <dt class="col-sm-3">Agreement</dt>
                                                        <dd class="col-sm-9">
                                                            <a href="{{ route('agreements.show', $ticketAgreement) }}">Agreement #{{ $ticketAgreement->id }}</a>
                                                        </dd>

                                                        <dt class="col-sm-3">Hire period</dt>
                                                        <dd class="col-sm-9">
                                                            {{ $ticketAgreement->start_date?->format('d M Y, H:i') ?? '—' }}
                                                            to
                                                            {{ $ticketAgreement->effectiveAssignmentEndAt()->format('d M Y, H:i') }}
                                                        </dd>

                                                        <dt class="col-sm-3">Status</dt>
                                                        <dd class="col-sm-9">{{ $ticketAgreement->status?->name ?? '—' }}</dd>
                                                    </dl>
                                                </div>
                                            </div>
                                        @else
                                            <div class="alert alert-warning mb-0">
                                                No driver was assigned to this vehicle at the selected date and time.
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @php
        $filterCompanies = $cars->map(fn ($car) => $car->company->name ?? null)->filter()->unique()->sort()->values();
    @endphp

    <div class="cars-filter-backdrop" id="reportsFilterBackdrop"></div>

    <aside class="cars-filter-panel" id="reportsMotsFilterPanel" aria-hidden="true">
        <div class="cars-filter-panel__header">
            <h5 class="mb-0">Advanced Search — MOTs</h5>
            <button type="button" class="close reports-mots-filter-close" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="cars-filter-panel__body">
            <div class="form-group">
                <label for="reportsMotsFilterCompany">Company</label>
                <select id="reportsMotsFilterCompany" class="form-control">
                    <option value="">All Companies</option>
                    @foreach($filterCompanies as $company)
                        <option value="{{ $company }}">{{ $company }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group reports-expiring-month-group">
                <div class="reports-expiring-label-row d-flex justify-content-between align-items-center mb-1">
                    <label class="mb-0">MOT Expiring</label>
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-outline-secondary reports-month-picker-btn" id="reportsMotMonthPickerBtn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Pick month">
                            <i class="fa fa-calendar" aria-hidden="true"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" id="reportsMotMonthMenu" aria-labelledby="reportsMotMonthPickerBtn"></div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-6">
                        <label class="small text-muted mb-25 d-block" for="reportsMotExpiringFrom">From</label>
                        <input type="date" id="reportsMotExpiringFrom" class="form-control">
                    </div>
                    <div class="col-6">
                        <label class="small text-muted mb-25 d-block" for="reportsMotExpiringTo">To</label>
                        <input type="date" id="reportsMotExpiringTo" class="form-control">
                    </div>
                </div>
            </div>
            <div class="form-group reports-expiring-month-group">
                <div class="reports-expiring-label-row d-flex justify-content-between align-items-center mb-1">
                    <label class="mb-0">MOT Start</label>
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-outline-secondary reports-month-picker-btn" id="reportsMotStartMonthPickerBtn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Pick month">
                            <i class="fa fa-calendar" aria-hidden="true"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" id="reportsMotStartMonthMenu" aria-labelledby="reportsMotStartMonthPickerBtn"></div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-6">
                        <label class="small text-muted mb-25 d-block" for="reportsMotStartFrom">From</label>
                        <input type="date" id="reportsMotStartFrom" class="form-control">
                    </div>
                    <div class="col-6">
                        <label class="small text-muted mb-25 d-block" for="reportsMotStartTo">To</label>
                        <input type="date" id="reportsMotStartTo" class="form-control">
                    </div>
                </div>
                <small class="text-muted">Filters by MOT test date.</small>
            </div>
            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="reportsIncludeMissingMot">
                    <label class="custom-control-label" for="reportsIncludeMissingMot">Include cars with no MOT added yet</label>
                </div>
            </div>
            <button type="button" class="btn btn-primary btn-block mb-1" id="reportsMotsFilterApply">Apply</button>
            <button type="button" class="btn btn-outline-secondary btn-block" id="reportsMotsFilterReset">Reset Filters</button>
        </div>
    </aside>

    <aside class="cars-filter-panel" id="reportsPhvlFilterPanel" aria-hidden="true">
        <div class="cars-filter-panel__header">
            <h5 class="mb-0">Advanced Search — PHVL</h5>
            <button type="button" class="close reports-phvl-filter-close" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="cars-filter-panel__body">
            <div class="form-group">
                <label for="reportsPhvlFilterCompany">Company</label>
                <select id="reportsPhvlFilterCompany" class="form-control">
                    <option value="">All Companies</option>
                    @foreach($filterCompanies as $company)
                        <option value="{{ $company }}">{{ $company }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group reports-expiring-month-group">
                <div class="reports-expiring-label-row d-flex justify-content-between align-items-center mb-1">
                    <label class="mb-0">PHVL Expiring</label>
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-outline-secondary reports-month-picker-btn" id="reportsPhvlMonthPickerBtn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Pick month">
                            <i class="fa fa-calendar" aria-hidden="true"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" id="reportsPhvlMonthMenu" aria-labelledby="reportsPhvlMonthPickerBtn"></div>
                    </div>
                </div>
                <label class="small text-muted mb-25 d-block" for="reportsPhvlExpiringFrom">From</label>
                <input type="date" id="reportsPhvlExpiringFrom" class="form-control mb-1">
                <label class="small text-muted mb-25 d-block" for="reportsPhvlExpiringTo">To</label>
                <input type="date" id="reportsPhvlExpiringTo" class="form-control">
            </div>
            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="reportsIncludeMissingPhvl">
                    <label class="custom-control-label" for="reportsIncludeMissingPhvl">Include cars with no PHVL added yet</label>
                </div>
            </div>
            <button type="button" class="btn btn-primary btn-block mb-1" id="reportsPhvlFilterApply">Apply</button>
            <button type="button" class="btn btn-outline-secondary btn-block" id="reportsPhvlFilterReset">Reset Filters</button>
        </div>
    </aside>
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/tables/datatable/datatables.min.css') }}">
    <style>
        #reportsMotsTable_filter,
        #reportsPhvlTable_filter,
        .reports-insurance-table_filter {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        #reportsMotsTable_filter label,
        #reportsPhvlTable_filter label,
        .reports-insurance-table_filter label {
            display: flex;
            align-items: center;
            margin-bottom: 0;
        }

        #reportsMotsTable_filter input,
        #reportsPhvlTable_filter input,
        .reports-insurance-table_filter input {
            margin-left: .5rem;
        }

        .cars-filter-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
            margin-left: .5rem;
            margin-top: 1rem;
            border: 1px solid #d8d6de;
            border-radius: .25rem;
            color: #6e6b7b;
            background: #fff;
            cursor: pointer;
        }

        .cars-filter-button:hover,
        .cars-filter-button:focus {
            border-color: #7367f0;
            color: #7367f0;
            outline: none;
        }

        .cars-filter-backdrop {
            position: fixed;
            inset: 0;
            z-index: 1040;
            display: none;
            background: rgba(34, 41, 47, .35);
        }

        .cars-filter-backdrop.is-open {
            display: block;
        }

        .cars-filter-panel {
            position: fixed;
            top: 0;
            right: 0;
            z-index: 1050;
            width: 360px;
            max-width: 92vw;
            height: 100vh;
            background: #fff;
            box-shadow: -8px 0 24px rgba(34, 41, 47, .15);
            transform: translateX(100%);
            transition: transform .2s ease;
        }

        .cars-filter-panel.is-open {
            transform: translateX(0);
        }

        .cars-filter-panel__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #ebe9f1;
        }

        .cars-filter-panel__body {
            height: calc(100vh - 65px);
            padding: 1.25rem;
            overflow-y: auto;
        }

        .reports-mots-filter-close,
        .reports-phvl-filter-close {
            padding: 0.3rem 0.7rem;
        }

        .insurance-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
        }

        .insurance-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            display: inline-block;
        }

        .insurance-status-dot--active {
            background: #28c76f;
        }

        .insurance-status-dot--pending {
            background: #ff9f43;
        }

        .insurance-status-dot--inactive {
            background: #ea5455;
        }

        #reports-tabs .nav-link.active {
            background-color: #7367f0;
            color: #fff;
        }

        #reports-insurance-subtabs .nav-link.active {
            background-color: #7367f0;
            color: #fff;
        }

        #reports-insurance-subtabs .nav-link.active .badge-light {
            background-color: rgba(255, 255, 255, 0.9);
            color: #7367f0;
        }

        .reports-expiring-month-group {
            position: relative;
            overflow: visible;
        }

        .reports-month-picker-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.25rem;
            height: 2.25rem;
            padding: 0;
            line-height: 1;
        }

        .reports-month-picker-btn .fa {
            margin: 0;
            font-size: 0.95rem;
            line-height: 1;
        }

        .cars-filter-panel__body .reports-expiring-month-group .dropdown-menu {
            min-width: 11rem;
        }
    </style>
@endsection

@section('js')
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/pdfmake.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/vfs_fonts.js') }}"></script>
    @php
        $insuranceReportMeta = [
            'fromLabel' => ($insuranceReportReady ?? false) && filled($insuranceFrom) ? \Carbon\Carbon::parse($insuranceFrom)->format('d M, Y') : null,
            'toLabel' => ($insuranceReportReady ?? false) && filled($insuranceTo) ? \Carbon\Carbon::parse($insuranceTo)->format('d M, Y') : null,
            'company' => ($insuranceReportReady ?? false) && $selectedInsuranceCompany ? $selectedInsuranceCompany->name : null,
            'provider' => ($insuranceReportReady ?? false) && $selectedInsuranceProvider ? $selectedInsuranceProvider->provider_name : null,
        ];
    @endphp
    <script>
        $(document).ready(function () {
            let activeReportTab = @json($activeMainTab ?? 'mots');
            let activeInsuranceSubTab = 'removed';

            const insuranceReportMeta = @json($insuranceReportMeta);

            function setReportsExportVisible(isVisible) {
                $('#reportsExportGroup').toggle(isVisible);
            }

            if (activeReportTab === 'ticket') {
                setReportsExportVisible(false);
            }

            const motFilters = { company: '', from: '', to: '', startFrom: '', startTo: '', includeMissing: false };
            const phvlFilters = { company: '', from: '', to: '', includeMissing: false };

            function parseDateYmd(value) {
                if (!value) return null;
                const parts = value.split('-');
                if (parts.length !== 3) return null;
                const date = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
                return isNaN(date.getTime()) ? null : date;
            }

            function expiryInRange(expiryIso, fromStr, toStr) {
                if (!expiryIso) return false;
                const expiry = parseDateYmd(expiryIso);
                if (!expiry) return false;
                const from = parseDateYmd(fromStr);
                const to = parseDateYmd(toStr);
                if (from && expiry < from) return false;
                if (to && expiry > to) return false;
                return true;
            }

            function isExpiryFilterActive(filters) {
                return !!(filters.from || filters.to || filters.includeMissing);
            }

            function isStartFilterActive(filters) {
                return !!(filters.startFrom || filters.startTo);
            }

            function isAnyFilterActive(filters) {
                return !!filters.company || isExpiryFilterActive(filters) || isStartFilterActive(filters);
            }

            function reportRowNode(settings, dataIndex) {
                const aoData = settings.aoData[dataIndex];
                return aoData ? aoData.nTr : null;
            }

            function applyReportRowFilter(settings, dataIndex, tableApi, filters, expiryKey, missingKey) {
                if (!isAnyFilterActive(filters)) return true;
                const row = reportRowNode(settings, dataIndex);
                if (!row) return true;

                const company = row.getAttribute('data-company') || '';
                if (filters.company && company !== filters.company) return false;

                if (isStartFilterActive(filters)) {
                    const testIso = row.getAttribute('data-mot-test-date') || '';
                    if (!expiryInRange(testIso, filters.startFrom, filters.startTo)) {
                        return false;
                    }
                }

                if (!isExpiryFilterActive(filters)) return true;

                const missingAttr = missingKey === 'motMissing' ? 'data-mot-missing' : 'data-phv-missing';
                const expiryAttr = expiryKey === 'motExpiry' ? 'data-mot-expiry' : 'data-phv-expiry';
                const missing = row.getAttribute(missingAttr) === '1';
                const expiryIso = row.getAttribute(expiryAttr) || '';
                const hasDateRange = !!(filters.from || filters.to);

                if (filters.includeMissing && missing) return true;
                if (hasDateRange && expiryInRange(expiryIso, filters.from, filters.to)) return true;
                if (!hasDateRange && filters.includeMissing) return missing;
                return false;
            }

            const motsDataTable = $('#reportsMotsTable').DataTable({
                processing: true,
                responsive: true,
                order: [[7, 'asc']]
            });

            const phvlDataTable = $('#reportsPhvlTable').DataTable({
                processing: true,
                responsive: true,
                order: [[7, 'asc']]
            });

            const insuranceDataTables = {};
            const insuranceTableIds = {
                removed: 'reportsInsuranceRemovedTable',
                active: 'reportsInsuranceActiveTable',
                ended: 'reportsInsuranceEndedTable',
                preexisting: 'reportsInsurancePreExistingTable'
            };

            function initInsuranceDataTable(key) {
                if (insuranceDataTables[key]) {
                    return insuranceDataTables[key];
                }

                const tableId = insuranceTableIds[key];
                const $table = $('#' + tableId);
                if (!$table.length) {
                    return null;
                }

                insuranceDataTables[key] = $table.DataTable({
                    processing: true,
                    responsive: true,
                    order: [[4, 'asc']]
                });

                return insuranceDataTables[key];
            }

            function initAllInsuranceDataTables() {
                Object.keys(insuranceTableIds).forEach(function (key) {
                    initInsuranceDataTable(key);
                });
            }

            @if($insuranceReportReady ?? false)
            initAllInsuranceDataTables();
            @endif

            $('#reportsMotsTable_filter').append(
                '<button type="button" class="cars-filter-button" id="reportsMotsFilterOpen" title="Filter" aria-label="Filter"><i class="fa fa-filter"></i></button>'
            );
            $('#reportsPhvlTable_filter').append(
                '<button type="button" class="cars-filter-button" id="reportsPhvlFilterOpen" title="Filter" aria-label="Filter"><i class="fa fa-filter"></i></button>'
            );

            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                if (settings.nTable.id === 'reportsMotsTable') {
                    return applyReportRowFilter(settings, dataIndex, motsDataTable, motFilters, 'motExpiry', 'motMissing');
                }
                if (settings.nTable.id === 'reportsPhvlTable') {
                    return applyReportRowFilter(settings, dataIndex, phvlDataTable, phvlFilters, 'phvExpiry', 'phvMissing');
                }
                return true;
            });

            function setFilterPanelOpen(panelSelector, isOpen) {
                $(panelSelector).toggleClass('is-open', isOpen).attr('aria-hidden', isOpen ? 'false' : 'true');
                $('#reportsFilterBackdrop').toggleClass('is-open', isOpen);
            }

            function closeAllFilterPanels() {
                setFilterPanelOpen('#reportsMotsFilterPanel', false);
                setFilterPanelOpen('#reportsPhvlFilterPanel', false);
            }

            $('#reportsMotsFilterOpen').on('click', function () {
                closeAllFilterPanels();
                setFilterPanelOpen('#reportsMotsFilterPanel', true);
            });
            $('#reportsPhvlFilterOpen').on('click', function () {
                closeAllFilterPanels();
                setFilterPanelOpen('#reportsPhvlFilterPanel', true);
            });

            $('.reports-mots-filter-close, .reports-phvl-filter-close, #reportsFilterBackdrop').on('click', function () {
                closeAllFilterPanels();
            });

            $('#reportsMotsFilterApply').on('click', function () {
                motFilters.company = document.getElementById('reportsMotsFilterCompany').value;
                motFilters.from = document.getElementById('reportsMotExpiringFrom').value;
                motFilters.to = document.getElementById('reportsMotExpiringTo').value;
                motFilters.startFrom = document.getElementById('reportsMotStartFrom').value;
                motFilters.startTo = document.getElementById('reportsMotStartTo').value;
                motFilters.includeMissing = document.getElementById('reportsIncludeMissingMot').checked;
                closeAllFilterPanels();
                motsDataTable.draw();
            });

            $('#reportsPhvlFilterApply').on('click', function () {
                phvlFilters.company = document.getElementById('reportsPhvlFilterCompany').value;
                phvlFilters.from = document.getElementById('reportsPhvlExpiringFrom').value;
                phvlFilters.to = document.getElementById('reportsPhvlExpiringTo').value;
                phvlFilters.includeMissing = document.getElementById('reportsIncludeMissingPhvl').checked;
                closeAllFilterPanels();
                phvlDataTable.draw();
            });

            $('#reportsMotsFilterReset').on('click', function () {
                document.getElementById('reportsMotsFilterCompany').value = '';
                document.getElementById('reportsMotExpiringFrom').value = '';
                document.getElementById('reportsMotExpiringTo').value = '';
                document.getElementById('reportsMotStartFrom').value = '';
                document.getElementById('reportsMotStartTo').value = '';
                document.getElementById('reportsIncludeMissingMot').checked = false;
                motFilters.company = '';
                motFilters.from = '';
                motFilters.to = '';
                motFilters.startFrom = '';
                motFilters.startTo = '';
                motFilters.includeMissing = false;
                motsDataTable.draw();
            });

            $('#reportsPhvlFilterReset').on('click', function () {
                document.getElementById('reportsPhvlFilterCompany').value = '';
                document.getElementById('reportsPhvlExpiringFrom').value = '';
                document.getElementById('reportsPhvlExpiringTo').value = '';
                document.getElementById('reportsIncludeMissingPhvl').checked = false;
                phvlFilters.company = '';
                phvlFilters.from = '';
                phvlFilters.to = '';
                phvlFilters.includeMissing = false;
                phvlDataTable.draw();
            });

            $('a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
                const href = e.target.getAttribute('href') || '';

                if (href.indexOf('#reports-insurance-') === 0 && href !== '#reports-insurance-pane') {
                    if (href === '#reports-insurance-removed-pane') {
                        activeInsuranceSubTab = 'removed';
                    } else if (href === '#reports-insurance-active-pane') {
                        activeInsuranceSubTab = 'active';
                    } else if (href === '#reports-insurance-ended-pane') {
                        activeInsuranceSubTab = 'ended';
                    } else if (href === '#reports-insurance-preexisting-pane') {
                        activeInsuranceSubTab = 'preexisting';
                    }

                    const table = initInsuranceDataTable(activeInsuranceSubTab);
                    if (table) {
                        table.columns.adjust().responsive.recalc();
                    }
                    return;
                }

                if (href === '#reports-phvl-pane') {
                    activeReportTab = 'phvl';
                    setReportsExportVisible(true);
                } else if (href === '#reports-insurance-pane') {
                    activeReportTab = 'insurance';
                    setReportsExportVisible(true);
                    initAllInsuranceDataTables();
                    const table = initInsuranceDataTable(activeInsuranceSubTab);
                    if (table) {
                        table.columns.adjust().responsive.recalc();
                    }
                } else if (href === '#reports-ticket-pane') {
                    activeReportTab = 'ticket';
                    setReportsExportVisible(false);
                } else {
                    activeReportTab = 'mots';
                    setReportsExportVisible(true);
                }

                closeAllFilterPanels();
                if (activeReportTab === 'phvl') {
                    phvlDataTable.columns.adjust().responsive.recalc();
                } else if (activeReportTab === 'mots') {
                    motsDataTable.columns.adjust().responsive.recalc();
                }
            });

            const reportExportHeaders = [
                'Registration', 'Company', 'Model', 'Color', 'Status',
                'PHV Council', 'Insurance Status'
            ];

            const insuranceExportHeaders = [
                'Registration', 'Company', 'Model', 'Provider',
                'Start Date', 'Expiry', 'Cancelled Date', 'Current Status'
            ];

            function formatYmd(date) {
                const y = date.getFullYear();
                const m = String(date.getMonth() + 1).padStart(2, '0');
                const d = String(date.getDate()).padStart(2, '0');
                return y + '-' + m + '-' + d;
            }

            function getUpcomingFourMonths() {
                const months = [];
                const now = new Date();
                for (let i = 0; i < 4; i++) {
                    const start = new Date(now.getFullYear(), now.getMonth() + i, 1);
                    const end = new Date(now.getFullYear(), now.getMonth() + i + 1, 0);
                    months.push({
                        label: start.toLocaleString('en-GB', { month: 'long', year: 'numeric' }),
                        from: formatYmd(start),
                        to: formatYmd(end)
                    });
                }
                return months;
            }

            function closeDropdownMenu(menuEl) {
                const $dropdown = $(menuEl).closest('.dropdown');
                $dropdown.removeClass('show');
                $(menuEl).removeClass('show');
                $dropdown.find('[data-toggle="dropdown"]').attr('aria-expanded', 'false');
            }

            function buildMonthPickerMenu(menuEl, fromInputId, toInputId, filtersObj, tableApi, fromKey, toKey) {
                fromKey = fromKey || 'from';
                toKey = toKey || 'to';
                menuEl.innerHTML = '';
                getUpcomingFourMonths().forEach(function (month) {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'dropdown-item';
                    btn.textContent = month.label;
                    btn.addEventListener('click', function () {
                        document.getElementById(fromInputId).value = month.from;
                        document.getElementById(toInputId).value = month.to;
                        filtersObj[fromKey] = month.from;
                        filtersObj[toKey] = month.to;
                        tableApi.draw();
                        closeDropdownMenu(menuEl);
                    });
                    menuEl.appendChild(btn);
                });
            }

            buildMonthPickerMenu(
                document.getElementById('reportsMotMonthMenu'),
                'reportsMotExpiringFrom',
                'reportsMotExpiringTo',
                motFilters,
                motsDataTable
            );
            buildMonthPickerMenu(
                document.getElementById('reportsMotStartMonthMenu'),
                'reportsMotStartFrom',
                'reportsMotStartTo',
                motFilters,
                motsDataTable,
                'startFrom',
                'startTo'
            );
            buildMonthPickerMenu(
                document.getElementById('reportsPhvlMonthMenu'),
                'reportsPhvlExpiringFrom',
                'reportsPhvlExpiringTo',
                phvlFilters,
                phvlDataTable
            );

            function csvEscape(value) {
                const str = String(value ?? '').replace(/"/g, '""').trim();
                return /[",\n\r]/.test(str) ? '"' + str + '"' : str;
            }

            function downloadCsv(filename, lines) {
                const blob = new Blob(['\uFEFF' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = filename;
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            }

            function collectExportRows(tableApi, maxCells) {
                const limit = maxCells || 9;
                const rows = [];
                tableApi.rows({ search: 'applied', order: 'applied' }).every(function () {
                    const node = this.node();
                    if (!node) return;
                    const cells = node.querySelectorAll('td');
                    if (cells.length < limit) return;
                    const row = [];
                    for (let i = 0; i < limit; i++) {
                        row.push(cells[i].innerText.replace(/\s+/g, ' ').trim());
                    }
                    rows.push(row);
                });
                return rows;
            }

            function buildInsuranceExportMeta(subTabLabel) {
                const lines = [
                    'Report type: ' + subTabLabel,
                ];

                if (insuranceReportMeta.fromLabel && insuranceReportMeta.toLabel) {
                    lines.push('Period: ' + insuranceReportMeta.fromLabel + ' to ' + insuranceReportMeta.toLabel);
                }

                lines.push('Company: ' + (insuranceReportMeta.company || 'All companies'));
                lines.push('Insurance provider: ' + (insuranceReportMeta.provider || 'All providers'));

                return {
                    title: 'Insurance Report — ' + subTabLabel,
                    lines: lines,
                };
            }

            function exportTableCsv(tableApi, expiryLabel, statusLabel, filePrefix, reportTitle, options) {
                options = options || {};
                const headers = options.headers || reportExportHeaders.concat([expiryLabel, statusLabel]);
                const lines = [];

                if (reportTitle) {
                    lines.push(csvEscape(reportTitle));
                }

                (options.subtitleLines || []).forEach(function (line) {
                    lines.push(csvEscape(line));
                });

                if (reportTitle || (options.subtitleLines && options.subtitleLines.length)) {
                    lines.push('');
                }

                lines.push(headers.map(csvEscape).join(','));
                const bodyRows = collectExportRows(tableApi, options.maxCells || 9);

                bodyRows.forEach(function (row) {
                    lines.push(row.map(csvEscape).join(','));
                });

                if (bodyRows.length === 0) {
                    alert('No records to export. Adjust your search or filters and try again.');
                    return;
                }

                downloadCsv(filePrefix + '-' + new Date().toISOString().slice(0, 10) + '.csv', lines);
            }

            function exportTablePdf(tableApi, expiryLabel, statusLabel, filePrefix, reportTitle, options) {
                options = options || {};
                const headers = options.headers || reportExportHeaders.concat([expiryLabel, statusLabel]);
                const bodyRows = collectExportRows(tableApi, options.maxCells || 9);

                if (bodyRows.length === 0) {
                    alert('No records to export. Adjust your search or filters and try again.');
                    return;
                }

                if (typeof pdfMake === 'undefined') {
                    alert('PDF export is not available. Please refresh the page and try again.');
                    return;
                }

                const tableBody = [
                    headers.map(function (h) {
                        return { text: h, style: 'tableHeader' };
                    })
                ];
                bodyRows.forEach(function (row) {
                    tableBody.push(row.map(function (cell) {
                        return { text: cell, style: 'tableCell' };
                    }));
                });

                const doc = {
                    pageSize: 'A4',
                    pageOrientation: 'landscape',
                    pageMargins: [24, 48, 24, 32],
                    content: [
                        {
                            text: reportTitle + ' — ' + new Date().toISOString().slice(0, 10),
                            style: 'title',
                            margin: [0, 0, 0, 4]
                        },
                        ...(options.subtitleLines || []).map(function (line) {
                            return {
                                text: line,
                                style: 'subtitle',
                                margin: [0, 0, 0, 2]
                            };
                        }),
                        {
                            text: '',
                            margin: [0, 0, 0, 8]
                        },
                        {
                            table: {
                                headerRows: 1,
                                widths: headers.map(function () { return '*'; }),
                                body: tableBody
                            },
                            layout: 'lightHorizontalLines'
                        }
                    ],
                    styles: {
                        title: { fontSize: 14, bold: true },
                        subtitle: { fontSize: 9, color: '#5e5873' },
                        tableHeader: { fontSize: 8, bold: true, fillColor: '#f3f2f7' },
                        tableCell: { fontSize: 7 }
                    },
                    defaultStyle: { fontSize: 8 }
                };

                pdfMake.createPdf(doc).download(filePrefix + '-' + new Date().toISOString().slice(0, 10) + '.pdf');
            }

            function runActiveTabExport(exportFn) {
                if (activeReportTab === 'ticket') {
                    return;
                }

                if (activeReportTab === 'insurance') {
                    const table = initInsuranceDataTable(activeInsuranceSubTab);
                    if (!table) {
                        alert('Run an insurance report first, then export from the active sub-tab.');
                        return;
                    }

                    const subTabLabels = {
                        removed: 'Removed in range',
                        active: 'Activated in range',
                        ended: 'Activated and ended in range',
                        preexisting: 'Active on insurance'
                    };
                    const label = subTabLabels[activeInsuranceSubTab] || 'Insurance';
                    const filePrefix = 'insurance-' + activeInsuranceSubTab + '-report';
                    const exportMeta = buildInsuranceExportMeta(label);

                    exportFn(table, null, null, filePrefix, exportMeta.title, {
                        headers: insuranceExportHeaders,
                        maxCells: 8,
                        subtitleLines: exportMeta.lines,
                    });
                    return;
                }

                if (activeReportTab === 'phvl') {
                    exportFn(phvlDataTable, 'PHVL Expiry', 'PHVL Status', 'phvl-report', 'PHVL Report');
                } else {
                    exportFn(motsDataTable, 'MOT Expiry', 'MOT Status', 'mot-report', 'MOT Report');
                }
            }

            $('#reportsExportCsv').on('click', function (e) {
                e.preventDefault();
                runActiveTabExport(function (tableApi, expiryLabel, statusLabel, filePrefix, reportTitle, options) {
                    exportTableCsv(tableApi, expiryLabel, statusLabel, filePrefix, reportTitle, options);
                });
            });

            $('#reportsExportPdf').on('click', function (e) {
                e.preventDefault();
                runActiveTabExport(function (tableApi, expiryLabel, statusLabel, filePrefix, reportTitle, options) {
                    exportTablePdf(tableApi, expiryLabel, statusLabel, filePrefix, reportTitle, options);
                });
            });
        });
    </script>
@endsection
