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
                               min="0.01" step="0.01" required value="{{ old('amount') }}">
                        <small class="text-muted">Full deposit prefilled — you can reduce for deductions.</small>
                    </div>
                    <div class="form-group">
                        <label for="refund_date">Refund Date <span class="text-danger">*</span></label>
                        <input type="date" name="refund_date" id="refund_date" class="form-control"
                               value="{{ old('refund_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="form-group">
                        <label for="refund_payment_method">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" id="refund_payment_method" class="form-control" required>
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
                amountInput.max = parseFloat(amount).toFixed(2);
            });
        });
    });
</script>
@endpush
@endonce
