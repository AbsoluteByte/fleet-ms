@extends('layouts.admin', ['title' => 'Cars'])
@section('content')
    <section id="basic-datatable">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ $plural }}</h4>
                        <div class="float-right">
                            <a class="btn btn-outline-primary btn-sm" href="{{ route('cars.reports.available-by-phv') }}">Available by PHV</a>
                            <a class="btn btn-outline-primary btn-sm" href="{{ route('cars.reports.awaiting-phv') }}">Awaiting PHV</a>
                            <a class="btn btn-outline-primary btn-sm" href="{{ route('cars.reports.status', 'damaged') }}">Damaged</a>
                            <a class="btn btn-outline-primary btn-sm" href="{{ route('cars.reports.status', 'written_off') }}">Written off</a>
                            <a class="btn btn-outline-primary btn-sm" href="{{ route('cars.reports.status', 'stolen') }}">Stolen</a>
                            <a class="btn btn-outline-primary btn-sm" href="{{ route('cars.reports.status', 'for_sale') }}">For sale</a>
                            <a class="btn btn-outline-primary btn-sm" href="{{ route('cars.reports.status', 'sold') }}">Sold</a>
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
                                            $carStatusLabel = ucwords(str_replace('_', ' ', $car->fleet_status ?? 'available_for_rent'));
                                            $latestInsurance = $car->insurances
                                                ->sortByDesc(fn (\App\Models\CarInsurance $i) => [optional($i->expiry_date)->timestamp ?? 0, $i->id])
                                                ->first();
                                            $latestInsuranceStatusName = trim((string) optional(optional($latestInsurance)->status)->name);
                                            $insuranceStatusLabel = strcasecmp($latestInsuranceStatusName, 'Applied') === 0
                                                ? 'Applied'
                                                : ($car->isInsuranceCurrentlyActive() ? 'Active' : 'Inactive');
                                            $phvCounselLabel = $car->latestPhvCounselName() ?? '—';
                                        @endphp
                                        <tr
                                            data-company="{{ $car->company->name }}"
                                            data-model="{{ $car->carModel->name }}"
                                            data-color="{{ $car->color }}"
                                            data-car-status="{{ $carStatusLabel }}"
                                            data-council="{{ $phvCounselLabel }}"
                                            data-insurance-status="{{ $insuranceStatusLabel }}"
                                        >
                                            <td>
                                                <strong>{{ $car->registration }}</strong>
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
        $filterStatuses = [
            'Available For Rent',
            'Reserved',
            'Vehicle Swap',
            'Damaged',
            'Written Off',
            'Stolen',
            'For Sale',
            'Sold',
        ];
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
                    @foreach($filterStatuses as $status)
                        <option value="{{ $status }}">{{ $status }}</option>
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
                color: ''
            };

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
                    && (!advancedFilters.carStatus || row.dataset.carStatus === advancedFilters.carStatus)
                    && (!advancedFilters.model || row.dataset.model === advancedFilters.model)
                    && (!advancedFilters.color || row.dataset.color === advancedFilters.color);
            });

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

            $('#carsFilterReset').on('click', function () {
                $('.cars-advanced-filter').val('').trigger('change');
            });
        });
    </script>
@endsection






