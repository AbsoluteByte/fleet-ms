<div class="card mb-1">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fa fa-credit-card"></i> Payment Information
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-2">
                <label for="company_id" class="form-label">Select Company <span class="text-danger">*</span></label>
                <select name="company_id" id="company_id" class="form-control @error('company_id') is-invalid @enderror" required>
                    <option value="">-- Select Company --</option>
                    @foreach($companies as $id => $name)
                        <option value="{{ $id }}" {{ (string) old('company_id', $model->company_id ?? '') === (string) $id ? 'selected' : '' }}>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
                @error('company_id')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-2">
                <label for="payment_type" class="form-label">Payment Type <span class="text-danger">*</span></label>
                <select name="payment_type" id="payment_type" class="form-control @error('payment_type') is-invalid @enderror" required>
                    <option value="">Select Payment Type</option>
                    @foreach(['Bank Transfer', 'Cash', 'PayPal', 'Stripe'] as $paymentType)
                        <option value="{{ $paymentType }}" {{ old('payment_type', $model->payment_type ?? '') === $paymentType ? 'selected' : '' }}>
                            {{ $paymentType }}
                        </option>
                    @endforeach
                </select>
                @error('payment_type')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>

<div class="card mb-1" id="bank-details-section" style="display: none;">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fa fa-university"></i> Bank Details
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12 mb-2">
                <label for="bank_name" class="form-label">Bank/Building Society Name <span class="text-danger">*</span></label>
                <input type="text" name="bank_name" id="bank_name"
                       class="form-control @error('bank_name') is-invalid @enderror"
                       value="{{ old('bank_name', $model->bank_name ?? '') }}"
                       placeholder="Enter Bank or Building Society Name">
                @error('bank_name')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-2">
                <label for="account_number" class="form-label">Account Number <span class="text-danger">*</span></label>
                <input type="text" name="account_number" id="account_number"
                       class="form-control @error('account_number') is-invalid @enderror"
                       value="{{ old('account_number', $model->account_number ?? '') }}"
                       placeholder="Enter account number">
                @error('account_number')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-2">
                <label for="sort_code" class="form-label">Sort Code <span class="text-danger">*</span></label>
                <input type="text" name="sort_code" id="sort_code"
                       class="form-control @error('sort_code') is-invalid @enderror"
                       value="{{ old('sort_code', $model->sort_code ?? '') }}"
                       placeholder="XX-XX-XX">
                @error('sort_code')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-12 mb-2">
                <label for="iban_number" class="form-label">IBAN Number</label>
                <input type="text" name="iban_number" id="iban_number"
                       class="form-control @error('iban_number') is-invalid @enderror"
                       value="{{ old('iban_number', $model->iban_number ?? '') }}"
                       placeholder="Optional IBAN">
                @error('iban_number')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>

<div class="card mb-1" id="stripe-details-section" style="display: none;">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fab fa-stripe"></i> Stripe API Keys
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-2">
                <label for="stripe_public_key" class="form-label">Stripe Public Key <span class="text-danger">*</span></label>
                <input type="text" name="stripe_public_key" id="stripe_public_key"
                       class="form-control @error('stripe_public_key') is-invalid @enderror"
                       value="{{ old('stripe_public_key', $model->stripe_public_key ?? '') }}"
                       placeholder="pk_live_...">
                @error('stripe_public_key')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-2">
                <label for="stripe_secret_key" class="form-label">Stripe Secret Key <span class="text-danger">*</span></label>
                <input type="password" name="stripe_secret_key" id="stripe_secret_key"
                       class="form-control @error('stripe_secret_key') is-invalid @enderror"
                       value="{{ old('stripe_secret_key', $model->stripe_secret_key ?? '') }}"
                       placeholder="sk_live_...">
                @error('stripe_secret_key')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>

<div class="card mb-1" id="paypal-details-section" style="display: none;">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fab fa-paypal"></i> PayPal API Credentials
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-2">
                <label for="paypal_client_id" class="form-label">PayPal Client ID <span class="text-danger">*</span></label>
                <input type="text" name="paypal_client_id" id="paypal_client_id"
                       class="form-control @error('paypal_client_id') is-invalid @enderror"
                       value="{{ old('paypal_client_id', $model->paypal_client_id ?? '') }}"
                       placeholder="Client ID">
                @error('paypal_client_id')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-2">
                <label for="paypal_secret" class="form-label">PayPal Secret <span class="text-danger">*</span></label>
                <input type="password" name="paypal_secret" id="paypal_secret"
                       class="form-control @error('paypal_secret') is-invalid @enderror"
                       value="{{ old('paypal_secret', $model->paypal_secret ?? '') }}"
                       placeholder="Secret">
                @error('paypal_secret')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>

<div class="card mb-1" id="cash-notice-section" style="display: none;">
    <div class="card-body">
        <div class="alert alert-info mb-0">
            <i class="fa fa-info-circle"></i>
            Cash payment method does not need additional details.
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <button type="submit" class="btn btn-primary">
            <i class="fa fa-save"></i> {{ isset($model->id) ? 'Update Payment Setting' : 'Create Payment Setting' }}
        </button>
        <a href="{{ route($url . 'index') }}" class="btn btn-secondary ml-2">
            <i class="fa fa-times"></i> Cancel
        </a>
    </div>
</div>

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const paymentType = document.getElementById('payment_type');
            const sections = {
                'Bank Transfer': document.getElementById('bank-details-section'),
                'Stripe': document.getElementById('stripe-details-section'),
                'PayPal': document.getElementById('paypal-details-section'),
                'Cash': document.getElementById('cash-notice-section')
            };

            function togglePaymentSections() {
                Object.values(sections).forEach(section => section.style.display = 'none');

                if (sections[paymentType.value]) {
                    sections[paymentType.value].style.display = 'block';
                }
            }

            paymentType.addEventListener('change', togglePaymentSections);
            togglePaymentSections();
        });
    </script>
@endpush
