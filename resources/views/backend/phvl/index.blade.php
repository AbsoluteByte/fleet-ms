@extends('layouts.admin', ['title' => 'PHVL Management'])

@section('css')
    <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/tables/datatable/datatables.min.css') }}">
    <style>
        .navbar-floating .header-navbar-shadow {
            height: 105px !important;
        }

        #phvlTable.table {
            min-width: 1500px;
        }

        #phvlTable.table thead th,
        #phvlTable.table tbody td {
            vertical-align: middle;
            padding: 0.45rem 0.65rem;
            font-size: 0.8125rem;
        }

        #phvlTable.table thead th {
            white-space: nowrap;
        }

        #phvlTable.table th:nth-child(1),
        #phvlTable.table td:nth-child(1)  { min-width: 120px; }
        #phvlTable.table th:nth-child(2),
        #phvlTable.table td:nth-child(2)  { min-width: 115px; }
        #phvlTable.table th:nth-child(3),
        #phvlTable.table td:nth-child(3)  { min-width: 130px; }
        #phvlTable.table th:nth-child(4),
        #phvlTable.table td:nth-child(4)  { min-width: 130px; }
        #phvlTable.table th:nth-child(5),
        #phvlTable.table td:nth-child(5)  { min-width: 120px; }
        #phvlTable.table th:nth-child(6),
        #phvlTable.table td:nth-child(6)  { min-width: 90px; }
        #phvlTable.table th:nth-child(7),
        #phvlTable.table td:nth-child(7)  { min-width: 90px; }
        #phvlTable.table th:nth-child(8),
        #phvlTable.table td:nth-child(8)  { min-width: 100px; }
        #phvlTable.table th:nth-child(9),
        #phvlTable.table td:nth-child(9)  { min-width: 110px; }
        #phvlTable.table th:nth-child(10),
        #phvlTable.table td:nth-child(10) { min-width: 100px; }
        #phvlTable.table th:nth-child(11),
        #phvlTable.table td:nth-child(11) { min-width: 130px; }
        #phvlTable.table th:nth-child(12),
        #phvlTable.table td:nth-child(12) { min-width: 140px; }
        #phvlTable.table th:nth-child(13),
        #phvlTable.table td:nth-child(13) { min-width: 160px; }

        #phvlTable_wrapper .dataTables_filter {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        #phvlTable_wrapper .dataTables_filter label {
            display: flex;
            align-items: center;
            margin-bottom: 0;
        }

        #phvlTable_wrapper .dataTables_filter input {
            margin-left: 0.5rem;
        }

        .phvl-actions-cell {
            white-space: nowrap;
        }

        .phvl-status-btn,
        .phvl-result-btn {
            font-size: 0.75rem;
            padding: 0.2rem 0.55rem;
            line-height: 1.4;
        }

        .phvl-status-btn:disabled,
        .phvl-result-btn:disabled {
            opacity: 0.45;
            cursor: not-allowed;
            pointer-events: none;
        }

        #phvlTable .insurance-status {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        #phvlTable .insurance-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            display: inline-block;
        }

        #phvlTable .insurance-status-dot--inactive {
            background: #ea5455;
        }

        .gap-1 { gap: 0.35rem; }

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

        .phvl-card-body {
            overflow-x: hidden;
        }

        #phvlTable_wrapper {
            width: 100%;
            max-width: 100%;
            overflow: hidden;
        }

        .phvl-dt-top {
            width: 100%;
            overflow: visible;
        }

        .phvl-dt-top::after {
            content: '';
            display: table;
            clear: both;
        }

        .phvl-dt-top .dataTables_length {
            float: left;
        }

        .phvl-dt-top .dataTables_filter {
            float: right;
        }

        .phvl-dt-scroll {
            width: 100%;
            clear: both;
            max-height: calc(100vh - 280px);
            overflow: auto;
            -webkit-overflow-scrolling: touch;
        }

        .phvl-dt-scroll thead th {
            position: sticky !important;
            top: 0;
            z-index: 3;
            background: #fff;
            box-shadow: 0 1px 0 #ebe9f1;
        }

        .phvl-dt-scroll-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            clear: both;
            margin-bottom: 0.35rem;
        }

        .phvl-dt-scroll-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border: 1px solid #d8d6de;
            border-radius: 0.25rem;
            color: #6e6b7b;
            background: #fff;
            cursor: pointer;
            padding: 0;
        }

        .phvl-dt-scroll-btn:hover:not(:disabled),
        .phvl-dt-scroll-btn:focus:not(:disabled) {
            border-color: #7367f0;
            color: #7367f0;
            outline: none;
        }

        .phvl-dt-scroll-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .phvl-dt-scroll::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }

        .phvl-dt-scroll::-webkit-scrollbar-thumb {
            background: #d8d6de;
            border-radius: 4px;
        }

        .phvl-dt-scroll table {
            min-width: 1500px;
            width: max-content;
            margin-bottom: 0;
        }

        .phvl-dt-bottom {
            width: 100%;
            margin-top: 0.75rem;
            overflow: visible;
        }

        .phvl-dt-bottom::after {
            content: '';
            display: table;
            clear: both;
        }

        .phvl-dt-bottom .dataTables_info {
            float: left;
            padding-top: 0.75rem;
        }

        .phvl-dt-bottom .dataTables_paginate {
            float: right;
        }
    </style>
@endsection

@section('content')
    <section id="basic-datatable">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center py-75">
                        <h4 class="card-title mb-0 mr-2">PHVL</h4>
                        <div class="d-flex align-items-center">
                            <label class="mb-0 mr-50 small text-muted" for="phvl-type-filter">Show</label>
                            <select id="phvl-type-filter" class="custom-select custom-select-sm" style="width:auto;">
                                <option value="all">All</option>
                                <option value="need_to_apply">Need to apply</option>
                                <option value="renewal">Renewal</option>
                            </select>
                        </div>
                        <div class="btn-group ml-auto">
                            <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle" id="phvlExportDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fa fa-download mr-50"></i> Export
                            </button>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="phvlExportDropdown">
                                <button type="button" class="dropdown-item" id="phvlExportCsv">Export CSV</button>
                                <button type="button" class="dropdown-item" id="phvlExportPdf">Export PDF</button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body px-1 pt-1 pb-0 phvl-card-body">
                        @include('alerts')
                        <table id="phvlTable" class="table table-bordered table-striped w-100">
                                <thead>
                                <tr>
                                    <th>Make &amp; Model</th>
                                    <th>Car Registration</th>
                                    <th>Company Name</th>
                                    <th>Council</th>
                                    <th>Expiry Detail</th>
                                    <th>MOT Status</th>
                                    <th>MOT Date</th>
                                    <th>Days Since MOT</th>
                                    <th>Application Status</th>
                                    <th>PHVL applied date</th>
                                    <th>Appointment Confirmation</th>
                                    <th>Appointment Date &amp; Time</th>
                                    <th>PHVL Status</th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="cars-filter-backdrop" id="phvlFilterBackdrop"></div>
    <aside class="cars-filter-panel" id="phvlFilterPanel" aria-hidden="true">
        <div class="cars-filter-panel__header">
            <h5 class="mb-0">Advanced Search</h5>
            <button type="button" class="close" id="phvlFilterClose" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="cars-filter-panel__body">
            <div class="form-group">
                <label for="phvlFilterMotStatus">MOT Status</label>
                <select id="phvlFilterMotStatus" class="form-control">
                    <option value="">All</option>
                    <option value="pending">Pending</option>
                    <option value="done">Done</option>
                </select>
            </div>
            <div class="form-group">
                <label for="phvlFilterApplicationStatus">Application Status</label>
                <select id="phvlFilterApplicationStatus" class="form-control">
                    <option value="">All</option>
                    <option value="pending">Pending</option>
                    <option value="applied">Applied</option>
                </select>
            </div>
            <div class="form-group">
                <label for="phvlFilterAppointmentConfirmation">Appointment Confirmation</label>
                <select id="phvlFilterAppointmentConfirmation" class="form-control">
                    <option value="">All</option>
                    <option value="pending">Pending</option>
                    <option value="additional_documents">Additional Documents required</option>
                    <option value="approved">Approved</option>
                </select>
            </div>
            <div class="form-group">
                <label>PHVL Appointment</label>
                <label class="small text-muted mb-25 d-block" for="phvlFilterAppointmentFrom">From</label>
                <input type="date" id="phvlFilterAppointmentFrom" class="form-control mb-1">
                <label class="small text-muted mb-25 d-block" for="phvlFilterAppointmentTo">To</label>
                <input type="date" id="phvlFilterAppointmentTo" class="form-control">
            </div>
            <button type="button" class="btn btn-primary btn-block mb-1" id="phvlFilterApply">Apply</button>
            <button type="button" class="btn btn-outline-secondary btn-block" id="phvlFilterReset">Reset Filters</button>
        </div>
    </aside>

    {{-- Shared field popup --}}
    <div class="modal fade" id="phvlFieldModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header py-75">
                    <h5 class="modal-title" id="phvlFieldModalTitle">Update</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body" id="phvl-field-body"></div>
                <div class="modal-footer py-75">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm" id="phvl-field-save">Save</button>
                </div>
            </div>
        </div>
    </div>

    {{-- PHVL Result popup (Pass / Fail / —) --}}
    <div class="modal fade" id="phvlResultModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header py-75">
                    <h5 class="modal-title">PHVL Status</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="phvl-result-car-id" value="">
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" id="phvl-result-select">
                            <option value="">—</option>
                            <option value="pass">Pass</option>
                            <option value="fail">Fail</option>
                        </select>
                    </div>
                    <div class="form-group mb-0 d-none" id="phvl-result-notes-group">
                        <label for="phvl-result-notes">Fail notes</label>
                        <textarea id="phvl-result-notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer py-75">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm" id="phvl-result-save">Save</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Add PHV modal --}}
    <div class="modal fade" id="phvlAddPhvModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header py-75">
                    <h5 class="modal-title">Add PHV details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <form id="phvlAddPhvForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="phvl-add-phv-car-id" value="">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Council</label>
                                    <select class="form-control" id="phvl_phv_counsel_id" name="counsel_id" required>
                                        <option value="">— Select —</option>
                                        @foreach($counsels as $c)
                                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Amount</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="amount" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Start date</label>
                                    <input type="date" class="form-control" name="start_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Expiry date</label>
                                    <input type="date" class="form-control" name="expiry_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Notify (days before expiry)</label>
                                    <input type="number" min="1" class="form-control" name="notify_before_expiry" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Document <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control-file" name="document" accept=".pdf,.jpg,.jpeg,.png" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer py-75">
                        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-sm">Save PHV</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Appointment notes modal --}}
    <div class="modal fade" id="phvlAppointmentNotesModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header py-75">
                    <h5 class="modal-title">Appointment notes</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="phvl-appointment-notes-car-id" value="">
                    <div class="form-group mb-0">
                        <label>Notes</label>
                        <textarea id="phvl-appointment-notes-text" class="form-control" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer py-75">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary btn-sm" id="phvl-appointment-notes-save">Save</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Fail notes modal --}}
    <div class="modal fade" id="phvlFailNotesModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header py-75">
                    <h5 class="modal-title">PHVL fail notes</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="phvl-fail-notes-car-id" value="">
                    <div class="form-group mb-0">
                        <label>Notes</label>
                        <textarea id="phvl-fail-notes-text" class="form-control" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer py-75">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary btn-sm" id="phvl-fail-notes-save">Save</button>
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
            var csrfToken = document.querySelector('meta[name="csrf-token"]');
            csrfToken = csrfToken ? csrfToken.getAttribute('content') : '';

            var phvlProgressBase = @json(url('/admin/phvl/progress'));
            var phvlBase = @json(url('/admin/phvl'));

            function progressUrl(carId) { return phvlProgressBase + '/' + carId; }
            function completePassUrl(carId) { return phvlBase + '/' + carId + '/complete-pass'; }
            function addMotUrl(carId) { return phvlBase + '/' + carId + '/add-mot'; }

            function patchProgress(carId, body) {
                return fetch(progressUrl(carId), {
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    body: JSON.stringify(body)
                }).then(function (r) {
                    return r.json().then(function (d) {
                        if (!r.ok) {
                            var msg = (d && d.message) || 'Save failed';
                            if (d && d.errors) msg = Object.values(d.errors).flat().join(' ');
                            throw new Error(msg);
                        }
                        return d;
                    });
                });
            }

            var $modal = window.jQuery;

            // ==================== DataTable ====================
            var table = $('#phvlTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: @json(route('phvl.data')),
                    dataSrc: 'data',
                    data: function (d) {
                        d.type = document.getElementById('phvl-type-filter').value;
                        d.appointment_from = document.getElementById('phvlFilterAppointmentFrom').value;
                        d.appointment_to = document.getElementById('phvlFilterAppointmentTo').value;
                        d.mot_status = document.getElementById('phvlFilterMotStatus').value;
                        d.application_status = document.getElementById('phvlFilterApplicationStatus').value;
                        d.appointment_confirmation = document.getElementById('phvlFilterAppointmentConfirmation').value;
                    }
                },
                columns: [
                    { data: 'make_model' },
                    { data: 'registration' },
                    { data: 'company' },
                    { data: 'council' },
                    { data: 'expiry_detail', orderData: [13] },
                    { data: 'mot_status', orderable: false },
                    { data: 'mot_date' },
                    { data: 'mot_days_old', orderable: false },
                    { data: 'application_status', orderable: false },
                    { data: 'applied_date', orderable: false },
                    { data: 'appointment_confirmation', orderable: false },
                    { data: 'appointment_at', orderable: false },
                    { data: 'phvl_actions', orderable: false, searchable: false },
                    { data: 'expiry_sort', visible: false, searchable: false, orderable: true }
                ],
                columnDefs: [
                    { targets: 0, width: '120px' },
                    { targets: 1, width: '115px' },
                    { targets: 2, width: '130px' },
                    { targets: 3, width: '130px' },
                    { targets: 4, width: '120px' },
                    { targets: 5, width: '90px' },
                    { targets: 6, width: '90px' },
                    { targets: 7, width: '100px' },
                    { targets: 8, width: '110px' },
                    { targets: 9, width: '100px' },
                    { targets: 10, width: '130px' },
                    { targets: 11, width: '140px' },
                    { targets: 12, width: '160px' },
                    { targets: 13, width: '0px', visible: false }
                ],
                order: [[4, 'asc']],
                pageLength: 25,
                autoWidth: false,
                initComplete: function () {
                    var $wrapper = $('#phvlTable_wrapper');
                    if (! $wrapper.find('.phvl-dt-scroll').length) {
                        var $top = $('<div class="phvl-dt-top"></div>');
                        var $scrollControls = $(
                            '<div class="phvl-dt-scroll-controls mt-1">' +
                            '  <button type="button" class="phvl-dt-scroll-btn" id="phvlScrollLeft" title="Scroll table left" aria-label="Scroll table left"><i class="fa fa-chevron-left"></i></button>' +
                            '  <button type="button" class="phvl-dt-scroll-btn" id="phvlScrollRight" title="Scroll table right" aria-label="Scroll table right"><i class="fa fa-chevron-right"></i></button>' +
                            '</div>'
                        );
                        var $scroll = $('<div class="phvl-dt-scroll"></div>');
                        var $bottom = $('<div class="phvl-dt-bottom"></div>');

                        $wrapper.find('.dataTables_length').appendTo($top);
                        $wrapper.find('.dataTables_filter').appendTo($top);
                        $wrapper.find('.dataTables_info').appendTo($bottom);
                        $wrapper.find('.dataTables_paginate').appendTo($bottom);
                        $wrapper.find('table.dataTable').appendTo($scroll);

                        $wrapper.empty().append($top).append($scrollControls).append($scroll).append($bottom);
                    }

                    if (! document.getElementById('phvlFilterOpen')) {
                        $('#phvlTable_filter').append(
                            '<button type="button" class="cars-filter-button" id="phvlFilterOpen" title="Filter" aria-label="Filter"><i class="fa fa-filter"></i></button>'
                        );
                    }

                    initPhvlTableScrollControls();
                },
                drawCallback: function () {
                    updatePhvlTableScrollControls();
                }
            });

            function initPhvlTableScrollControls() {
                var scrollEl = document.querySelector('.phvl-dt-scroll');
                var leftBtn = document.getElementById('phvlScrollLeft');
                var rightBtn = document.getElementById('phvlScrollRight');

                if (!scrollEl || !leftBtn || !rightBtn) {
                    return;
                }

                if (leftBtn.dataset.bound === '1') {
                    updatePhvlTableScrollControls();
                    return;
                }

                leftBtn.dataset.bound = '1';
                rightBtn.dataset.bound = '1';

                leftBtn.addEventListener('click', function () {
                    var step = Math.max(300, Math.floor(scrollEl.clientWidth * 0.8));
                    scrollEl.scrollLeft = Math.max(0, scrollEl.scrollLeft - step);
                    updatePhvlTableScrollControls();
                });

                rightBtn.addEventListener('click', function () {
                    var step = Math.max(300, Math.floor(scrollEl.clientWidth * 0.8));
                    scrollEl.scrollLeft = Math.min(scrollEl.scrollWidth - scrollEl.clientWidth, scrollEl.scrollLeft + step);
                    updatePhvlTableScrollControls();
                });

                scrollEl.addEventListener('scroll', updatePhvlTableScrollControls);
                window.addEventListener('resize', updatePhvlTableScrollControls);
                updatePhvlTableScrollControls();
            }

            function updatePhvlTableScrollControls() {
                var scrollEl = document.querySelector('.phvl-dt-scroll');
                var leftBtn = document.getElementById('phvlScrollLeft');
                var rightBtn = document.getElementById('phvlScrollRight');

                if (!scrollEl || !leftBtn || !rightBtn) {
                    return;
                }

                var maxScroll = scrollEl.scrollWidth - scrollEl.clientWidth;
                var canScroll = maxScroll > 1;

                leftBtn.disabled = !canScroll || scrollEl.scrollLeft <= 0;
                rightBtn.disabled = !canScroll || scrollEl.scrollLeft >= maxScroll - 1;
            }

            document.getElementById('phvl-type-filter').addEventListener('change', function () { table.ajax.reload(); });

            function setPhvlFilterPanelOpen(isOpen) {
                $('#phvlFilterPanel').toggleClass('is-open', isOpen).attr('aria-hidden', isOpen ? 'false' : 'true');
                $('#phvlFilterBackdrop').toggleClass('is-open', isOpen);
            }

            $(document).on('click', '#phvlFilterOpen', function () {
                setPhvlFilterPanelOpen(true);
            });

            $('#phvlFilterClose, #phvlFilterBackdrop').on('click', function () {
                setPhvlFilterPanelOpen(false);
            });

            $('#phvlFilterApply').on('click', function () {
                setPhvlFilterPanelOpen(false);
                table.ajax.reload();
            });

            $('#phvlFilterReset').on('click', function () {
                document.getElementById('phvlFilterMotStatus').value = '';
                document.getElementById('phvlFilterApplicationStatus').value = '';
                document.getElementById('phvlFilterAppointmentConfirmation').value = '';
                document.getElementById('phvlFilterAppointmentFrom').value = '';
                document.getElementById('phvlFilterAppointmentTo').value = '';
                table.ajax.reload();
            });

            // ==================== Shared field popup ====================
            var fieldCarId = null;
            var fieldName = null;
            var fieldHasMotForm = false;
            var fieldLabels = {
                mot_status: 'MOT Status',
                application_status: 'Application Status',
                appointment_confirmation: 'Appointment Confirmation',
                applied_date: 'PHVL applied date',
                appointment_at: 'Appointment Date & Time'
            };

            $('#phvlTable').on('click', '.phvl-status-btn', function () {
                if (this.disabled) return;
                var btn = this;
                fieldCarId = btn.getAttribute('data-car-id');
                fieldName = btn.getAttribute('data-field');
                fieldHasMotForm = btn.getAttribute('data-has-mot-form') === '1';
                var current = btn.getAttribute('data-current') || '';
                var optionsRaw = btn.getAttribute('data-options');
                var inputType = btn.getAttribute('data-input-type') || '';

                document.getElementById('phvlFieldModalTitle').textContent = fieldLabels[fieldName] || 'Update';
                var body = document.getElementById('phvl-field-body');
                body.innerHTML = '';

                if (inputType) {
                    var inp = document.createElement('input');
                    inp.type = inputType;
                    inp.className = 'form-control';
                    inp.id = 'phvl-field-input';
                    inp.value = current;
                    body.appendChild(inp);
                } else if (optionsRaw) {
                    var options = {};
                    try { options = JSON.parse(optionsRaw); } catch (e) {}
                    var sel = document.createElement('select');
                    sel.className = 'form-control';
                    sel.id = 'phvl-field-input';
                    for (var k in options) {
                        var o = document.createElement('option');
                        o.value = k;
                        o.textContent = options[k];
                        if (k === current) o.selected = true;
                        sel.appendChild(o);
                    }
                    body.appendChild(sel);
                }

                if (fieldHasMotForm) {
                    var motSection = document.createElement('div');
                    motSection.id = 'phvl-mot-section';
                    motSection.className = 'mt-2';
                    motSection.innerHTML =
                        '<button type="button" class="btn btn-sm btn-outline-primary" id="phvl-mot-toggle"><i class="fa fa-plus mr-50"></i>Add MOT details</button>' +
                        '<div id="phvl-mot-fields" class="d-none mt-2">' +
                        '  <div class="form-group"><label>Test Date <span class="text-danger">*</span></label><input type="date" class="form-control" id="phvl-mot-test-date" required></div>' +
                        '  <div class="form-group"><label>Expiry Date</label><input type="date" class="form-control" id="phvl-mot-expiry"></div>' +
                        '  <div class="form-group"><label>Amount</label><input type="number" step="0.01" min="0" class="form-control" id="phvl-mot-amount"></div>' +
                        '  <div class="form-group"><label>Term</label><input type="text" class="form-control" id="phvl-mot-term" placeholder="e.g. 12 months"></div>' +
                        '  <div class="form-group mb-0"><label>Document <span class="text-danger">*</span></label><input type="file" class="form-control-file" id="phvl-mot-document" accept=".pdf,.jpg,.jpeg,.png" required></div>' +
                        '</div>';
                    body.appendChild(motSection);
                }

                $modal('#phvlFieldModal').modal('show');

                setTimeout(function () {
                    var toggle = document.getElementById('phvl-mot-toggle');
                    if (toggle) {
                        toggle.addEventListener('click', function () {
                            var f = document.getElementById('phvl-mot-fields');
                            if (f) { f.classList.toggle('d-none'); }
                        });
                    }
                }, 50);
            });

            document.getElementById('phvl-field-save').addEventListener('click', function () {
                if (!fieldCarId || !fieldName) return;

                var inp = document.getElementById('phvl-field-input');
                var val = inp ? inp.value : '';
                if (fieldName === 'applied_date' && !val) {
                    alert('PHVL applied date is required.');
                    return;
                }
                var body = {};
                body[fieldName] = val || null;

                var motExpiry = document.getElementById('phvl-mot-expiry');
                var hasMotData = motExpiry && motExpiry.value;

                if (hasMotData) {
                    var motTestDate = document.getElementById('phvl-mot-test-date');
                    if (!motTestDate || !motTestDate.value) {
                        alert('MOT test date is required when MOT details are provided.');
                        return;
                    }
                    var motDoc = document.getElementById('phvl-mot-document');
                    if (!motDoc || !motDoc.files || !motDoc.files.length) {
                        alert('MOT document is required when MOT details are provided.');
                        return;
                    }
                }

                patchProgress(fieldCarId, body).then(function () {
                    if (!hasMotData) {
                        $modal('#phvlFieldModal').modal('hide');
                        table.ajax.reload(null, false);
                        return;
                    }
                    var fd = new FormData();
                    fd.append('_token', csrfToken);
                    fd.append('expiry_date', document.getElementById('phvl-mot-expiry').value);
                    fd.append('test_date', document.getElementById('phvl-mot-test-date').value);
                    var amt = document.getElementById('phvl-mot-amount');
                    if (amt && amt.value) fd.append('amount', amt.value);
                    var term = document.getElementById('phvl-mot-term');
                    if (term && term.value) fd.append('term', term.value);
                    var doc = document.getElementById('phvl-mot-document');
                    if (doc && doc.files && doc.files.length) fd.append('document', doc.files[0]);

                    return fetch(addMotUrl(fieldCarId), {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                        body: fd
                    }).then(function (r) {
                        return r.json().then(function (d) { if (!r.ok) throw new Error((d && d.message) || 'MOT save failed'); return d; });
                    }).then(function () {
                        $modal('#phvlFieldModal').modal('hide');
                        table.ajax.reload(null, false);
                    });
                }).catch(function (e) {
                    alert(e.message || 'Could not save');
                });
            });

            // ==================== PHVL Result popup ====================
            $('#phvlTable').on('click', '.phvl-result-btn', function () {
                if (this.disabled) return;
                var carId = this.getAttribute('data-car-id');
                var current = this.getAttribute('data-current') || '';
                var notes = this.getAttribute('data-notes') || '';
                document.getElementById('phvl-result-car-id').value = carId;
                document.getElementById('phvl-result-select').value = current;
                document.getElementById('phvl-result-notes').value = notes;
                document.getElementById('phvl-result-notes-group').classList.toggle('d-none', current !== 'fail');
                $modal('#phvlResultModal').modal('show');
            });

            document.getElementById('phvl-result-select').addEventListener('change', function () {
                document.getElementById('phvl-result-notes-group').classList.toggle('d-none', this.value !== 'fail');
            });

            document.getElementById('phvl-result-save').addEventListener('click', function () {
                var carId = document.getElementById('phvl-result-car-id').value;
                var val = document.getElementById('phvl-result-select').value;
                var notes = document.getElementById('phvl-result-notes').value;
                var payload = { phvl_result_status: val || null };
                if (val === 'fail') payload.fail_notes = notes;
                if (val !== 'fail') payload.fail_notes = null;

                patchProgress(carId, payload).then(function () {
                    $modal('#phvlResultModal').modal('hide');
                    table.ajax.reload(null, false);
                }).catch(function (e) {
                    alert(e.message || 'Could not save');
                });
            });

            // ==================== Fail notes modal ====================
            $('#phvlTable').on('click', '.phvl-fail-notes-btn', function () {
                var carId = this.getAttribute('data-car-id');
                var notes = this.getAttribute('data-notes') || '';
                document.getElementById('phvl-fail-notes-car-id').value = carId;
                document.getElementById('phvl-fail-notes-text').value = notes;
                $modal('#phvlFailNotesModal').modal('show');
            });

            document.getElementById('phvl-fail-notes-save').addEventListener('click', function () {
                var carId = document.getElementById('phvl-fail-notes-car-id').value;
                var notes = document.getElementById('phvl-fail-notes-text').value;
                patchProgress(carId, { phvl_result_status: 'fail', fail_notes: notes }).then(function () {
                    $modal('#phvlFailNotesModal').modal('hide');
                    table.ajax.reload(null, false);
                }).catch(function (e) { alert(e.message || 'Could not save'); });
            });

            // ==================== Appointment notes modal ====================
            $('#phvlTable').on('click', '.phvl-appointment-notes-btn', function () {
                var carId = this.getAttribute('data-car-id');
                var notes = this.getAttribute('data-notes') || '';
                document.getElementById('phvl-appointment-notes-car-id').value = carId;
                document.getElementById('phvl-appointment-notes-text').value = notes;
                $modal('#phvlAppointmentNotesModal').modal('show');
            });

            document.getElementById('phvl-appointment-notes-save').addEventListener('click', function () {
                var carId = document.getElementById('phvl-appointment-notes-car-id').value;
                var notes = document.getElementById('phvl-appointment-notes-text').value;
                patchProgress(carId, {
                    appointment_confirmation: 'additional_documents',
                    appointment_notes: notes
                }).then(function () {
                    $modal('#phvlAppointmentNotesModal').modal('hide');
                    table.ajax.reload(null, false);
                }).catch(function (e) { alert(e.message || 'Could not save'); });
            });

            // ==================== Add PHV modal ====================
            $('#phvlTable').on('click', '.phvl-add-phv-btn', function () {
                var carId = this.getAttribute('data-car-id');
                document.getElementById('phvlAddPhvForm').reset();
                document.getElementById('phvl-add-phv-car-id').value = carId;
                $modal('#phvlAddPhvModal').modal('show');
            });

            document.getElementById('phvlAddPhvForm').addEventListener('submit', function (e) {
                e.preventDefault();
                var carId = document.getElementById('phvl-add-phv-car-id').value;
                var docInput = this.querySelector('input[name="document"]');
                if (!docInput || !docInput.files || !docInput.files.length) {
                    alert('PHV document is required when adding PHV details.');
                    return;
                }
                var fd = new FormData(this);

                fetch(completePassUrl(carId), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    body: fd
                }).then(function (r) {
                    return r.json().then(function (d) {
                        if (!r.ok) { var msg = (d && d.message) || 'Save failed'; if (d && d.errors) msg = Object.values(d.errors).flat().join(' '); throw new Error(msg); }
                        return d;
                    });
                }).then(function () {
                    $modal('#phvlAddPhvModal').modal('hide');
                    table.ajax.reload(null, false);
                }).catch(function (err) { alert(err.message || 'Could not save PHV'); });
            });

            // ==================== Export CSV / PDF ====================
            var phvlExportHeaders = [
                'Make & Model',
                'Car Registration',
                'Company Name',
                'Council',
                'Expiry Detail',
                'MOT Status',
                'MOT Date',
                'Days Since MOT',
                'Application Status',
                'PHVL applied date',
                'Appointment Confirmation',
                'Appointment Date & Time',
                'PHVL Status'
            ];
            var phvlExportFilenamePrefix = 'phvl-management';
            var phvlExportTitle = 'PHVL Management';

            function phvlExportFilename(extension) {
                return phvlExportFilenamePrefix + '-' + new Date().toISOString().slice(0, 10) + extension;
            }

            function buildPhvlExportMeta() {
                var lines = [];
                var typeFilter = document.getElementById('phvl-type-filter');
                var typeLabel = typeFilter && typeFilter.options[typeFilter.selectedIndex]
                    ? typeFilter.options[typeFilter.selectedIndex].text
                    : 'All';

                lines.push('Show: ' + typeLabel);

                var motStatusFilter = document.getElementById('phvlFilterMotStatus');
                if (motStatusFilter && motStatusFilter.value) {
                    lines.push('MOT status: ' + motStatusFilter.options[motStatusFilter.selectedIndex].text);
                }

                var applicationStatusFilter = document.getElementById('phvlFilterApplicationStatus');
                if (applicationStatusFilter && applicationStatusFilter.value) {
                    lines.push('Application status: ' + applicationStatusFilter.options[applicationStatusFilter.selectedIndex].text);
                }

                var appointmentConfirmationFilter = document.getElementById('phvlFilterAppointmentConfirmation');
                if (appointmentConfirmationFilter && appointmentConfirmationFilter.value) {
                    lines.push('Appointment confirmation: ' + appointmentConfirmationFilter.options[appointmentConfirmationFilter.selectedIndex].text);
                }

                var appointmentFrom = document.getElementById('phvlFilterAppointmentFrom').value;
                var appointmentTo = document.getElementById('phvlFilterAppointmentTo').value;
                if (appointmentFrom || appointmentTo) {
                    lines.push('Appointment: ' + (appointmentFrom || '—') + ' to ' + (appointmentTo || '—'));
                }

                var searchValue = (table.search() || '').trim();
                if (searchValue) {
                    lines.push('Search: ' + searchValue);
                }

                if (lines.length === 0) {
                    lines.push('Filters: None');
                }

                return {
                    title: phvlExportTitle,
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

            function collectPhvlExportRows() {
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

            function exportPhvlCsv() {
                var exportMeta = buildPhvlExportMeta();
                var bodyRows = collectPhvlExportRows();

                if (bodyRows.length === 0) {
                    alert('No records to export. Adjust your search or filters and try again.');
                    return;
                }

                var lines = [csvEscape(exportMeta.title)];
                exportMeta.lines.forEach(function (line) {
                    lines.push(csvEscape(line));
                });
                lines.push('');
                lines.push(phvlExportHeaders.map(csvEscape).join(','));
                bodyRows.forEach(function (row) {
                    lines.push(row.map(csvEscape).join(','));
                });

                downloadCsv(phvlExportFilename('.csv'), lines);
            }

            function exportPhvlPdf() {
                var exportMeta = buildPhvlExportMeta();
                var bodyRows = collectPhvlExportRows();

                if (bodyRows.length === 0) {
                    alert('No records to export. Adjust your search or filters and try again.');
                    return;
                }

                if (typeof pdfMake === 'undefined') {
                    alert('PDF export is not available. Please refresh the page and try again.');
                    return;
                }

                var tableBody = [
                    phvlExportHeaders.map(function (header) {
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
                                widths: phvlExportHeaders.map(function () { return '*'; }),
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

                pdfMake.createPdf(doc).download(phvlExportFilename('.pdf'));
            }

            $('#phvlExportCsv').on('click', exportPhvlCsv);
            $('#phvlExportPdf').on('click', exportPhvlPdf);
        })();
    </script>
@endsection
