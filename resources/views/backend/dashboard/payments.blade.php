@extends('layouts.admin', ['title' => 'Payment Notifications'])

@section('content')

    {{-- Page Header --}}
    <div class="content-header row">
        <div class="content-header-left col-md-9 col-12 mb-2">
            <div class="row breadcrumbs-top">
                <div class="col-12">
                    <h2 class="content-header-title float-left mb-0">
                        <i class="feather icon-credit-card mr-1"></i>
                        Payment Notifications
                    </h2>
                    <div class="breadcrumb-wrapper col-12">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Payments</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <div class="content-header-right text-md-right col-md-3 col-12 d-md-block d-none">
            <div class="form-group breadcrum-right">
                <a href="{{ route('notifications.index') }}" class="btn btn-outline-primary">
                    <i class="feather icon-bell mr-50"></i>
                    All Notifications
                </a>
            </div>
        </div>
    </div>

    {{-- Summary Cards - PAYMENT TYPES ONLY --}}
    <div class="row">
        {{-- Overdue Payments --}}
        <div class="col-lg-4 col-md-6 col-12">
            <div class="card text-center cursor-pointer" onclick="filterPayments('overdue_payment')">
                <div class="card-content">
                    <div class="card-body">
                        <div class="avatar bg-rgba-danger p-75 m-0 mb-1">
                            <div class="avatar-content">
                                <i class="feather icon-alert-triangle text-danger font-large-2"></i>
                            </div>
                        </div>
                        <h2 class="text-bold-700 text-danger">{{ $summary['overdue_payments'] }}</h2>
                        <p class="mb-0">Overdue Payments</p>
                        <p class="text-muted font-small-3 mb-0">Immediate action required</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Generated Today --}}
        <div class="col-lg-4 col-md-6 col-12">
            <div class="card text-center cursor-pointer" onclick="filterPayments('due_today')">
                <div class="card-content">
                    <div class="card-body">
                        <div class="avatar bg-rgba-warning p-75 m-0 mb-1">
                            <div class="avatar-content">
                                <i class="feather icon-clock text-warning font-large-2"></i>
                            </div>
                        </div>
                        <h2 class="text-bold-700 text-warning">{{ $summary['due_today'] }}</h2>
                        <p class="mb-0">Generated Today</p>
                        <p class="text-muted font-small-3 mb-0">Invoices generated today</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Due This Week --}}
        <div class="col-lg-4 col-md-6 col-12">
            <div class="card text-center cursor-pointer" onclick="filterPayments('due_this_week')">
                <div class="card-content">
                    <div class="card-body">
                        <div class="avatar bg-rgba-info p-75 m-0 mb-1">
                            <div class="avatar-content">
                                <i class="feather icon-calendar text-info font-large-2"></i>
                            </div>
                        </div>
                        <h2 class="text-bold-700 text-info">{{ $summary['due_this_week'] }}</h2>
                        <p class="mb-0">Due This Week</p>
                        <p class="text-muted font-small-3 mb-0">Upcoming payments</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Tabs --}}
    <div class="card">
        <div class="card-content">
            <div class="card-body p-1">
                <ul class="nav nav-pills nav-justified" id="payment-tabs">
                    <li class="nav-item">
                        <a class="nav-link active" href="javascript:void(0)" onclick="filterPayments('')">
                            All Payments
                            <span class="badge badge-pill badge-light ml-50">
                                {{ $summary['overdue_payments'] + $summary['due_today'] + $summary['due_this_week'] }}
                            </span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="javascript:void(0)" onclick="filterPayments('overdue_payment')">
                            Overdue
                            <span class="badge badge-pill badge-danger ml-50">{{ $summary['overdue_payments'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="javascript:void(0)" onclick="filterPayments('due_today')">
                            Generated Today
                            <span class="badge badge-pill badge-warning ml-50">{{ $summary['due_today'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="javascript:void(0)" onclick="filterPayments('due_this_week')">
                            This Week
                            <span class="badge badge-pill badge-info ml-50">{{ $summary['due_this_week'] }}</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Payment Notifications Table --}}
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h4 class="card-title mb-0">Payment Notifications</h4>
            <div class="btn-group ml-auto">
                <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle" id="paymentsExportDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-download mr-50"></i> Export
                </button>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="paymentsExportDropdown">
                    <button type="button" class="dropdown-item" id="paymentsExportCsv">Export CSV</button>
                    <button type="button" class="dropdown-item" id="paymentsExportPdf">Export PDF</button>
                </div>
            </div>
        </div>
        <div class="card-content">
            <div class="card-body">
                <div id="paymentsGeneratedDateFilter" class="form-row align-items-end mb-2">
                    <div class="form-group col-md-3 col-sm-6 mb-1">
                        <label class="small text-muted mb-25 d-block" for="paymentsInvoiceDateFrom">Generated From</label>
                        <input type="date" id="paymentsInvoiceDateFrom" class="form-control">
                    </div>
                    <div class="form-group col-md-3 col-sm-6 mb-1">
                        <label class="small text-muted mb-25 d-block" for="paymentsInvoiceDateTo">Generated To</label>
                        <input type="date" id="paymentsInvoiceDateTo" class="form-control">
                    </div>
                    <div class="form-group col-md-2 col-sm-6 mb-1">
                        <label class="small text-muted mb-25 d-block invisible" aria-hidden="true">&nbsp;</label>
                        <button type="button" class="btn btn-primary btn-block" id="paymentsApplyDateFilter">
                            Apply
                        </button>
                    </div>
                    <div class="form-group col-md-2 col-sm-6 mb-1">
                        <label class="small text-muted mb-25 d-block invisible" aria-hidden="true">&nbsp;</label>
                        <button type="button" class="btn btn-outline-secondary btn-block" id="paymentsClearDateFilter">
                            Clear
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="paymentsTable" class="table table-hover-animation">
                        <thead>
                        <tr>
                            <th>PRIORITY</th>
                            <th>DRIVER</th>
                            <th>VEHICLE</th>
                            <th>AMOUNT</th>
                            <th>GENERATED DATE</th>
                            <th>DUE DATE</th>
                            <th>STATUS</th>
                            <th>ACTIONS</th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Payment Modal --}}
    <div class="modal fade" id="quickPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="quickPaymentForm" method="POST">
                    @csrf
                    <div class="modal-header bg-primary">
                        <h5 class="modal-title text-white">
                            <i class="feather icon-credit-card mr-50"></i>
                            Record Payment
                        </h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info" id="payment-summary"></div>

                        <div class="form-group">
                            <label>Payment Amount (£) <span class="text-danger">*</span></label>
                            <input type="number" name="amount_paid" id="quick_amount_paid"
                                   class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Payment Date <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" id="quick_payment_date"
                                   class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="form-group">
                            <label>Payment Method</label>
                            <select name="payment_method" class="form-control">
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cash">Cash</option>
                                <option value="card">Card Payment</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Notes (Optional)</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="feather icon-check mr-50"></i>Record Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('backend.payments.partials.follow-up-modal')

@endsection

@section('css')
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/vendors/css/tables/datatable/datatables.min.css') }}">
    <style>
        .paying-company-subtitle {
            color: #6e6b7b;
            font-size: 0.875rem;
        }

        .cursor-pointer {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .cursor-pointer:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 25px 0 rgba(0,0,0,.1);
        }
        .nav-pills .nav-link.active {
            background-color: #7367F0 !important;
        }
        .priority-badge-1,
        .priority-badge-2 {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
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
        #paymentsGeneratedDateFilter .form-control,
        #paymentsGeneratedDateFilter .btn.btn-block {
            min-height: calc(1.25em + 1.4rem + 2px);
        }
    </style>
@endsection

@section('js')
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/pdfmake.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/vfs_fonts.js') }}"></script>
    <script>
        let paymentsTable;
        let currentFilter = '';

        function escapeHtmlAttr(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        function formatDriverWithPayingCompany(name, payingCompany) {
            const driverName = name || 'N/A';
            if (!payingCompany) {
                return driverName;
            }

            return `${driverName}<div class="paying-company-subtitle">Pays via: ${payingCompany}</div>`;
        }

        function formatDriverWithPayingCompanyExport(name, payingCompany) {
            const driverName = name || '—';
            if (!payingCompany) {
                return driverName;
            }

            return driverName + ' — Pays via: ' + payingCompany;
        }

        $(document).ready(function() {
            initializeDataTable();
            toggleGeneratedDateFilter();
            $('#paymentsApplyDateFilter').on('click', function() {
                if (currentFilter === '') {
                    paymentsTable.ajax.reload();
                }
            });
            $('#paymentsClearDateFilter').on('click', function() {
                $('#paymentsInvoiceDateFrom').val('');
                $('#paymentsInvoiceDateTo').val('');
                if (currentFilter === '') {
                    paymentsTable.ajax.reload();
                }
            });

            initPaymentNotificationsFollowUp();
        });

        function toggleGeneratedDateFilter() {
            if (currentFilter === '') {
                $('#paymentsGeneratedDateFilter').show();
            } else {
                $('#paymentsGeneratedDateFilter').hide();
            }
        }

        function initializeDfsPendingTooltips() {
            $('.js-dfs-pending-amount[data-toggle="tooltip"]').tooltip({ container: 'body' });
        }

        function initializeDataTable() {
            paymentsTable = $('#paymentsTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: '{{ route("payments.notifications") }}',
                    data: function(d) {
                        d.type = currentFilter;
                        if (currentFilter === '') {
                            d.invoice_date_from = $('#paymentsInvoiceDateFrom').val();
                            d.invoice_date_to = $('#paymentsInvoiceDateTo').val();
                        }
                    }
                },
                columns: [
                    {
                        data: 'priority',
                        render: function(data, type, row) {
                            const color = row.amount_color || row.color || 'danger';
                            const labels = {
                                1: 'CRITICAL',
                                2: 'HIGH',
                                3: 'MEDIUM'
                            };

                            return `<span class="badge badge-${color} priority-badge-${data}">${labels[data] || labels[3]}</span>`;
                        }
                    },
                    {
                        data: 'driver_name',
                        render: function(data, type, row) {
                            return formatDriverWithPayingCompany(data, row.paying_company);
                        }
                    },
                    {
                        data: 'vehicle',
                        render: function(data) {
                            return `<span class="badge badge-light-secondary">${data}</span>`;
                        }
                    },
                    {
                        data: 'amount',
                        render: function(data, type, row) {
                            const color = row.amount_color || row.color;
                            const tooltipAttr = row.amount_tooltip
                                ? ` data-toggle="tooltip" data-placement="top" title="${escapeHtmlAttr(row.amount_tooltip)}"`
                                : '';
                            const pendingClass = row.amount_tooltip ? ' js-dfs-pending-amount' : '';

                            return `<span class="font-weight-bold text-${color}${pendingClass}"${tooltipAttr}>${data}</span>`;
                        }
                    },
                    {
                        data: 'invoice_generated_date'
                    },
                    {
                        data: 'due_date'
                    },
                    {
                        data: 'time_ago',
                        render: function(data, type, row) {
                            const color = row.amount_color || row.color || 'danger';

                            return `<small class="text-${color}">${data}</small>`;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        render: function(data, type, row) {
                            const hasFollowUp = row.follow_up_has_note || row.follow_up_has_reminder;
                            const followUpBtnClass = hasFollowUp ? 'btn-warning' : 'btn-outline-secondary';
                            const followUpBtn = row.driver_id && row.follow_up_update_url
                                ? `<button type="button"
                                    class="btn btn-sm ${followUpBtnClass} js-driver-follow-up"
                                    title="Notes/Reminder"
                                    aria-label="Notes/Reminder"
                                    data-driver-id="${row.driver_id}"
                                    data-driver-name="${escapeHtmlAttr(row.driver_name || '')}"
                                    data-notes="${escapeHtmlAttr(row.follow_up_notes || '')}"
                                    data-remind-at="${escapeHtmlAttr(row.follow_up_remind_at || '')}"
                                    data-update-url="${escapeHtmlAttr(row.follow_up_update_url)}">
                                    <i class="fa fa-sticky-note"></i>
                                </button>`
                                : '';

                            return `
                        <div class="btn-group">
                            <a href="${row.action_url}" class="btn btn-sm btn-outline-primary">
                                <i class="feather icon-eye"></i>
                            </a>
                            <a href="{{ route('payments.create') }}?driver_id=${row.driver_id}" class="btn btn-sm btn-${row.amount_color || row.color || 'danger'}">
                                <i class="feather icon-credit-card"></i> Pay
                            </a>
                            ${followUpBtn}
                        </div>
                    `;
                        }
                    }
                ],
                order: [], // Preserve API order: latest invoice first
                pageLength: 25,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search payments...",
                    lengthMenu: "Show _MENU_ payments",
                    info: "Showing _START_ to _END_ of _TOTAL_ payments"
                }
            });

            initializeDfsPendingTooltips();
            paymentsTable.on('draw.dt', function () {
                $('.tooltip').remove();
                initializeDfsPendingTooltips();
            });
        }

        function filterPayments(type) {
            currentFilter = type;

            // Update active tab
            $('#payment-tabs .nav-link').removeClass('active');
            event.currentTarget.classList.add('active');

            toggleGeneratedDateFilter();

            // Reload table
            paymentsTable.ajax.reload();
        }

        const paymentsExportHeaders = [
            'Priority',
            'Driver',
            'Vehicle',
            'Amount',
            'Generated Date',
            'Due Date',
            'Status'
        ];
        const paymentsExportFilenamePrefix = 'payment-notifications';
        const paymentsExportTitle = 'Payment Notifications';
        const paymentPriorityLabels = { 1: 'Critical', 2: 'High', 3: 'Medium' };
        const paymentFilterLabels = {
            '': 'All Payments',
            overdue_payment: 'Overdue',
            due_today: 'Generated Today',
            due_this_week: 'This Week'
        };

        function paymentsExportFilename(extension) {
            return paymentsExportFilenamePrefix + '-' + new Date().toISOString().slice(0, 10) + extension;
        }

        function buildPaymentsExportMeta() {
            const lines = [];
            lines.push('Filter: ' + (paymentFilterLabels[currentFilter] || 'All Payments'));
            if (currentFilter === '') {
                const fromDate = ($('#paymentsInvoiceDateFrom').val() || '').trim();
                const toDate = ($('#paymentsInvoiceDateTo').val() || '').trim();
                if (fromDate || toDate) {
                    lines.push('Generated date: ' + (fromDate || 'Any') + ' to ' + (toDate || 'Any'));
                }
            }
            const searchValue = (paymentsTable.search() || '').trim();
            if (searchValue) {
                lines.push('Search: ' + searchValue);
            }
            return { title: paymentsExportTitle, lines: lines };
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

        function collectPaymentsExportRows() {
            const rows = [];
            paymentsTable.rows({ search: 'applied', order: 'applied' }).every(function () {
                const row = this.data();
                rows.push([
                    paymentPriorityLabels[row.priority] || 'Medium',
                    formatDriverWithPayingCompanyExport(row.driver_name, row.paying_company),
                    row.vehicle || '—',
                    row.amount || '—',
                    row.invoice_generated_date || '—',
                    row.due_date || '—',
                    row.time_ago || '—'
                ]);
            });
            return rows;
        }

        function exportPaymentsCsv() {
            const exportMeta = buildPaymentsExportMeta();
            const bodyRows = collectPaymentsExportRows();
            if (bodyRows.length === 0) {
                alert('No records to export. Adjust your search or filters and try again.');
                return;
            }
            const lines = [csvEscape(exportMeta.title)];
            exportMeta.lines.forEach(function (line) {
                lines.push(csvEscape(line));
            });
            lines.push('');
            lines.push(paymentsExportHeaders.map(csvEscape).join(','));
            bodyRows.forEach(function (row) {
                lines.push(row.map(csvEscape).join(','));
            });
            downloadCsv(paymentsExportFilename('.csv'), lines);
        }

        function exportPaymentsPdf() {
            const exportMeta = buildPaymentsExportMeta();
            const bodyRows = collectPaymentsExportRows();
            if (bodyRows.length === 0) {
                alert('No records to export. Adjust your search or filters and try again.');
                return;
            }
            if (typeof pdfMake === 'undefined') {
                alert('PDF export is not available. Please refresh the page and try again.');
                return;
            }
            const tableBody = [
                paymentsExportHeaders.map(function (header) {
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
                            widths: paymentsExportHeaders.map(function () { return '*'; }),
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
            pdfMake.createPdf(doc).download(paymentsExportFilename('.pdf'));
        }

        $('#paymentsExportCsv').on('click', exportPaymentsCsv);
        $('#paymentsExportPdf').on('click', exportPaymentsPdf);

        function initPaymentNotificationsFollowUp() {
            const $followUpModal = $('#driverFollowUpModal');
            const $followUpForm = $('#driverFollowUpForm');
            const $followUpNotes = $('#driverFollowUpNotes');
            const $followUpSetReminder = $('#driverFollowUpSetReminder');
            const $followUpRemindAtGroup = $('#driverFollowUpRemindAtGroup');
            const $followUpRemindAt = $('#driverFollowUpRemindAt');
            const $followUpError = $('#driverFollowUpError');
            const $followUpSubtitle = $('#driverFollowUpModalSubtitle');
            let activeFollowUpButton = null;

            if (!$followUpModal.length) {
                return;
            }

            function pad2(n) {
                return String(n).padStart(2, '0');
            }

            function toDatetimeLocalValue(value) {
                if (!value) {
                    return '';
                }
                const date = new Date(value);
                if (isNaN(date.getTime())) {
                    return String(value).slice(0, 16);
                }
                return date.getFullYear() + '-' + pad2(date.getMonth() + 1) + '-' + pad2(date.getDate()) +
                    'T' + pad2(date.getHours()) + ':' + pad2(date.getMinutes());
            }

            function datetimeLocalToIso(value) {
                if (!value) {
                    return null;
                }
                const date = new Date(value);
                if (isNaN(date.getTime())) {
                    return value;
                }
                return date.toISOString();
            }

            function toggleFollowUpRemindAt() {
                if ($followUpSetReminder.is(':checked')) {
                    $followUpRemindAtGroup.show();
                } else {
                    $followUpRemindAtGroup.hide();
                    $followUpRemindAt.val('');
                }
            }

            function updateFollowUpButtonsForDriver(driver) {
                if (!driver || !driver.id) {
                    return;
                }

                const selector = '.js-driver-follow-up[data-driver-id="' + driver.id + '"]';
                $(selector).each(function () {
                    const $btn = $(this);
                    $btn.attr('data-notes', driver.notes || '');
                    $btn.attr('data-remind-at', driver.remind_at || '');
                    $btn
                        .removeClass('btn-warning btn-outline-secondary')
                        .addClass(driver.has_note || driver.has_reminder ? 'btn-warning' : 'btn-outline-secondary');
                });
            }

            $followUpSetReminder.on('change', toggleFollowUpRemindAt);

            $(document).on('click', '.js-driver-follow-up', function (e) {
                e.preventDefault();
                e.stopPropagation();
                activeFollowUpButton = $(this);
                $followUpError.hide().text('');
                $followUpForm.attr('action', activeFollowUpButton.attr('data-update-url') || '');
                $followUpSubtitle.text(activeFollowUpButton.attr('data-driver-name') || '');
                $followUpNotes.val(activeFollowUpButton.attr('data-notes') || '');
                const remindAt = activeFollowUpButton.attr('data-remind-at') || '';
                $followUpSetReminder.prop('checked', !!remindAt);
                $followUpRemindAt.val(toDatetimeLocalValue(remindAt));
                toggleFollowUpRemindAt();
                $followUpModal.modal('show');
            });

            $followUpForm.on('submit', function (e) {
                e.preventDefault();
                $followUpError.hide().text('');

                const payload = {
                    notes: $followUpNotes.val(),
                    set_reminder: $followUpSetReminder.is(':checked') ? 1 : 0,
                    remind_at: $followUpSetReminder.is(':checked') ? datetimeLocalToIso($followUpRemindAt.val()) : null,
                    _method: 'PATCH',
                    _token: '{{ csrf_token() }}'
                };

                $('#driverFollowUpSaveBtn').prop('disabled', true);

                $.ajax({
                    url: $followUpForm.attr('action'),
                    method: 'POST',
                    data: payload,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                }).done(function (response) {
                    const driver = response.driver || {};
                    updateFollowUpButtonsForDriver(driver);
                    if (window.clearPaymentFollowUpSnooze && driver.id) {
                        window.clearPaymentFollowUpSnooze(driver.id);
                    }
                    $followUpModal.modal('hide');
                    if (window.toastr) {
                        toastr.success(response.message || 'Saved');
                    }
                }).fail(function (xhr) {
                    let message = 'Unable to save note / reminder.';
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        message = Object.values(xhr.responseJSON.errors).flat().join(' ');
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    $followUpError.text(message).show();
                }).always(function () {
                    $('#driverFollowUpSaveBtn').prop('disabled', false);
                });
            });
        }
    </script>
@endsection
