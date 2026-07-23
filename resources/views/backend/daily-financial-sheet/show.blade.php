@extends('layouts.admin', ['title' => 'Daily Financial Sheet'])

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center" style="position: static; z-index: unset; width: 100%;">
                    <div>
                        <h4 class="card-title mb-0">Sheet: {{ \Carbon\Carbon::parse($sheetDate)->format('d M Y') }}</h4>
                        @if($isApproved)
                            <span class="badge badge-success">Approved</span>
                            @if($hasPending)
                                <span class="badge badge-warning">New entries pending</span>
                            @endif
                        @else
                            <span class="badge badge-warning">Pending Approval</span>
                        @endif
                    </div>
                    <div class="d-flex align-items-center" style="gap: 0.5rem;">
                        <a href="{{ route('daily-financial-sheet.pdf', $sheetDate) }}" class="btn btn-outline-primary btn-sm">
                            <i class="fa fa-file-pdf-o"></i> Export PDF
                        </a>
                        <a href="{{ route('daily-financial-sheet.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                <div class="card-body" style="margin-top: 0;">
                    @include('alerts')

                    <div class="row mb-2">
                        <div class="col-md-3 mb-1">
                            <div class="border rounded p-1">
                                <small class="text-muted">{{ $isApproved ? 'Approved Cash In' : 'Cash In' }}</small>
                                <div><strong>£{{ number_format($totals['cash_in'], 2) }}</strong></div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-1">
                            <div class="border rounded p-1">
                                <small class="text-muted">{{ $isApproved ? 'Approved Cash Out' : 'Cash Out' }}</small>
                                <div><strong>£{{ number_format($totals['cash_out'], 2) }}</strong></div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-1">
                            <div class="border rounded p-1">
                                <small class="text-muted">{{ $isApproved ? 'Approved Net Cash' : 'Net Cash' }}</small>
                                <div><strong>£{{ number_format($totals['net_cash'], 2) }}</strong></div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-1">
                            <div class="border rounded p-1">
                                <small class="text-muted">{{ $isApproved ? 'Approved Bank In' : 'Bank In (total)' }}</small>
                                <div><strong>£{{ number_format(collect($totals['bank_in'])->sum('total'), 2) }}</strong></div>
                            </div>
                        </div>
                    </div>

                    @if(!empty($totals['bank_in']))
                        <div class="mb-2">
                            <h6>{{ $isApproved ? 'Approved Bank In breakdown' : 'Bank In breakdown' }}</h6>
                            <ul class="mb-0">
                                @foreach($totals['bank_in'] as $bankRow)
                                    <li>{{ $bankRow['bank_name'] }} ({{ $bankRow['account_number'] }}): £{{ number_format($bankRow['total'], 2) }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(!empty($totals['bank_out'] ?? []))
                        <div class="mb-2">
                            <h6>{{ $isApproved ? 'Approved Bank Out breakdown' : 'Bank Out breakdown' }}</h6>
                            <ul class="mb-0">
                                @foreach($totals['bank_out'] as $bankRow)
                                    <li>{{ $bankRow['bank_name'] }} ({{ $bankRow['account_number'] }}): £{{ number_format($bankRow['total'], 2) }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if($pendingTotals)
                        <div class="alert alert-warning mb-2">
                            <strong>Pending batch</strong>
                            — Cash in £{{ number_format($pendingTotals['cash_in'], 2) }},
                            Cash out £{{ number_format($pendingTotals['cash_out'], 2) }},
                            Bank in £{{ number_format(collect($pendingTotals['bank_in'])->sum('total'), 2) }},
                            Bank out £{{ number_format(collect($pendingTotals['bank_out'] ?? [])->sum('total'), 2) }}
                        </div>
                    @endif

                    @if($isApproved && $sheet)
                        <div class="alert alert-success">
                            Approved by {{ $sheet->approvedByUser?->name ?? '—' }}
                            on {{ optional($sheet->approved_at)->format('d M Y H:i') }}.
                            @if($sheet->approval_notes)
                                <div class="mt-50"><strong>Notes:</strong> {{ $sheet->approval_notes }}</div>
                            @endif
                        </div>
                    @endif

                    <form method="POST" action="{{ route('daily-financial-sheet.approve', $sheetDate) }}" id="dfsApproveForm"
                          data-approve-action="{{ route('daily-financial-sheet.approve', $sheetDate) }}"
                          data-reject-action="{{ route('daily-financial-sheet.reject', $sheetDate) }}">
                        @csrf
                        <input type="hidden" name="approve_mode" id="dfsApproveMode" value="all">
                        <input type="hidden" name="reject_mode" id="dfsRejectMode" value="all">

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                <tr>
                                    @if($canApprove)
                                        <th style="width: 40px;">
                                            <input type="checkbox" id="dfsSelectAllPending" title="Select all pending">
                                        </th>
                                    @endif
                                    <th>Direction</th>
                                    <th>Employee</th>
                                    <th>Description</th>
                                    <th>Category</th>
                                    <th>Vehicle</th>
                                    <th>Method</th>
                                    <th>Bank</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    @if($canApprove)
                                        <th style="width: 160px; white-space: nowrap;">Action</th>
                                    @endif
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($entries as $entry)
                                    <tr>
                                        @if($canApprove)
                                            <td>
                                                @if($entry['posting_status'] === 'pending')
                                                    <input type="checkbox"
                                                           class="dfs-entry-checkbox"
                                                           name="entry_ids[]"
                                                           value="{{ $entry['id'] }}">
                                                @endif
                                            </td>
                                        @endif
                                        <td>
                                            @if($entry['direction'] === 'in')
                                                <span class="text-success">IN</span>
                                            @elseif($entry['direction'] === 'internal')
                                                <span class="text-info">INTERNAL</span>
                                            @else
                                                <span class="text-danger">OUT</span>
                                            @endif
                                        </td>
                                        <td>{{ $entry['employee'] }}</td>
                                        <td @if(!empty($entry['is_adjustment']) && ($entry['adjustment_event_type'] ?? '') === 'reversal') class="text-muted" @endif>
                                            @if(!empty($entry['is_adjustment']) && ($entry['adjustment_event_type'] ?? '') === 'reversal')
                                                <del>{{ $entry['description'] }}</del>
                                            @else
                                                {{ $entry['description'] }}
                                            @endif
                                        </td>
                                        <td>
                                            {{ $entry['category'] }}
                                            @if(!empty($entry['agreement_url']))
                                                <div><a href="{{ $entry['agreement_url'] }}">Agreement #{{ $entry['agreement_id'] }}</a></div>
                                            @endif
                                            @if(!empty($entry['paying_company_name']))
                                                <div class="text-muted small">Pays via: {{ $entry['paying_company_name'] }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $entry['car_registration'] ?? '—' }}</td>
                                        <td>{{ $entry['payment_method'] }}</td>
                                        <td>
                                            @if($entry['bank_name'])
                                                {{ $entry['bank_name'] }}
                                                @if($entry['account_number'])
                                                    <div class="text-muted small">{{ $entry['account_number'] }}</div>
                                                @endif
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>£{{ number_format($entry['amount'], 2) }}</td>
                                        <td>
                                            @if(($entry['posting_status'] ?? '') === 'adjustment')
                                                <span class="badge badge-info">Adjustment</span>
                                            @elseif($entry['posting_status'] === 'pending')
                                                <span class="badge badge-warning">Pending</span>
                                            @else
                                                <span class="badge badge-success">Posted</span>
                                            @endif
                                        </td>
                                        @if($canApprove)
                                            <td>
                                                @if($entry['posting_status'] === 'pending')
                                                    <div class="d-flex flex-nowrap align-items-center" style="gap: 0.35rem;">
                                                        <button type="button"
                                                                class="btn btn-sm btn-success"
                                                                data-dfs-approve-single="{{ $entry['id'] }}">
                                                            Approve
                                                        </button>
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-danger"
                                                                data-dfs-reject-single="{{ $entry['id'] }}">
                                                            Reject
                                                        </button>
                                                    </div>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $canApprove ? 11 : 9 }}" class="text-center text-muted">No entries for this date.</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($canApprove)
                            <hr>
                            <div class="form-group">
                                <label for="approval_notes">Approval notes (optional)</label>
                                <textarea name="approval_notes" id="approval_notes" class="form-control" rows="3" placeholder="e.g. Cash counted £500, bank matches statement">{{ old('approval_notes') }}</textarea>
                            </div>
                            <div class="d-flex flex-wrap" style="gap: 0.5rem;">
                                <button type="button" class="btn btn-success" id="dfsApproveAllBtn" data-dfs-approve-mode="all">
                                    Approve all pending
                                </button>
                                <button type="button" class="btn btn-outline-success" id="dfsApproveSelectedBtn" data-dfs-approve-mode="selected">
                                    Approve selected
                                </button>
                                <button type="button" class="btn btn-outline-danger" id="dfsRejectSelectedBtn" data-dfs-reject-mode="selected">
                                    Reject selected
                                </button>
                                <button type="button" class="btn btn-danger" id="dfsRejectAllBtn" data-dfs-reject-mode="all">
                                    Reject all pending
                                </button>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if($canApprove)
        <div class="modal fade" id="dfsApproveConfirmModal" tabindex="-1" role="dialog" aria-labelledby="dfsApproveConfirmModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 28rem;">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title d-flex align-items-center mb-0" id="dfsApproveConfirmModalLabel">
                            <span class="rounded-circle d-inline-flex align-items-center justify-content-center mr-75"
                                  id="dfsApproveConfirmIcon"
                                  style="width:2.25rem;height:2.25rem;background:rgba(115,103,240,.12);color:#7367f0;">
                                <i class="fa fa-check" id="dfsApproveConfirmIconGlyph"></i>
                            </span>
                            <span id="dfsApproveConfirmTitle">Confirm approval</span>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body pt-1 pb-2">
                        <p class="mb-0 text-body" id="dfsApproveConfirmBody" style="line-height: 1.55;"></p>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal" id="dfsApproveConfirmCancel">Cancel</button>
                        <button type="button" class="btn btn-success" id="dfsApproveConfirmBtn">
                            <i class="fa fa-check mr-50" id="dfsApproveConfirmBtnIcon"></i><span id="dfsApproveConfirmBtnText">Approve</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('css')
    <style>
        .navbar-floating .header-navbar-shadow {
            height: 90px !important;
        }
    </style>
@endsection

@section('js')
    @if($canApprove)
    <script>
        (function () {
            var form = document.getElementById('dfsApproveForm');
            var modeInput = document.getElementById('dfsApproveMode');
            var rejectModeInput = document.getElementById('dfsRejectMode');
            var selectAll = document.getElementById('dfsSelectAllPending');
            var $modal = window.jQuery;
            var pendingAction = null;
            var approveAction = form ? form.getAttribute('data-approve-action') : '';
            var rejectAction = form ? form.getAttribute('data-reject-action') : '';

            var titleEl = document.getElementById('dfsApproveConfirmTitle');
            var bodyEl = document.getElementById('dfsApproveConfirmBody');
            var btnTextEl = document.getElementById('dfsApproveConfirmBtnText');
            var confirmBtn = document.getElementById('dfsApproveConfirmBtn');
            var confirmBtnIcon = document.getElementById('dfsApproveConfirmBtnIcon');
            var cancelBtn = document.getElementById('dfsApproveConfirmCancel');
            var iconWrap = document.getElementById('dfsApproveConfirmIcon');
            var iconGlyph = document.getElementById('dfsApproveConfirmIconGlyph');

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    document.querySelectorAll('.dfs-entry-checkbox').forEach(function (cb) {
                        cb.checked = selectAll.checked;
                    });
                });
            }

            function showModal(config) {
                pendingAction = config.action || null;
                if (titleEl) titleEl.textContent = config.title;
                if (bodyEl) bodyEl.textContent = config.body;
                if (btnTextEl) btnTextEl.textContent = config.btnText || 'OK';

                var isAlert = !!config.alertOnly;
                var isDanger = !!config.danger;
                if (confirmBtn) {
                    confirmBtn.classList.toggle('d-none', isAlert);
                    confirmBtn.className = isDanger ? 'btn btn-danger' : 'btn btn-success';
                    confirmBtn.disabled = false;
                }
                if (confirmBtnIcon) {
                    confirmBtnIcon.className = isDanger ? 'fa fa-times mr-50' : 'fa fa-check mr-50';
                }
                if (cancelBtn) {
                    cancelBtn.textContent = isAlert ? 'Close' : 'Cancel';
                    cancelBtn.className = isAlert ? 'btn btn-primary' : 'btn btn-outline-secondary';
                }
                if (iconWrap && iconGlyph) {
                    if (isAlert) {
                        iconWrap.style.background = 'rgba(255, 159, 67, 0.15)';
                        iconWrap.style.color = '#ff9f43';
                        iconGlyph.className = 'fa fa-exclamation';
                    } else if (isDanger) {
                        iconWrap.style.background = 'rgba(234, 84, 85, 0.12)';
                        iconWrap.style.color = '#ea5455';
                        iconGlyph.className = 'fa fa-times';
                    } else {
                        iconWrap.style.background = 'rgba(40, 199, 111, 0.12)';
                        iconWrap.style.color = '#28c76f';
                        iconGlyph.className = 'fa fa-check';
                    }
                }

                if ($modal && $modal.fn && $modal.fn.modal) {
                    $modal('#dfsApproveConfirmModal').modal('show');
                }
            }

            function prepareSelectedMode(entryId) {
                if (entryId) {
                    document.querySelectorAll('.dfs-entry-checkbox').forEach(function (cb) {
                        cb.checked = cb.value === entryId;
                    });
                }
                if (modeInput) {
                    modeInput.value = 'selected';
                }
                if (rejectModeInput) {
                    rejectModeInput.value = 'selected';
                }
            }

            function prepareAllMode() {
                if (modeInput) {
                    modeInput.value = 'all';
                }
                if (rejectModeInput) {
                    rejectModeInput.value = 'all';
                }
            }

            function submitApprove() {
                if (!form) return;
                form.action = approveAction;
                form.submit();
            }

            function submitReject() {
                if (!form) return;
                form.action = rejectAction;
                form.submit();
            }

            document.querySelectorAll('[data-dfs-approve-single]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var entryId = btn.getAttribute('data-dfs-approve-single');
                    showModal({
                        title: 'Approve this entry?',
                        body: 'This entry will be posted and merged into this day\'s financial sheet.',
                        btnText: 'Yes, approve',
                        action: function () {
                            prepareSelectedMode(entryId);
                            submitApprove();
                        },
                    });
                });
            });

            document.querySelectorAll('[data-dfs-reject-single]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var entryId = btn.getAttribute('data-dfs-reject-single');
                    showModal({
                        title: 'Reject this entry?',
                        body: 'This pending entry will be discarded and removed from the sheet. This cannot be undone.',
                        btnText: 'Yes, reject',
                        danger: true,
                        action: function () {
                            prepareSelectedMode(entryId);
                            submitReject();
                        },
                    });
                });
            });

            document.querySelectorAll('[data-dfs-approve-mode]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var mode = btn.getAttribute('data-dfs-approve-mode');
                    if (mode === 'selected') {
                        var checked = document.querySelectorAll('.dfs-entry-checkbox:checked');
                        if (!checked.length) {
                            showModal({
                                title: 'No entries selected',
                                body: 'Select at least one pending entry to approve.',
                                btnText: 'OK',
                                alertOnly: true,
                            });
                            return;
                        }
                        showModal({
                            title: 'Approve selected entries?',
                            body: 'Selected pending entries will be posted to invoices and merged into this day\'s sheet.',
                            btnText: 'Yes, approve selected',
                            action: function () {
                                prepareSelectedMode();
                                submitApprove();
                            },
                        });
                        return;
                    }

                    showModal({
                        title: 'Approve all pending?',
                        body: 'All pending entries for this day will be posted to invoices and added to this day\'s sheet.',
                        btnText: 'Yes, approve all',
                        action: function () {
                            prepareAllMode();
                            submitApprove();
                        },
                    });
                });
            });

            document.querySelectorAll('[data-dfs-reject-mode]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var mode = btn.getAttribute('data-dfs-reject-mode');
                    if (mode === 'selected') {
                        var checked = document.querySelectorAll('.dfs-entry-checkbox:checked');
                        if (!checked.length) {
                            showModal({
                                title: 'No entries selected',
                                body: 'Select at least one pending entry to reject.',
                                btnText: 'OK',
                                alertOnly: true,
                            });
                            return;
                        }
                        showModal({
                            title: 'Reject selected entries?',
                            body: 'Selected pending entries will be discarded and removed from the sheet. This cannot be undone.',
                            btnText: 'Yes, reject selected',
                            danger: true,
                            action: function () {
                                prepareSelectedMode();
                                submitReject();
                            },
                        });
                        return;
                    }

                    showModal({
                        title: 'Reject all pending?',
                        body: 'All pending entries for this day will be discarded and removed from the sheet. This cannot be undone.',
                        btnText: 'Yes, reject all',
                        danger: true,
                        action: function () {
                            prepareAllMode();
                            submitReject();
                        },
                    });
                });
            });

            if (confirmBtn) {
                confirmBtn.addEventListener('click', function () {
                    if (typeof pendingAction === 'function') {
                        confirmBtn.disabled = true;
                        pendingAction();
                    }
                });
            }
        })();
    </script>
    @endif
@endsection
