@php
    $bankAccounts = $bankAccounts ?? collect();
    $defaultCardBankAccountId = $defaultCardBankAccountId
        ?? $bankAccounts->firstWhere('account_number', \App\Models\BankAccount::DEFAULT_CARD_ACCOUNT_NUMBER)?->id;
@endphp

<div class="modal fade" id="refundDepositModal" tabindex="-1" role="dialog" aria-labelledby="refundDepositModalLabel" aria-hidden="true"
     data-default-card-bank-id="{{ $defaultCardBankAccountId ?? '' }}">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="refundDepositForm" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="refundDepositModalLabel">Refund Deposit</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="refundDepositAmount">Amount <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="refundDepositAmount" class="form-control"
                               min="0" step="0.01" readonly value="{{ old('amount') }}">
                        <small class="text-muted">Calculated automatically: deposit minus deductions and outstanding balance.</small>
                    </div>
                    <div class="refund-settlement-summary mb-3">
                        <div class="refund-settlement-summary__title">
                            <span><i class="fa fa-calculator"></i></span>
                            Settlement Breakdown
                        </div>
                        <div class="refund-settlement-summary__row"><span>Deposit Amount</span><strong id="refundGrossDeposit">£0.00</strong></div>
                        <div class="refund-settlement-summary__row"><span>Deductions</span><strong id="refundDeductions">£0.00</strong></div>
                        <div class="refund-settlement-summary__row"><span>Driver outstanding</span><strong id="refundOutstanding">£0.00</strong></div>
                        <div class="refund-settlement-summary__row"><span>Applied to debt</span><strong id="refundDebtOffset">£0.00</strong></div>
                        <div class="refund-settlement-summary__row"><span>Remaining debt</span><strong id="refundRemainingDebt">£0.00</strong></div>
                        <div class="refund-settlement-summary__row refund-settlement-summary__total">
                            <span>Final refund</span><strong id="refundFinalAmount">£0.00</strong>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="refund_date">Refund Date <span class="text-danger">*</span></label>
                        <input type="date" name="refund_date" id="refund_date" class="form-control"
                               value="{{ old('refund_date', now()->toDateString()) }}" required>
                    </div>
                    <div id="refundPaymentFields">
                        <div class="form-group">
                            <label for="refund_payment_method">Payment Method <span class="text-danger">*</span></label>
                            <select name="payment_method" id="refund_payment_method" class="form-control">
                                <option value="">Select method</option>
                                <option value="Cash" {{ old('payment_method') === 'Cash' ? 'selected' : '' }}>Cash</option>
                                <option value="Bank Transfer" {{ old('payment_method') === 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="Cheque" {{ old('payment_method') === 'Cheque' ? 'selected' : '' }}>Cheque</option>
                                <option value="Card Payment" {{ old('payment_method') === 'Card Payment' ? 'selected' : '' }}>Card Payment</option>
                                <option value="Direct Debit" {{ old('payment_method') === 'Direct Debit' ? 'selected' : '' }}>Direct Debit</option>
                                <option value="Driver Credit" {{ old('payment_method') === 'Driver Credit' ? 'selected' : '' }}>Add to driver account</option>
                            </select>
                        </div>
                        @include('backend.payments.partials.bank-account-select', [
                            'bankAccounts' => $bankAccounts,
                            'name' => 'bank_account_id',
                            'id' => 'refund_bank_account_id',
                            'selected' => old('bank_account_id'),
                            'wrapperClass' => 'bank-account-field d-none form-group',
                            'errorKey' => 'bank_account_id',
                        ])
                    </div>
                    <div class="form-group">
                        <label for="refund_notes">Notes</label>
                        <textarea name="notes" id="refund_notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Record Refund</button>
                </div>
            </form>
        </div>
    </div>
</div>

@once
@push('css')
<style>
    .refund-settlement-summary {
        overflow: hidden;
        padding: 0;
        color: #5e5873;
        background: #fff;
        border: 1px solid rgba(115, 103, 240, 0.22);
        border-radius: 9px;
        box-shadow: 0 2px 8px rgba(34, 41, 47, 0.05);
    }

    .refund-settlement-summary__title {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 11px 14px;
        color: #5e5873;
        background: rgba(115, 103, 240, 0.08);
        border-bottom: 1px solid rgba(115, 103, 240, 0.16);
        font-size: 13px;
        font-weight: 600;
    }

    .refund-settlement-summary__title span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 26px;
        height: 26px;
        color: #7367f0;
        background: rgba(115, 103, 240, 0.14);
        border-radius: 6px;
    }

    .refund-settlement-summary__row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 6px 14px;
        font-size: 13px;
    }

    .refund-settlement-summary__row strong {
        color: #5e5873;
        font-weight: 600;
    }

    .refund-settlement-summary__total {
        margin-top: 5px;
        padding-top: 11px;
        padding-bottom: 11px;
        color: #7367f0;
        background: rgba(115, 103, 240, 0.05);
        border-top: 1px solid rgba(115, 103, 240, 0.16);
        font-size: 14px;
        font-weight: 600;
    }

    .refund-settlement-summary__total strong {
        color: #7367f0;
    }
</style>
@endpush
@push('js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modal = document.getElementById('refundDepositModal');
        if (!modal) {
            return;
        }

        var form = document.getElementById('refundDepositForm');
        var methodSelect = document.getElementById('refund_payment_method');
        var amountInput = document.getElementById('refundDepositAmount');
        var paymentFields = document.getElementById('refundPaymentFields');
        var bankField = modal.querySelector('[data-bank-account-field]');
        var bankSelect = modal.querySelector('[data-bank-account-select]');
        var defaultCardBankId = modal.getAttribute('data-default-card-bank-id') || '';
        var hasBankAccounts = !!(bankSelect && bankSelect.options.length > 1);

        function requiresBankAccount(method) {
            return method === 'Bank Transfer' || method === 'Card Payment';
        }

        function applyDefaultCardBank() {
            if (!bankSelect || !defaultCardBankId) {
                return;
            }
            if (methodSelect && methodSelect.value === 'Card Payment' && !bankSelect.value) {
                bankSelect.value = defaultCardBankId;
            }
        }

        function toggleBankAccount() {
            var needsBank = methodSelect && requiresBankAccount(methodSelect.value);
            if (bankField) {
                bankField.classList.toggle('d-none', !needsBank);
            }
            if (bankSelect) {
                bankSelect.required = !!needsBank && hasBankAccounts;
                if (!needsBank) {
                    bankSelect.value = '';
                } else {
                    applyDefaultCardBank();
                }
            }
        }

        if (methodSelect) {
            methodSelect.addEventListener('change', toggleBankAccount);
            toggleBankAccount();
        }

        document.querySelectorAll('[data-refund-deposit-btn]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (btn.disabled) {
                    return;
                }
                form.action = btn.getAttribute('data-action');
                var amount = btn.getAttribute('data-amount') || '0';
                amountInput.value = parseFloat(amount).toFixed(2);
                var isRefundDue = parseFloat(amount) > 0;
                paymentFields.classList.toggle('d-none', !isRefundDue);
                methodSelect.required = isRefundDue;
                if (!isRefundDue) {
                    methodSelect.value = '';
                }
                [
                    ['refundGrossDeposit', 'data-gross-deposit'],
                    ['refundDeductions', 'data-deductions'],
                    ['refundOutstanding', 'data-driver-outstanding'],
                    ['refundDebtOffset', 'data-debt-offset'],
                    ['refundRemainingDebt', 'data-remaining-debt'],
                    ['refundFinalAmount', 'data-amount']
                ].forEach(function (item) {
                    var element = document.getElementById(item[0]);
                    var value = parseFloat(btn.getAttribute(item[1]) || '0');
                    element.textContent = '£' + value.toFixed(2);
                });
                toggleBankAccount();
            });
        });
    });
</script>
@endpush
@endonce
