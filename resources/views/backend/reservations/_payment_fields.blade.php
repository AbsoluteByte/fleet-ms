@php
    $reservationModel = $reservation ?? null;
    $selectedPaymentMethod = old('payment_method', $reservationModel->payment_method ?? '');
    $paymentMethods = ['Bank Transfer', 'Cash', 'Cheque', 'Card Payment', 'Direct Debit'];
    $amountPaidValue = old('amount_paid', $reservationModel->amount_paid ?? '');
    $showPaymentFields = (float) $amountPaidValue > 0 || filled($selectedPaymentMethod);
@endphp

<div id="reservation-payment-fields" class="col-12 {{ $showPaymentFields ? '' : 'd-none' }}">
    <div class="row">
        <div class="col-md-6 form-group">
            <label for="reservation_payment_method">Payment method <span class="text-danger">*</span></label>
            <select name="payment_method" id="reservation_payment_method"
                    class="form-control @error('payment_method') is-invalid @enderror"
                    data-reservation-payment-method>
                <option value="">Select Method</option>
                @foreach($paymentMethods as $paymentMethod)
                    <option value="{{ $paymentMethod }}" {{ $selectedPaymentMethod === $paymentMethod ? 'selected' : '' }}>
                        {{ $paymentMethod }}
                    </option>
                @endforeach
            </select>
            @error('payment_method')
            <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-6 form-group">
            @include('backend.payments.partials.bank-account-select', [
                'bankAccounts' => $bankAccounts ?? collect(),
                'selected' => old('bank_account_id', $reservationModel->bank_account_id ?? null),
                'name' => 'bank_account_id',
                'id' => 'reservation_bank_account_id',
                'errorKey' => 'bank_account_id',
                'wrapperClass' => 'bank-account-field' . ($selectedPaymentMethod === 'Bank Transfer' ? '' : ' d-none'),
            ])
        </div>
    </div>
</div>

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var amountPaidInput = document.getElementById('amount_paid');
            var paymentFields = document.getElementById('reservation-payment-fields');
            var paymentMethodSelect = document.getElementById('reservation_payment_method');
            var bankAccountField = paymentFields ? paymentFields.querySelector('[data-bank-account-field]') : null;
            var bankAccountSelect = paymentFields ? paymentFields.querySelector('[data-bank-account-select]') : null;
            var hasBankAccounts = {{ ($bankAccounts ?? collect())->isNotEmpty() ? 'true' : 'false' }};

            function parseAmountPaid() {
                if (!amountPaidInput) {
                    return 0;
                }
                var value = parseFloat(String(amountPaidInput.value).replace(',', '.'));
                return isNaN(value) ? 0 : value;
            }

            function toggleBankAccountField() {
                if (!paymentMethodSelect || !bankAccountField) {
                    return;
                }

                var isBankTransfer = paymentMethodSelect.value === 'Bank Transfer';
                bankAccountField.classList.toggle('d-none', !isBankTransfer);

                if (bankAccountSelect) {
                    bankAccountSelect.required = isBankTransfer && hasBankAccounts && parseAmountPaid() > 0;
                    if (!isBankTransfer) {
                        bankAccountSelect.value = '';
                    }
                }
            }

            function togglePaymentFields() {
                if (!paymentFields || !amountPaidInput) {
                    return;
                }

                var showFields = parseAmountPaid() > 0;
                paymentFields.classList.toggle('d-none', !showFields);

                if (paymentMethodSelect) {
                    paymentMethodSelect.required = showFields;
                    if (!showFields) {
                        paymentMethodSelect.value = '';
                    }
                }

                if (!showFields && bankAccountSelect) {
                    bankAccountSelect.value = '';
                    bankAccountSelect.required = false;
                }

                if (bankAccountField) {
                    bankAccountField.classList.toggle('d-none', !showFields || paymentMethodSelect.value !== 'Bank Transfer');
                }

                toggleBankAccountField();
            }

            if (paymentMethodSelect) {
                paymentMethodSelect.addEventListener('change', toggleBankAccountField);
            }

            if (amountPaidInput) {
                amountPaidInput.addEventListener('input', togglePaymentFields);
                amountPaidInput.addEventListener('change', togglePaymentFields);
            }

            togglePaymentFields();
        });
    </script>
@endpush
