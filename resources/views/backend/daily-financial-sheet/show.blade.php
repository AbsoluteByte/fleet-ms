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
                        @php
                            $pdfQuery = array_filter([
                                'payment_method' => $activeFilters['payment_method'] ?? null,
                                'bank_account_id' => $activeFilters['bank_account_id'] ?? null,
                            ], fn ($value) => $value !== null && $value !== '');
                            $pdfUrl = route('daily-financial-sheet.pdf', $sheetDate).($pdfQuery !== [] ? '?'.http_build_query($pdfQuery) : '');
                        @endphp
                        <a href="{{ $pdfUrl }}" id="dfsExportPdfLink" class="btn btn-outline-primary btn-sm" data-base-url="{{ route('daily-financial-sheet.pdf', $sheetDate) }}">
                            <i class="fa fa-file-pdf-o"></i> Export PDF
                        </a>
                        <a href="{{ route('daily-financial-sheet.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                <div class="card-body" style="margin-top: 0;">
                    @include('alerts')

                    <div class="row mb-2 align-items-end">
                        <div class="col-md-4 mb-1">
                            <label for="dfsFilterPaymentMethod" class="form-label mb-25">Payment Method</label>
                            <select id="dfsFilterPaymentMethod" class="form-control">
                                <option value="">All methods</option>
                                @foreach($filterOptions['payment_methods'] as $method)
                                    <option value="{{ $method }}" @selected(($activeFilters['payment_method'] ?? null) === $method)>{{ $method }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-1">
                            <label for="dfsFilterBankAccount" class="form-label mb-25">Bank Account</label>
                            <select id="dfsFilterBankAccount" class="form-control">
                                <option value="">All bank accounts</option>
                                @foreach($filterOptions['bank_accounts'] as $bankAccount)
                                    <option value="{{ $bankAccount['id'] }}" @selected((int) ($activeFilters['bank_account_id'] ?? 0) === (int) $bankAccount['id'])>{{ $bankAccount['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-1">
                            <button type="button" class="btn btn-outline-secondary" id="dfsClearFilters">Clear filters</button>
                        </div>
                    </div>

                    <div class="alert alert-info py-1 mb-2 {{ $isFiltered ? '' : 'd-none' }}" id="dfsFilterBanner">
                        <strong>Filtered view</strong>
                        — <span id="dfsFilterCount">{{ $entries->count() }}</span> of {{ $allEntries->count() }} entries
                        @if($isFiltered)
                            @if($filterLabels['payment_method'])
                                · Method: {{ $filterLabels['payment_method'] }}
                            @endif
                            @if($filterLabels['bank_account'])
                                · Bank: {{ $filterLabels['bank_account'] }}
                            @endif
                        @endif
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-3 mb-1">
                            <div class="border rounded p-1">
                                <small class="text-muted" id="dfsCashInLabel">{{ $isApproved && ! $isFiltered ? 'Approved Cash In' : 'Cash In' }}</small>
                                <div><strong id="dfsCashIn">£{{ number_format($totals['cash_in'], 2) }}</strong></div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-1">
                            <div class="border rounded p-1">
                                <small class="text-muted" id="dfsCashOutLabel">{{ $isApproved && ! $isFiltered ? 'Approved Cash Out' : 'Cash Out' }}</small>
                                <div><strong id="dfsCashOut">£{{ number_format($totals['cash_out'], 2) }}</strong></div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-1">
                            <div class="border rounded p-1">
                                <small class="text-muted" id="dfsNetCashLabel">{{ $isApproved && ! $isFiltered ? 'Approved Net Cash' : 'Net Cash' }}</small>
                                <div><strong id="dfsNetCash">£{{ number_format($totals['net_cash'], 2) }}</strong></div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-1">
                            <div class="border rounded p-1">
                                <small class="text-muted" id="dfsBankInLabel">{{ $isApproved && ! $isFiltered ? 'Approved Bank In' : 'Bank In (total)' }}</small>
                                <div><strong id="dfsBankInTotal">£{{ number_format(collect($totals['bank_in'])->sum('total'), 2) }}</strong></div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2 {{ !empty($totals['bank_in']) ? '' : 'd-none' }}" id="dfsBankInSection">
                        <h6 id="dfsBankInHeading">{{ $isApproved && ! $isFiltered ? 'Approved Bank In breakdown' : 'Bank In breakdown' }}</h6>
                        <ul class="mb-0" id="dfsBankInList">
                            @foreach($totals['bank_in'] as $bankRow)
                                <li>{{ $bankRow['bank_name'] }} ({{ $bankRow['account_number'] }}): £{{ number_format($bankRow['total'], 2) }}</li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="mb-2 {{ !empty($totals['bank_out'] ?? []) ? '' : 'd-none' }}" id="dfsBankOutSection">
                        <h6 id="dfsBankOutHeading">{{ $isApproved && ! $isFiltered ? 'Approved Bank Out breakdown' : 'Bank Out breakdown' }}</h6>
                        <ul class="mb-0" id="dfsBankOutList">
                            @foreach($totals['bank_out'] ?? [] as $bankRow)
                                <li>{{ $bankRow['bank_name'] }} ({{ $bankRow['account_number'] }}): £{{ number_format($bankRow['total'], 2) }}</li>
                            @endforeach
                        </ul>
                    </div>

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
                                @forelse($allEntries as $entry)
                                    @php
                                        $rowVisible = ! $isFiltered || $entries->contains(fn ($visible) => ($visible['id'] ?? null) === ($entry['id'] ?? null));
                                    @endphp
                                    <tr class="dfs-entry-row {{ $rowVisible ? '' : 'd-none' }}"
                                        data-payment-method="{{ $entry['payment_method'] ?? '' }}"
                                        data-bank-account-id="{{ $entry['bank_account_id'] ?? '' }}"
                                        data-direction="{{ $entry['direction'] ?? '' }}"
                                        data-amount="{{ $entry['amount'] ?? 0 }}">
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
                                            @if(!empty($entry['is_new_rent_out']))
                                                <div class="paying-company-subtitle">New car rent out</div>
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
                                    <tr id="dfsNoEntriesRow">
                                        <td colspan="{{ $canApprove ? 11 : 9 }}" class="text-center text-muted">No entries for this date.</td>
                                    </tr>
                                @endforelse
                                <tr id="dfsNoFilterMatchesRow" class="d-none">
                                    <td colspan="{{ $canApprove ? 11 : 9 }}" class="text-center text-muted">No entries match the selected filters.</td>
                                </tr>
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
        .paying-company-subtitle {
            color: #6e6b7b;
            font-size: 0.875rem;
        }

        .navbar-floating .header-navbar-shadow {
            height: 90px !important;
        }
    </style>
@endsection

@section('js')
    <script>
        (function () {
            var bankMethods = @json(\App\Models\Payment::METHODS_REQUIRING_BANK_ACCOUNT);
            var allEntries = @json($allEntries->values());
            var fullTotals = @json($fullTotals);
            var isApproved = @json($isApproved);

            var paymentSelect = document.getElementById('dfsFilterPaymentMethod');
            var bankSelect = document.getElementById('dfsFilterBankAccount');
            var clearBtn = document.getElementById('dfsClearFilters');
            var filterBanner = document.getElementById('dfsFilterBanner');
            var filterCountEl = document.getElementById('dfsFilterCount');
            var pdfLink = document.getElementById('dfsExportPdfLink');
            var noFilterMatchesRow = document.getElementById('dfsNoFilterMatchesRow');

            function formatMoney(value) {
                return '£' + Number(value || 0).toLocaleString('en-GB', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function requiresBankAccount(method) {
                return bankMethods.indexOf(method) !== -1;
            }

            function computeTotalsFromEntries(entries) {
                var cashIn = 0;
                var cashOut = 0;
                var bankIn = {};
                var bankOut = {};

                entries.forEach(function (entry) {
                    var amount = Number(entry.amount || 0);
                    var method = entry.payment_method || '';
                    var direction = entry.direction || '';
                    var bankId = String(entry.bank_account_id || 'unknown');

                    if (direction === 'in' && method === 'Cash') {
                        cashIn += amount;
                    }
                    if (direction === 'out' && method === 'Cash') {
                        cashOut += amount;
                    }
                    if (direction === 'in' && requiresBankAccount(method)) {
                        if (!bankIn[bankId]) {
                            bankIn[bankId] = {
                                bank_account_id: entry.bank_account_id,
                                bank_name: entry.bank_name || 'Bank',
                                account_number: entry.account_number || '',
                                total: 0
                            };
                        }
                        bankIn[bankId].total += amount;
                    }
                    if (direction === 'out' && requiresBankAccount(method)) {
                        if (!bankOut[bankId]) {
                            bankOut[bankId] = {
                                bank_account_id: entry.bank_account_id,
                                bank_name: entry.bank_name || 'Bank',
                                account_number: entry.account_number || '',
                                total: 0
                            };
                        }
                        bankOut[bankId].total += amount;
                    }
                });

                var bankInRows = Object.keys(bankIn).map(function (key) {
                    bankIn[key].total = Math.round(bankIn[key].total * 100) / 100;
                    return bankIn[key];
                });
                var bankOutRows = Object.keys(bankOut).map(function (key) {
                    bankOut[key].total = Math.round(bankOut[key].total * 100) / 100;
                    return bankOut[key];
                });

                cashIn = Math.round(cashIn * 100) / 100;
                cashOut = Math.round(cashOut * 100) / 100;

                return {
                    cash_in: cashIn,
                    cash_out: cashOut,
                    net_cash: Math.round((cashIn - cashOut) * 100) / 100,
                    bank_in: bankInRows,
                    bank_out: bankOutRows
                };
            }

            function renderBankList(listEl, rows) {
                if (!listEl) {
                    return;
                }
                listEl.innerHTML = '';
                rows.forEach(function (row) {
                    var li = document.createElement('li');
                    var accountSuffix = row.account_number ? ' (' + row.account_number + ')' : '';
                    li.textContent = row.bank_name + accountSuffix + ': ' + formatMoney(row.total);
                    listEl.appendChild(li);
                });
            }

            function applyTotals(totals, filtered) {
                var approvedPrefix = isApproved && !filtered ? 'Approved ' : '';
                var cashInEl = document.getElementById('dfsCashIn');
                var cashOutEl = document.getElementById('dfsCashOut');
                var netCashEl = document.getElementById('dfsNetCash');
                var bankInTotalEl = document.getElementById('dfsBankInTotal');
                var bankInSection = document.getElementById('dfsBankInSection');
                var bankOutSection = document.getElementById('dfsBankOutSection');

                if (cashInEl) cashInEl.textContent = formatMoney(totals.cash_in);
                if (cashOutEl) cashOutEl.textContent = formatMoney(totals.cash_out);
                if (netCashEl) netCashEl.textContent = formatMoney(totals.net_cash);
                if (bankInTotalEl) {
                    bankInTotalEl.textContent = formatMoney((totals.bank_in || []).reduce(function (sum, row) {
                        return sum + Number(row.total || 0);
                    }, 0));
                }

                var cashInLabel = document.getElementById('dfsCashInLabel');
                var cashOutLabel = document.getElementById('dfsCashOutLabel');
                var netCashLabel = document.getElementById('dfsNetCashLabel');
                var bankInLabel = document.getElementById('dfsBankInLabel');
                var bankInHeading = document.getElementById('dfsBankInHeading');
                var bankOutHeading = document.getElementById('dfsBankOutHeading');

                if (cashInLabel) cashInLabel.textContent = approvedPrefix + 'Cash In';
                if (cashOutLabel) cashOutLabel.textContent = approvedPrefix + 'Cash Out';
                if (netCashLabel) netCashLabel.textContent = approvedPrefix + 'Net Cash';
                if (bankInLabel) bankInLabel.textContent = approvedPrefix + 'Bank In (total)';
                if (bankInHeading) bankInHeading.textContent = approvedPrefix + 'Bank In breakdown';
                if (bankOutHeading) bankOutHeading.textContent = approvedPrefix + 'Bank Out breakdown';

                renderBankList(document.getElementById('dfsBankInList'), totals.bank_in || []);
                renderBankList(document.getElementById('dfsBankOutList'), totals.bank_out || []);

                if (bankInSection) bankInSection.classList.toggle('d-none', !(totals.bank_in || []).length);
                if (bankOutSection) bankOutSection.classList.toggle('d-none', !(totals.bank_out || []).length);
            }

            function updatePdfLink() {
                if (!pdfLink) {
                    return;
                }
                var baseUrl = pdfLink.getAttribute('data-base-url') || pdfLink.href.split('?')[0];
                var params = new URLSearchParams();
                if (paymentSelect && paymentSelect.value) {
                    params.set('payment_method', paymentSelect.value);
                }
                if (bankSelect && bankSelect.value) {
                    params.set('bank_account_id', bankSelect.value);
                }
                var query = params.toString();
                pdfLink.href = query ? baseUrl + '?' + query : baseUrl;
            }

            function applyFilters() {
                var paymentMethod = paymentSelect ? paymentSelect.value : '';
                var bankAccountId = bankSelect ? bankSelect.value : '';
                var filtered = paymentMethod !== '' || bankAccountId !== '';
                var visibleCount = 0;

                document.querySelectorAll('.dfs-entry-row').forEach(function (row) {
                    var rowMethod = row.getAttribute('data-payment-method') || '';
                    var rowBankId = row.getAttribute('data-bank-account-id') || '';
                    var matches = true;

                    if (paymentMethod !== '' && rowMethod !== paymentMethod) {
                        matches = false;
                    }
                    if (bankAccountId !== '' && rowBankId !== bankAccountId) {
                        matches = false;
                    }

                    row.classList.toggle('d-none', !matches);
                    if (matches) {
                        visibleCount += 1;
                    }
                });

                var visibleEntries = allEntries.filter(function (entry) {
                    if (paymentMethod !== '' && entry.payment_method !== paymentMethod) {
                        return false;
                    }
                    if (bankAccountId !== '' && String(entry.bank_account_id || '') !== bankAccountId) {
                        return false;
                    }
                    return true;
                });

                if (filterBanner) {
                    filterBanner.classList.toggle('d-none', !filtered);
                }
                if (filterCountEl) {
                    filterCountEl.textContent = String(visibleCount);
                }
                if (noFilterMatchesRow) {
                    noFilterMatchesRow.classList.toggle('d-none', !filtered || visibleCount > 0);
                }

                var totals = filtered ? computeTotalsFromEntries(visibleEntries) : fullTotals;
                applyTotals(totals, filtered);
                updatePdfLink();
            }

            if (paymentSelect) {
                paymentSelect.addEventListener('change', applyFilters);
            }
            if (bankSelect) {
                bankSelect.addEventListener('change', applyFilters);
            }
            if (clearBtn) {
                clearBtn.addEventListener('click', function () {
                    if (paymentSelect) paymentSelect.value = '';
                    if (bankSelect) bankSelect.value = '';
                    applyFilters();
                });
            }

            applyFilters();
        })();
    </script>
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
