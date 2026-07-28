@extends('layouts.admin', ['title' => 'Driver Payments'])
@section('content')
    <section id="basic-datatable">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Driver Payments</h4>
                        <a class="btn btn-primary float-right" href="{{ route('payments.create') }}">
                            <i class="fa fa-plus"></i> Add Payment
                        </a>
                    </div>
                    <div class="card-content">
                        <div class="card-body card-dashboard">
                            @include('alerts')
                            <div class="payments-table-toolbar" id="paymentsTableToolbar"></div>
                            <div class="table-responsive">
                                <table id="dataTable" class="table datatable table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th>Driver</th>
                                        <th>Vehicle</th>
                                        <th>Phone</th>
                                        <th>Invoices</th>
                                        <th>Payments</th>
                                        <th>Payment Due</th>
                                        <th>Last Payment</th>
                                        <th>Total Due</th>
                                        <th>Credit</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($drivers as $driver)
                                        @php
                                            $lastPaymentIso = $driver->last_posted_payment_date
                                                ? \Carbon\Carbon::parse($driver->last_posted_payment_date)->format('Y-m-d')
                                                : '';
                                            $latestInvoiceIso = $driver->latest_invoice_date
                                                ? \Carbon\Carbon::parse($driver->latest_invoice_date)->format('Y-m-d')
                                                : '';
                                            $remindAtIso = $driver->payment_remind_at?->toIso8601String() ?? '';
                                        @endphp
                                        <tr
                                            data-driver-status="{{ $driver->is_active ? 'active' : 'inactive' }}"
                                            data-remind-at="{{ $remindAtIso }}"
                                            data-last-payment-date="{{ $lastPaymentIso }}"
                                            data-latest-invoice-date="{{ $latestInvoiceIso }}"
                                        >
                                            <td>
                                                <strong>{{ $driver->selectOptionLabel() ?: 'N/A' }}</strong>
                                            </td>
                                            <td>
                                                @php
                                                    $registrations = $driver->agreements
                                                        ->pluck('car.registration')
                                                        ->filter()
                                                        ->unique()
                                                        ->values();
                                                @endphp
                                                {{ $registrations->isNotEmpty() ? $registrations->implode(', ') : '—' }}
                                            </td>
                                            <td>{{ $driver->phone_number ?? 'N/A' }}</td>
                                            <td>{{ $driver->invoices_count }}</td>
                                            <td>{{ $driver->payments_count }}</td>
                                            <td>
                                                {{ $latestInvoiceIso ? \Carbon\Carbon::parse($latestInvoiceIso)->format('d M Y') : '—' }}
                                            </td>
                                            <td>
                                                {{ $lastPaymentIso ? \Carbon\Carbon::parse($lastPaymentIso)->format('d M Y') : '—' }}
                                            </td>
                                            <td>
                                                @php
                                                    $pendingDfsAmount = (float) ($driver->pending_dfs_amount ?? 0);
                                                    $hasPendingDfs = $driver->total_due > 0 && $pendingDfsAmount > 0;
                                                    $totalDueClass = $driver->total_due > 0
                                                        ? ($hasPendingDfs ? 'text-warning' : 'text-danger')
                                                        : 'text-muted';
                                                    $pendingDfsTooltip = $hasPendingDfs
                                                        ? '£'.number_format($pendingDfsAmount, 2).' pending daily financial sheet approval.'
                                                        : null;
                                                @endphp
                                                <strong class="{{ $totalDueClass }}{{ $hasPendingDfs ? ' js-dfs-pending-amount' : '' }}"
                                                    @if($pendingDfsTooltip)
                                                        data-toggle="tooltip"
                                                        data-placement="top"
                                                        title="{{ $pendingDfsTooltip }}"
                                                    @endif
                                                >
                                                    £{{ number_format($driver->total_due, 2) }}
                                                </strong>
                                            </td>
                                            <td>
                                                <strong class="{{ $driver->credit_amount > 0 ? 'text-success' : 'text-muted' }}">
                                                    £{{ number_format($driver->credit_amount, 2) }}
                                                </strong>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('payments.driver', $driver) }}"
                                                       class="btn btn-sm btn-outline-info js-action-tooltip"
                                                       data-toggle="tooltip" data-placement="top"
                                                       title="View Driver Payments" aria-label="View Driver Payments">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('payments.create', ['driver_id' => $driver->id]) }}"
                                                       class="btn btn-sm btn-outline-primary js-action-tooltip"
                                                       data-toggle="tooltip" data-placement="top"
                                                       title="Add Payment" aria-label="Add Payment">
                                                        <i class="fa fa-plus"></i>
                                                    </a>
                                                    @php
                                                        $hasFollowUp = $driver->hasPaymentFollowUpNote() || $driver->hasPaymentReminder();
                                                        $followUpBtnClass = $hasFollowUp ? 'btn-warning' : 'btn-outline-secondary';
                                                    @endphp
                                                    <button type="button"
                                                            class="btn btn-sm {{ $followUpBtnClass }} js-action-tooltip js-driver-follow-up"
                                                            data-toggle="tooltip" data-placement="top"
                                                            title="Notes/Reminder"
                                                            aria-label="Notes/Reminder"
                                                            data-driver-id="{{ $driver->id }}"
                                                            data-driver-name="{{ $driver->selectOptionLabel() ?: trim($driver->first_name.' '.$driver->last_name) }}"
                                                            data-notes="{{ $driver->payment_follow_up_notes ?? '' }}"
                                                            data-remind-at="{{ $driver->payment_remind_at?->toIso8601String() ?? '' }}"
                                                            data-update-url="{{ route('payments.follow-up.update', $driver) }}">
                                                        <i class="fa fa-sticky-note"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center text-muted py-4">
                                                <i class="fa fa-user fa-3x mb-3"></i>
                                                <br>
                                                No drivers found.
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

    <div class="payments-filter-backdrop" id="paymentsFilterBackdrop"></div>
    <aside class="payments-filter-panel" id="paymentsFilterPanel" aria-hidden="true">
        <div class="payments-filter-panel__header">
            <h5 class="mb-0">Advanced Search</h5>
            <button type="button" class="close" id="paymentsFilterClose" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="payments-filter-panel__body">
            <div class="form-group">
                <label for="paymentsFilterStatus">Driver Status</label>
                <select id="paymentsFilterStatus" class="form-control payments-advanced-filter" data-filter-key="driverStatus">
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="form-group">
                <label class="d-block mb-50">Reminder between</label>
                <div class="form-row payments-date-row">
                    <div class="col-6">
                        <label class="small text-muted mb-25 d-block" for="paymentsReminderFrom">From</label>
                        <input type="datetime-local" id="paymentsReminderFrom" class="form-control payments-datetime-filter">
                    </div>
                    <div class="col-6">
                        <label class="small text-muted mb-25 d-block" for="paymentsReminderTo">To</label>
                        <input type="datetime-local" id="paymentsReminderTo" class="form-control payments-datetime-filter">
                    </div>
                </div>
                <small class="text-muted d-block mt-50">Uses the driver note/reminder date and time.</small>
            </div>

            <div class="form-group">
                <label class="d-block mb-50">Last payment between</label>
                <div class="form-row payments-date-row">
                    <div class="col-6">
                        <label class="small text-muted mb-25 d-block" for="paymentsLastPaymentFrom">From</label>
                        <input type="date" id="paymentsLastPaymentFrom" class="form-control payments-date-filter">
                    </div>
                    <div class="col-6">
                        <label class="small text-muted mb-25 d-block" for="paymentsLastPaymentTo">To</label>
                        <input type="date" id="paymentsLastPaymentTo" class="form-control payments-date-filter">
                    </div>
                </div>
                <small class="text-muted d-block mt-50">Uses latest posted payment date.</small>
            </div>

            <div class="form-group">
                <label class="d-block mb-50">Latest invoice date between</label>
                <div class="form-row payments-date-row">
                    <div class="col-6">
                        <label class="small text-muted mb-25 d-block" for="paymentsLatestInvoiceFrom">From</label>
                        <input type="date" id="paymentsLatestInvoiceFrom" class="form-control payments-date-filter">
                    </div>
                    <div class="col-6">
                        <label class="small text-muted mb-25 d-block" for="paymentsLatestInvoiceTo">To</label>
                        <input type="date" id="paymentsLatestInvoiceTo" class="form-control payments-date-filter">
                    </div>
                </div>
                <small class="text-muted d-block mt-50">Uses the most recent invoice date for the driver.</small>
            </div>

            <button type="button" class="btn btn-outline-secondary btn-block" id="paymentsFilterReset">Reset Filters</button>
        </div>
    </aside>

    @include('backend.payments.partials.follow-up-modal')
@endsection
@section('css')
    <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/tables/datatable/datatables.min.css') }}">
    <style>
        #dataTable_filter {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .payments-table-toolbar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .payments-table-controls {
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

        .payments-filter-button {
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

        .payments-filter-button:hover,
        .payments-filter-button:focus {
            border-color: #7367f0;
            color: #7367f0;
            outline: none;
        }

        .payments-filter-backdrop {
            position: fixed;
            inset: 0;
            z-index: 1040;
            display: none;
            background: rgba(34, 41, 47, .35);
        }

        .payments-filter-backdrop.is-open {
            display: block;
        }

        .payments-filter-panel {
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

        .payments-filter-panel.is-open {
            transform: translateX(0);
        }

        .payments-filter-panel__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #ebe9f1;
        }

        .payments-filter-panel__body {
            height: calc(100vh - 65px);
            padding: 1.25rem;
            overflow-y: auto;
        }

        #paymentsFilterClose {
            padding: 0.3rem 0.7rem;
        }

        .payments-date-row {
            margin-left: 0;
            margin-right: 0;
        }

        .payments-date-row > [class*="col-"] {
            padding-left: 0;
            padding-right: 0.5rem;
        }

        .payments-date-row > [class*="col-"]:last-child {
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
            function initializeActionTooltips() {
                $('.js-action-tooltip').tooltip({ container: 'body' });
                $('.js-dfs-pending-amount[data-toggle="tooltip"]').tooltip({ container: 'body' });
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

            const advancedFilters = {
                driverStatus: '',
                reminderFrom: '',
                reminderTo: '',
                lastPaymentFrom: '',
                lastPaymentTo: '',
                latestInvoiceFrom: '',
                latestInvoiceTo: '',
            };

            const $filter = $('#dataTable_filter');
            const $toolbar = $('#paymentsTableToolbar');
            if ($filter.length && $toolbar.length && !$filter.parent().hasClass('payments-table-controls')) {
                const $controls = $('<div class="payments-table-controls"></div>');
                $filter.before($controls);
                $controls.append($toolbar);
                $controls.append($filter);
            }

            $('#dataTable_filter').append(
                '<button type="button" class="payments-filter-button" id="paymentsFilterOpen" title="Filter" aria-label="Filter"><i class="fa fa-filter"></i></button>'
            );

            function parseDateYmd(value) {
                if (!value) {
                    return null;
                }
                const parts = value.split('-');
                if (parts.length !== 3) {
                    return null;
                }
                const date = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
                return isNaN(date.getTime()) ? null : date;
            }

            function dateInRange(iso, fromStr, toStr) {
                if (!iso) {
                    return false;
                }
                const date = parseDateYmd(iso);
                if (!date) {
                    return false;
                }
                const from = parseDateYmd(fromStr);
                const to = parseDateYmd(toStr);
                if (from && date < from) {
                    return false;
                }
                if (to && date > to) {
                    return false;
                }
                return true;
            }

            function datetimeInRange(iso, fromLocal, toLocal) {
                if (!iso) {
                    return false;
                }
                const value = new Date(iso);
                if (isNaN(value.getTime())) {
                    return false;
                }
                if (fromLocal) {
                    const from = new Date(fromLocal);
                    if (!isNaN(from.getTime()) && value < from) {
                        return false;
                    }
                }
                if (toLocal) {
                    const to = new Date(toLocal);
                    if (!isNaN(to.getTime()) && value > to) {
                        return false;
                    }
                }
                return true;
            }

            function passesReminderFilter(row) {
                if (!advancedFilters.reminderFrom && !advancedFilters.reminderTo) {
                    return true;
                }

                return datetimeInRange(
                    row.dataset.remindAt || '',
                    advancedFilters.reminderFrom,
                    advancedFilters.reminderTo
                );
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

            function passesLatestInvoiceFilter(row) {
                if (!advancedFilters.latestInvoiceFrom && !advancedFilters.latestInvoiceTo) {
                    return true;
                }

                return dateInRange(
                    row.dataset.latestInvoiceDate || '',
                    advancedFilters.latestInvoiceFrom,
                    advancedFilters.latestInvoiceTo
                );
            }

            function syncFiltersFromForm() {
                advancedFilters.driverStatus = $('#paymentsFilterStatus').val() || '';
                advancedFilters.reminderFrom = $('#paymentsReminderFrom').val() || '';
                advancedFilters.reminderTo = $('#paymentsReminderTo').val() || '';
                advancedFilters.lastPaymentFrom = $('#paymentsLastPaymentFrom').val() || '';
                advancedFilters.lastPaymentTo = $('#paymentsLastPaymentTo').val() || '';
                advancedFilters.latestInvoiceFrom = $('#paymentsLatestInvoiceFrom').val() || '';
                advancedFilters.latestInvoiceTo = $('#paymentsLatestInvoiceTo').val() || '';
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

                if (!passesReminderFilter(row)) {
                    return false;
                }

                if (!passesLastPaymentFilter(row)) {
                    return false;
                }

                if (!passesLatestInvoiceFilter(row)) {
                    return false;
                }

                return true;
            });

            function setFilterPanelOpen(isOpen) {
                $('#paymentsFilterPanel').toggleClass('is-open', isOpen).attr('aria-hidden', isOpen ? 'false' : 'true');
                $('#paymentsFilterBackdrop').toggleClass('is-open', isOpen);
            }

            $(document).on('click', '#paymentsFilterOpen', function () {
                setFilterPanelOpen(true);
            });

            $('#paymentsFilterClose, #paymentsFilterBackdrop').on('click', function () {
                setFilterPanelOpen(false);
            });

            $('.payments-advanced-filter').on('change', function () {
                syncFiltersFromForm();
                dataTable.draw();
            });

            $('.payments-date-filter, .payments-datetime-filter').on('change input', function () {
                syncFiltersFromForm();
                dataTable.draw();
            });

            $('#paymentsFilterReset').on('click', function () {
                $('#paymentsFilterStatus').val('');
                $('#paymentsReminderFrom, #paymentsReminderTo, #paymentsLastPaymentFrom, #paymentsLastPaymentTo, #paymentsLatestInvoiceFrom, #paymentsLatestInvoiceTo').val('');
                syncFiltersFromForm();
                dataTable.draw();
            });

            const $modal = $('#driverFollowUpModal');
            const $form = $('#driverFollowUpForm');
            const $notes = $('#driverFollowUpNotes');
            const $setReminder = $('#driverFollowUpSetReminder');
            const $remindAtGroup = $('#driverFollowUpRemindAtGroup');
            const $remindAt = $('#driverFollowUpRemindAt');
            const $error = $('#driverFollowUpError');
            const $subtitle = $('#driverFollowUpModalSubtitle');
            let activeButton = null;

            function pad2(n) {
                return String(n).padStart(2, '0');
            }

            /** Convert ISO/UTC (or datetime-local) to input[type=datetime-local] value in the browser's local timezone. */
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

            /** datetime-local is timezone-naive; send real instant as ISO so server UTC matches the clock the user picked. */
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

            function toggleRemindAt() {
                if ($setReminder.is(':checked')) {
                    $remindAtGroup.show();
                } else {
                    $remindAtGroup.hide();
                    $remindAt.val('');
                }
            }

            $setReminder.on('change', toggleRemindAt);

            $(document).on('click', '.js-driver-follow-up', function () {
                activeButton = $(this);
                $error.hide().text('');
                $form.attr('action', activeButton.data('update-url'));
                $subtitle.text(activeButton.data('driver-name') || '');
                $notes.val(activeButton.attr('data-notes') || '');
                const remindAt = activeButton.attr('data-remind-at') || '';
                $setReminder.prop('checked', !!remindAt);
                $remindAt.val(toDatetimeLocalValue(remindAt));
                toggleRemindAt();
                $modal.modal('show');
            });

            $form.on('submit', function (e) {
                e.preventDefault();
                $error.hide().text('');

                const payload = {
                    notes: $notes.val(),
                    set_reminder: $setReminder.is(':checked') ? 1 : 0,
                    remind_at: $setReminder.is(':checked') ? datetimeLocalToIso($remindAt.val()) : null,
                    _method: 'PATCH',
                    _token: '{{ csrf_token() }}'
                };

                $('#driverFollowUpSaveBtn').prop('disabled', true);

                $.ajax({
                    url: $form.attr('action'),
                    method: 'POST',
                    data: payload,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                }).done(function (response) {
                    const driver = response.driver || {};
                    if (activeButton && activeButton.length) {
                        activeButton.attr('data-notes', driver.notes || '');
                        activeButton.attr('data-remind-at', driver.remind_at || '');
                        activeButton
                            .removeClass('btn-warning btn-outline-secondary')
                            .addClass(driver.has_note || driver.has_reminder ? 'btn-warning' : 'btn-outline-secondary');
                    }
                    if (window.clearPaymentFollowUpSnooze && driver.id) {
                        window.clearPaymentFollowUpSnooze(driver.id);
                    }
                    $modal.modal('hide');
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
                    $error.text(message).show();
                }).always(function () {
                    $('#driverFollowUpSaveBtn').prop('disabled', false);
                });
            });
        });
    </script>
@endsection
