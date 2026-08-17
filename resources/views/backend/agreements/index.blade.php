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
                            <div class="agreements-table-toolbar" id="agreementsTableToolbar">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle" id="agreementsExportDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fa fa-download mr-50"></i> Export
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="agreementsExportDropdown">
                                        <button type="button" class="dropdown-item" id="agreementsExportCsv">Export CSV</button>
                                        <button type="button" class="dropdown-item" id="agreementsExportPdf">Export PDF</button>
                                    </div>
                                </div>
                            </div>
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
                                            $statusName = (string) optional($agreement->status)->name;
                                            $noticeIso = (in_array(strtolower($statusName), ['active', 'swap'], true) && $agreement->termination_notice_date)
                                                ? $agreement->termination_notice_date->format('Y-m-d')
                                                : '';
                                            $closedOnIso = optional($agreement->effectiveCloseDate())->format('Y-m-d') ?? '';
                                            $isBillableForNotice = in_array(strtolower($statusName), ['active', 'swap'], true) ? '1' : '0';
                                            // Filter labels match the action button:
                                            // refunded = refund already recorded (grey button)
                                            // pending  = eligible to refund, not recorded yet (green button)
                                            $filterRefundStatus = $agreement->depositRefund
                                                ? 'refunded'
                                                : (
                                                    $agreement->isClosedForDepositRefund()
                                                    && (float) $agreement->deposit_amount > 0
                                                        ? 'pending'
                                                        : ''
                                                );
                                        @endphp
                                        <tr
                                            data-start-date="{{ $startIso }}"
                                            data-end-date="{{ $endIso }}"
                                            data-closing-date="{{ $closingIso }}"
                                            data-notice-date="{{ $noticeIso }}"
                                            data-is-billable="{{ $isBillableForNotice }}"
                                            data-closed-on="{{ $closedOnIso }}"
                                            data-status="{{ $statusName }}"
                                            data-refund-status="{{ $filterRefundStatus }}"
                                        >
                                            <td>{{ $agreement->company->name  }}</td>
                                            <td>
                                                <strong>{{ $agreement->driver->full_name }}</strong>
                                                @if($agreement->paying_company_name)
                                                    <br>
                                                    <span class="text-muted">Pays via: {{ $agreement->paying_company_name }}</span>
                                                @endif
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
                                                       class="btn btn-sm btn-outline-info js-action-tooltip"
                                                       data-toggle="tooltip" data-placement="top"
                                                       title="View Agreement" aria-label="View Agreement">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('agreements.edit', $agreement) }}"
                                                       class="btn btn-sm btn-outline-warning js-action-tooltip"
                                                       data-toggle="tooltip" data-placement="top"
                                                       title="Edit Agreement" aria-label="Edit Agreement">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    @if(app(\App\Services\AgreementUpgradeService::class)->canRenew($agreement))
                                                        <a href="{{ route('agreements.renew', $agreement) }}"
                                                           class="btn btn-sm btn-outline-primary js-action-tooltip"
                                                           data-toggle="tooltip" data-placement="top"
                                                           title="Renew Agreement" aria-label="Renew Agreement">
                                                            <i class="fa fa-refresh"></i>
                                                        </a>
                                                    @endif
                                                    @php
                                                        $refundStatus = $agreement->depositRefundStatus();
                                                        $showRefundBtn = $refundStatus !== null || $agreement->canRequestDepositRefund();
                                                        $settlement = $agreement->deposit_settlement_preview ?? null;
                                                    @endphp
                                                    @if($showRefundBtn)
                                                        @if($refundStatus === 'pending')
                                                            <span class="d-inline-flex js-action-tooltip"
                                                                  data-toggle="tooltip" data-placement="top"
                                                                  title="Deposit Refund Pending Daily Financial Sheet Approval"
                                                                  tabindex="0">
                                                                <button type="button"
                                                                        class="btn btn-sm btn-outline-secondary"
                                                                        disabled
                                                                        style="opacity: .45;"
                                                                        aria-label="Deposit Refund Pending Daily Financial Sheet Approval">
                                                                    <i class="fa fa-undo"></i>
                                                                </button>
                                                            </span>
                                                        @elseif($refundStatus === 'posted')
                                                            <span class="d-inline-flex js-action-tooltip"
                                                                  data-toggle="tooltip" data-placement="top"
                                                                  title="Deposit Already Refunded"
                                                                  tabindex="0">
                                                                <button type="button"
                                                                        class="btn btn-sm btn-outline-secondary"
                                                                        disabled
                                                                        style="opacity: .45;"
                                                                        aria-label="Deposit Already Refunded">
                                                                    <i class="fa fa-undo"></i>
                                                                </button>
                                                            </span>
                                                        @else
                                                            <span class="d-inline-flex js-action-tooltip"
                                                                  data-toggle="tooltip" data-placement="top"
                                                                  title="Refund Deposit">
                                                                <button type="button"
                                                                        class="btn btn-sm btn-outline-success"
                                                                        data-toggle="modal"
                                                                        data-target="#refundDepositModal"
                                                                        data-refund-deposit-btn
                                                                        data-action="{{ route('agreements.refund-deposit', $agreement) }}"
                                                                        data-amount="{{ number_format((float) ($settlement['refund_amount'] ?? 0), 2, '.', '') }}"
                                                                        data-gross-deposit="{{ number_format((float) ($settlement['gross_deposit_amount'] ?? 0), 2, '.', '') }}"
                                                                        data-deductions="{{ number_format((float) ($settlement['deductions_amount'] ?? 0), 2, '.', '') }}"
                                                                        data-driver-outstanding="{{ number_format((float) ($settlement['driver_outstanding_amount'] ?? 0), 2, '.', '') }}"
                                                                        data-debt-offset="{{ number_format((float) ($settlement['debt_offset_amount'] ?? 0), 2, '.', '') }}"
                                                                        data-remaining-debt="{{ number_format((float) ($settlement['remaining_debt_amount'] ?? 0), 2, '.', '') }}"
                                                                        aria-label="Refund Deposit">
                                                                    <i class="fa fa-undo"></i>
                                                                </button>
                                                            </span>
                                                        @endif
                                                    @endif
                                                    <a href="{{ route('agreements.pdf', $agreement) }}"
                                                       class="btn btn-sm btn-outline-danger js-action-tooltip" target="_blank"
                                                       data-toggle="tooltip" data-placement="top"
                                                       title="Generate PDF" aria-label="Generate PDF">
                                                        <i class="fa fa-file-pdf-o"></i>
                                                    </a>
                                                    <form action="{{ route('agreements.destroy', $agreement) }}"
                                                          method="POST" style="display: inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger js-action-tooltip"
                                                                data-toggle="tooltip" data-placement="top"
                                                                title="Delete Agreement" aria-label="Delete Agreement"
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
                <small class="text-muted">Terminated only; uses closing date, else end date. If only From is set, To defaults to today.</small>
            </div>

            <div class="form-group">
                <label>Expired between</label>
                <div class="row">
                    <div class="col-6">
                        <input type="date" id="agreementsExpiredFrom" class="form-control agreements-date-filter" data-range="expired" data-bound="from">
                    </div>
                    <div class="col-6">
                        <input type="date" id="agreementsExpiredTo" class="form-control agreements-date-filter" data-range="expired" data-bound="to">
                    </div>
                </div>
                <small class="text-muted">Expired agreements only; matches the original agreement end date.</small>
            </div>

            <div class="form-group">
                <label for="agreementsFilterRefundStatus">Refund status</label>
                <select id="agreementsFilterRefundStatus" class="form-control agreements-advanced-filter" data-filter-key="refundStatus">
                    <option value="">All</option>
                    <option value="refunded">Refunded</option>
                    <option value="pending">Refund Pending</option>
                </select>
            </div>

            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="agreementsHasNotice">
                    <label class="custom-control-label" for="agreementsHasNotice">Has termination notice</label>
                </div>
                <small class="text-muted">Active or Swap agreements only.</small>
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
                <small class="text-muted">Active or Swap agreements with a termination notice in this range.</small>
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

        .agreements-table-toolbar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .agreements-table-controls {
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
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/pdfmake.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/vfs_fonts.js') }}"></script>
    <script>
        $(document).ready(function () {
            const filters = {
                status: '',
                hasNotice: false,
                rented: { from: '', to: '' },
                closed: { from: '', to: '' },
                expired: { from: '', to: '' },
                notice: { from: '', to: '' },
                due: { from: '', to: '' },
                refundStatus: '',
            };

            function initializeActionTooltips() {
                $('.js-action-tooltip').tooltip({ container: 'body' });
            }

            const dataTable = $('#dataTable').DataTable({
                processing: true,
                responsive: true,
                order: [],
            });

            initializeActionTooltips();
            dataTable.on('draw.dt responsive-display.dt', function () {
                $('.tooltip').remove();
                initializeActionTooltips();
            });

            const $filter = $('#dataTable_filter');
            const $toolbar = $('#agreementsTableToolbar');
            if ($filter.length && $toolbar.length && !$filter.parent().hasClass('agreements-table-controls')) {
                const $controls = $('<div class="agreements-table-controls"></div>');
                $filter.before($controls);
                $controls.append($toolbar);
                $controls.append($filter);
            }

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

            function formatDisplayDate(iso) {
                if (!iso) return '';
                const date = parseDateYmd(iso);
                if (!date) return iso;
                return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
            }

            function formatDateRangeLine(label, range) {
                if (!isRangeActive(range)) {
                    return '';
                }

                const fromLabel = range.from ? formatDisplayDate(range.from) : 'any';
                const toLabel = range.to ? formatDisplayDate(range.to) : 'any';

                return label + ': ' + fromLabel + ' to ' + toLabel;
            }

            function selectedOptionText(selectId) {
                const select = document.getElementById(selectId);
                if (!select || !select.value) {
                    return '';
                }

                return select.options[select.selectedIndex]?.text || select.value;
            }

            const agreementsExportHeaders = [
                'Company', 'Driver', 'Car', 'Start Date', 'End Date',
                'Notice Date', 'Closing Date', 'Rent', 'E-Sign', 'Status'
            ];
            const agreementsExportFilenamePrefix = 'agreement-list';
            const agreementsExportTitle = 'Agreement List';

            function agreementsExportFilename(extension) {
                return agreementsExportFilenamePrefix + '-' + new Date().toISOString().slice(0, 10) + extension;
            }

            function getAgreementsExportHeaders() {
                return agreementsExportHeaders.slice();
            }

            function buildAgreementsExportMeta() {
                syncFiltersFromForm();

                const lines = [];
                const searchTerm = (dataTable.search() || '').trim();

                if (searchTerm) {
                    lines.push('Search: ' + searchTerm);
                }

                if (filters.status) {
                    lines.push('Status: ' + selectedOptionText('agreementsFilterStatus'));
                }

                const rentedLine = formatDateRangeLine('Rented between', filters.rented);
                if (rentedLine) {
                    lines.push(rentedLine);
                }

                if (isRangeActive(filters.closed)) {
                    const fromLabel = filters.closed.from ? formatDisplayDate(filters.closed.from) : 'any';
                    const toLabel = filters.closed.to
                        ? formatDisplayDate(filters.closed.to)
                        : (filters.closed.from ? formatDisplayDate(closedRangeTo(filters.closed)) : 'any');
                    lines.push('Closed between: ' + fromLabel + ' to ' + toLabel);
                }

                const expiredLine = formatDateRangeLine('Expired between', filters.expired);
                if (expiredLine) {
                    lines.push(expiredLine);
                }

                if (filters.refundStatus) {
                    lines.push('Refund status: ' + selectedOptionText('agreementsFilterRefundStatus'));
                }

                if (filters.hasNotice) {
                    lines.push('Has termination notice: Yes');
                }

                const noticeLine = formatDateRangeLine('Notice date between', filters.notice);
                if (noticeLine) {
                    lines.push(noticeLine);
                }

                const dueLine = formatDateRangeLine('Due / ending between', filters.due);
                if (dueLine) {
                    lines.push(dueLine);
                }

                if (lines.length === 0) {
                    lines.push('Filters: None');
                }

                return {
                    title: agreementsExportTitle,
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

            function collectAgreementsExportRows() {
                const rows = [];
                dataTable.rows({ search: 'applied', order: 'applied' }).every(function () {
                    const node = this.node();
                    if (!node) {
                        return;
                    }

                    const cells = node.querySelectorAll('td');
                    if (cells.length < 10) {
                        return;
                    }

                    const row = [];
                    for (let i = 0; i < 10; i++) {
                        row.push(cells[i].innerText.replace(/\s+/g, ' ').trim());
                    }

                    rows.push(row);
                });

                return rows;
            }

            function exportAgreementsCsv() {
                const exportMeta = buildAgreementsExportMeta();
                const bodyRows = collectAgreementsExportRows();
                const exportHeaders = getAgreementsExportHeaders();

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

                downloadCsv(agreementsExportFilename('.csv'), lines);
            }

            function exportAgreementsPdf() {
                const exportMeta = buildAgreementsExportMeta();
                const bodyRows = collectAgreementsExportRows();
                const exportHeaders = getAgreementsExportHeaders();

                if (bodyRows.length === 0) {
                    alert('No records to export. Adjust your search or filters and try again.');
                    return;
                }

                if (typeof pdfMake === 'undefined') {
                    alert('PDF export is not available. Please refresh the page and try again.');
                    return;
                }

                const tableBody = [
                    exportHeaders.map(function (header) {
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
                                widths: exportHeaders.map(function () { return '*'; }),
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

                pdfMake.createPdf(doc).download(agreementsExportFilename('.pdf'));
            }

            $('#agreementsExportCsv').on('click', exportAgreementsCsv);
            $('#agreementsExportPdf').on('click', exportAgreementsPdf);

            function todayYmd() {
                const d = new Date();
                return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
            }

            function closedRangeTo(range) {
                if (range.to) return range.to;
                if (range.from) return todayYmd();
                return '';
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
                return name === 'terminated';
            }

            function isExpiredStatus(status) {
                return (status || '').toLowerCase() === 'expired';
            }

            function isBillableRow(row) {
                return row && row.getAttribute('data-is-billable') === '1';
            }

            function syncFiltersFromForm() {
                filters.status = document.getElementById('agreementsFilterStatus').value;
                filters.hasNotice = document.getElementById('agreementsHasNotice').checked;
                filters.rented.from = document.getElementById('agreementsRentedFrom').value;
                filters.rented.to = document.getElementById('agreementsRentedTo').value;
                filters.closed.from = document.getElementById('agreementsClosedFrom').value;
                filters.closed.to = document.getElementById('agreementsClosedTo').value;
                filters.expired.from = document.getElementById('agreementsExpiredFrom').value;
                filters.expired.to = document.getElementById('agreementsExpiredTo').value;
                filters.notice.from = document.getElementById('agreementsNoticeFrom').value;
                filters.notice.to = document.getElementById('agreementsNoticeTo').value;
                filters.due.from = document.getElementById('agreementsDueFrom').value;
                filters.due.to = document.getElementById('agreementsDueTo').value;
                filters.refundStatus = document.getElementById('agreementsFilterRefundStatus').value;
            }

            function passesTerminationNoticeFilters(row) {
                if (!row) {
                    return !filters.hasNotice && !isRangeActive(filters.notice);
                }

                var billable = isBillableRow(row);
                var noticeDate = row.getAttribute('data-notice-date') || '';

                if (filters.hasNotice && (!billable || !noticeDate)) {
                    return false;
                }

                if (isRangeActive(filters.notice) && (!billable || !passesDateRange(noticeDate, filters.notice))) {
                    return false;
                }

                return true;
            }

            function passesFilters(row) {
                if (!row) {
                    return !filters.hasNotice && !isRangeActive(filters.notice);
                }

                var status = row.getAttribute('data-status') || '';
                var startDate = row.getAttribute('data-start-date') || '';
                var closedOn = row.getAttribute('data-closed-on') || '';
                var noticeDate = row.getAttribute('data-notice-date') || '';
                var endDate = row.getAttribute('data-end-date') || '';
                var refundStatus = row.getAttribute('data-refund-status') || '';
                var billable = isBillableRow(row);

                if (filters.status && status !== filters.status) {
                    return false;
                }

                if (!passesDateRange(startDate, filters.rented)) {
                    return false;
                }

                if (isRangeActive(filters.closed)) {
                    if (!isClosedStatus(status)) {
                        return false;
                    }
                    if (!dateInRange(closedOn, filters.closed.from, closedRangeTo(filters.closed))) {
                        return false;
                    }
                }

                if (isRangeActive(filters.expired)) {
                    if (!isExpiredStatus(status)) {
                        return false;
                    }
                    if (!dateInRange(endDate, filters.expired.from, filters.expired.to)) {
                        return false;
                    }
                }

                if (filters.refundStatus && refundStatus !== filters.refundStatus) {
                    return false;
                }

                if (!passesTerminationNoticeFilters(row)) {
                    return false;
                }

                if (isRangeActive(filters.due)) {
                    var endMatch = dateInRange(endDate, filters.due.from, filters.due.to);
                    var noticeMatch = billable && dateInRange(noticeDate, filters.due.from, filters.due.to);
                    if (!endMatch && !noticeMatch) {
                        return false;
                    }
                }

                return true;
            }

            $.fn.dataTable.ext.search.push(function (settings, searchData, dataIndex) {
                if (!settings.nTable || settings.nTable.id !== 'dataTable') {
                    return true;
                }

                var row = dataTable.row(dataIndex).node();

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

            $('#agreementsFilterStatus, #agreementsHasNotice, #agreementsFilterRefundStatus').on('change', function () {
                syncFiltersFromForm();
                dataTable.draw();
            });

            $('.agreements-date-filter').on('change input', function () {
                syncFiltersFromForm();
                dataTable.draw();
            });

            $('#agreementsFilterReset').on('click', function () {
                $('#agreementsFilterStatus').val('');
                $('#agreementsFilterRefundStatus').val('');
                $('#agreementsHasNotice').prop('checked', false);
                $('#agreementsRentedFrom, #agreementsRentedTo, #agreementsClosedFrom, #agreementsClosedTo, #agreementsExpiredFrom, #agreementsExpiredTo, #agreementsNoticeFrom, #agreementsNoticeTo, #agreementsDueFrom, #agreementsDueTo').val('');
                syncFiltersFromForm();
                dataTable.draw();
            });
        });
    </script>
@endsection
