@extends('layouts.admin', ['title' => 'Reports'])

@section('content')
    <section id="reports-page">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Reports</h4>
                        <button type="button" class="btn btn-outline-primary btn-sm mt-25 mt-md-0" id="reportsExportCsv">
                            <i class="fa fa-download mr-50"></i> Export CSV
                        </button>
                    </div>
                    <div class="card-content">
                        <div class="card-body card-dashboard">
                            @include('alerts')

                            <ul class="nav nav-pills mb-2" id="reports-tabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="reports-mots-tab" data-toggle="pill" href="#reports-mots-pane" role="tab" aria-controls="reports-mots-pane" aria-selected="true">MOTs</a>
                                </li>
                            </ul>

                            <div class="tab-content" id="reports-tab-content">
                                <div class="tab-pane fade show active" id="reports-mots-pane" role="tabpanel" aria-labelledby="reports-mots-tab">
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
                                                    $motStatus = $car->report_mot_status;
                                                    $motExpiryIso = $car->report_mot_expiry ? $car->report_mot_expiry->format('Y-m-d') : '';
                                                    $motStatusClass = match ($motStatus) {
                                                        'Missing' => 'badge-light-warning',
                                                        'Expired' => 'badge-light-danger',
                                                        'Expiring' => 'badge-light-warning',
                                                        default => 'badge-light-success',
                                                    };
                                                @endphp
                                                <tr
                                                    data-mot-expiry="{{ $motExpiryIso }}"
                                                    data-mot-missing="{{ $car->report_mot_missing ? '1' : '0' }}"
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
                                                        @if($car->report_mot_expiry)
                                                            {{ $car->report_mot_expiry->format('d M, Y') }}
                                                        @else
                                                            <span class="text-muted">—</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge {{ $motStatusClass }}">{{ $motStatus }}</span>
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
                                            @empty
                                                <tr>
                                                    <td colspan="10" class="text-center text-muted py-4">No cars found.</td>
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
            </div>
        </div>
    </section>

    <div class="cars-filter-backdrop" id="reportsFilterBackdrop"></div>
    <aside class="cars-filter-panel" id="reportsFilterPanel" aria-hidden="true">
        <div class="cars-filter-panel__header">
            <h5 class="mb-0">Advanced Search</h5>
            <button type="button" class="close" id="reportsFilterClose" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="cars-filter-panel__body">
            <div class="form-group">
                <label>MOT Expiring</label>
                <label class="small text-muted mb-25 d-block" for="reportsMotExpiringFrom">From</label>
                <input type="date" id="reportsMotExpiringFrom" class="form-control mb-1">
                <label class="small text-muted mb-25 d-block" for="reportsMotExpiringTo">To</label>
                <input type="date" id="reportsMotExpiringTo" class="form-control">
            </div>
            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="reportsIncludeMissingMot">
                    <label class="custom-control-label" for="reportsIncludeMissingMot">Include cars with no MOT added yet</label>
                </div>
            </div>
            <button type="button" class="btn btn-primary btn-block mb-1" id="reportsFilterApply">Apply</button>
            <button type="button" class="btn btn-outline-secondary btn-block" id="reportsFilterReset">Reset Filters</button>
        </div>
    </aside>
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/tables/datatable/datatables.min.css') }}">
    <style>
        #reportsMotsTable_filter {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        #reportsMotsTable_filter label {
            display: flex;
            align-items: center;
            margin-bottom: 0;
        }

        #reportsMotsTable_filter input {
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

        #reportsFilterClose {
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
    </style>
@endsection

@section('js')
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.bootstrap4.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            const motFilters = {
                from: '',
                to: '',
                includeMissing: false,
                active: false
            };

            const dataTable = $('#reportsMotsTable').DataTable({
                processing: true,
                responsive: true,
                order: [[7, 'asc']]
            });

            $('#reportsMotsTable_filter').append(
                '<button type="button" class="cars-filter-button" id="reportsFilterOpen" title="Filter" aria-label="Filter"><i class="fa fa-filter"></i></button>'
            );

            function parseDateYmd(value) {
                if (!value) {
                    return null;
                }
                const parts = value.split('-');
                if (parts.length !== 3) {
                    return null;
                }
                const y = parseInt(parts[0], 10);
                const m = parseInt(parts[1], 10) - 1;
                const d = parseInt(parts[2], 10);
                const date = new Date(y, m, d);
                return isNaN(date.getTime()) ? null : date;
            }

            function motExpiryInRange(expiryIso, fromStr, toStr) {
                if (!expiryIso) {
                    return false;
                }
                const expiry = parseDateYmd(expiryIso);
                if (!expiry) {
                    return false;
                }
                const from = parseDateYmd(fromStr);
                const to = parseDateYmd(toStr);
                if (from && expiry < from) {
                    return false;
                }
                if (to && expiry > to) {
                    return false;
                }
                return true;
            }

            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                if (settings.nTable.id !== 'reportsMotsTable') {
                    return true;
                }
                if (!motFilters.active) {
                    return true;
                }

                const row = dataTable.row(dataIndex).node();
                if (!row) {
                    return true;
                }

                const isMissing = row.dataset.motMissing === '1';
                const expiryIso = row.dataset.motExpiry || '';
                const hasFrom = !!motFilters.from;
                const hasTo = !!motFilters.to;
                const hasDateRange = hasFrom || hasTo;

                if (motFilters.includeMissing && isMissing) {
                    return true;
                }

                if (hasDateRange && motExpiryInRange(expiryIso, motFilters.from, motFilters.to)) {
                    return true;
                }

                if (!hasDateRange && motFilters.includeMissing) {
                    return isMissing;
                }

                return false;
            });

            function syncMotFiltersFromPanel() {
                motFilters.from = document.getElementById('reportsMotExpiringFrom').value;
                motFilters.to = document.getElementById('reportsMotExpiringTo').value;
                motFilters.includeMissing = document.getElementById('reportsIncludeMissingMot').checked;
                motFilters.active = !!(motFilters.from || motFilters.to || motFilters.includeMissing);
            }

            function setFilterPanelOpen(isOpen) {
                $('#reportsFilterPanel').toggleClass('is-open', isOpen).attr('aria-hidden', isOpen ? 'false' : 'true');
                $('#reportsFilterBackdrop').toggleClass('is-open', isOpen);
            }

            $(document).on('click', '#reportsFilterOpen', function () {
                setFilterPanelOpen(true);
            });

            $('#reportsFilterClose, #reportsFilterBackdrop').on('click', function () {
                setFilterPanelOpen(false);
            });

            $('#reportsFilterApply').on('click', function () {
                syncMotFiltersFromPanel();
                setFilterPanelOpen(false);
                dataTable.draw();
            });

            $('#reportsFilterReset').on('click', function () {
                document.getElementById('reportsMotExpiringFrom').value = '';
                document.getElementById('reportsMotExpiringTo').value = '';
                document.getElementById('reportsIncludeMissingMot').checked = false;
                motFilters.from = '';
                motFilters.to = '';
                motFilters.includeMissing = false;
                motFilters.active = false;
                dataTable.draw();
            });

            const motExportHeaders = [
                'Registration',
                'Company',
                'Model',
                'Color',
                'Status',
                'PHV Council',
                'Insurance Status',
                'MOT Expiry',
                'MOT Status'
            ];

            function csvEscape(value) {
                const str = String(value ?? '').replace(/"/g, '""').trim();
                return /[",\n\r]/.test(str) ? '"' + str + '"' : str;
            }

            function exportMotsCsv() {
                const lines = [motExportHeaders.map(csvEscape).join(',')];
                let rowCount = 0;

                dataTable.rows({ search: 'applied', order: 'applied' }).every(function () {
                    const node = this.node();
                    if (!node) {
                        return;
                    }
                    const cells = node.querySelectorAll('td');
                    if (cells.length < 9) {
                        return;
                    }
                    const row = [];
                    for (let i = 0; i < 9; i++) {
                        row.push(csvEscape(cells[i].innerText.replace(/\s+/g, ' ').trim()));
                    }
                    lines.push(row.join(','));
                    rowCount++;
                });

                if (rowCount === 0) {
                    alert('No records to export. Adjust your search or filters and try again.');
                    return;
                }

                const csvContent = '\uFEFF' + lines.join('\r\n');
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                const stamp = new Date().toISOString().slice(0, 10);
                link.href = url;
                link.download = 'mot-report-' + stamp + '.csv';
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            }

            $('#reportsExportCsv').on('click', exportMotsCsv);
        });
    </script>
@endsection
