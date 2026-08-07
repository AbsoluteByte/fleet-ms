@extends('layouts.admin', ['title' => 'Cars'])
@section('content')
    <section id="basic-datatable">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ $plural }}</h4>
                        <div class="float-right">
                            <button type="button" class="btn btn-outline-primary btn-sm cars-quick-filter" data-quick-filter="available_by_phv">Available by PHV</button>
                            <button type="button" class="btn btn-outline-primary btn-sm cars-quick-filter" data-quick-filter="on_rent">On Rent</button>
                            <button type="button" class="btn btn-outline-primary btn-sm cars-quick-filter" data-quick-filter="preparation_for_phvl">PHVL Preparation</button>
                            <button type="button" class="btn btn-outline-primary btn-sm cars-quick-filter" data-quick-filter="damaged">Damaged</button>
                            <button type="button" class="btn btn-outline-primary btn-sm cars-quick-filter" data-quick-filter="non_compliant">Non-Compliant</button>
                            <button type="button" class="btn btn-outline-primary btn-sm cars-quick-filter" data-quick-filter="written_off">Written off</button>
                            <button type="button" class="btn btn-outline-primary btn-sm cars-quick-filter" data-quick-filter="stolen">Stolen</button>
                            <button type="button" class="btn btn-outline-primary btn-sm cars-quick-filter" data-quick-filter="for_sale">For sale</button>
                            <button type="button" class="btn btn-outline-primary btn-sm cars-quick-filter" data-quick-filter="sold">Sold</button>
                            <a class="btn btn-primary btn-sm" href="{{ route($url . 'create') }}"><i class="fa fa-plus"></i> Add {{ $singular }}</a>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="card-body card-dashboard">
                            @php
                                $deletedCarRegistration = session('car_deleted_registration');
                                $successMessage = session('success');
                                $genericCarDeleteMessage = $successMessage === 'Car deleted successfully.';
                            @endphp
                            @if ($deletedCarRegistration)
                                <div class="alert alert-success" id="carDeletedAlert">
                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                    Car {{ $deletedCarRegistration }} deleted successfully.
                                </div>
                            @elseif ($genericCarDeleteMessage)
                                <div class="alert alert-success" id="carDeletedAlert">
                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                    Car deleted successfully.
                                </div>
                            @else
                                @include('alerts')
                            @endif
                            <div class="cars-table-toolbar" id="carsTableToolbar">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle" id="carsExportDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fa fa-download mr-50"></i> Export
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="carsExportDropdown">
                                        <button type="button" class="dropdown-item" id="carsExportCsv">Export CSV</button>
                                        <button type="button" class="dropdown-item" id="carsExportPdf">Export PDF</button>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table id="dataTable" class="table datatable table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th>Registration</th>
                                        <th>Company</th>
                                        <th>Model</th>
                                        <th>Color</th>
                                        <th>Status</th>
                                        <th>PHV Council</th>
                                        <th>Insurance Status</th>
                                        <th class="cars-available-from-col">Available From</th>
                                        <th>Actions</th>
                                        <th>VIN</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($cars as $car)
                                        @php
                                            $carStatusLabel = $car->fleetStatusLabel();
                                            $isAvailableByPhv = $car->isSelectableForAgreement($rentedCarIds ?? []);
                                            $isAwaitingPhv = $car->phvs->isEmpty();
                                            $isAwaitingLogBook = $car->log_book_applied && $car->v5DocumentFileNames() === [];
                                            $latestInsurance = $car->insurances
                                                ->sortByDesc(fn (\App\Models\CarInsurance $i) => [optional($i->created_at)->timestamp ?? 0, $i->id])
                                                ->first();
                                            $latestInsuranceStatusName = trim((string) optional(optional($latestInsurance)->status)->name);
                                            $insuranceStatusLabel = strcasecmp($latestInsuranceStatusName, 'Applied') === 0
                                                ? 'Applied'
                                                : (strcasecmp($latestInsuranceStatusName, 'Active') === 0 ? 'Active' : 'Inactive');
                                            $phvCounselLabel = in_array($car->fleet_status ?? '', [
                                                \App\Models\Car::FLEET_STATUS_WRITTEN_OFF,
                                                \App\Models\Car::FLEET_STATUS_STOLEN,
                                                'for_sale',
                                                \App\Models\Car::FLEET_STATUS_SOLD,
                                            ], true)
                                                ? '—'
                                                : ($car->latestPhvCounselName() ?? '—');
                                            $motExpiry = $car->latestMot()?->expiry_date;
                                            $motExpiryIso = $motExpiry ? $motExpiry->format('Y-m-d') : '';
                                            $roadTaxExpiry = $car->latestRoadTax()?->expiryDate();
                                            $roadTaxExpiryIso = $roadTaxExpiry ? $roadTaxExpiry->format('Y-m-d') : '';
                                            $latestPhv = $car->phvs
                                                ->sortByDesc(fn (\App\Models\CarPhv $p) => [optional($p->expiry_date)->timestamp ?? 0, $p->id])
                                                ->first();
                                            $phvExpiry = $latestPhv?->expiry_date;
                                            $phvExpiryIso = $phvExpiry ? $phvExpiry->format('Y-m-d') : '';
                                            $terminationAgreement = $car->terminationNoticeAgreement();
                                            $terminationNoticeIso = optional($terminationAgreement?->termination_notice_date)->format('Y-m-d') ?? '';
                                            $terminationAvailableFromIso = optional($terminationAgreement?->termination_available_from_date)->format('Y-m-d') ?? '';
                                        @endphp
                                        <tr
                                            data-company="{{ $car->company->name }}"
                                            data-model="{{ $car->carModel->name }}"
                                            data-color="{{ $car->color }}"
                                            data-car-status="{{ $carStatusLabel }}"
                                            data-fleet-status="{{ $car->fleet_status ?? 'available_for_rent' }}"
                                            data-available-by-phv="{{ $isAvailableByPhv ? '1' : '0' }}"
                                            data-awaiting-phv="{{ $isAwaitingPhv ? '1' : '0' }}"
                                            data-awaiting-log-book="{{ $isAwaitingLogBook ? '1' : '0' }}"
                                            data-council="{{ $phvCounselLabel }}"
                                            data-insurance-status="{{ $insuranceStatusLabel }}"
                                            data-mot-expiry="{{ $motExpiryIso }}"
                                            data-mot-missing="{{ $motExpiryIso === '' ? '1' : '0' }}"
                                            data-road-tax-expiry="{{ $roadTaxExpiryIso }}"
                                            data-road-tax-missing="{{ $roadTaxExpiryIso === '' ? '1' : '0' }}"
                                            data-phv-expiry="{{ $phvExpiryIso }}"
                                            data-phv-missing="{{ $phvExpiryIso === '' ? '1' : '0' }}"
                                            data-tracker-installed="{{ $car->tracker_installed ? '1' : '0' }}"
                                            data-tracker-status="{{ $car->tracker_installed ? ($car->tracker_status ?? '') : '' }}"
                                            data-dashcam-installed="{{ $car->dashcam_installed ? '1' : '0' }}"
                                            data-dashcam-status="{{ $car->dashcam_installed ? ($car->dashcam_status ?? '') : '' }}"
                                            data-tag-installed="{{ $car->tag_installed ? '1' : '0' }}"
                                            data-tag-status="{{ $car->tag_installed ? ($car->tag_status ?? '') : '' }}"
                                            data-termination-notice-date="{{ $terminationNoticeIso }}"
                                            data-termination-available-from="{{ $terminationAvailableFromIso }}"
                                            data-manufacture-year="{{ $car->manufacture_year }}"
                                        >
                                            <td>
                                                <strong>{{ $car->registration ?: '—' }}</strong>
                                            </td>
                                            <td>{{ $car->company->name }}</td>
                                            <td>{{ $car->carModel->name }}</td>
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
                                            <td class="cars-available-from-col">
                                                {{ $terminationAvailableFromIso ? \Carbon\Carbon::parse($terminationAvailableFromIso)->format('d M Y') : '—' }}
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('cars.show', $car) }}"
                                                       class="btn btn-sm btn-outline-info js-action-tooltip"
                                                       data-toggle="tooltip" data-placement="top"
                                                       title="View Car" aria-label="View Car">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('cars.edit', $car) }}"
                                                       class="btn btn-sm btn-outline-warning js-action-tooltip"
                                                       data-toggle="tooltip" data-placement="top"
                                                       title="Edit Car" aria-label="Edit Car">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <span class="car-notifications-wrap" data-registration="{{ $car->registration }}">
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-primary car-notifications-btn js-action-tooltip"
                                                                data-notifications-url="{{ route('cars.notifications', $car) }}"
                                                                data-registration="{{ $car->registration }}"
                                                                data-toggle="tooltip" data-placement="top"
                                                                title="View Car Notifications"
                                                                aria-label="View Car Notifications">
                                                            <i class="fa fa-bell"></i>
                                                        </button>
                                                    </span>
                                                    <form action="{{ route('cars.destroy', $car) }}" method="POST" style="display: inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger js-action-tooltip"
                                                                data-toggle="tooltip" data-placement="top"
                                                                title="Delete Car" aria-label="Delete Car"
                                                                data-car-registration="{{ $car->registration }}"
                                                                onclick="if (!confirm('Are you sure?')) { return false; } try { sessionStorage.setItem('fleetiq_deleted_car_registration', this.dataset.carRegistration || ''); } catch (e) {} return true;">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                            <td>{{ $car->vin }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center text-muted py-4">
                                                <i class="fa fa-car fa-3x mb-3"></i>
                                                <br>
                                                No cars found. <a href="{{ route('cars.create') }}">Add your first car</a>
                                            </td>
                                        </tr>
                                    @endforelse
                                    </tbody>

                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @php
        $filterCompanies = $cars->map(fn ($car) => $car->company->name ?? null)->filter()->unique()->sort()->values();
        $filterModels = $cars->map(fn ($car) => $car->carModel->name ?? null)->filter()->unique()->sort()->values();
        $filterColors = $cars->pluck('color')->filter()->unique()->sort()->values();
        $filterCouncils = $cars->map(fn ($car) => $car->latestPhvCounselName())->filter()->unique()->sort()->values();
        $filterStatuses = \App\Models\Car::fleetStatusLabels();
    @endphp
    <div class="cars-filter-backdrop" id="carsFilterBackdrop"></div>
    <aside class="cars-filter-panel" id="carsFilterPanel" aria-hidden="true">
        <div class="cars-filter-panel__header">
            <h5 class="mb-0">Advanced Search</h5>
            <button type="button" class="close" id="carsFilterClose" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="cars-filter-panel__body">
            <div class="form-group">
                <label class="d-block mb-50">Log book</label>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="carsFilterAwaitingLogBook">
                    <label class="form-check-label" for="carsFilterAwaitingLogBook">Awaiting log book</label>
                </div>
            </div>

            <div class="form-group">
                <label for="carsFilterCompany">Company</label>
                <select id="carsFilterCompany" class="form-control cars-advanced-filter" data-filter-key="company">
                    <option value="">All Companies</option>
                    @foreach($filterCompanies as $company)
                        <option value="{{ $company }}">{{ $company }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="carsFilterCouncil">Council</label>
                <select id="carsFilterCouncil" class="form-control cars-advanced-filter" data-filter-key="council">
                    <option value="">All Councils</option>
                    @foreach($filterCouncils as $council)
                        <option value="{{ $council }}">{{ $council }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="carsFilterInsurance">Insurance Status</label>
                <select id="carsFilterInsurance" class="form-control cars-advanced-filter" data-filter-key="insuranceStatus">
                    <option value="">All Insurance Statuses</option>
                    <option value="Active">Active</option>
                    <option value="Applied">Applied</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>

            <div class="form-group">
                <label for="carsFilterStatus">Car Status</label>
                <select id="carsFilterStatus" class="form-control cars-advanced-filter" data-filter-key="carStatus">
                    <option value="">All Car Statuses</option>
                    @foreach($filterStatuses as $statusKey => $statusLabel)
                        <option value="{{ $statusKey }}">{{ $statusLabel }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="d-block mb-50">Termination Notice</label>
                <label class="small text-muted mb-25 d-block">Notice date between</label>
                <div class="form-row">
                    <div class="col-6">
                        <label class="small text-muted mb-25 d-block" for="carsTerminationNoticeFrom">From</label>
                        <input type="date" id="carsTerminationNoticeFrom" class="form-control cars-termination-notice-filter">
                    </div>
                    <div class="col-6">
                        <label class="small text-muted mb-25 d-block" for="carsTerminationNoticeTo">To</label>
                        <input type="date" id="carsTerminationNoticeTo" class="form-control cars-termination-notice-filter">
                    </div>
                </div>
                <small class="text-muted d-block mt-50">Shows cars whose Active/Swap agreement has a termination notice in this range.</small>
            </div>

            <div class="form-group">
                <label class="d-block mb-50">Manufacture Year</label>
                <label class="small text-muted mb-25 d-block">Year between</label>
                <div class="form-row">
                    <div class="col-6">
                        <label class="small text-muted mb-25 d-block" for="carsManufactureYearFrom">From</label>
                        <input type="text" id="carsManufactureYearFrom" class="form-control cars-manufacture-year-filter cars-manufacture-year-from" inputmode="numeric" maxlength="4" placeholder="e.g. 2018" autocomplete="off">
                    </div>
                    <div class="col-6">
                        <label class="small text-muted mb-25 d-block" for="carsManufactureYearTo">To</label>
                        <input type="text" id="carsManufactureYearTo" class="form-control cars-manufacture-year-filter cars-manufacture-year-to" inputmode="numeric" maxlength="4" placeholder="e.g. 2022" autocomplete="off">
                    </div>
                </div>
                <small class="text-muted d-block mt-50">Shows cars whose manufacture year falls within this range (inclusive).</small>
            </div>

            <div class="form-group">
                <label for="carsFilterModel">Make/Model</label>
                <select id="carsFilterModel" class="form-control cars-advanced-filter" data-filter-key="model">
                    <option value="">All Models</option>
                    @foreach($filterModels as $model)
                        <option value="{{ $model }}">{{ $model }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="carsFilterColor">Color</label>
                <select id="carsFilterColor" class="form-control cars-advanced-filter" data-filter-key="color">
                    <option value="">All Colors</option>
                    @foreach($filterColors as $color)
                        <option value="{{ $color }}">{{ $color }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="carsFilterTrackerInstalled">Tracker</label>
                <select id="carsFilterTrackerInstalled" class="form-control cars-advanced-filter" data-filter-key="trackerInstalled">
                    <option value="">All</option>
                    <option value="1">Installed</option>
                    <option value="0">Uninstalled</option>
                </select>
            </div>

            <div class="form-group">
                <label for="carsFilterTrackerStatus">Tracker status</label>
                <select id="carsFilterTrackerStatus" class="form-control cars-advanced-filter" data-filter-key="trackerStatus">
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="form-group">
                <label for="carsFilterDashcamInstalled">Dashcam</label>
                <select id="carsFilterDashcamInstalled" class="form-control cars-advanced-filter" data-filter-key="dashcamInstalled">
                    <option value="">All</option>
                    <option value="1">Installed</option>
                    <option value="0">Uninstalled</option>
                </select>
            </div>

            <div class="form-group">
                <label for="carsFilterDashcamStatus">Dashcam status</label>
                <select id="carsFilterDashcamStatus" class="form-control cars-advanced-filter" data-filter-key="dashcamStatus">
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="form-group">
                <label for="carsFilterTagInstalled">Tag</label>
                <select id="carsFilterTagInstalled" class="form-control cars-advanced-filter" data-filter-key="tagInstalled">
                    <option value="">All</option>
                    <option value="1">Installed</option>
                    <option value="0">Uninstalled</option>
                </select>
            </div>

            <div class="form-group">
                <label for="carsFilterTagStatus">Tag status</label>
                <select id="carsFilterTagStatus" class="form-control cars-advanced-filter" data-filter-key="tagStatus">
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="form-group reports-expiring-month-group">
                <div class="reports-expiring-label-row d-flex justify-content-between align-items-center mb-1">
                    <label class="mb-0">MOT expiring in</label>
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-outline-secondary reports-month-picker-btn" id="carsMotMonthPickerBtn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Pick month">
                            <i class="fa fa-calendar" aria-hidden="true"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" id="carsMotMonthMenu" aria-labelledby="carsMotMonthPickerBtn"></div>
                    </div>
                </div>
                <div class="form-row cars-expiry-date-row">
                    <div class="col-6">
                        <label class="small text-muted mb-25 d-block" for="carsMotExpiringFrom">From</label>
                        <input type="date" id="carsMotExpiringFrom" class="form-control cars-expiry-filter">
                    </div>
                    <div class="col-6">
                        <label class="small text-muted mb-25 d-block" for="carsMotExpiringTo">To</label>
                        <input type="date" id="carsMotExpiringTo" class="form-control cars-expiry-filter">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input cars-expiry-filter" id="carsIncludeMissingMot">
                    <label class="custom-control-label" for="carsIncludeMissingMot">Include cars with no MOT added yet</label>
                </div>
            </div>

            <div class="form-group reports-expiring-month-group">
                <div class="reports-expiring-label-row d-flex justify-content-between align-items-center mb-1">
                    <label class="mb-0">Road tax expiring in</label>
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-outline-secondary reports-month-picker-btn" id="carsRoadTaxMonthPickerBtn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Pick month">
                            <i class="fa fa-calendar" aria-hidden="true"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" id="carsRoadTaxMonthMenu" aria-labelledby="carsRoadTaxMonthPickerBtn"></div>
                    </div>
                </div>
                <div class="form-row cars-expiry-date-row">
                    <div class="col-6">
                        <label class="small text-muted mb-25 d-block" for="carsRoadTaxExpiringFrom">From</label>
                        <input type="date" id="carsRoadTaxExpiringFrom" class="form-control cars-expiry-filter">
                    </div>
                    <div class="col-6">
                        <label class="small text-muted mb-25 d-block" for="carsRoadTaxExpiringTo">To</label>
                        <input type="date" id="carsRoadTaxExpiringTo" class="form-control cars-expiry-filter">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input cars-expiry-filter" id="carsIncludeMissingRoadTax">
                    <label class="custom-control-label" for="carsIncludeMissingRoadTax">Include cars with no road tax added yet</label>
                </div>
            </div>

            <div class="form-group reports-expiring-month-group">
                <div class="reports-expiring-label-row d-flex justify-content-between align-items-center mb-1">
                    <label class="mb-0">PHV expiring in</label>
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-outline-secondary reports-month-picker-btn" id="carsPhvMonthPickerBtn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Pick month">
                            <i class="fa fa-calendar" aria-hidden="true"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" id="carsPhvMonthMenu" aria-labelledby="carsPhvMonthPickerBtn"></div>
                    </div>
                </div>
                <div class="form-row cars-expiry-date-row">
                    <div class="col-6">
                        <label class="small text-muted mb-25 d-block" for="carsPhvExpiringFrom">From</label>
                        <input type="date" id="carsPhvExpiringFrom" class="form-control cars-expiry-filter">
                    </div>
                    <div class="col-6">
                        <label class="small text-muted mb-25 d-block" for="carsPhvExpiringTo">To</label>
                        <input type="date" id="carsPhvExpiringTo" class="form-control cars-expiry-filter">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input cars-expiry-filter" id="carsIncludeMissingPhv">
                    <label class="custom-control-label" for="carsIncludeMissingPhv">Include cars with no PHV added yet</label>
                </div>
            </div>

            <button type="button" class="btn btn-outline-secondary btn-block" id="carsFilterReset">Reset Filters</button>
        </div>
    </aside>

    <div class="modal fade" id="carNotificationsModal" tabindex="-1" role="dialog" aria-labelledby="carNotificationsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="carNotificationsModalLabel">Car notifications</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="carNotificationsModalBody">
                    <p class="text-muted mb-0">Loading notifications...</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('css')
    <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/tables/datatable/datatables.min.css') }}">
    <style>
        #dataTable_filter {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .cars-table-toolbar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .cars-table-controls {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.5rem;
            margin-left: auto;
        }

        .card-dashboard .dataTables_wrapper .dataTables_filter {
            margin-top: 0;
            float: none;
        }

        #dataTable_filter label {
            display: flex;
            align-items: center;
            margin-bottom: 0;
        }

        #dataTable_filter input {
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

        #carsFilterClose {
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

        .car-notification-item {
            border: 1px solid #ebe9f1;
            border-left-width: 4px;
            border-radius: .35rem;
            padding: .75rem .9rem;
            margin-bottom: .6rem;
        }

        .car-notification-item:last-child {
            margin-bottom: 0;
        }

        .car-notification-item--danger { border-left-color: #ea5455; }
        .car-notification-item--warning { border-left-color: #ff9f43; }
        .car-notification-item--info { border-left-color: #00cfe8; }
        .car-notification-item--primary { border-left-color: #7367f0; }
        .car-notification-item--success { border-left-color: #28c76f; }
        .car-notification-item--secondary { border-left-color: #82868b; }

        .car-notifications-wrap {
            position: relative;
            display: inline-block;
            vertical-align: middle;
        }

        .btn-group .car-notifications-wrap {
            overflow: visible;
        }

        .car-notifications-badge {
            position: absolute;
            top: -7px;
            right: -7px;
            z-index: 3;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            min-width: 18px;
            padding: 0 !important;
            font-size: 10px;
            font-weight: 700;
            line-height: 1 !important;
            border-radius: 50% !important;
            border: 2px solid #fff;
            box-shadow: 0 1px 3px rgba(34, 41, 47, 0.2);
            pointer-events: none;
            box-sizing: border-box;
        }

        .car-notifications-badge.car-notifications-badge--wide {
            width: auto;
            min-width: 22px;
            height: 18px;
            padding: 0 5px !important;
            border-radius: 999px !important;
        }

        .reports-expiring-month-group {
            margin-bottom: 1rem;
        }

        .cars-filter-panel__body .reports-expiring-month-group .dropdown-menu {
            max-height: 240px;
            overflow-y: auto;
        }

        .cars-expiry-date-row {
            margin-left: 0;
            margin-right: 0;
        }

        .cars-expiry-date-row > [class*="col-"] {
            padding-left: 0;
            padding-right: 0.5rem;
        }

        .cars-expiry-date-row > [class*="col-"]:last-child {
            padding-right: 0;
            padding-left: 0.5rem;
        }
    </style>
@endsection
@section('js')
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/pdfmake.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/vfs_fonts.js') }}"></script>
    <script>
        $(document).ready(function () {
            (function applyDeletedCarRegistrationAlert() {
                var registration = '';
                try {
                    registration = sessionStorage.getItem('fleetiq_deleted_car_registration') || '';
                    sessionStorage.removeItem('fleetiq_deleted_car_registration');
                } catch (e) {}

                if (!registration) {
                    return;
                }

                var $alert = $('#carDeletedAlert');
                if (!$alert.length) {
                    $('.card-dashboard').first().prepend(
                        '<div class="alert alert-success" id="carDeletedAlert">' +
                        '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>' +
                        '</div>'
                    );
                    $alert = $('#carDeletedAlert');
                }

                $alert.html(
                    '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>' +
                    'Car ' + $('<div>').text(registration).html() + ' deleted successfully.'
                );
            })();

            const advancedFilters = {
                company: '',
                council: '',
                insuranceStatus: '',
                carStatus: '',
                model: '',
                color: '',
                logBook: '',
                trackerInstalled: '',
                trackerStatus: '',
                dashcamInstalled: '',
                dashcamStatus: '',
                tagInstalled: '',
                tagStatus: ''
            };
            const expiryFilters = {
                mot: { from: '', to: '', includeMissing: false },
                roadTax: { from: '', to: '', includeMissing: false },
                phv: { from: '', to: '', includeMissing: false },
            };
            const terminationNoticeFilter = { from: '', to: '' };
            const manufactureYearFilter = { from: '', to: '' };
            let manufactureYearToManuallyEdited = false;
            const availableFromColumnIndex = 7;
            const quickFilterLabels = {
                available_by_phv: 'Available by PHV',
                on_rent: 'On Rent',
                preparation_for_phvl: 'PHVL Preparation',
                damaged: 'Damaged',
                non_compliant: 'Non-Compliant',
                written_off: 'Written off',
                stolen: 'Stolen',
                for_sale: 'For sale',
                sold: 'Sold',
            };
            const carsExportHeaders = [
                'Registration', 'Company', 'Model', 'Color', 'Status',
                'PHV Council', 'Insurance Status', 'MOT Expiry', 'Road Tax Expiry', 'PHV Expiry'
            ];
            const carsExportFilenamePrefix = 'vehicle-list';
            const carsExportTitle = 'Vehicle List';

            function carsExportFilename(extension) {
                return carsExportFilenamePrefix + '-' + new Date().toISOString().slice(0, 10) + extension;
            }
            let quickFilter = '';

            function initializeActionTooltips() {
                $('.js-action-tooltip').tooltip({ container: 'body' });
            }

            const dataTable = $('#dataTable').DataTable({
                processing: true,
                responsive: true,
                columnDefs: [
                    { targets: availableFromColumnIndex, visible: false },
                    { targets: -1, visible: false, searchable: true }
                ],
            });

            initializeActionTooltips();
            dataTable.on('draw.dt responsive-display.dt', function () {
                $('.tooltip').remove();
                initializeActionTooltips();
            });

            const $filter = $('#dataTable_filter');
            const $toolbar = $('#carsTableToolbar');
            if ($filter.length && $toolbar.length && !$filter.parent().hasClass('cars-table-controls')) {
                const $controls = $('<div class="cars-table-controls"></div>');
                $filter.before($controls);
                $controls.append($toolbar);
                $controls.append($filter);
            }

            $('#dataTable_filter').append(
                '<button type="button" class="cars-filter-button" id="carsFilterOpen" title="Filter" aria-label="Filter"><i class="fa fa-filter"></i></button>'
            );

            function parseDateYmd(value) {
                if (!value) return null;
                const parts = value.split('-');
                if (parts.length !== 3) return null;
                const date = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
                return isNaN(date.getTime()) ? null : date;
            }

            function formatDisplayDate(iso) {
                if (!iso) return '';
                const date = parseDateYmd(iso);
                if (!date) return iso;
                return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
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

            function passesExpiryFilter(row, filters, expiryKey, missingKey) {
                if (!isExpiryFilterActive(filters)) {
                    return true;
                }

                const isMissing = row.dataset[missingKey] === '1';
                const expiryIso = row.dataset[expiryKey] || '';
                const hasDateRange = !!(filters.from || filters.to);

                if (filters.includeMissing && isMissing) {
                    return true;
                }

                if (hasDateRange && expiryInRange(expiryIso, filters.from, filters.to)) {
                    return true;
                }

                if (!hasDateRange && filters.includeMissing) {
                    return isMissing;
                }

                return false;
            }

            function syncExpiryFiltersFromForm() {
                expiryFilters.mot.from = document.getElementById('carsMotExpiringFrom').value;
                expiryFilters.mot.to = document.getElementById('carsMotExpiringTo').value;
                expiryFilters.mot.includeMissing = document.getElementById('carsIncludeMissingMot').checked;
                expiryFilters.roadTax.from = document.getElementById('carsRoadTaxExpiringFrom').value;
                expiryFilters.roadTax.to = document.getElementById('carsRoadTaxExpiringTo').value;
                expiryFilters.roadTax.includeMissing = document.getElementById('carsIncludeMissingRoadTax').checked;
                expiryFilters.phv.from = document.getElementById('carsPhvExpiringFrom').value;
                expiryFilters.phv.to = document.getElementById('carsPhvExpiringTo').value;
                expiryFilters.phv.includeMissing = document.getElementById('carsIncludeMissingPhv').checked;
            }

            function syncTerminationNoticeFilterFromForm() {
                terminationNoticeFilter.from = document.getElementById('carsTerminationNoticeFrom').value;
                terminationNoticeFilter.to = document.getElementById('carsTerminationNoticeTo').value;
            }

            function isTerminationNoticeFilterActive() {
                return !!(terminationNoticeFilter.from || terminationNoticeFilter.to);
            }

            function passesTerminationNoticeFilter(row) {
                if (!isTerminationNoticeFilterActive()) {
                    return true;
                }

                const noticeDate = row.dataset.terminationNoticeDate || '';
                if (!noticeDate) {
                    return false;
                }

                return expiryInRange(noticeDate, terminationNoticeFilter.from, terminationNoticeFilter.to);
            }

            function syncManufactureYearFilterFromForm() {
                manufactureYearFilter.from = document.getElementById('carsManufactureYearFrom').value;
                manufactureYearFilter.to = document.getElementById('carsManufactureYearTo').value;
            }

            function passesManufactureYearFilter(row) {
                if (!manufactureYearFilter.from && !manufactureYearFilter.to) {
                    return true;
                }

                const year = parseInt(row.dataset.manufactureYear, 10);
                if (Number.isNaN(year)) {
                    return false;
                }

                if (manufactureYearFilter.from !== '') {
                    const fromYear = parseInt(manufactureYearFilter.from, 10);
                    if (!Number.isNaN(fromYear) && year < fromYear) {
                        return false;
                    }
                }

                if (manufactureYearFilter.to !== '') {
                    const toYear = parseInt(manufactureYearFilter.to, 10);
                    if (!Number.isNaN(toYear) && year > toYear) {
                        return false;
                    }
                }

                if (manufactureYearFilter.from !== '' && manufactureYearFilter.to !== '') {
                    const fromYear = parseInt(manufactureYearFilter.from, 10);
                    const toYear = parseInt(manufactureYearFilter.to, 10);
                    if (!Number.isNaN(fromYear) && !Number.isNaN(toYear) && fromYear > toYear) {
                        return false;
                    }
                }

                return true;
            }

            function toggleAvailableFromColumn(show) {
                dataTable.column(availableFromColumnIndex).visible(show);
            }

            function drawCarsTable() {
                toggleAvailableFromColumn(isTerminationNoticeFilterActive());
                dataTable.draw();
            }

            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                if (settings.nTable.id !== 'dataTable') {
                    return true;
                }

                const row = dataTable.row(dataIndex).node();
                if (!row) {
                    return true;
                }

                return (!advancedFilters.company || row.dataset.company === advancedFilters.company)
                    && (!advancedFilters.council || row.dataset.council === advancedFilters.council)
                    && (!advancedFilters.insuranceStatus || row.dataset.insuranceStatus === advancedFilters.insuranceStatus)
                    && (!advancedFilters.carStatus || row.dataset.fleetStatus === advancedFilters.carStatus)
                    && (!advancedFilters.model || row.dataset.model === advancedFilters.model)
                    && (!advancedFilters.color || row.dataset.color === advancedFilters.color)
                    && passesAccessoryFilters(row)
                    && passesLogBookFilter(row)
                    && passesQuickFilter(row)
                    && passesExpiryFilter(row, expiryFilters.mot, 'motExpiry', 'motMissing')
                    && passesExpiryFilter(row, expiryFilters.roadTax, 'roadTaxExpiry', 'roadTaxMissing')
                    && passesExpiryFilter(row, expiryFilters.phv, 'phvExpiry', 'phvMissing')
                    && passesTerminationNoticeFilter(row)
                    && passesManufactureYearFilter(row);
            });

            function passesAccessoryFilters(row) {
                if (advancedFilters.trackerInstalled !== ''
                    && row.dataset.trackerInstalled !== advancedFilters.trackerInstalled) {
                    return false;
                }
                if (advancedFilters.trackerStatus
                    && row.dataset.trackerStatus !== advancedFilters.trackerStatus) {
                    return false;
                }
                if (advancedFilters.dashcamInstalled !== ''
                    && row.dataset.dashcamInstalled !== advancedFilters.dashcamInstalled) {
                    return false;
                }
                if (advancedFilters.dashcamStatus
                    && row.dataset.dashcamStatus !== advancedFilters.dashcamStatus) {
                    return false;
                }
                if (advancedFilters.tagInstalled !== ''
                    && row.dataset.tagInstalled !== advancedFilters.tagInstalled) {
                    return false;
                }
                if (advancedFilters.tagStatus
                    && row.dataset.tagStatus !== advancedFilters.tagStatus) {
                    return false;
                }

                return true;
            }

            function passesLogBookFilter(row) {
                if (advancedFilters.logBook !== 'awaiting') {
                    return true;
                }

                return row.dataset.awaitingLogBook === '1';
            }

            function passesQuickFilter(row) {
                if (!quickFilter) {
                    return true;
                }

                if (quickFilter === 'available_by_phv') {
                    return row.dataset.availableByPhv === '1';
                }

                return row.dataset.fleetStatus === quickFilter;
            }

            function updateQuickFilterButtons() {
                $('.cars-quick-filter').each(function () {
                    const isActive = $(this).data('quick-filter') === quickFilter;
                    $(this)
                        .toggleClass('btn-primary', isActive)
                        .toggleClass('btn-outline-primary', !isActive);
                });
            }

            function setFilterPanelOpen(isOpen) {
                $('#carsFilterPanel').toggleClass('is-open', isOpen).attr('aria-hidden', isOpen ? 'false' : 'true');
                $('#carsFilterBackdrop').toggleClass('is-open', isOpen);
            }

            $(document).on('click', '#carsFilterOpen', function () {
                setFilterPanelOpen(true);
            });

            $('#carsFilterClose, #carsFilterBackdrop').on('click', function () {
                setFilterPanelOpen(false);
            });

            $('.cars-advanced-filter').on('change', function () {
                advancedFilters[$(this).data('filter-key')] = this.value;
                dataTable.draw();
            });

            $('#carsFilterAwaitingLogBook').on('change', function () {
                advancedFilters.logBook = this.checked ? 'awaiting' : '';
                dataTable.draw();
            });

            $('.cars-expiry-filter').on('change input', function () {
                syncExpiryFiltersFromForm();
                dataTable.draw();
            });

            $('.cars-termination-notice-filter').on('change input', function () {
                syncTerminationNoticeFilterFromForm();
                drawCarsTable();
            });

            $('.cars-manufacture-year-from').on('input', function () {
                const toInput = document.getElementById('carsManufactureYearTo');
                if (!manufactureYearToManuallyEdited && toInput) {
                    toInput.value = this.value;
                }
                syncManufactureYearFilterFromForm();
                dataTable.draw();
            });

            $('.cars-manufacture-year-to').on('input', function () {
                manufactureYearToManuallyEdited = true;
                syncManufactureYearFilterFromForm();
                dataTable.draw();
            });

            $('.cars-quick-filter').on('click', function () {
                const selectedFilter = $(this).data('quick-filter');
                quickFilter = quickFilter === selectedFilter ? '' : selectedFilter;
                updateQuickFilterButtons();
                dataTable.draw();
            });

            $('#carsFilterReset').on('click', function () {
                $('.cars-advanced-filter').val('').trigger('change');
                $('#carsFilterAwaitingLogBook').prop('checked', false);
                advancedFilters.logBook = '';
                $('#carsMotExpiringFrom, #carsMotExpiringTo, #carsRoadTaxExpiringFrom, #carsRoadTaxExpiringTo, #carsPhvExpiringFrom, #carsPhvExpiringTo').val('');
                $('#carsIncludeMissingMot, #carsIncludeMissingRoadTax, #carsIncludeMissingPhv').prop('checked', false);
                expiryFilters.mot = { from: '', to: '', includeMissing: false };
                expiryFilters.roadTax = { from: '', to: '', includeMissing: false };
                expiryFilters.phv = { from: '', to: '', includeMissing: false };
                $('#carsTerminationNoticeFrom, #carsTerminationNoticeTo').val('');
                terminationNoticeFilter.from = '';
                terminationNoticeFilter.to = '';
                $('#carsManufactureYearFrom, #carsManufactureYearTo').val('');
                manufactureYearFilter.from = '';
                manufactureYearFilter.to = '';
                manufactureYearToManuallyEdited = false;
                quickFilter = '';
                updateQuickFilterButtons();
                drawCarsTable();
            });

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

            function buildMonthPickerMenu(menuEl, fromInputId, toInputId, filtersObj) {
                menuEl.innerHTML = '';
                getUpcomingFourMonths().forEach(function (month) {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'dropdown-item';
                    btn.textContent = month.label;
                    btn.addEventListener('click', function () {
                        document.getElementById(fromInputId).value = month.from;
                        document.getElementById(toInputId).value = month.to;
                        filtersObj.from = month.from;
                        filtersObj.to = month.to;
                        syncExpiryFiltersFromForm();
                        dataTable.draw();
                        closeDropdownMenu(menuEl);
                    });
                    menuEl.appendChild(btn);
                });
            }

            buildMonthPickerMenu(
                document.getElementById('carsMotMonthMenu'),
                'carsMotExpiringFrom',
                'carsMotExpiringTo',
                expiryFilters.mot
            );
            buildMonthPickerMenu(
                document.getElementById('carsRoadTaxMonthMenu'),
                'carsRoadTaxExpiringFrom',
                'carsRoadTaxExpiringTo',
                expiryFilters.roadTax
            );
            buildMonthPickerMenu(
                document.getElementById('carsPhvMonthMenu'),
                'carsPhvExpiringFrom',
                'carsPhvExpiringTo',
                expiryFilters.phv
            );

            function selectedOptionText(selectId) {
                const select = document.getElementById(selectId);
                if (!select || !select.value) {
                    return '';
                }
                return select.options[select.selectedIndex].text;
            }

            function formatExpiryRangeLine(label, filters) {
                if (!isExpiryFilterActive(filters)) {
                    return null;
                }

                const parts = [];
                if (filters.from || filters.to) {
                    const fromLabel = filters.from ? formatDisplayDate(filters.from) : 'any';
                    const toLabel = filters.to ? formatDisplayDate(filters.to) : 'any';
                    parts.push(fromLabel + ' to ' + toLabel);
                }
                if (filters.includeMissing) {
                    parts.push('include missing');
                }

                return label + ': ' + parts.join('; ');
            }

            function getCarsExportHeaders() {
                const headers = carsExportHeaders.slice();

                if (isTerminationNoticeFilterActive()) {
                    headers.splice(7, 0, 'Available From');
                }

                return headers;
            }

            function buildCarsExportMeta() {
                const lines = [];
                const searchTerm = (dataTable.search() || '').trim();

                if (searchTerm) {
                    lines.push('Search: ' + searchTerm);
                }

                if (quickFilter) {
                    lines.push('Quick filter: ' + (quickFilterLabels[quickFilter] || quickFilter));
                }

                if (advancedFilters.logBook === 'awaiting') {
                    lines.push('Log book: Awaiting log book');
                }

                if (advancedFilters.company) {
                    lines.push('Company: ' + advancedFilters.company);
                }

                if (advancedFilters.council) {
                    lines.push('Council: ' + advancedFilters.council);
                }

                if (advancedFilters.insuranceStatus) {
                    lines.push('Insurance status: ' + advancedFilters.insuranceStatus);
                }

                if (advancedFilters.carStatus) {
                    lines.push('Vehicle status: ' + selectedOptionText('carsFilterStatus'));
                }

                if (isTerminationNoticeFilterActive()) {
                    const fromLabel = terminationNoticeFilter.from ? formatDisplayDate(terminationNoticeFilter.from) : 'any';
                    const toLabel = terminationNoticeFilter.to ? formatDisplayDate(terminationNoticeFilter.to) : 'any';
                    lines.push('Termination notice: ' + fromLabel + ' to ' + toLabel);
                }

                if (advancedFilters.model) {
                    lines.push('Make/Model: ' + advancedFilters.model);
                }

                if (advancedFilters.color) {
                    lines.push('Color: ' + advancedFilters.color);
                }

                if (advancedFilters.trackerInstalled !== '') {
                    lines.push('Tracker: ' + (advancedFilters.trackerInstalled === '1' ? 'Installed' : 'Uninstalled'));
                }

                if (advancedFilters.trackerStatus) {
                    lines.push('Tracker status: ' + (advancedFilters.trackerStatus === 'active' ? 'Active' : 'Inactive'));
                }

                if (advancedFilters.dashcamInstalled !== '') {
                    lines.push('Dashcam: ' + (advancedFilters.dashcamInstalled === '1' ? 'Installed' : 'Uninstalled'));
                }

                if (advancedFilters.dashcamStatus) {
                    lines.push('Dashcam status: ' + (advancedFilters.dashcamStatus === 'active' ? 'Active' : 'Inactive'));
                }

                if (advancedFilters.tagInstalled !== '') {
                    lines.push('Tag: ' + (advancedFilters.tagInstalled === '1' ? 'Installed' : 'Uninstalled'));
                }

                if (advancedFilters.tagStatus) {
                    lines.push('Tag status: ' + (advancedFilters.tagStatus === 'active' ? 'Active' : 'Inactive'));
                }

                [
                    formatExpiryRangeLine('MOT expiring', expiryFilters.mot),
                    formatExpiryRangeLine('Road tax expiring', expiryFilters.roadTax),
                    formatExpiryRangeLine('PHV expiring', expiryFilters.phv),
                ].forEach(function (line) {
                    if (line) {
                        lines.push(line);
                    }
                });

                if (lines.length === 0) {
                    lines.push('Filters: None');
                }

                return {
                    title: carsExportTitle,
                    lines: lines,
                };
            }

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

            function collectCarsExportRows() {
                const rows = [];
                dataTable.rows({ search: 'applied', order: 'applied' }).every(function () {
                    const node = this.node();
                    if (!node) {
                        return;
                    }

                    const cells = node.querySelectorAll('td');
                    if (cells.length < 7) {
                        return;
                    }

                    const row = [];
                    for (let i = 0; i < 7; i++) {
                        row.push(cells[i].innerText.replace(/\s+/g, ' ').trim());
                    }

                    row.push(formatDisplayDate(node.dataset.motExpiry || ''));
                    row.push(formatDisplayDate(node.dataset.roadTaxExpiry || ''));
                    row.push(formatDisplayDate(node.dataset.phvExpiry || ''));

                    if (isTerminationNoticeFilterActive()) {
                        row.splice(7, 0, formatDisplayDate(node.dataset.terminationAvailableFrom || '') || '—');
                    }

                    rows.push(row);
                });

                return rows;
            }

            function exportCarsCsv() {
                const exportMeta = buildCarsExportMeta();
                const bodyRows = collectCarsExportRows();
                const exportHeaders = getCarsExportHeaders();

                if (bodyRows.length === 0) {
                    alert('No records to export. Adjust your search or filters and try again.');
                    return;
                }

                const lines = [csvEscape(exportMeta.title)];
                exportMeta.lines.forEach(function (line) {
                    lines.push(csvEscape(line));
                });
                lines.push('');
                lines.push(exportHeaders.map(csvEscape).join(','));
                bodyRows.forEach(function (row) {
                    lines.push(row.map(csvEscape).join(','));
                });

                downloadCsv(carsExportFilename('.csv'), lines);
            }

            function exportCarsPdf() {
                const exportMeta = buildCarsExportMeta();
                const bodyRows = collectCarsExportRows();
                const exportHeaders = getCarsExportHeaders();

                if (bodyRows.length === 0) {
                    alert('No records to export. Adjust your search or filters and try again.');
                    return;
                }

                if (typeof pdfMake === 'undefined') {
                    alert('PDF export is not available. Please refresh the page and try again.');
                    return;
                }

                const tableBody = [
                    exportHeaders.map(function (header) {
                        return { text: header, style: 'tableHeader' };
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
                            text: exportMeta.title + ' — ' + new Date().toISOString().slice(0, 10),
                            style: 'title',
                            margin: [0, 0, 0, 4]
                        },
                        ...exportMeta.lines.map(function (line) {
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
                                widths: exportHeaders.map(function () { return '*'; }),
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
                    defaultStyle: { fontSize: 8 },
                    footer: function (currentPage, pageCount) {
                        return {
                            text: 'Page ' + currentPage + ' of ' + pageCount,
                            alignment: 'center',
                            fontSize: 8,
                            color: '#5e5873',
                            margin: [0, 8, 0, 0]
                        };
                    }
                };

                pdfMake.createPdf(doc).download(carsExportFilename('.pdf'));
            }

            $('#carsExportCsv').on('click', exportCarsCsv);
            $('#carsExportPdf').on('click', exportCarsPdf);

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function renderCarNotifications(registration, notifications) {
                const $body = $('#carNotificationsModalBody');
                const safeReg = escapeHtml(registration || '');
                $('#carNotificationsModalLabel').text('Notifications - ' + (registration || 'Car'));

                if (!Array.isArray(notifications) || notifications.length === 0) {
                    $body.html('<p class="text-muted mb-0">No notifications found for <strong>' + safeReg + '</strong>.</p>');
                    return;
                }

                const html = notifications.map(function (notification) {
                    const color = notification.color || 'info';
                    const title = escapeHtml(notification.title || 'Notification');
                    const message = escapeHtml(notification.message || '');
                    const timeAgo = escapeHtml(notification.time_ago || '');
                    const actionUrl = notification.action_url ? escapeHtml(notification.action_url) : '';
                    const actionHtml = actionUrl
                        ? '<a href="' + actionUrl + '" class="btn btn-sm btn-outline-primary mt-50">Open</a>'
                        : '';

                    return '<div class="car-notification-item car-notification-item--' + color + '">' +
                        '<div class="d-flex justify-content-between align-items-start">' +
                        '<div class="pr-1">' +
                        '<div class="font-weight-bold">' + title + '</div>' +
                        '<div class="text-muted small mt-25">' + message + '</div>' +
                        '</div>' +
                        '<small class="text-muted text-nowrap ml-1">' + timeAgo + '</small>' +
                        '</div>' +
                        actionHtml +
                        '</div>';
                }).join('');

                $body.html(html);
            }

            $(document).on('click', '.car-notifications-btn', function () {
                const url = $(this).data('notifications-url');
                const registration = $(this).data('registration');

                $('#carNotificationsModalLabel').text('Notifications - ' + (registration || 'Car'));
                $('#carNotificationsModalBody').html('<p class="text-muted mb-0">Loading notifications...</p>');
                $('#carNotificationsModal').modal('show');

                fetch(url, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Failed to load notifications');
                        }
                        return response.json();
                    })
                    .then(function (data) {
                        renderCarNotifications(data.car_registration || registration, data.notifications || []);
                    })
                    .catch(function () {
                        $('#carNotificationsModalBody').html('<p class="text-danger mb-0">Unable to load notifications for this car right now.</p>');
                    });
            });

            fetch('{{ route('cars.notification-counts') }}', {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Failed to load notification counts');
                    }
                    return response.json();
                })
                .then(function (data) {
                    const counts = data.counts || {};

                    $('.car-notifications-wrap[data-registration]').each(function () {
                        const $wrap = $(this);
                        const registration = String($wrap.data('registration') || '');
                        const count = Number(counts[registration] || 0);

                        if (count <= 0) {
                            return;
                        }

                        const wideClass = count > 9 ? ' car-notifications-badge--wide' : '';
                        $wrap.append(
                            '<span class="badge badge-danger car-notifications-badge' + wideClass + '">' + count + '</span>'
                        );

                        const $button = $wrap.find('.car-notifications-btn');
                        const tooltipTitle = 'View Car Notifications (' + count + ')';
                        $button.attr('title', tooltipTitle).attr('aria-label', tooltipTitle);
                    });
                })
                .catch(function () {
                    // Badges are optional; leave buttons usable without counts.
                });
        });
    </script>
@endsection






