@php
    $selectedPaymentMethod = old('payment_method', $model->payment_method ?? '');
    $paymentMethods = ['Bank Transfer', 'Cash', 'Cheque', 'Card Payment', 'Direct Debit'];
    $defaultCardBankAccountId = ($bankAccounts ?? collect())->firstWhere(
        'account_number',
        \App\Models\BankAccount::DEFAULT_CARD_ACCOUNT_NUMBER
    )?->id;
    $selectedBankAccountId = old(
        'bank_account_id',
        $model->bank_account_id
            ?? ($selectedPaymentMethod === 'Card Payment' ? $defaultCardBankAccountId : null)
    );
@endphp

<div class="row">
    <div class="col-md-6 mb-2">
        <div class="form-group">
            <label for="title">Expense Title <span class="text-danger">*</span></label>
            <input type="text" name="title" id="title"
                   class="form-control @error('title') is-invalid @enderror"
                   value="{{ old('title', $model->title ?? '') }}"
                   placeholder="e.g. Office supplies" required>
            @error('title')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6 mb-2">
        <div class="form-group">
            <label for="amount">Amount <span class="text-danger">*</span></label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">£</span>
                </div>
                <input type="number" name="amount" id="amount"
                       class="form-control @error('amount') is-invalid @enderror"
                       value="{{ old('amount', $model->amount ?? '') }}"
                       min="0.01" step="0.01" placeholder="0.00" required>
            </div>
            @error('amount')
            <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6 mb-2">
        <div class="form-group">
            <label for="payment_method">Payment Method <span class="text-danger">*</span></label>
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
    </div>

    <div class="col-md-6 mb-2">
        @include('backend.payments.partials.bank-account-select', [
            'bankAccounts' => $bankAccounts ?? collect(),
            'selected' => $selectedBankAccountId,
            'name' => 'bank_account_id',
            'id' => 'bank_account_id',
            'errorKey' => 'bank_account_id',
            'wrapperClass' => 'bank-account-field d-none',
        ])
    </div>

    <div class="col-md-6 mb-2">
        <div class="form-group">
            <label for="date">Date <span class="text-danger">*</span></label>
            <input type="date" name="date" id="date"
                   class="form-control @error('date') is-invalid @enderror"
                   value="{{ old('date', optional($model->date)->format('Y-m-d') ?? now()->toDateString()) }}"
                   required>
            @error('date')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6 mb-2">
        <div class="form-group">
            <label for="document">Document <span class="text-muted">(optional)</span></label>
            <input type="file" name="document" id="document"
                   class="form-control @error('document') is-invalid @enderror">
            @error('document')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-12 mb-2">
        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea name="notes" id="notes" rows="3"
                      class="form-control @error('notes') is-invalid @enderror"
                      placeholder="Optional notes">{{ old('notes', $model->notes ?? '') }}</textarea>
            @error('notes')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-12">
        <button type="submit" class="btn btn-primary">
            <i class="fa fa-save"></i> Save Daily Expense
        </button>
    </div>
</div>

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const paymentMethodSelect = document.getElementById('payment_method');
            const bankAccountField = document.querySelector('[data-bank-account-field]');
            const bankAccountSelect = document.querySelector('[data-bank-account-select]');
            const defaultCardBankId = @json($defaultCardBankAccountId ?? null);
            const hasBankAccounts = @json(($bankAccounts ?? collect())->isNotEmpty());

            function toggleBankAccountField() {
                if (!paymentMethodSelect || !bankAccountField) {
                    return;
                }

                const needsBank = paymentMethodSelect.value === 'Bank Transfer'
                    || paymentMethodSelect.value === 'Card Payment';
                bankAccountField.classList.toggle('d-none', !needsBank);

                if (bankAccountSelect) {
                    bankAccountSelect.required = needsBank && hasBankAccounts;

                    if (!needsBank) {
                        bankAccountSelect.value = '';
                    } else if (paymentMethodSelect.value === 'Card Payment' && !bankAccountSelect.value && defaultCardBankId) {
                        bankAccountSelect.value = String(defaultCardBankId);
                    }
                }
            }

            if (paymentMethodSelect) {
                paymentMethodSelect.addEventListener('change', toggleBankAccountField);
                toggleBankAccountField();
            }
        });
    </script>
@endpush
