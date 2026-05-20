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
                                <li class="nav-item">
                                    <a class="nav-link" id="reports-phvl-tab" data-toggle="pill" href="#reports-phvl-pane" role="tab" aria-controls="reports-phvl-pane" aria-selected="false">PHVL</a>
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

                                <div class="tab-pane fade" id="reports-phvl-pane" role="tabpanel" aria-labelledby="reports-phvl-tab">
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
            <div class="form-group">
                <label>PHVL Expiring</label>
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
        #reportsPhvlTable_filter {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        #reportsMotsTable_filter label,
        #reportsPhvlTable_filter label {
            display: flex;
            align-items: center;
            margin-bottom: 0;
        }

        #reportsMotsTable_filter input,
        #reportsPhvlTable_filter input {
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
    </style>
@endsection

@section('js')
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.bootstrap4.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            let activeReportTab = 'mots';

            const motFilters = { company: '', from: '', to: '', includeMissing: false };
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

            function isAnyFilterActive(filters) {
                return !!filters.company || isExpiryFilterActive(filters);
            }

            function applyReportRowFilter(settings, dataIndex, tableApi, filters, expiryKey, missingKey) {
                if (!isAnyFilterActive(filters)) return true;
                const row = tableApi.row(dataIndex).node();
                if (!row) return true;
                if (filters.company && row.dataset.company !== filters.company) return false;
                if (!isExpiryFilterActive(filters)) return true;

                const isMissing = row.dataset[missingKey] === '1';
                const expiryIso = row.dataset[expiryKey] || '';
                const hasDateRange = !!(filters.from || filters.to);

                if (filters.includeMissing && isMissing) return true;
                if (hasDateRange && expiryInRange(expiryIso, filters.from, filters.to)) return true;
                if (!hasDateRange && filters.includeMissing) return isMissing;
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
                document.getElementById('reportsIncludeMissingMot').checked = false;
                motFilters.company = '';
                motFilters.from = '';
                motFilters.to = '';
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
                activeReportTab = e.target.getAttribute('href') === '#reports-phvl-pane' ? 'phvl' : 'mots';
                closeAllFilterPanels();
                if (activeReportTab === 'phvl') {
                    phvlDataTable.columns.adjust().responsive.recalc();
                } else {
                    motsDataTable.columns.adjust().responsive.recalc();
                }
            });

            const reportExportHeaders = [
                'Registration', 'Company', 'Model', 'Color', 'Status',
                'PHV Council', 'Insurance Status'
            ];

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

            function exportTableCsv(tableApi, expiryLabel, statusLabel, filePrefix) {
                const headers = reportExportHeaders.concat([expiryLabel, statusLabel]);
                const lines = [headers.map(csvEscape).join(',')];
                let rowCount = 0;

                tableApi.rows({ search: 'applied', order: 'applied' }).every(function () {
                    const node = this.node();
                    if (!node) return;
                    const cells = node.querySelectorAll('td');
                    if (cells.length < 9) return;
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

                downloadCsv(filePrefix + '-' + new Date().toISOString().slice(0, 10) + '.csv', lines);
            }

            $('#reportsExportCsv').on('click', function () {
                if (activeReportTab === 'phvl') {
                    exportTableCsv(phvlDataTable, 'PHVL Expiry', 'PHVL Status', 'phvl-report');
                } else {
                    exportTableCsv(motsDataTable, 'MOT Expiry', 'MOT Status', 'mot-report');
                }
            });
        });
    </script>
@endsection
