@extends('layouts.admin', ['title' => 'Damaged Cars — PHVL Suspension'])

@section('css')
    <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/tables/datatable/datatables.min.css') }}">
    <style>
        .navbar-floating .header-navbar-shadow {
            height: 160px !important;
        }

        #damagedCarsTable.table thead th,
        #damagedCarsTable.table tbody td {
            vertical-align: middle;
            padding: 0.45rem 0.65rem;
            font-size: 0.8125rem;
        }

        .damaged-cars-tabs .nav-link {
            font-size: 0.875rem;
            padding: 0.5rem 0.85rem;
        }
    </style>
@endsection

@section('content')
    <section id="damaged-cars-datatable">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header py-75">
                        <h4 class="card-title mb-0">Damaged Cars</h4>
                        <p class="text-muted small mb-0 mt-50">
                            Non-fault damaged vehicles — council PHVL suspension tracking and 60-day limit warnings.
                        </p>
                    </div>
                    <div class="card-body px-1 pt-1 pb-0">
                        @include('alerts')
                        <ul class="nav nav-tabs damaged-cars-tabs mb-1" id="damagedCarsTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" href="#" data-tab="all">All</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#" data-tab="suspended">Suspended</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#" data-tab="suspension_uplifted">Suspension uplifted</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#" data-tab="licence_revoked">Licence revoked</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#" data-tab="active">Active (not suspended)</a>
                            </li>
                        </ul>
                        <div class="table-responsive">
                            <table id="damagedCarsTable" class="table table-bordered table-striped w-100">
                                <thead>
                                <tr>
                                    <th>Registration</th>
                                    <th>Make &amp; model</th>
                                    <th>Company</th>
                                    <th>Damage date</th>
                                    <th>Incident date</th>
                                    <th>Claim ref</th>
                                    <th>PHVL status</th>
                                    <th>Status date</th>
                                    <th>Days suspended</th>
                                    <th>60-day warning</th>
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

    <div class="modal fade" id="damagedCarsStatusModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="damagedCarsStatusForm">
                    <div class="modal-header py-75">
                        <h5 class="modal-title">Change PHVL suspension status</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-2" id="damagedCarsStatusCarLabel"></p>
                        <div class="form-group">
                            <label for="damaged_cars_phvl_status">PHVL suspension status <span class="text-danger">*</span></label>
                            <select name="phvl_suspension_status" id="damaged_cars_phvl_status" class="form-control" required>
                                @foreach($statusLabels as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group d-none" id="damaged_cars_phvl_date_wrap">
                            <label for="damaged_cars_phvl_date">Status date <span class="text-danger">*</span></label>
                            <input type="date" name="phvl_suspension_status_date" id="damaged_cars_phvl_date" class="form-control">
                        </div>
                        <div class="form-group mb-0">
                            <label for="damaged_cars_phvl_notes">Notes</label>
                            <textarea name="phvl_suspension_notes" id="damaged_cars_phvl_notes" rows="2" class="form-control" placeholder="Optional"></textarea>
                        </div>
                        <div id="damagedCarsStatusError" class="alert alert-danger mt-1 mb-0 d-none" role="alert"></div>
                    </div>
                    <div class="modal-footer py-75">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm" id="damagedCarsStatusSubmit">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.bootstrap4.min.js') }}"></script>
    <script>
        (function () {
            var activeTab = 'all';
            var selectedCarId = null;
            var csrf = @json(csrf_token());
            var dataUrl = @json(route('phvl.damaged-cars.data'));
            var updateUrlTemplate = @json(url('/admin/phvl/damaged-cars'));

            function toggleModalDateField() {
                var status = String($('#damaged_cars_phvl_status').val() || 'active');
                var show = status !== 'active';
                $('#damaged_cars_phvl_date_wrap').toggleClass('d-none', !show);
                $('#damaged_cars_phvl_date').prop('required', show);
            }

            var table = $('#damagedCarsTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: dataUrl,
                    data: function (d) {
                        d.tab = activeTab;
                    },
                    dataSrc: 'data'
                },
                columns: [
                    { data: 'registration' },
                    { data: 'make_model' },
                    { data: 'company' },
                    { data: 'damage_date' },
                    { data: 'incident_date' },
                    { data: 'claim_ref' },
                    { data: 'phvl_status' },
                    { data: 'phvl_status_date' },
                    { data: 'days_suspended' },
                    { data: 'suspension_warning', orderable: false, searchable: false },
                    { data: 'actions', orderable: false, searchable: false }
                ],
                order: [[0, 'asc']],
                pageLength: 25,
                columnDefs: [
                    { targets: 9, render: function (data) { return data; } },
                    { targets: 10, render: function (data) { return data; } }
                ]
            });

            $('#damagedCarsTabs .nav-link').on('click', function (e) {
                e.preventDefault();
                $('#damagedCarsTabs .nav-link').removeClass('active');
                $(this).addClass('active');
                activeTab = $(this).data('tab');
                table.ajax.reload();
            });

            $('#damaged_cars_phvl_status').on('change', toggleModalDateField);

            $(document).on('click', '.damaged-cars-change-status', function () {
                selectedCarId = $(this).data('car-id');
                var reg = $(this).data('registration');
                var currentStatus = String($(this).data('current-status') || 'active');
                var currentDate = String($(this).data('current-date') || '');

                $('#damagedCarsStatusCarLabel').text('Vehicle: ' + reg);
                $('#damaged_cars_phvl_status').val(currentStatus);
                $('#damaged_cars_phvl_date').val(currentDate);
                $('#damaged_cars_phvl_notes').val('');
                $('#damagedCarsStatusError').addClass('d-none').empty();
                toggleModalDateField();
                $('#damagedCarsStatusModal').modal('show');
            });

            $('#damagedCarsStatusForm').on('submit', function (e) {
                e.preventDefault();
                if (!selectedCarId) {
                    return;
                }

                var $btn = $('#damagedCarsStatusSubmit');
                $btn.prop('disabled', true);
                $('#damagedCarsStatusError').addClass('d-none').empty();

                $.ajax({
                    url: updateUrlTemplate + '/' + selectedCarId + '/phvl-status',
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': csrf },
                    data: $(this).serialize(),
                    success: function () {
                        $('#damagedCarsStatusModal').modal('hide');
                        table.ajax.reload(null, false);
                    },
                    error: function (xhr) {
                        var msg = 'Unable to update status.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            msg = Object.values(xhr.responseJSON.errors).flat().join(' ');
                        }
                        $('#damagedCarsStatusError').removeClass('d-none').text(msg);
                    },
                    complete: function () {
                        $btn.prop('disabled', false);
                    }
                });
            });
        })();
    </script>
@endsection
