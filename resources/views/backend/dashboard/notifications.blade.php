{{-- resources/views/backend/dashboard/notifications.blade.php --}}

@extends('layouts.admin', ['title' => 'Fleet Notifications'])

@section('content')

    {{-- Page Header --}}
    <div class="content-header row">
        <div class="content-header-left col-md-9 col-12 mb-2">
            <div class="row breadcrumbs-top">
                <div class="col-12">
                    <h2 class="content-header-title float-left mb-0">
                        <i class="feather icon-bell mr-1"></i>
                        Fleet Notifications
                    </h2>
                    <div class="breadcrumb-wrapper col-12">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Fleet Notifications</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <div class="content-header-right text-md-right col-md-3 col-12 d-md-block d-none">
            <div class="form-group breadcrum-right">
                <button class="btn btn-primary btn-icon" onclick="refreshNotifications()">
                    <i class="feather icon-refresh-cw"></i>
                </button>
                <a href="{{ route('payments.notifications') }}" class="btn btn-outline-danger ml-50">
                    <i class="feather icon-credit-card"></i>
                    Payments
                </a>
            </div>
        </div>
    </div>

    {{-- Summary Cards - FLEET NOTIFICATIONS ONLY (NO PAYMENTS) --}}
    <div class="row" id="notification-summary-cards">
        {{-- Insurance Applied --}}
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card text-center cursor-pointer notification-summary-card" data-notification-filter="insurance_applied" onclick="filterNotifications('insurance_applied')">
                <div class="card-content">
                    <div class="card-body py-1">
                        <div class="avatar bg-rgba-warning p-50 m-0 mb-1">
                            <div class="avatar-content">
                                <i class="feather icon-clock text-warning font-large-1"></i>
                            </div>
                        </div>
                        <h2 class="text-bold-700">{{ $summary['insurance_applied'] }}</h2>
                        <p class="mb-0 font-small-3">Insurance Applied</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Expiring Insurance --}}
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card text-center cursor-pointer notification-summary-card" data-notification-filter="insurance_expiry" onclick="filterNotifications('insurance_expiry')">
                <div class="card-content">
                    <div class="card-body py-1">
                        <div class="avatar bg-rgba-primary p-50 m-0 mb-1">
                            <div class="avatar-content">
                                <i class="feather icon-shield text-primary font-large-1"></i>
                            </div>
                        </div>
                        <h2 class="text-bold-700">{{ $summary['expiring_insurance'] }}</h2>
                        <p class="mb-0 font-small-3">Insurance</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Expiring PHV --}}
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card text-center cursor-pointer notification-summary-card" data-notification-filter="phv_expiry" onclick="filterNotifications('phv_expiry')">
                <div class="card-content">
                    <div class="card-body py-1">
                        <div class="avatar bg-rgba-secondary p-50 m-0 mb-1">
                            <div class="avatar-content">
                                <i class="feather icon-award text-secondary font-large-1"></i>
                            </div>
                        </div>
                        <h2 class="text-bold-700">{{ $summary['expiring_phv'] }}</h2>
                        <p class="mb-0 font-small-3">PHVL</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Expiring MOT --}}
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card text-center cursor-pointer notification-summary-card" data-notification-filter="mot_expiry" onclick="filterNotifications('mot_expiry')">
                <div class="card-content">
                    <div class="card-body py-1">
                        <div class="avatar bg-rgba-warning p-50 m-0 mb-1">
                            <div class="avatar-content">
                                <i class="feather icon-tool text-warning font-large-1"></i>
                            </div>
                        </div>
                        <h2 class="text-bold-700">{{ $summary['expiring_mot'] }}</h2>
                        <p class="mb-0 font-small-3">MOT</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Expiring Road Tax --}}
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card text-center cursor-pointer notification-summary-card" data-notification-filter="road_tax_expiry" onclick="filterNotifications('road_tax_expiry')">
                <div class="card-content">
                    <div class="card-body py-1">
                        <div class="avatar bg-rgba-success p-50 m-0 mb-1">
                            <div class="avatar-content">
                                <i class="feather icon-credit-card text-success font-large-1"></i>
                            </div>
                        </div>
                        <h2 class="text-bold-700">{{ $summary['expiring_road_tax'] }}</h2>
                        <p class="mb-0 font-small-3">Road Tax</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Driver Licenses --}}
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card text-center cursor-pointer notification-summary-card" data-notification-filter="driver_license_expiry" onclick="filterNotifications('driver_license_expiry')">
                <div class="card-content">
                    <div class="card-body py-1">
                        <div class="avatar bg-rgba-info p-50 m-0 mb-1">
                            <div class="avatar-content">
                                <i class="feather icon-user text-info font-large-1"></i>
                            </div>
                        </div>
                        <h2 class="text-bold-700">{{ $summary['expiring_driver_licenses'] }}</h2>
                        <p class="mb-0 font-small-3">Driver License</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- PHD Licenses --}}
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card text-center cursor-pointer notification-summary-card" data-notification-filter="phd_license_expiry" onclick="filterNotifications('phd_license_expiry')">
                <div class="card-content">
                    <div class="card-body py-1">
                        <div class="avatar bg-rgba-secondary p-50 m-0 mb-1">
                            <div class="avatar-content">
                                <i class="feather icon-user-check text-secondary font-large-1"></i>
                            </div>
                        </div>
                        <h2 class="text-bold-700">{{ $summary['expiring_phd_licenses'] }}</h2>
                        <p class="mb-0 font-small-3">PHD License</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Agreement Notifications --}}
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card text-center cursor-pointer notification-summary-card" data-notification-filter="agreement_notifications" onclick="filterNotifications('agreement_notifications')">
                <div class="card-content">
                    <div class="card-body py-1">
                        <div class="avatar bg-rgba-info p-50 m-0 mb-1">
                            <div class="avatar-content">
                                <i class="feather icon-file-text text-info font-large-1"></i>
                            </div>
                        </div>
                        <h2 class="text-bold-700">{{ $summary['agreement_notifications'] ?? 0 }}</h2>
                        <p class="mb-0 font-small-3">Agreements</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Tabs - FLEET ONLY (NO PAYMENT TABS) --}}
    <div class="card">
        <div class="card-content">
            <div class="card-body p-1">
                <ul class="nav nav-pills nav-justified" id="notification-tabs">
                    <li class="nav-item">
                        <a class="nav-link active" href="javascript:void(0)" data-notification-filter="" onclick="filterNotifications('')">
                            All Fleet
                            <span class="badge badge-pill badge-light ml-50">
                                {{ $summary['insurance_applied'] + $summary['expiring_insurance'] + $summary['expiring_phv'] + $summary['expiring_mot'] + $summary['expiring_road_tax'] + $summary['expiring_driver_licenses'] + $summary['expiring_phd_licenses'] + ($summary['agreement_notifications'] ?? 0) }}
                            </span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="javascript:void(0)" data-notification-filter="insurance_applied" onclick="filterNotifications('insurance_applied')">
                            Insurance Applied
                            <span class="badge badge-pill badge-warning ml-50">{{ $summary['insurance_applied'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="javascript:void(0)" data-notification-filter="insurance_expiry" onclick="filterNotifications('insurance_expiry')">
                            Insurance
                            <span class="badge badge-pill badge-primary ml-50">{{ $summary['expiring_insurance'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="javascript:void(0)" data-notification-filter="phv_expiry" onclick="filterNotifications('phv_expiry')">
                            PHVL
                            <span class="badge badge-pill badge-secondary ml-50">{{ $summary['expiring_phv'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="javascript:void(0)" data-notification-filter="mot_expiry" onclick="filterNotifications('mot_expiry')">
                            MOT
                            <span class="badge badge-pill badge-warning ml-50">{{ $summary['expiring_mot'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="javascript:void(0)" data-notification-filter="road_tax_expiry" onclick="filterNotifications('road_tax_expiry')">
                            Road Tax
                            <span class="badge badge-pill badge-success ml-50">{{ $summary['expiring_road_tax'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="javascript:void(0)" data-notification-filter="driver_license_expiry" onclick="filterNotifications('driver_license_expiry')">
                            Driver License
                            <span class="badge badge-pill badge-info ml-50">{{ $summary['expiring_driver_licenses'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="javascript:void(0)" data-notification-filter="phd_license_expiry" onclick="filterNotifications('phd_license_expiry')">
                            PHD License
                            <span class="badge badge-pill badge-secondary ml-50">{{ $summary['expiring_phd_licenses'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="javascript:void(0)" data-notification-filter="agreement_notifications" onclick="filterNotifications('agreement_notifications')">
                            Agreements
                            <span class="badge badge-pill badge-info ml-50">{{ $summary['agreement_notifications'] ?? 0 }}</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Fleet Notifications DataTable --}}
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h4 class="card-title mb-0">Fleet Notifications</h4>
            <div class="btn-group ml-auto">
                <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle" id="fleetExportDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-download mr-50"></i> Export
                </button>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="fleetExportDropdown">
                    <button type="button" class="dropdown-item" id="fleetExportCsv">Export CSV</button>
                    <button type="button" class="dropdown-item" id="fleetExportPdf">Export PDF</button>
                </div>
            </div>
        </div>
        <div class="card-content">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="notificationsTable" class="table table-hover-animation">
                        <thead>
                        <tr>
                            <th>TYPE</th>
                            <th>TITLE</th>
                            <th>MESSAGE</th>
                            <th>VEHICLE/DRIVER</th>
                            <th>Last Car</th>
                            <th>EXPIRY STATUS</th>
                            {{-- Must match hidden sort_key column in DataTables (column count) --}}
                            <th class="d-none"></th>
                            <th>ACTIONS</th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/tables/datatable/datatables.min.css') }}">
    <style>
        .cursor-pointer {
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .cursor-pointer:hover {
            transform: translateY(-5px);
        }
        .notification-summary-card {
            border: 2px solid transparent;
        }
        .notification-summary-card.is-active {
            border-color: #7367F0;
            box-shadow: 0 0.25rem 1rem rgba(115, 103, 240, 0.25);
        }
        .nav-pills .nav-link.active {
            background-color: #7367F0 !important;
        }
        .expired-row {
            background-color: rgba(234, 84, 85, 0.1) !important;
        }
        .expiring-soon-row {
            background-color: rgba(255, 159, 67, 0.1) !important;
        }
        .card .card-header {
            position: static !important;
            z-index: auto !important;
            width: auto !important;
        }
        .card .card-content {
            margin-top: 0 !important;
        }
        .navbar-floating .header-navbar-shadow {
            height: 85px !important;
        }
    </style>
@endsection

@section('js')
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/pdfmake.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/vfs_fonts.js') }}"></script>
    <script>
        let notificationsTable;
        let currentFilter = '';

        $(document).ready(function() {
            initializeDataTable();
        });

        function initializeDataTable() {
            notificationsTable = $('#notificationsTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: '{{ route("notifications.index") }}',
                    data: function(d) {
                        d.type = currentFilter;
                    }
                },
                columns: [
                    {
                        data: 'type',
                        render: function(data, type, row) {
                            const iconMap = {
                                'insurance_applied': 'icon-clock text-warning',
                                'insurance_expiry': 'icon-shield text-primary',
                                'phv_expiry': 'icon-award text-secondary',
                                'mot_expiry': 'icon-tool text-warning',
                                'road_tax_expiry': 'icon-credit-card text-success',
                                'road_tax_missing': 'icon-credit-card text-warning',
                                'driver_license_expiry': 'icon-user text-info',
                                'phd_license_expiry': 'icon-user-check text-secondary'
                            };

                            const icon = iconMap[data] || 'icon-bell text-secondary';
                            return `<i class="feather ${icon} font-medium-3"></i>`;
                        }
                    },
                    {
                        data: 'title',
                        render: function(data, type, row) {
                            let badge = '';
                            if (row.priority === 1) {
                                badge = '<span class="badge badge-danger ml-50">EXPIRED</span>';
                            } else if (row.priority === 2) {
                                badge = '<span class="badge badge-warning ml-50">TODAY</span>';
                            }
                            return `<span class="font-weight-bold text-${row.color}">${data}</span>${badge}`;
                        }
                    },
                    {
                        data: 'simple_message'
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            let html = '';
                            if (row.vehicle) {
                                html += `<span class="badge badge-light-secondary mr-50">
                            <i class="feather icon-truck"></i> ${row.vehicle}
                        </span>`;
                            }
                            if (row.driver) {
                                html += `<span class="badge badge-light-info">
                            <i class="feather icon-user"></i> ${row.driver}
                        </span>`;
                            }
                            if (row.paying_company) {
                                html += `<span class="badge badge-light-primary ml-50">
                            Pays via: ${row.paying_company}
                        </span>`;
                            }
                            return html || '-';
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            if (row.last_car_registration && row.last_car_agreement_url) {
                                return `<a href="${row.last_car_agreement_url}" class="font-weight-bold">${row.last_car_registration}</a>`;
                            }

                            return '-';
                        }
                    },
                    {
                        data: 'time_ago',
                        render: function(data, type, row) {
                            if (row.type === 'insurance_applied' || row.type === 'road_tax_missing') {
                                return '-';
                            }
                            let colorClass = 'success';
                            if (row.priority === 1) colorClass = 'danger';
                            else if (row.priority === 2) colorClass = 'warning';

                            return `<span class="badge badge-light-${colorClass}">
                        <i class="feather icon-clock"></i> ${data}
                    </span>`;
                        }
                    },
                    {
                        data: 'sort_key',
                        visible: false,
                        searchable: false
                    },
                    {
                        data: null,
                        orderable: false,
                        render: function(data, type, row) {
                            let html = '<div class="btn-group">';

                            if (row.action_url) {
                                html += `<a href="${row.action_url}" class="btn btn-sm btn-outline-primary" title="View Details">
                            <i class="feather icon-eye"></i>
                        </a>`;
                            }

                            html += '</div>';
                            return html;
                        }
                    }
                ],
                order: [[6, 'asc']], // Chronological expiry (server order; column is Unix timestamp)
                pageLength: 25,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search fleet notifications...",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ fleet notifications"
                },
                dom: '<"row"<"col-sm-6"l><"col-sm-6"f>>' +
                    '<"row"<"col-sm-12"tr>>' +
                    '<"row"<"col-sm-5"i><"col-sm-7"p>>',
                rowCallback: function(row, data) {
                    // Add background color for expired items
                    if (data.priority === 1) {
                        $(row).addClass('expired-row');
                    } else if (data.priority === 2) {
                        $(row).addClass('expiring-soon-row');
                    }
                }
            });
        }

        function setActiveNotificationFilter(type) {
            const filterValue = type || '';

            $('#notification-tabs .nav-link').removeClass('active');
            $('#notification-tabs .nav-link').filter(function () {
                return String($(this).attr('data-notification-filter') ?? '') === filterValue;
            }).addClass('active');

            $('.notification-summary-card').removeClass('is-active');
            if (filterValue !== '') {
                $('.notification-summary-card').filter(function () {
                    return String($(this).attr('data-notification-filter') ?? '') === filterValue;
                }).addClass('is-active');
            }
        }

        function filterNotifications(type) {
            currentFilter = type || '';
            setActiveNotificationFilter(currentFilter);
            notificationsTable.ajax.reload();
        }

        function refreshNotifications() {
            notificationsTable.ajax.reload(null, false);
            toastr.success('Fleet notifications refreshed!', 'Success', {
                positionClass: 'toast-top-right'
            });
        }

        const fleetExportHeaders = [
            'Type',
            'Title',
            'Message',
            'Vehicle / Driver',
            'Last Car',
            'Expiry Status'
        ];
        const fleetExportFilenamePrefix = 'fleet-notifications';
        const fleetExportTitle = 'Fleet Notifications';
        const fleetTypeLabels = {
            insurance_applied: 'Insurance Applied',
            insurance_expiry: 'Insurance',
            phv_expiry: 'PHVL',
            mot_expiry: 'MOT',
            road_tax_expiry: 'Road Tax',
            road_tax_missing: 'Road Tax Missing',
            driver_license_expiry: 'Driver License',
            phd_license_expiry: 'PHD License',
            agreement_end_date: 'Agreement End Date',
            agreement_termination_notice: 'Termination Notice',
            agreement_notifications: 'Agreements'
        };
        const fleetFilterLabels = {
            '': 'All Fleet',
            insurance_applied: 'Insurance Applied',
            insurance_expiry: 'Insurance',
            phv_expiry: 'PHVL',
            mot_expiry: 'MOT',
            road_tax_expiry: 'Road Tax',
            driver_license_expiry: 'Driver License',
            phd_license_expiry: 'PHD License',
            agreement_end_date: 'Agreement End Date',
            agreement_termination_notice: 'Termination Notice',
            agreement_notifications: 'Agreements'
        };

        function fleetExportFilename(extension) {
            return fleetExportFilenamePrefix + '-' + new Date().toISOString().slice(0, 10) + extension;
        }

        function fleetFormatVehicleDriver(row) {
            const parts = [];
            if (row.vehicle) {
                parts.push(row.vehicle);
            }
            if (row.driver) {
                parts.push(row.driver);
            }
            if (row.paying_company) {
                parts.push('Pays via: ' + row.paying_company);
            }
            return parts.length ? parts.join(' / ') : '—';
        }

        function fleetFormatExpiryStatus(row) {
            if (row.type === 'insurance_applied' || row.type === 'road_tax_missing') {
                return '—';
            }
            return row.time_ago || '—';
        }

        function buildFleetExportMeta() {
            const lines = [];
            lines.push('Filter: ' + (fleetFilterLabels[currentFilter] || 'All Fleet'));
            const searchValue = (notificationsTable.search() || '').trim();
            if (searchValue) {
                lines.push('Search: ' + searchValue);
            }
            return { title: fleetExportTitle, lines: lines };
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

        function collectFleetExportRows() {
            const rows = [];
            notificationsTable.rows({ search: 'applied', order: 'applied' }).every(function () {
                const row = this.data();
                rows.push([
                    fleetTypeLabels[row.type] || row.type || '—',
                    row.title || '—',
                    row.simple_message || '—',
                    fleetFormatVehicleDriver(row),
                    row.last_car_registration || '—',
                    fleetFormatExpiryStatus(row)
                ]);
            });
            return rows;
        }

        function exportFleetCsv() {
            const exportMeta = buildFleetExportMeta();
            const bodyRows = collectFleetExportRows();
            if (bodyRows.length === 0) {
                alert('No records to export. Adjust your search or filters and try again.');
                return;
            }
            const lines = [csvEscape(exportMeta.title)];
            exportMeta.lines.forEach(function (line) {
                lines.push(csvEscape(line));
            });
            lines.push('');
            lines.push(fleetExportHeaders.map(csvEscape).join(','));
            bodyRows.forEach(function (row) {
                lines.push(row.map(csvEscape).join(','));
            });
            downloadCsv(fleetExportFilename('.csv'), lines);
        }

        function exportFleetPdf() {
            const exportMeta = buildFleetExportMeta();
            const bodyRows = collectFleetExportRows();
            if (bodyRows.length === 0) {
                alert('No records to export. Adjust your search or filters and try again.');
                return;
            }
            if (typeof pdfMake === 'undefined') {
                alert('PDF export is not available. Please refresh the page and try again.');
                return;
            }
            const tableBody = [
                fleetExportHeaders.map(function (header) {
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
                pageOrientation: 'portrait',
                pageMargins: [24, 48, 24, 32],
                content: [
                    {
                        text: exportMeta.title + ' — ' + new Date().toISOString().slice(0, 10),
                        style: 'title',
                        margin: [0, 0, 0, 4]
                    },
                    ...exportMeta.lines.map(function (line) {
                        return { text: line, style: 'subtitle', margin: [0, 0, 0, 2] };
                    }),
                    { text: '', margin: [0, 0, 0, 8] },
                    {
                        table: {
                            headerRows: 1,
                            widths: fleetExportHeaders.map(function () { return '*'; }),
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
            pdfMake.createPdf(doc).download(fleetExportFilename('.pdf'));
        }

        $('#fleetExportCsv').on('click', exportFleetCsv);
        $('#fleetExportPdf').on('click', exportFleetPdf);
    </script>
@endsection
