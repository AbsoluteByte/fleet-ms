@extends('layouts.admin', ['title' => 'Drivers'])
@section('content')
    <section id="basic-datatable">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ $plural }}</h4>
                        <a class="btn btn-primary float-right" href="{{ route($url . 'create') }}"><i
                                class="fa fa-plus"></i>
                            Add {{ $singular }}</a>
                    </div>
                    <div class="card-content">
                        <div class="card-body card-dashboard">
                            @include('alerts')
                            <div class="drivers-table-toolbar" id="driversTableToolbar"></div>
                            <div class="table-responsive">
                                <table id="dataTable" class="table datatable table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>License Expiry</th>
                                        <th>Status</th>
                                        <th>Invitation Status</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($drivers as $driver)
                                        @php
                                            $documentFields = [
                                                'driver_license_document',
                                                'dvla_license_summary',
                                                'proof_of_address_document',
                                                'phd_card_document',
                                                'driver_phd_license_document',
                                                'misc_document',
                                            ];
                                            $hasMissingDocuments = collect($documentFields)->contains(
                                                fn (string $field) => trim((string) ($driver->{$field} ?? '')) === ''
                                            );
                                            $lastPaymentIso = $driver->last_posted_payment_date
                                                ? \Carbon\Carbon::parse($driver->last_posted_payment_date)->format('Y-m-d')
                                                : '';
                                            $activeAgreementEnds = $driver->agreements
                                                ->pluck('end_date')
                                                ->filter()
                                                ->map(fn ($date) => $date->format('Y-m-d'))
                                                ->values()
                                                ->implode(',');
                                        @endphp
                                        <tr
                                            data-driver-status="{{ $driver->is_active ? 'active' : 'inactive' }}"
                                            data-has-missing-documents="{{ $hasMissingDocuments ? '1' : '0' }}"
                                            data-last-payment-date="{{ $lastPaymentIso }}"
                                            data-active-agreement-ends="{{ $activeAgreementEnds }}"
                                        >
                                            <td style="width: 500px !important;">
                                                <strong>{{ $driver->full_name }}</strong>
                                                <br>
                                                <span>Post Code: {{ $driver->post_code ?: '—' }}</span>
                                                <br>
                                                <small class="text-muted">DOB: {{ $driver->dob?->format('M d, Y') ?? '—' }}</small>
                                            </td>
                                            <td>{{ $driver->email }}</td>
                                            <td>{{ $driver->phone_number }}</td>
                                            <td>
                                                @if($driver->driver_license_expiry_date)
                                                    @if($driver->driver_license_expiry_date->isPast())
                                                        <span class="badge bg-danger">
                                                        Expired {{ $driver->driver_license_expiry_date->format('M d, Y') }}
                                                        </span>
                                                    @elseif($driver->driver_license_expiry_date->diffInDays(now()) <= 30)
                                                        <span class="badge bg-warning">
                                                         Expires {{ $driver->driver_license_expiry_date->format('M d, Y') }}
                                                         </span>
                                                    @else
                                                        <span class="badge bg-success">
                                                            {{ $driver->driver_license_expiry_date->format('M d, Y') }}
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($driver->is_active)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($driver->hasAcceptedInvitation())
                                                    <span class="badge bg-success">
                                        <i class="feather icon-check me-1"></i>
                                        Accepted
                                    </span>
                                                @elseif($driver->is_invited)
                                                    @if($driver->isInvitationExpired())
                                                        <span class="badge bg-danger">
                                            <i class="feather icon-x me-1"></i>
                                            Expired
                                        </span>
                                                    @else
                                                        <span class="badge bg-warning">
                                            <i class="feather icon-clock me-1"></i>
                                            Pending
                                        </span>
                                                    @endif
                                                @else
                                                    <span class="badge bg-secondary">
                                        <i class="feather icon-mail me-1"></i>
                                        Not Invite
                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">

                                                    <a href="{{ route('drivers.show', $driver) }}"
                                                       class="btn btn-sm btn-outline-info js-action-tooltip"
                                                       data-toggle="tooltip" data-placement="top"
                                                       title="View Driver" aria-label="View Driver"
                                                       style="margin-right:5px;">
                                                        <i class="fa fa-eye"></i>
                                                    </a>

                                                    <a href="{{ route('drivers.edit', $driver) }}"
                                                       class="btn btn-sm btn-outline-warning js-action-tooltip"
                                                       data-toggle="tooltip" data-placement="top"
                                                       title="Edit Driver" aria-label="Edit Driver"
                                                       style="margin-right:5px;">
                                                        <i class="fa fa-edit"></i>
                                                    </a>

                                                    @if($driver->canBeInvited())
                                                        <button type="button" class="btn btn-sm btn-outline-primary js-action-tooltip"
                                                                data-toggle="tooltip" data-placement="top"
                                                                title="Send Invitation" aria-label="Send Invitation"
                                                                style="margin-right:5px;"
                                                                onclick="inviteDriver({{ $driver->id }}, '{{ $driver->full_name }}')">
                                                            <i class="feather icon-send"></i>
                                                        </button>
                                                    @elseif($driver->is_invited && !$driver->hasAcceptedInvitation())
                                                        <button type="button" class="btn btn-sm btn-outline-secondary js-action-tooltip"
                                                                data-toggle="tooltip" data-placement="top"
                                                                title="Resend Invitation" aria-label="Resend Invitation"
                                                                style="margin-right:5px;"
                                                                onclick="resendInvitation({{ $driver->id }}, '{{ $driver->full_name }}')">
                                                            <i class="feather icon-refresh-cw"></i>
                                                            Resend
                                                        </button>
                                                    @endif

                                                    <form action="{{ route('drivers.destroy', $driver) }}" method="POST"
                                                          style="display: inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger js-action-tooltip"
                                                                data-toggle="tooltip" data-placement="top"
                                                                title="Delete Driver" aria-label="Delete Driver"
                                                                style="margin-right:5px;"
                                                                onclick="return confirm('Are you sure?')">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>

                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="fa fa-users fa-3x mb-3"></i>
                                                <br>
                                                No drivers found. <a href="{{ route('drivers.create') }}">Add your first
                                                    driver</a>
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

    <div class="drivers-filter-backdrop" id="driversFilterBackdrop"></div>
    <aside class="drivers-filter-panel" id="driversFilterPanel" aria-hidden="true">
        <div class="drivers-filter-panel__header">
            <h5 class="mb-0">Advanced Search</h5>
            <button type="button" class="close" id="driversFilterClose" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="drivers-filter-panel__body">
            <div class="form-group">
                <label for="driversFilterStatus">Driver Status</label>
                <select id="driversFilterStatus" class="form-control drivers-advanced-filter" data-filter-key="driverStatus">
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input drivers-advanced-filter" id="driversFilterMissingDocuments" data-filter-key="missingDocuments">
                    <label class="custom-control-label" for="driversFilterMissingDocuments">Drivers with missing documents</label>
                </div>
                <small class="text-muted d-block mt-50">Any of: licence, DVLA summary, proof of address, PHD licence/card, misc.</small>
            </div>

            <div class="form-group">
                <label class="d-block mb-50">Last payment between</label>
                <div class="form-row drivers-date-row">
                    <div class="col-6">
                        <label class="small text-muted mb-25 d-block" for="driversLastPaymentFrom">From</label>
                        <input type="date" id="driversLastPaymentFrom" class="form-control drivers-date-filter" data-filter-key="lastPaymentFrom">
                    </div>
                    <div class="col-6">
                        <label class="small text-muted mb-25 d-block" for="driversLastPaymentTo">To</label>
                        <input type="date" id="driversLastPaymentTo" class="form-control drivers-date-filter" data-filter-key="lastPaymentTo">
                    </div>
                </div>
                <small class="text-muted d-block mt-50">Uses latest posted payment date.</small>
            </div>

            <div class="form-group">
                <label class="d-block mb-50">Active agreement ending between</label>
                <div class="form-row drivers-date-row">
                    <div class="col-6">
                        <label class="small text-muted mb-25 d-block" for="driversAgreementEndFrom">From</label>
                        <input type="date" id="driversAgreementEndFrom" class="form-control drivers-date-filter" data-filter-key="agreementEndFrom">
                    </div>
                    <div class="col-6">
                        <label class="small text-muted mb-25 d-block" for="driversAgreementEndTo">To</label>
                        <input type="date" id="driversAgreementEndTo" class="form-control drivers-date-filter" data-filter-key="agreementEndTo">
                    </div>
                </div>
                <small class="text-muted d-block mt-50">Matches if any active agreement end date falls in this range.</small>
            </div>

            <button type="button" class="btn btn-outline-secondary btn-block" id="driversFilterReset">Reset Filters</button>
        </div>
    </aside>

    <!-- Invitation Modal -->
    <div class="modal fade" id="invitationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Invite Driver</h5>
                    <button type="button" class="btn-close" data-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to send an invitation to <strong id="driverName"></strong>?</p>
                    <p class="text-muted">The driver will receive an email with instructions to set up their account and
                        access the driver portal.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <form id="invitationForm" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            <i class="feather icon-send me-1"></i>
                            Send Invitation
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('css')
    <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/tables/datatable/datatables.min.css') }}">
    <style>
        #dataTable_filter {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .drivers-table-toolbar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .drivers-table-controls {
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

        #dataTable_filter label {
            display: flex;
            align-items: center;
            margin-bottom: 0;
        }

        #dataTable_filter input {
            margin-left: .5rem;
        }

        .drivers-filter-button {
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

        .drivers-filter-button:hover,
        .drivers-filter-button:focus {
            border-color: #7367f0;
            color: #7367f0;
            outline: none;
        }

        .drivers-filter-backdrop {
            position: fixed;
            inset: 0;
            z-index: 1040;
            display: none;
            background: rgba(34, 41, 47, .35);
        }

        .drivers-filter-backdrop.is-open {
            display: block;
        }

        .drivers-filter-panel {
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

        .drivers-filter-panel.is-open {
            transform: translateX(0);
        }

        .drivers-filter-panel__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #ebe9f1;
        }

        .drivers-filter-panel__body {
            height: calc(100vh - 65px);
            padding: 1.25rem;
            overflow-y: auto;
        }

        #driversFilterClose {
            padding: 0.3rem 0.7rem;
        }

        .drivers-date-row {
            margin-left: 0;
            margin-right: 0;
        }

        .drivers-date-row > [class*="col-"] {
            padding-left: 0;
            padding-right: 0.5rem;
        }

        .drivers-date-row > [class*="col-"]:last-child {
            padding-right: 0;
            padding-left: 0.5rem;
        }
    </style>
@endsection
@section('js')
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.bootstrap4.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            const advancedFilters = {
                driverStatus: '',
                missingDocuments: false,
                lastPaymentFrom: '',
                lastPaymentTo: '',
                agreementEndFrom: '',
                agreementEndTo: '',
            };

            function initializeActionTooltips() {
                $('.js-action-tooltip').tooltip({ container: 'body' });
            }

            const dataTable = $('#dataTable').DataTable({
                processing: true,
                responsive: true,
            });

            initializeActionTooltips();
            dataTable.on('draw.dt responsive-display.dt', function () {
                $('.tooltip').remove();
                initializeActionTooltips();
            });

            const $filter = $('#dataTable_filter');
            const $toolbar = $('#driversTableToolbar');
            if ($filter.length && $toolbar.length && !$filter.parent().hasClass('drivers-table-controls')) {
                const $controls = $('<div class="drivers-table-controls"></div>');
                $filter.before($controls);
                $controls.append($toolbar);
                $controls.append($filter);
            }

            $('#dataTable_filter').append(
                '<button type="button" class="drivers-filter-button" id="driversFilterOpen" title="Filter" aria-label="Filter"><i class="fa fa-filter"></i></button>'
            );

            function parseDateYmd(value) {
                if (!value) return null;
                const parts = value.split('-');
                if (parts.length !== 3) return null;
                const date = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
                return isNaN(date.getTime()) ? null : date;
            }

            function dateInRange(iso, fromStr, toStr) {
                if (!iso) return false;
                const date = parseDateYmd(iso);
                if (!date) return false;
                const from = parseDateYmd(fromStr);
                const to = parseDateYmd(toStr);
                if (from && date < from) return false;
                if (to && date > to) return false;
                return true;
            }

            function anyAgreementEndInRange(row, fromStr, toStr) {
                if (!fromStr && !toStr) {
                    return true;
                }

                const raw = row.dataset.activeAgreementEnds || '';
                if (!raw) {
                    return false;
                }

                return raw.split(',').some(function (iso) {
                    return dateInRange(iso.trim(), fromStr, toStr);
                });
            }

            function passesLastPaymentFilter(row) {
                if (!advancedFilters.lastPaymentFrom && !advancedFilters.lastPaymentTo) {
                    return true;
                }

                return dateInRange(
                    row.dataset.lastPaymentDate || '',
                    advancedFilters.lastPaymentFrom,
                    advancedFilters.lastPaymentTo
                );
            }

            function syncFiltersFromForm() {
                advancedFilters.driverStatus = $('#driversFilterStatus').val() || '';
                advancedFilters.missingDocuments = $('#driversFilterMissingDocuments').is(':checked');
                advancedFilters.lastPaymentFrom = $('#driversLastPaymentFrom').val() || '';
                advancedFilters.lastPaymentTo = $('#driversLastPaymentTo').val() || '';
                advancedFilters.agreementEndFrom = $('#driversAgreementEndFrom').val() || '';
                advancedFilters.agreementEndTo = $('#driversAgreementEndTo').val() || '';
            }

            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                if (settings.nTable.id !== 'dataTable') {
                    return true;
                }

                const row = dataTable.row(dataIndex).node();
                if (!row) {
                    return true;
                }

                if (advancedFilters.driverStatus
                    && row.dataset.driverStatus !== advancedFilters.driverStatus) {
                    return false;
                }

                if (advancedFilters.missingDocuments
                    && row.dataset.hasMissingDocuments !== '1') {
                    return false;
                }

                if (!passesLastPaymentFilter(row)) {
                    return false;
                }

                if (!anyAgreementEndInRange(
                    row,
                    advancedFilters.agreementEndFrom,
                    advancedFilters.agreementEndTo
                )) {
                    return false;
                }

                return true;
            });

            function setFilterPanelOpen(isOpen) {
                $('#driversFilterPanel').toggleClass('is-open', isOpen).attr('aria-hidden', isOpen ? 'false' : 'true');
                $('#driversFilterBackdrop').toggleClass('is-open', isOpen);
            }

            $(document).on('click', '#driversFilterOpen', function () {
                setFilterPanelOpen(true);
            });

            $('#driversFilterClose, #driversFilterBackdrop').on('click', function () {
                setFilterPanelOpen(false);
            });

            $('.drivers-advanced-filter').on('change', function () {
                syncFiltersFromForm();
                dataTable.draw();
            });

            $('.drivers-date-filter').on('change input', function () {
                syncFiltersFromForm();
                dataTable.draw();
            });

            $('#driversFilterReset').on('click', function () {
                $('#driversFilterStatus').val('');
                $('#driversFilterMissingDocuments').prop('checked', false);
                $('#driversLastPaymentFrom, #driversLastPaymentTo, #driversAgreementEndFrom, #driversAgreementEndTo').val('');
                syncFiltersFromForm();
                dataTable.draw();
            });
        });

        function inviteDriver(driverId, driverName) {
            document.getElementById('driverName').textContent = driverName;
            document.getElementById('invitationForm').action = `drivers/${driverId}/invite`;

            new bootstrap.Modal(document.getElementById('invitationModal')).show();
        }

        function resendInvitation(driverId, driverName) {
            document.getElementById('driverName').textContent = driverName;
            document.getElementById('invitationForm').action = `drivers/${driverId}/resend-invitation`;

            new bootstrap.Modal(document.getElementById('invitationModal')).show();
        }
    </script>
@endsection
