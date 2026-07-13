@extends('layouts.admin', ['title' => 'Agreements'])
@section('content')
    @php
        $filterStatuses = $agreements->map(fn ($a) => optional($a->status)->name)->filter()->unique()->sort()->values();
    @endphp
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
                            <div class="table-responsive">
                                <table id="dataTable" class="table datatable table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th>Company</th>
                                        <th>Driver</th>
                                        <th>Car</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Notice Date</th>
                                        <th>Closing Date</th>
                                        <th>Rent</th>
                                        <th>E-Sign</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($agreements as $agreement)
                                        @php
                                            $startIso = optional($agreement->start_date)->format('Y-m-d') ?? '';
                                            $endIso = optional($agreement->end_date)->format('Y-m-d') ?? '';
                                            $closingIso = optional($agreement->closing_date)->format('Y-m-d') ?? '';
                                            $noticeIso = optional($agreement->termination_notice_date)->format('Y-m-d') ?? '';
                                            $closedOnIso = optional($agreement->effectiveCloseDate())->format('Y-m-d') ?? '';
                                            $statusName = (string) optional($agreement->status)->name;
                                        @endphp
                                        <tr
                                            data-start-date="{{ $startIso }}"
                                            data-end-date="{{ $endIso }}"
                                            data-closing-date="{{ $closingIso }}"
                                            data-notice-date="{{ $noticeIso }}"
                                            data-closed-on="{{ $closedOnIso }}"
                                            data-status="{{ $statusName }}"
                                        >
                                            <td>{{ $agreement->company->name  }}</td>
                                            <td>
                                                <strong>{{ $agreement->driver->full_name }}</strong>
                                                <br>
                                                <span>Post Code: {{ $agreement->driver->post_code }}</span>
                                            </td>
                                            <td>{{ $agreement->car->registration }}</td>
                                            <td>{{ $agreement->start_date->format('M d, Y') }}</td>
                                            <td>{{ $agreement->end_date->format('M d, Y') }}</td>
                                            <td>{{ $agreement->termination_notice_date ? $agreement->termination_notice_date->format('M d, Y') : '—' }}</td>
                                            <td>{{ $agreement->closing_date ? $agreement->closing_date->format('M d, Y') : '—' }}</td>
                                            <td>
                                                @if($agreement->isReplacementVehicle())
                                                    <span class="text-muted">Replacement</span>
                                                @else
                                                    £{{ number_format($agreement->agreed_rent, 2) }}
                                                @endif
                                            </td>
                                            <td>
                                                @if($agreement->hellosign_status)
                                                    <span class="badge {{ $agreement->esign_status_badge }}">
                                                        {{ ucfirst($agreement->hellosign_status) }}
                                                    </span>
                                                    @if($agreement->hellosign_status === 'signed' && $agreement->esign_document_path)
                                                        <br>
                                                        <a href="{{ asset($agreement->esign_document_path) }}"
                                                           class="btn btn-sm btn-success mt-1"
                                                           download
                                                           title="Download Signed Document">
                                                            <i class="fa fa-download"></i>
                                                        </a>
                                                    @endif
                                                @else
                                                    <span class="badge bg-light text-dark">Not Sent</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge"
                                                      style="background-color: {{ $agreement->status->color }}">
                                                    {{ $agreement->status->name }}
                                                </span>
                                                @if($agreement->isReplacementVehicle() && $agreement->parentAgreement)
                                                    <br>
                                                    <small class="text-muted">
                                                        Original:
                                                        <a href="{{ route('agreements.show', $agreement->parentAgreement) }}">
                                                            #{{ $agreement->parentAgreement->id }}
                                                        </a>
                                                    </small>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('agreements.show', $agreement) }}"
                                                       class="btn btn-sm btn-outline-info">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('agreements.edit', $agreement) }}"
                                                       class="btn btn-sm btn-outline-warning">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    @php
                                                        $refundStatus = $agreement->depositRefundStatus();
                                                        $showRefundBtn = $agreement->isClosedForDepositRefund() && (float) $agreement->deposit_amount > 0;
                                                    @endphp
                                                    @if($showRefundBtn)
                                                        @if($refundStatus === 'pending')
                                                            <button type="button"
                                                                    class="btn btn-sm btn-outline-secondary"
                                                                    disabled
                                                                    style="opacity: .45;"
                                                                    title="Deposit refund pending daily financial sheet approval">
                                                                <i class="fa fa-undo"></i>
                                                            </button>
                                                        @elseif($refundStatus === 'posted')
                                                            <button type="button"
                                                                    class="btn btn-sm btn-outline-secondary"
                                                                    disabled
                                                                    style="opacity: .45;"
                                                                    title="Deposit already refunded">
                                                                <i class="fa fa-undo"></i>
                                                            </button>
                                                        @else
                                                            <button type="button"
                                                                    class="btn btn-sm btn-outline-success"
                                                                    data-toggle="modal"
                                                                    data-target="#refundDepositModal"
                                                                    data-refund-deposit-btn
                                                                    data-action="{{ route('agreements.refund-deposit', $agreement) }}"
                                                                    data-amount="{{ number_format((float) $agreement->deposit_amount, 2, '.', '') }}"
                                                                    title="Refund Deposit">
                                                                <i class="fa fa-undo"></i>
                                                            </button>
                                                        @endif
                                                    @endif
                                                    <a href="{{ route('agreements.pdf', $agreement) }}"
                                                       class="btn btn-sm btn-outline-danger" target="_blank"
                                                       title="Generate PDF">
                                                        <i class="fa fa-file-pdf-o"></i>
                                                    </a>
                                                    <form action="{{ route('agreements.destroy', $agreement) }}"
                                                          method="POST" style="display: inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                                onclick="return confirm('Are you sure?')">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="11" class="text-center text-muted py-4">
                                                <i class="fa fa-handshake fa-3x mb-3"></i>
                                                <br>
                                                No agreements found. <a href="{{ route('agreements.create') }}">Create
                                                    your first agreement</a>
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

    <div class="agreements-filter-backdrop" id="agreementsFilterBackdrop"></div>
    <aside class="agreements-filter-panel" id="agreementsFilterPanel" aria-hidden="true">
        <div class="agreements-filter-panel__header">
            <h5 class="mb-0">Advanced Filters</h5>
            <button type="button" class="close" id="agreementsFilterClose" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="agreements-filter-panel__body">
            <div class="form-group">
                <label for="agreementsFilterStatus">Status</label>
                <select id="agreementsFilterStatus" class="form-control agreements-advanced-filter" data-filter-key="status">
                    <option value="">All</option>
                    @foreach($filterStatuses as $status)
                        <option value="{{ $status }}">{{ $status }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Rented between (start date)</label>
                <div class="row">
                    <div class="col-6">
                        <input type="date" id="agreementsRentedFrom" class="form-control agreements-date-filter" data-range="rented" data-bound="from">
                    </div>
                    <div class="col-6">
                        <input type="date" id="agreementsRentedTo" class="form-control agreements-date-filter" data-range="rented" data-bound="to">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Closed between</label>
                <div class="row">
                    <div class="col-6">
                        <input type="date" id="agreementsClosedFrom" class="form-control agreements-date-filter" data-range="closed" data-bound="from">
                    </div>
                    <div class="col-6">
                        <input type="date" id="agreementsClosedTo" class="form-control agreements-date-filter" data-range="closed" data-bound="to">
                    </div>
                </div>
                <small class="text-muted">Expired/Terminated only; uses closing date, else end date.</small>
            </div>

            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="agreementsHasNotice">
                    <label class="custom-control-label" for="agreementsHasNotice">Has termination notice</label>
                </div>
            </div>

            <div class="form-group">
                <label>Notice date between</label>
                <div class="row">
                    <div class="col-6">
                        <input type="date" id="agreementsNoticeFrom" class="form-control agreements-date-filter" data-range="notice" data-bound="from">
                    </div>
                    <div class="col-6">
                        <input type="date" id="agreementsNoticeTo" class="form-control agreements-date-filter" data-range="notice" data-bound="to">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Due / ending between</label>
                <div class="row">
                    <div class="col-6">
                        <input type="date" id="agreementsDueFrom" class="form-control agreements-date-filter" data-range="due" data-bound="from">
                    </div>
                    <div class="col-6">
                        <input type="date" id="agreementsDueTo" class="form-control agreements-date-filter" data-range="due" data-bound="to">
                    </div>
                </div>
                <small class="text-muted">Matches if end date or notice date falls in range.</small>
            </div>

            <button type="button" class="btn btn-outline-secondary btn-block" id="agreementsFilterReset">Reset Filters</button>
        </div>
    </aside>

    @include('backend.agreements.partials.refund-deposit-modal', ['bankAccounts' => $bankAccounts ?? collect()])
@endsection
@section('css')
    <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/tables/datatable/datatables.min.css') }}">
    <style>
        #dataTable_filter {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: .5rem;
        }

        .agreements-filter-button {
            border: 1px solid #d8d6de;
            background: #fff;
            color: #6e6b7b;
            border-radius: .357rem;
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            margin-top: 1rem;
        }

        .agreements-filter-button:hover,
        .agreements-filter-button:focus {
            color: #7367f0;
            border-color: #7367f0;
        }

        .agreements-filter-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .35);
            z-index: 1040;
            display: none;
        }

        .agreements-filter-backdrop.is-open {
            display: block;
        }

        .agreements-filter-panel {
            position: fixed;
            top: 0;
            right: 0;
            width: 360px;
            max-width: 100%;
            height: 100%;
            background: #fff;
            z-index: 1050;
            box-shadow: -4px 0 24px rgba(0, 0, 0, .12);
            transform: translateX(100%);
            transition: transform .25s ease;
            display: flex;
            flex-direction: column;
        }

        .agreements-filter-panel.is-open {
            transform: translateX(0);
        }

        .agreements-filter-panel__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #ebe9f1;
        }

        .agreements-filter-panel__body {
            padding: 1.25rem;
            overflow-y: auto;
            flex: 1;
        }

        #agreementsFilterClose {
            background: transparent;
            border: 0;
            font-size: 1.5rem;
            line-height: 1;
            opacity: .6;
        }
    </style>
@endsection
@section('js')
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.bootstrap4.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            const filters = {
                status: '',
                hasNotice: false,
                rented: { from: '', to: '' },
                closed: { from: '', to: '' },
                notice: { from: '', to: '' },
                due: { from: '', to: '' },
            };

            const dataTable = $('#dataTable').DataTable({
                processing: true,
                responsive: true,
                order: [],
            });

            $('#dataTable_filter').append(
                '<button type="button" class="agreements-filter-button" id="agreementsFilterOpen" title="Filter" aria-label="Filter"><i class="fa fa-filter"></i></button>'
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
                const value = parseDateYmd(iso);
                if (!value) return false;
                const from = parseDateYmd(fromStr);
                const to = parseDateYmd(toStr);
                if (from && value < from) return false;
                if (to && value > to) return false;
                return true;
            }

            function isRangeActive(range) {
                return !!(range.from || range.to);
            }

            function passesDateRange(iso, range) {
                if (!isRangeActive(range)) {
                    return true;
                }
                return dateInRange(iso, range.from, range.to);
            }

            function isClosedStatus(status) {
                const name = (status || '').toLowerCase();
                return name === 'expired' || name === 'terminated';
            }

            function syncFiltersFromForm() {
                filters.status = document.getElementById('agreementsFilterStatus').value;
                filters.hasNotice = document.getElementById('agreementsHasNotice').checked;
                filters.rented.from = document.getElementById('agreementsRentedFrom').value;
                filters.rented.to = document.getElementById('agreementsRentedTo').value;
                filters.closed.from = document.getElementById('agreementsClosedFrom').value;
                filters.closed.to = document.getElementById('agreementsClosedTo').value;
                filters.notice.from = document.getElementById('agreementsNoticeFrom').value;
                filters.notice.to = document.getElementById('agreementsNoticeTo').value;
                filters.due.from = document.getElementById('agreementsDueFrom').value;
                filters.due.to = document.getElementById('agreementsDueTo').value;
            }

            function passesFilters(row) {
                if (filters.status && row.dataset.status !== filters.status) {
                    return false;
                }

                if (!passesDateRange(row.dataset.startDate || '', filters.rented)) {
                    return false;
                }

                if (isRangeActive(filters.closed)) {
                    if (!isClosedStatus(row.dataset.status)) {
                        return false;
                    }
                    if (!passesDateRange(row.dataset.closedOn || '', filters.closed)) {
                        return false;
                    }
                }

                if (filters.hasNotice && !(row.dataset.noticeDate || '')) {
                    return false;
                }

                if (isRangeActive(filters.notice)) {
                    if (!passesDateRange(row.dataset.noticeDate || '', filters.notice)) {
                        return false;
                    }
                }

                if (isRangeActive(filters.due)) {
                    const endMatch = dateInRange(row.dataset.endDate || '', filters.due.from, filters.due.to);
                    const noticeMatch = dateInRange(row.dataset.noticeDate || '', filters.due.from, filters.due.to);
                    if (!endMatch && !noticeMatch) {
                        return false;
                    }
                }

                return true;
            }

            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                if (settings.nTable.id !== 'dataTable') {
                    return true;
                }

                const row = dataTable.row(dataIndex).node();
                if (!row) {
                    return true;
                }

                return passesFilters(row);
            });

            function setFilterPanelOpen(isOpen) {
                $('#agreementsFilterPanel').toggleClass('is-open', isOpen).attr('aria-hidden', isOpen ? 'false' : 'true');
                $('#agreementsFilterBackdrop').toggleClass('is-open', isOpen);
            }

            $(document).on('click', '#agreementsFilterOpen', function () {
                setFilterPanelOpen(true);
            });

            $('#agreementsFilterClose, #agreementsFilterBackdrop').on('click', function () {
                setFilterPanelOpen(false);
            });

            $('#agreementsFilterStatus, #agreementsHasNotice').on('change', function () {
                syncFiltersFromForm();
                dataTable.draw();
            });

            $('.agreements-date-filter').on('change input', function () {
                syncFiltersFromForm();
                dataTable.draw();
            });

            $('#agreementsFilterReset').on('click', function () {
                $('#agreementsFilterStatus').val('');
                $('#agreementsHasNotice').prop('checked', false);
                $('#agreementsRentedFrom, #agreementsRentedTo, #agreementsClosedFrom, #agreementsClosedTo, #agreementsNoticeFrom, #agreementsNoticeTo, #agreementsDueFrom, #agreementsDueTo').val('');
                syncFiltersFromForm();
                dataTable.draw();
            });
        });
    </script>
@endsection
