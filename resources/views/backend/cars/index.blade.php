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
                            <button type="button" class="btn btn-outline-primary btn-sm cars-quick-filter" data-quick-filter="awaiting_phv">Awaiting PHV</button>
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
                    <hr>
                    <div class="card-content">
                        <div class="card-body card-dashboard">
                            @include('alerts')
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
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($cars as $car)
                                        @php
                                            $carStatusLabel = $car->fleetStatusLabel();
                                            $isAvailableByPhv = $car->isAvailableForRent();
                                            $isAwaitingPhv = $car->phvs->isEmpty();
                                            $isAwaitingLogBook = $car->log_book_applied && $car->v5DocumentFileNames() === [];
                                            $latestInsurance = $car->insurances
                                                ->sortByDesc(fn (\App\Models\CarInsurance $i) => [optional($i->created_at)->timestamp ?? 0, $i->id])
                                                ->first();
                                            $latestInsuranceStatusName = trim((string) optional(optional($latestInsurance)->status)->name);
                                            $insuranceStatusLabel = strcasecmp($latestInsuranceStatusName, 'Applied') === 0
                                                ? 'Applied'
                                                : (strcasecmp($latestInsuranceStatusName, 'Active') === 0 ? 'Active' : 'Inactive');
                                            $phvCounselLabel = $car->latestPhvCounselName() ?? '—';
                                            $carNotificationCount = $carNotificationCounts[$car->registration] ?? 0;
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
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('cars.show', $car) }}" class="btn btn-sm btn-outline-info">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('cars.edit', $car) }}" class="btn btn-sm btn-outline-warning">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <span class="car-notifications-wrap">
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-primary car-notifications-btn"
                                                                data-notifications-url="{{ route('cars.notifications', $car) }}"
                                                                data-registration="{{ $car->registration }}"
                                                                title="View car notifications{{ $carNotificationCount > 0 ? ' (' . $carNotificationCount . ')' : '' }}">
                                                            <i class="fa fa-bell"></i>
                                                        </button>
                                                        @if($carNotificationCount > 0)
                                                            <span class="badge badge-danger car-notifications-badge{{ $carNotificationCount > 9 ? ' car-notifications-badge--wide' : '' }}">{{ $carNotificationCount }}</span>
                                                        @endif
                                                    </span>
                                                    <form action="{{ route('cars.destroy', $car) }}" method="POST" style="display: inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                                onclick="return confirm('Are you sure?')">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
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
    </style>
@endsection
@section('js')
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.bootstrap4.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            const advancedFilters = {
                company: '',
                council: '',
                insuranceStatus: '',
                carStatus: '',
                model: '',
                color: '',
                logBook: ''
            };
            let quickFilter = '';

            const dataTable = $('#dataTable').DataTable({
                processing: true,
                responsive: true,
            });

            $('#dataTable_filter').append(
                '<button type="button" class="cars-filter-button" id="carsFilterOpen" title="Filter" aria-label="Filter"><i class="fa fa-filter"></i></button>'
            );

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
                    && passesLogBookFilter(row)
                    && passesQuickFilter(row);
            });

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

                if (quickFilter === 'awaiting_phv') {
                    return row.dataset.awaitingPhv === '1';
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
                quickFilter = '';
                updateQuickFilterButtons();
                dataTable.draw();
            });

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
        });
    </script>
@endsection






