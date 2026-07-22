@extends('layouts.admin', ['title' => 'PHVL Archive'])

@section('css')
    <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/tables/datatable/datatables.min.css') }}">
    <style>
        #phvlArchiveTable.table thead th,
        #phvlArchiveTable.table tbody td {
            vertical-align: middle;
            padding: 0.45rem 0.65rem;
            font-size: 0.8125rem;
        }
    </style>
@endsection

@section('content')
    <section id="phvl-archive-datatable">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center py-75">
                        <div>
                            <h4 class="card-title mb-0">PHVL Archive</h4>
                            <p class="text-muted small mb-0 mt-50">Completed PHVL cycles. View timeline for step-by-step history.</p>
                        </div>
                        <div class="btn-group ml-auto">
                            <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle" id="phvlArchiveExportDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fa fa-download mr-50"></i> Export
                            </button>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="phvlArchiveExportDropdown">
                                <button type="button" class="dropdown-item" id="phvlArchiveExportCsv">Export CSV</button>
                                <button type="button" class="dropdown-item" id="phvlArchiveExportPdf">Export PDF</button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body px-1 pt-1 pb-0">
                        @include('alerts')
                        <div class="table-responsive">
                            <table id="phvlArchiveTable" class="table table-bordered table-striped w-100">
                                <thead>
                                <tr>
                                    <th>Make &amp; Model</th>
                                    <th>Car Registration</th>
                                    <th>Company</th>
                                    <th>Expiry context</th>
                                    <th>MOT Status</th>
                                    <th>Application Status</th>
                                    <th>PHVL applied date</th>
                                    <th>Appointment Confirmation</th>
                                    <th>Appointment Date &amp; Time</th>
                                    <th>PHVL Status</th>
                                    <th>PHV summary</th>
                                    <th>Completed</th>
                                    <th>Completed by</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="phvlArchiveTimelineModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header py-75">
                    <h5 class="modal-title">PHVL timeline</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                            <tr>
                                <th>Field</th>
                                <th>From</th>
                                <th>To</th>
                                <th>User</th>
                                <th>When</th>
                            </tr>
                            </thead>
                            <tbody id="phvl-archive-timeline-body">
                            <tr><td colspan="5" class="text-center text-muted py-2">Loading…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer py-75">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/pdfmake.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/vfs_fonts.js') }}"></script>
    <script>
        (function () {
            var timelineBase = @json(url('/admin/phvl/archive'));
            var $modal = window.jQuery;

            var table = $('#phvlArchiveTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: @json(route('phvl.archive.data')),
                    dataSrc: 'data'
                },
                columns: [
                    { data: 'make_model' },
                    { data: 'registration' },
                    { data: 'company' },
                    { data: 'renewal_context' },
                    { data: 'mot_status' },
                    { data: 'application_status' },
                    { data: 'applied_date' },
                    { data: 'appointment_confirmation' },
                    { data: 'appointment_at' },
                    { data: 'phvl_result_status' },
                    { data: 'phv_summary' },
                    {
                        data: 'completed_at',
                        type: 'string',
                        render: function (data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return row.completed_at_sort || '';
                            }
                            return data;
                        }
                    },
                    { data: 'completed_by' },
                    { data: 'actions', orderable: false, searchable: false }
                ],
                order: [[11, 'desc']],
                pageLength: 25
            });

            $('#phvlArchiveTable').on('click', '.phvl-archive-timeline-btn', function () {
                var archiveId = this.getAttribute('data-archive-id');
                var tbody = document.getElementById('phvl-archive-timeline-body');
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-2">Loading…</td></tr>';
                $modal('#phvlArchiveTimelineModal').modal('show');

                fetch(timelineBase + '/' + archiveId + '/timeline', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                }).then(function (r) {
                    return r.json().then(function (d) {
                        if (!r.ok) throw new Error('Could not load timeline');
                        return d;
                    });
                }).then(function (data) {
                    var events = data.events || [];
                    if (!events.length) {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-2">No events recorded.</td></tr>';
                        return;
                    }
                    tbody.innerHTML = events.map(function (e) {
                        return '<tr><td>' + e.field + '</td><td>' + e.old_value + '</td><td>' + e.new_value + '</td><td>' + e.user + '</td><td>' + e.at + '</td></tr>';
                    }).join('');
                }).catch(function () {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-2">Failed to load timeline.</td></tr>';
                });
            });

            // ==================== Export CSV / PDF ====================
            var phvlArchiveExportHeaders = [
                'Make & Model',
                'Car Registration',
                'Company',
                'Expiry context',
                'MOT Status',
                'Application Status',
                'PHVL applied date',
                'Appointment Confirmation',
                'Appointment Date & Time',
                'PHVL Status',
                'PHV summary',
                'Completed',
                'Completed by'
            ];
            var phvlArchiveExportFilenamePrefix = 'phvl-archive';
            var phvlArchiveExportTitle = 'PHVL Archive';

            function phvlArchiveExportFilename(extension) {
                return phvlArchiveExportFilenamePrefix + '-' + new Date().toISOString().slice(0, 10) + extension;
            }

            function buildPhvlArchiveExportMeta() {
                var lines = [];
                var searchValue = (table.search() || '').trim();
                if (searchValue) {
                    lines.push('Search: ' + searchValue);
                }

                if (lines.length === 0) {
                    lines.push('Filters: None');
                }

                return {
                    title: phvlArchiveExportTitle,
                    lines: lines
                };
            }

            function csvEscape(value) {
                var str = String(value == null ? '' : value).replace(/"/g, '""').trim();
                return /[",\n\r]/.test(str) ? '"' + str + '"' : str;
            }

            function downloadCsv(filename, lines) {
                var blob = new Blob(['\uFEFF' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
                var url = URL.createObjectURL(blob);
                var link = document.createElement('a');
                link.href = url;
                link.download = filename;
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            }

            function collectPhvlArchiveExportRows() {
                var rows = [];
                table.rows({ search: 'applied', order: 'applied' }).every(function () {
                    var node = this.node();
                    if (!node) {
                        return;
                    }

                    var cells = node.querySelectorAll('td');
                    if (cells.length < 13) {
                        return;
                    }

                    var row = [];
                    for (var i = 0; i < 13; i++) {
                        row.push(cells[i].innerText.replace(/\s+/g, ' ').trim());
                    }

                    rows.push(row);
                });

                return rows;
            }

            function exportPhvlArchiveCsv() {
                var exportMeta = buildPhvlArchiveExportMeta();
                var bodyRows = collectPhvlArchiveExportRows();

                if (bodyRows.length === 0) {
                    alert('No records to export. Adjust your search and try again.');
                    return;
                }

                var lines = [csvEscape(exportMeta.title)];
                exportMeta.lines.forEach(function (line) {
                    lines.push(csvEscape(line));
                });
                lines.push('');
                lines.push(phvlArchiveExportHeaders.map(csvEscape).join(','));
                bodyRows.forEach(function (row) {
                    lines.push(row.map(csvEscape).join(','));
                });

                downloadCsv(phvlArchiveExportFilename('.csv'), lines);
            }

            function exportPhvlArchivePdf() {
                var exportMeta = buildPhvlArchiveExportMeta();
                var bodyRows = collectPhvlArchiveExportRows();

                if (bodyRows.length === 0) {
                    alert('No records to export. Adjust your search and try again.');
                    return;
                }

                if (typeof pdfMake === 'undefined') {
                    alert('PDF export is not available. Please refresh the page and try again.');
                    return;
                }

                var tableBody = [
                    phvlArchiveExportHeaders.map(function (header) {
                        return { text: header, style: 'tableHeader' };
                    })
                ];

                bodyRows.forEach(function (row) {
                    tableBody.push(row.map(function (cell) {
                        return { text: cell, style: 'tableCell' };
                    }));
                });

                var doc = {
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
                                widths: phvlArchiveExportHeaders.map(function () { return '*'; }),
                                body: tableBody
                            },
                            layout: 'lightHorizontalLines'
                        }
                    ],
                    styles: {
                        title: { fontSize: 14, bold: true },
                        subtitle: { fontSize: 9, color: '#5e5873' },
                        tableHeader: { fontSize: 7, bold: true, fillColor: '#f3f2f7' },
                        tableCell: { fontSize: 6 }
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

                pdfMake.createPdf(doc).download(phvlArchiveExportFilename('.pdf'));
            }

            $('#phvlArchiveExportCsv').on('click', exportPhvlArchiveCsv);
            $('#phvlArchiveExportPdf').on('click', exportPhvlArchivePdf);
        })();
    </script>
@endsection
