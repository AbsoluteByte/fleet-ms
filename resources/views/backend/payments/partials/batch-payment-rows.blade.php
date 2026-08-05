@php
    $fieldName = $fieldName ?? 'payments';
    $containerId = $containerId ?? 'batch-payment-'.md5($fieldName);
    $paymentMethods = $paymentMethods ?? \App\Support\BatchPaymentInput::PAYMENT_METHODS;
    $bankAccounts = $bankAccounts ?? collect();
    $defaultPaymentDate = $defaultPaymentDate ?? now()->toDateString();
    $paymentRows = \App\Support\BatchPaymentInput::resolveRows($fieldName, $defaultRows ?? null, $defaultPaymentDate);
    $showPaymentDate = $showPaymentDate ?? true;
    $showNotes = $showNotes ?? true;
    $showRemoveButton = $showRemoveButton ?? true;
    $amountInputClass = $amountInputClass ?? 'batch-payment-amount';
    $helpText = $helpText ?? null;
    $limitMessageId = $limitMessageId ?? null;
    $defaultCardBankAccountId = $bankAccounts->firstWhere(
        'account_number',
        \App\Models\BankAccount::DEFAULT_CARD_ACCOUNT_NUMBER
    )?->id;
@endphp

@if($limitMessageId)
    <div class="alert alert-info py-2" id="{{ $limitMessageId }}"></div>
@endif

<div id="{{ $containerId }}-rows" data-batch-payment-rows data-field-name="{{ $fieldName }}">
    @foreach($paymentRows as $paymentIndex => $paymentRow)
        @php
            $selectedMethod = old("{$fieldName}.{$paymentIndex}.payment_method", $paymentRow['payment_method'] ?? '');
            $selectedBankId = old(
                "{$fieldName}.{$paymentIndex}.bank_account_id",
                $paymentRow['bank_account_id']
                    ?? ($selectedMethod === 'Card Payment' ? $defaultCardBankAccountId : null)
            );
            $needsBank = in_array($selectedMethod, ['Bank Transfer', 'Card Payment'], true);
        @endphp
        <div class="batch-payment-row border rounded p-2 mb-2" data-payment-row>
            <div class="row">
                <div class="col-md-3 mb-2">
                    <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                    <select name="{{ $fieldName }}[{{ $paymentIndex }}][payment_method]"
                            class="form-control @error("{$fieldName}.{$paymentIndex}.payment_method") is-invalid @enderror"
                            data-payment-method>
                        <option value="">Select Method</option>
                        @foreach($paymentMethods as $paymentMethod)
                            <option value="{{ $paymentMethod }}" {{ $selectedMethod === $paymentMethod ? 'selected' : '' }}>
                                {{ $paymentMethod }}
                            </option>
                        @endforeach
                    </select>
                    @error("{$fieldName}.{$paymentIndex}.payment_method")
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 mb-2">
                    @include('backend.payments.partials.bank-account-select', [
                        'bankAccounts' => $bankAccounts,
                        'selected' => $selectedBankId,
                        'name' => "{$fieldName}[{$paymentIndex}][bank_account_id]",
                        'id' => "{$containerId}_bank_account_{$paymentIndex}",
                        'errorKey' => "{$fieldName}.{$paymentIndex}.bank_account_id",
                        'wrapperClass' => 'bank-account-field'.($needsBank ? '' : ' d-none'),
                    ])
                </div>
                @if($showPaymentDate)
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                        <input type="date" name="{{ $fieldName }}[{{ $paymentIndex }}][payment_date]"
                               class="form-control @error("{$fieldName}.{$paymentIndex}.payment_date") is-invalid @enderror"
                               value="{{ old("{$fieldName}.{$paymentIndex}.payment_date", $paymentRow['payment_date'] ?? $defaultPaymentDate) }}"
                               data-payment-date>
                        @error("{$fieldName}.{$paymentIndex}.payment_date")
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                @endif
                <div class="col-md-3 mb-2">
                    <label class="form-label">Amount <span class="text-danger">*</span></label>
                    <input type="number" name="{{ $fieldName }}[{{ $paymentIndex }}][amount]"
                           class="form-control {{ $amountInputClass }} @error("{$fieldName}.{$paymentIndex}.amount") is-invalid @enderror"
                           value="{{ old("{$fieldName}.{$paymentIndex}.amount", $paymentRow['amount'] ?? '') }}"
                           min="0.01" step="0.01" placeholder="0.00" data-payment-amount>
                    @error("{$fieldName}.{$paymentIndex}.amount")
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                @if($showRemoveButton)
                    <div class="col-md-3 mb-2 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-danger btn-sm" data-remove-payment>
                            Remove
                        </button>
                    </div>
                @endif
                @if($showNotes)
                    <div class="col-12 mb-2">
                        <label class="form-label">Payment Notes</label>
                        <textarea name="{{ $fieldName }}[{{ $paymentIndex }}][notes]" rows="2"
                                  class="form-control @error("{$fieldName}.{$paymentIndex}.notes") is-invalid @enderror"
                                  placeholder="Optional payment notes"
                                  data-payment-notes>{{ old("{$fieldName}.{$paymentIndex}.notes", $paymentRow['notes'] ?? '') }}</textarea>
                        @error("{$fieldName}.{$paymentIndex}.notes")
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                @endif
            </div>
        </div>
    @endforeach
</div>

<button type="button" class="btn btn-outline-primary btn-sm mb-2" id="{{ $containerId }}-add-more" data-batch-payment-add>
    <i class="fa fa-plus"></i> Add More
</button>

@if($helpText)
    <small class="text-muted d-block">{{ $helpText }}</small>
@endif

@once
    @push('js')
        <script>
            window.BatchPaymentRows = window.BatchPaymentRows || {
                instances: {},

                init: function (config) {
                    const containerId = config.containerId;
                    if (this.instances[containerId]) {
                        return;
                    }

                    const instance = {
                        containerId: containerId,
                        fieldName: config.fieldName,
                        paymentMethods: config.paymentMethods || [],
                        defaultPaymentDate: config.defaultPaymentDate || '',
                        defaultCardBankId: config.defaultCardBankId || '',
                        hasBankAccounts: !!config.hasBankAccounts,
                        showPaymentDate: config.showPaymentDate !== false,
                        showNotes: config.showNotes !== false,
                        showRemoveButton: config.showRemoveButton !== false,
                        bankAccountOptionsHtml: config.bankAccountOptionsHtml || '',
                        onAmountChange: typeof config.onAmountChange === 'function' ? config.onAmountChange : null,
                        onRowCountChange: typeof config.onRowCountChange === 'function' ? config.onRowCountChange : null,
                    };

                    this.instances[containerId] = instance;
                    this.bind(containerId);
                    this.refreshRemoveButtons(containerId);
                    if (instance.onRowCountChange) {
                        instance.onRowCountChange(this.rows(containerId).length);
                    }
                },

                rowsContainer: function (containerId) {
                    return document.getElementById(containerId + '-rows');
                },

                rows: function (containerId) {
                    const container = this.rowsContainer(containerId);
                    return container ? Array.from(container.querySelectorAll('[data-payment-row]')) : [];
                },

                requiresBankAccount: function (method) {
                    return method === 'Bank Transfer' || method === 'Card Payment';
                },

                bindRow: function (containerId, row) {
                    const self = this;
                    const methodSelect = row.querySelector('[data-payment-method]');
                    const amountInput = row.querySelector('[data-payment-amount]');
                    const removeButton = row.querySelector('[data-remove-payment]');

                    if (methodSelect) {
                        methodSelect.addEventListener('change', function () {
                            self.toggleBankAccountField(row);
                        });
                        self.toggleBankAccountField(row);
                    }

                    if (amountInput) {
                        amountInput.addEventListener('input', function () {
                            const instance = self.instances[containerId];
                            if (instance && instance.onAmountChange) {
                                instance.onAmountChange();
                            }
                        });
                    }

                    if (removeButton) {
                        removeButton.addEventListener('click', function () {
                            self.removeRow(containerId, row);
                        });
                    }
                },

                toggleBankAccountField: function (row) {
                    const methodSelect = row.querySelector('[data-payment-method]');
                    const bankField = row.querySelector('[data-bank-account-field]');
                    const bankSelect = row.querySelector('[data-bank-account-select]');
                    const containerId = row.closest('[data-batch-payment-rows]')?.id?.replace('-rows', '') || '';
                    const instance = this.instances[containerId];

                    if (!methodSelect || !bankField) {
                        return;
                    }

                    const needsBank = this.requiresBankAccount(methodSelect.value);
                    bankField.classList.toggle('d-none', !needsBank);

                    if (bankSelect) {
                        bankSelect.required = needsBank && instance && instance.hasBankAccounts;

                        if (!needsBank) {
                            bankSelect.value = '';
                        } else if (methodSelect.value === 'Card Payment' && !bankSelect.value && instance && instance.defaultCardBankId) {
                            bankSelect.value = String(instance.defaultCardBankId);
                        }
                    }
                },

                reindex: function (containerId) {
                    const instance = this.instances[containerId];
                    if (!instance) {
                        return;
                    }

                    const pattern = new RegExp(instance.fieldName.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\[\\d+\\]');

                    this.rows(containerId).forEach(function (row, index) {
                        row.querySelectorAll('[name]').forEach(function (input) {
                            input.name = input.name.replace(pattern, instance.fieldName + '[' + index + ']');
                        });
                        row.querySelectorAll('[id]').forEach(function (input) {
                            if (input.id.indexOf(containerId) === 0) {
                                input.id = containerId + '_bank_account_' + index;
                            }
                        });
                    });
                },

                bankAccountFieldHtml: function (containerId, index, selected) {
                    const instance = this.instances[containerId];
                    if (!instance) {
                        return '';
                    }

                    let options = instance.bankAccountOptionsHtml;
                    if (selected) {
                        options = options.replace(
                            'value="' + selected + '"',
                            'value="' + selected + '" selected'
                        );
                    }

                    return '<div class="col-12 mb-2">' +
                        '<div class="bank-account-field d-none" data-bank-account-field>' +
                            '<label class="form-label">Bank Account <span class="text-danger">*</span></label>' +
                            '<select name="' + instance.fieldName + '[' + index + '][bank_account_id]" class="form-control" data-bank-account-select>' +
                                options +
                            '</select>' +
                        '</div>' +
                    '</div>';
                },

                rowTemplate: function (containerId, index) {
                    const instance = this.instances[containerId];
                    const methodOptions = instance.paymentMethods.map(function (method) {
                        return '<option value="' + method + '">' + method + '</option>';
                    }).join('');

                    let html = '<div class="batch-payment-row border rounded p-2 mb-2" data-payment-row>' +
                        '<div class="row">' +
                            '<div class="col-md-3 mb-2">' +
                                '<label class="form-label">Payment Method <span class="text-danger">*</span></label>' +
                                '<select name="' + instance.fieldName + '[' + index + '][payment_method]" class="form-control" data-payment-method>' +
                                    '<option value="">Select Method</option>' + methodOptions +
                                '</select>' +
                            '</div>' +
                            this.bankAccountFieldHtml(containerId, index, '') ;

                    if (instance.showPaymentDate) {
                        html += '<div class="col-md-3 mb-2">' +
                            '<label class="form-label">Payment Date <span class="text-danger">*</span></label>' +
                            '<input type="date" name="' + instance.fieldName + '[' + index + '][payment_date]" class="form-control" value="' + instance.defaultPaymentDate + '" data-payment-date>' +
                        '</div>';
                    }

                    html += '<div class="col-md-3 mb-2">' +
                            '<label class="form-label">Amount <span class="text-danger">*</span></label>' +
                            '<input type="number" name="' + instance.fieldName + '[' + index + '][amount]" class="form-control batch-payment-amount" min="0.01" step="0.01" placeholder="0.00" data-payment-amount>' +
                        '</div>';

                    if (instance.showRemoveButton) {
                        html += '<div class="col-md-3 mb-2 d-flex align-items-end">' +
                            '<button type="button" class="btn btn-outline-danger btn-sm" data-remove-payment>Remove</button>' +
                        '</div>';
                    }

                    if (instance.showNotes) {
                        html += '<div class="col-12 mb-2">' +
                            '<label class="form-label">Payment Notes</label>' +
                            '<textarea name="' + instance.fieldName + '[' + index + '][notes]" rows="2" class="form-control" placeholder="Optional payment notes" data-payment-notes></textarea>' +
                        '</div>';
                    }

                    html += '</div></div>';

                    return html;
                },

                addRow: function (containerId) {
                    const container = this.rowsContainer(containerId);
                    if (!container) {
                        return;
                    }

                    const index = this.rows(containerId).length;
                    container.insertAdjacentHTML('beforeend', this.rowTemplate(containerId, index));
                    const row = container.lastElementChild;
                    this.bindRow(containerId, row);
                    this.refreshRemoveButtons(containerId);

                    const instance = this.instances[containerId];
                    if (instance && instance.onRowCountChange) {
                        instance.onRowCountChange(this.rows(containerId).length);
                    }
                },

                removeRow: function (containerId, row) {
                    const rows = this.rows(containerId);
                    if (rows.length <= 1) {
                        return;
                    }

                    row.remove();
                    this.reindex(containerId);
                    this.refreshRemoveButtons(containerId);

                    const instance = this.instances[containerId];
                    if (instance && instance.onAmountChange) {
                        instance.onAmountChange();
                    }
                    if (instance && instance.onRowCountChange) {
                        instance.onRowCountChange(this.rows(containerId).length);
                    }
                },

                refreshRemoveButtons: function (containerId) {
                    const instance = this.instances[containerId];
                    if (!instance || !instance.showRemoveButton) {
                        return;
                    }

                    const rows = this.rows(containerId);
                    rows.forEach(function (row) {
                        const button = row.querySelector('[data-remove-payment]');
                        if (button) {
                            button.style.visibility = rows.length > 1 ? 'visible' : 'hidden';
                        }
                    });
                },

                totalAmount: function (containerId, exceptInput) {
                    let total = 0;
                    this.rows(containerId).forEach(function (row) {
                        const input = row.querySelector('[data-payment-amount]');
                        if (input && input !== exceptInput) {
                            total += Number(input.value || 0);
                        }
                    });
                    return total;
                },

                bind: function (containerId) {
                    const self = this;
                    const addButton = document.getElementById(containerId + '-add-more');
                    const container = this.rowsContainer(containerId);

                    if (container) {
                        this.rows(containerId).forEach(function (row) {
                            self.bindRow(containerId, row);
                        });
                    }

                    if (addButton) {
                        addButton.addEventListener('click', function () {
                            self.addRow(containerId);
                        });
                    }
                },
            };
        </script>
    @endpush
@endonce

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            @php
                $bankOptionsHtml = '<option value="">Select Bank Account</option>';
                foreach ($bankAccounts as $account) {
                    $bankOptionsHtml .= '<option value="'.$account->id.'" data-account-number="'.$account->account_number.'">'.$account->bank_name.'</option>';
                }
            @endphp

            window.BatchPaymentRows.init({
                containerId: @json($containerId),
                fieldName: @json($fieldName),
                paymentMethods: @json($paymentMethods),
                defaultPaymentDate: @json($defaultPaymentDate),
                defaultCardBankId: @json($defaultCardBankAccountId),
                hasBankAccounts: @json($bankAccounts->isNotEmpty()),
                showPaymentDate: @json($showPaymentDate),
                showNotes: @json($showNotes),
                showRemoveButton: @json($showRemoveButton),
                bankAccountOptionsHtml: @json($bankOptionsHtml),
                onAmountChange: @json(! empty($onAmountChangeCallback)) ? function () {
                    if (typeof window[@json($onAmountChangeCallback ?? '')] === 'function') {
                        window[@json($onAmountChangeCallback ?? '')]();
                    }
                } : null,
                onRowCountChange: @json(! empty($onRowCountChangeCallback)) ? function (count) {
                    if (typeof window[@json($onRowCountChangeCallback ?? '')] === 'function') {
                        window[@json($onRowCountChangeCallback ?? '')](count);
                    }
                } : null,
            });
        });
    </script>
@endpush
