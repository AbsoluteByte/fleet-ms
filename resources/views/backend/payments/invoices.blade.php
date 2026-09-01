@extends('layouts.admin', ['title' => 'Invoices'])
@section('content')
    <section id="invoice-report">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Invoices</h4>
                        <div class="btn-group mt-25 mt-md-0" id="invoicesTableToolbar">
                            <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle" id="invoicesExportDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fa fa-download mr-50"></i> Export
                            </button>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="invoicesExportDropdown">
                                <button type="button" class="dropdown-item" id="invoicesExportCsv">Export CSV</button>
                                <button type="button" class="dropdown-item" id="invoicesExportPdf">Export PDF</button>
                            </div>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="card-body card-dashboard">
                            @include('alerts')

                            <form method="GET" action="{{ route('payments.invoices') }}" class="mb-2" id="invoiceReportFilters">
                                <div class="form-row align-items-end">
                                    <div class="form-group col-md-3 col-lg-2 mb-1">
                                        <label class="small text-muted mb-25 d-block" for="invoice_from">From</label>
                                        <input type="date" name="from" id="invoice_from" class="form-control" value="{{ $from }}" required>
                                    </div>
                                    <div class="form-group col-md-3 col-lg-2 mb-1">
                                        <label class="small text-muted mb-25 d-block" for="invoice_to">To</label>
                                        <input type="date" name="to" id="invoice_to" class="form-control" value="{{ $to }}" required>
                                    </div>
                                    <div class="form-group col-md-3 col-lg-2 mb-1">
                                        <label class="small text-muted mb-25 d-block" for="invoice_status">Status</label>
                                        <select name="status" id="invoice_status" class="form-control">
                                            <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All</option>
                                            <option value="paid" {{ $statusFilter === 'paid' ? 'selected' : '' }}>Paid</option>
                                            <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="partial" {{ $statusFilter === 'partial' ? 'selected' : '' }}>Partially Paid</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-3 col-lg-2 mb-1">
                                        <label class="small text-muted mb-25 d-block" for="invoice_type">Invoice type</label>
                                        <select name="invoice_type" id="invoice_type" class="form-control">
                                            <option value="all" {{ $invoiceTypeFilter === 'all' ? 'selected' : '' }}>All</option>
                                            <option value="agreement" {{ $invoiceTypeFilter === 'agreement' ? 'selected' : '' }}>Rent</option>
                                            <option value="agreement_deposit" {{ $invoiceTypeFilter === 'agreement_deposit' ? 'selected' : '' }}>Deposit</option>
                                            <option value="agreement_additional_charge" {{ $invoiceTypeFilter === 'agreement_additional_charge' ? 'selected' : '' }}>Additional charge</option>
                                            <option value="manual" {{ $invoiceTypeFilter === 'manual' ? 'selected' : '' }}>Manual</option>
                                            <option value="subscription" {{ $invoiceTypeFilter === 'subscription' ? 'selected' : '' }}>Subscription</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-3 col-lg-2 mb-1">
                                        <button type="submit" class="btn btn-primary">Apply</button>
                                    </div>
                                </div>
                            </form>

                            <div class="row invoice-report-summary">
                                <div class="col-md-6 col-xl mb-1">
                                    <div class="payment-summary-card border-primary" data-summary="generated">
                                        <span>Invoices generated</span>
                                        <strong>{{ $summary['generated_count'] }}</strong>
                                        <small>£{{ number_format($summary['generated_total'], 2) }}</small>
                                    </div>
                                </div>
                                <div class="col-md-6 col-xl mb-1">
                                    <div class="payment-summary-card border-success" data-summary="paid">
                                        <span>Paid</span>
                                        <strong>{{ $summary['paid_count'] }}</strong>
                                        <small>£{{ number_format($summary['paid_total'], 2) }}</small>
                                    </div>
                                </div>
                                <div class="col-md-6 col-xl mb-1">
                                    <div class="payment-summary-card border-warning" data-summary="pending">
                                        <span>Pending / unpaid</span>
                                        <strong>{{ $summary['pending_count'] }}</strong>
                                        <small>£{{ number_format($summary['pending_total'], 2) }}</small>
                                    </div>
                                </div>
                                <div class="col-md-6 col-xl mb-1">
                                    <div class="payment-summary-card border-info" data-summary="partial">
                                        <span>Partially paid</span>
                                        <strong>{{ $summary['partial_count'] }}</strong>
                                        <small>£{{ number_format($summary['partial_total'], 2) }}</small>
                                    </div>
                                </div>
                                <div class="col-md-6 col-xl mb-1">
                                    <div class="payment-summary-card border-danger" data-summary="outstanding">
                                        <span>Outstanding still to collect</span>
                                        <strong>£{{ number_format($summary['outstanding'], 2) }}</strong>
                                    </div>
                                </div>
                                <div class="col-md-6 col-xl mb-1">
                                    <div class="payment-summary-card border-secondary" data-summary="unique-vehicles">
                                        <span>{{ $invoiceTypeFilter === 'agreement' ? 'Cars on rent' : 'Unique vehicles' }}</span>
                                        <strong>{{ $uniqueVehiclesCount }}</strong>
                                        <small>Unique vehicles in selected filters</small>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table id="invoicesReportTable" class="table datatable table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th>Invoice No</th>
                                        <th>Customer</th>
                                        <th>Vehicle</th>
                                        <th>Invoice Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Payment Date</th>
                                        <th>Balance</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($rows as $row)
                                        <tr>
                                            <td>{{ $row['invoice_no'] }}</td>
                                            <td>{{ $row['customer'] }}</td>
                                            <td>{{ $row['vehicle'] }}</td>
                                            <td>{{ $row['invoice_date'] }}</td>
                                            <td>{{ $row['amount'] }}</td>
                                            <td>{{ $row['status'] }}</td>
                                            <td>{{ $row['payment_date'] }}</td>
                                            <td>{{ $row['balance'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">No invoices found for the selected filters.</td>
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
@endsection
@section('css')
    <style>
        .payment-summary-card {
            border-left: 4px solid #7367f0;
            border-radius: .25rem;
            padding: 1rem;
            background: #fff;
            box-shadow: 0 2px 8px rgba(34, 41, 47, .08);
            height: 100%;
        }

        .payment-summary-card span {
            display: block;
            color: #6e6b7b;
            font-size: .85rem;
        }

        .payment-summary-card strong {
            display: block;
            margin-top: .25rem;
            font-size: 1.25rem;
        }

        .payment-summary-card small {
            display: block;
            margin-top: .15rem;
            color: #5e5873;
            font-size: .9rem;
        }

        .invoices-table-toolbar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        #invoicesReportTable_filter {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .invoices-table-controls {
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

        #invoicesReportTable_filter label {
            display: flex;
            align-items: center;
            margin-bottom: 0;
        }

        #invoicesReportTable_filter input {
            margin-left: .5rem;
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
            const invoicesExportTitle = 'Invoices';
            const dataTable = $('#invoicesReportTable').DataTable({
                processing: true,
                responsive: true,
                order: [[3, 'desc']],
            });

            const $filter = $('#invoicesReportTable_filter');
            const $toolbar = $('#invoicesTableToolbar');
            if ($filter.length && $toolbar.length && !$filter.parent().hasClass('invoices-table-controls')) {
                const $controls = $('<div class="invoices-table-controls"></div>');
                $filter.before($controls);
                $controls.append($toolbar);
                $controls.append($filter);
            }

            const invoiceReportSummary = {
                generatedCount: {{ (int) $summary['generated_count'] }},
                generatedTotal: @json(number_format($summary['generated_total'], 2)),
                paidCount: {{ (int) $summary['paid_count'] }},
                paidTotal: @json(number_format($summary['paid_total'], 2)),
                pendingCount: {{ (int) $summary['pending_count'] }},
                pendingTotal: @json(number_format($summary['pending_total'], 2)),
                partialCount: {{ (int) $summary['partial_count'] }},
                partialTotal: @json(number_format($summary['partial_total'], 2)),
                outstanding: @json(number_format($summary['outstanding'], 2)),
                uniqueVehiclesCount: {{ (int) $uniqueVehiclesCount }},
            };

            function invoicesExportFilename(extension) {
                return 'invoices-' + new Date().toISOString().slice(0, 10) + extension;
            }

            function getInvoicesExportHeaders() {
                return ['Invoice No', 'Customer', 'Vehicle', 'Invoice Date', 'Amount', 'Status', 'Payment Date', 'Balance'];
            }

            function formatDateLabel(value) {
                if (!value) {
                    return '';
                }

                const date = new Date(value + 'T00:00:00');
                if (Number.isNaN(date.getTime())) {
                    return value;
                }

                return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
            }

            function selectedStatusLabel() {
                const select = document.getElementById('invoice_status');
                if (!select) {
                    return 'All';
                }

                return select.options[select.selectedIndex] ? select.options[select.selectedIndex].text : 'All';
            }

            function selectedInvoiceTypeLabel() {
                const select = document.getElementById('invoice_type');
                if (!select) {
                    return 'All';
                }

                return select.options[select.selectedIndex] ? select.options[select.selectedIndex].text : 'All';
            }

            function buildInvoicesExportMeta() {
                const from = document.getElementById('invoice_from') ? document.getElementById('invoice_from').value : '';
                const to = document.getElementById('invoice_to') ? document.getElementById('invoice_to').value : '';
                const lines = [
                    'Invoice date: ' + formatDateLabel(from) + ' to ' + formatDateLabel(to),
                    'Invoice type filter: ' + selectedInvoiceTypeLabel(),
                    'Table status filter: ' + selectedStatusLabel(),
                    'Invoices generated: ' + invoiceReportSummary.generatedCount + ' (£' + invoiceReportSummary.generatedTotal + ')',
                    'Paid: ' + invoiceReportSummary.paidCount + ' (£' + invoiceReportSummary.paidTotal + ')',
                    'Pending / unpaid: ' + invoiceReportSummary.pendingCount + ' (£' + invoiceReportSummary.pendingTotal + ')',
                    'Partially paid: ' + invoiceReportSummary.partialCount + ' (£' + invoiceReportSummary.partialTotal + ')',
                    'Outstanding still to collect: £' + invoiceReportSummary.outstanding,
                    (document.getElementById('invoice_type') && document.getElementById('invoice_type').value === 'agreement'
                        ? 'Cars on rent: '
                        : 'Unique vehicles: ') + invoiceReportSummary.uniqueVehiclesCount,
                ];

                const searchTerm = (dataTable.search() || '').trim();
                if (searchTerm) {
                    lines.push('Search: ' + searchTerm);
                }

                return {
                    title: invoicesExportTitle,
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

            function collectInvoicesExportRows() {
                const rows = [];
                dataTable.rows({ search: 'applied', order: 'applied' }).every(function () {
                    const node = this.node();
                    if (!node) {
                        return;
                    }

                    const cells = node.querySelectorAll('td');
                    if (cells.length < 8) {
                        return;
                    }

                    const invoiceNo = (cells[0].textContent || '').trim();
                    if (!invoiceNo || invoiceNo.indexOf('No invoices found') !== -1) {
                        return;
                    }

                    rows.push([
                        invoiceNo,
                        (cells[1].textContent || '').trim(),
                        (cells[2].textContent || '').trim(),
                        (cells[3].textContent || '').trim(),
                        (cells[4].textContent || '').trim(),
                        (cells[5].textContent || '').trim(),
                        (cells[6].textContent || '').trim(),
                        (cells[7].textContent || '').trim(),
                    ]);
                });

                return rows;
            }

            function exportInvoicesCsv() {
                const exportMeta = buildInvoicesExportMeta();
                const bodyRows = collectInvoicesExportRows();
                const exportHeaders = getInvoicesExportHeaders();

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

                downloadCsv(invoicesExportFilename('.csv'), lines);
            }

            function exportInvoicesPdf() {
                const exportMeta = buildInvoicesExportMeta();
                const bodyRows = collectInvoicesExportRows();
                const exportHeaders = getInvoicesExportHeaders();

                if (bodyRows.length === 0) {
                    alert('No records to export. Adjust your search or filters and try again.');
                    return;
                }

                if (typeof pdfMake === 'undefined') {
                    alert('PDF export is not available. Please refresh the page and try again.');
                    return;
                }

                const numericColumns = { 4: true, 7: true };
                const tableBody = [
                    exportHeaders.map(function (header, columnIndex) {
                        return {
                            text: header,
                            style: numericColumns[columnIndex] ? 'tableHeaderNumeric' : 'tableHeader',
                            noWrap: false,
                        };
                    }),
                ];

                bodyRows.forEach(function (row) {
                    tableBody.push(row.map(function (cell, columnIndex) {
                        return {
                            text: cell,
                            style: numericColumns[columnIndex] ? 'tableCellNumeric' : 'tableCell',
                            noWrap: false,
                        };
                    }));
                });

                const doc = {
                    pageSize: 'A4',
                    pageOrientation: 'landscape',
                    pageMargins: [16, 40, 16, 28],
                    content: [
                        {
                            text: exportMeta.title + ' — ' + new Date().toISOString().slice(0, 10),
                            style: 'title',
                            margin: [0, 0, 0, 4],
                        },
                    ].concat(exportMeta.lines.map(function (line) {
                        return {
                            text: line,
                            style: 'subtitle',
                            margin: [0, 0, 0, 2],
                        };
                    })).concat([
                        {
                            text: '',
                            margin: [0, 0, 0, 8],
                        },
                        {
                            table: {
                                headerRows: 1,
                                widths: [90, '*', 70, 80, 70, 70, 80, 70],
                                body: tableBody,
                            },
                            layout: {
                                hLineWidth: function () { return 0.5; },
                                vLineWidth: function () { return 0; },
                                hLineColor: function () { return '#dfe3e8'; },
                                paddingLeft: function () { return 8; },
                                paddingRight: function () { return 8; },
                                paddingTop: function () { return 6; },
                                paddingBottom: function () { return 6; },
                            },
                        },
                    ]),
                    styles: {
                        title: { fontSize: 14, bold: true },
                        subtitle: { fontSize: 9, color: '#5e5873' },
                        tableHeader: { fontSize: 9, bold: true, fillColor: '#f3f2f7' },
                        tableHeaderNumeric: { fontSize: 9, bold: true, fillColor: '#f3f2f7', alignment: 'right' },
                        tableCell: { fontSize: 8, lineHeight: 1.25 },
                        tableCellNumeric: { fontSize: 8, lineHeight: 1.25, alignment: 'right' },
                    },
                    defaultStyle: { fontSize: 8 },
                    footer: function (currentPage, pageCount) {
                        return {
                            text: 'Page ' + currentPage + ' of ' + pageCount,
                            alignment: 'center',
                            fontSize: 8,
                            color: '#5e5873',
                            margin: [0, 8, 0, 0],
                        };
                    },
                };

                pdfMake.createPdf(doc).download(invoicesExportFilename('.pdf'));
            }

            $('#invoicesExportCsv').on('click', exportInvoicesCsv);
            $('#invoicesExportPdf').on('click', exportInvoicesPdf);
        });
    </script>
@endsection
