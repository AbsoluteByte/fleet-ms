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
                    <div class="card-header py-75">
                        <h4 class="card-title mb-0">PHVL Archive</h4>
                        <p class="text-muted small mb-0 mt-50">Completed PHVL cycles. View timeline for step-by-step history.</p>
                    </div>
                    <hr class="my-0">
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
                    { data: 'completed_at' },
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
        })();
    </script>
@endsection
