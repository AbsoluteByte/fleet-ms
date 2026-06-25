<div class="card mb-1">
    <div class="card-header" style="position: static; width: 100%; z-index: unset; border-bottom: 0 !important; padding-bottom: 0 !important;">
        <h5 class="card-title mb-0">
            <i class="fa fa-credit-card"></i> Payment Details
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-2">
                <label for="driver_id" class="form-label">Driver <span class="text-danger">*</span></label>
                <select name="driver_id" id="driver_id" class="form-control select-search @error('driver_id') is-invalid @enderror" required>
                    <option value="">Select Driver</option>
                    @include('backend.drivers._select_options', [
                        'drivers' => $drivers,
                        'selectedId' => old('driver_id', optional($selectedDriver)->id),
                    ])
                </select>
                @error('driver_id')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-2">
                <label for="payment_date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                <input type="date" name="payment_date" id="payment_date"
                       class="form-control @error('payment_date') is-invalid @enderror"
                       value="{{ old('payment_date', now()->toDateString()) }}" required>
                @error('payment_date')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-2">
                <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                @php
                    $selectedPaymentMethod = old('payment_method', $model->payment_method ?? '');
                    $paymentMethods = ['Bank Transfer', 'Cash', 'Cheque', 'Card Payment', 'Direct Debit'];
                @endphp
                <select name="payment_method" id="payment_method"
                        class="form-control @error('payment_method') is-invalid @enderror" required>
                    <option value="">Select Method</option>
                    @foreach($paymentMethods as $paymentMethod)
                        <option value="{{ $paymentMethod }}" {{ $selectedPaymentMethod === $paymentMethod ? 'selected' : '' }}>
                            {{ $paymentMethod }}
                        </option>
                    @endforeach
                </select>
                @error('payment_method')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-2">
                @include('backend.payments.partials.bank-account-select', [
                    'bankAccounts' => $bankAccounts ?? collect(),
                    'selected' => old('bank_account_id', $model->bank_account_id ?? null),
                    'name' => 'bank_account_id',
                    'id' => 'bank_account_id',
                    'errorKey' => 'bank_account_id',
                    'wrapperClass' => 'bank-account-field d-none',
                ])
            </div>

            <div class="col-md-6 mb-2">
                <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
                <input type="number" name="amount" id="amount"
                       class="form-control @error('amount') is-invalid @enderror"
                       value="{{ old('amount', $model->amount ?? '') }}"
                       min="0.01" step="0.01" placeholder="0.00" required>
                @error('amount')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 mb-2">
                <label for="notes" class="form-label">Notes</label>
                <textarea name="notes" id="notes" rows="3"
                          class="form-control @error('notes') is-invalid @enderror"
                          placeholder="Optional payment notes">{{ old('notes', $model->notes ?? '') }}</textarea>
                @error('notes')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>

<div class="card mb-1">
    <div class="card-header" style="position: static; width: 100%; z-index: unset; border-bottom: 0 !important; padding-bottom: 0 !important;">
        <h5 class="card-title mb-0">
            <i class="fa fa-random"></i> Invoice Allocation
        </h5>
    </div>
    <div class="card-body">
        <input type="hidden" name="auto_manage_invoices" value="0">
        <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" id="auto_manage_invoices"
                   name="auto_manage_invoices" value="1"
                {{ old('auto_manage_invoices', '1') ? 'checked' : '' }}>
            <label class="custom-control-label" for="auto_manage_invoices">Auto manage invoices</label>
        </div>
        <p class="text-muted">
            When enabled, this payment will automatically clear the oldest active invoices first. Any extra amount remains as driver credit.
        </p>

        <div id="allocation-section">
            @if($selectedDriver)
                <h6>Allocation Preview for {{ $selectedDriver->full_name }}</h6>
            @else
                <div class="alert alert-info">Select a driver first to load open invoices for allocation.</div>
            @endif

            @if($openInvoices->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                        <tr>
                            <th>Invoice No</th>
                            <th>Invoice Date</th>
                            <th>Due Date</th>
                            <th>Balance</th>
                            <th>Auto Allocation Preview</th>
                            <th class="manual-allocation-column">Manual Allocate Amount</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($openInvoices as $invoice)
                            <tr data-invoice-balance="{{ $invoice->balance_amount }}">
                                <td>{{ $invoice->invoice_no }}</td>
                                <td>{{ optional($invoice->invoice_date)->format('d M Y') }}</td>
                                <td>{{ optional($invoice->due_date)->format('d M Y') }}</td>
                                <td>£{{ number_format($invoice->balance_amount, 2) }}</td>
                                <td>
                                    <strong class="auto-allocation-preview">£0.00</strong>
                                </td>
                                <td class="manual-allocation-column">
                                    <input type="number" name="allocations[{{ $invoice->id }}]"
                                           class="form-control manual-allocation-input @error('allocations.'.$invoice->id) is-invalid @enderror"
                                           value="{{ old('allocations.'.$invoice->id) }}"
                                           min="0" max="{{ $invoice->balance_amount }}" step="0.01">
                                    @error('allocations.'.$invoice->id)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-info mb-0" id="allocation-summary">
                    Enter payment amount to preview allocation.
                </div>
                <div class="alert alert-warning mt-1 mb-0" id="manual-allocation-warning" style="display: none;">
                    Manual allocation cannot be greater than the payment amount.
                </div>
            @elseif($selectedDriver)
                <div class="alert alert-success">This driver has no active invoices.</div>
            @endif

            @error('allocations')
            <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="form-group">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> Add Payment
            </button>
            <a href="{{ route('payments.index') }}" class="btn btn-secondary ml-2">
                <i class="fa fa-times"></i> Cancel
            </a>
        </div>
    </div>
</div>

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const driverSelect = document.getElementById('driver_id');
            const paymentMethodSelect = document.getElementById('payment_method');
            const bankAccountField = document.querySelector('[data-bank-account-field]');
            const bankAccountSelect = document.querySelector('[data-bank-account-select]');
            const autoManage = document.getElementById('auto_manage_invoices');
            const amountInput = document.getElementById('amount');
            const manualColumns = document.querySelectorAll('.manual-allocation-column');
            const manualInputs = document.querySelectorAll('.manual-allocation-input');
            const allocationRows = document.querySelectorAll('#allocation-section tbody tr[data-invoice-balance]');
            const allocationSummary = document.getElementById('allocation-summary');
            const manualAllocationWarning = document.getElementById('manual-allocation-warning');

            function money(value) {
                return '£' + Number(value || 0).toFixed(2);
            }

            function updateAutoAllocationPreview() {
                let remainingAmount = Number(amountInput?.value || 0);
                let allocatedTotal = 0;

                allocationRows.forEach(function (row) {
                    const balance = Number(row.dataset.invoiceBalance || 0);
                    const allocation = Math.min(Math.max(remainingAmount, 0), balance);
                    const preview = row.querySelector('.auto-allocation-preview');

                    if (preview) {
                        preview.textContent = money(allocation);
                    }

                    allocatedTotal += allocation;
                    remainingAmount -= allocation;
                });

                if (allocationSummary) {
                    const credit = Math.max(remainingAmount, 0);
                    allocationSummary.textContent = 'Auto allocation total: ' + money(allocatedTotal) + '. Driver credit after allocation: ' + money(credit) + '.';
                }
            }

            function toggleManualAllocation() {
                manualColumns.forEach(function (column) {
                    column.style.display = autoManage.checked ? 'none' : '';
                });
                manualInputs.forEach(function (input) {
                    input.disabled = autoManage.checked;
                });
                updateManualAllocationLimits();
            }

            function manualTotal(exceptInput = null) {
                let total = 0;
                manualInputs.forEach(function (input) {
                    if (input !== exceptInput) {
                        total += Number(input.value || 0);
                    }
                });
                return total;
            }

            function updateManualAllocationLimits(changedInput = null) {
                const paymentAmount = Number(amountInput?.value || 0);
                let showWarning = false;

                manualInputs.forEach(function (input) {
                    const row = input.closest('tr');
                    const invoiceBalance = Number(row?.dataset.invoiceBalance || 0);
                    const otherAllocated = manualTotal(input);
                    const maxAllowed = Math.max(Math.min(invoiceBalance, paymentAmount - otherAllocated), 0);

                    input.max = maxAllowed.toFixed(2);

                    if ((!changedInput || input === changedInput) && Number(input.value || 0) > maxAllowed) {
                        input.value = maxAllowed > 0 ? maxAllowed.toFixed(2) : '';
                        showWarning = true;
                    }
                });

                const allocatedTotal = manualTotal();
                const remaining = Math.max(paymentAmount - allocatedTotal, 0);

                if (allocationSummary && !autoManage.checked) {
                    allocationSummary.textContent = 'Manual allocation total: ' + money(allocatedTotal) + '. Remaining unallocated credit: ' + money(remaining) + '.';
                }

                if (manualAllocationWarning) {
                    manualAllocationWarning.style.display = showWarning ? 'block' : 'none';
                }
            }

            function toggleBankAccountField() {
                if (!paymentMethodSelect || !bankAccountField) {
                    return;
                }

                const isBankTransfer = paymentMethodSelect.value === 'Bank Transfer';
                bankAccountField.classList.toggle('d-none', !isBankTransfer);

                if (bankAccountSelect) {
                    bankAccountSelect.required = isBankTransfer && {{ ($bankAccounts ?? collect())->isNotEmpty() ? 'true' : 'false' }};

                    if (!isBankTransfer) {
                        bankAccountSelect.value = '';
                    }
                }
            }

            if (paymentMethodSelect) {
                paymentMethodSelect.addEventListener('change', toggleBankAccountField);
                toggleBankAccountField();
            }

            if (driverSelect) {
                driverSelect.addEventListener('change', function () {
                    const url = new URL(window.location.href);
                    if (this.value) {
                        url.searchParams.set('driver_id', this.value);
                    } else {
                        url.searchParams.delete('driver_id');
                    }
                    window.location.href = url.toString();
                });
            }

            autoManage.addEventListener('change', toggleManualAllocation);
            amountInput?.addEventListener('input', function () {
                updateAutoAllocationPreview();
                updateManualAllocationLimits();
            });
            manualInputs.forEach(function (input) {
                input.addEventListener('input', function () {
                    updateManualAllocationLimits(input);
                });
            });
            toggleManualAllocation();
            updateAutoAllocationPreview();
        });
    </script>
@endpush
