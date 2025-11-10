<!-- Payment Information -->
<div class="card mb-1">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fa fa-credit-card me-2"></i>
            Payment Information
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            {{-- Company Selection --}}
            <div class="col-md-6 mb-2">
                <label for="company_id" class="form-label fw-bold">Select Company <span
                        class="text-danger">*</span></label>
                <select name="company_id" id="company_id"
                        class="form-control @error('company_id') is-invalid @enderror" required>
                    <option value="">-- Select Company --</option>
                    @php
                        try {
                            $companies = \App\Models\Company::select('name', 'id')->get()->pluck('name', 'id');
                            $selectedCompany = old('company_id') ?? ($model->company_id ?? '');
                        } catch (\Exception $e) {
                            $companies = collect();
                            $selectedCompany = old('company_id') ?? '';
                        }
                    @endphp
                    @foreach($companies as $id => $name)
                        <option value="{{ $id }}" {{ $selectedCompany == $id ? 'selected' : '' }}>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
                @error('company_id')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Payment Type --}}
            <div class="col-md-6 mb-2">
                <label for="payment_type" class="form-label fw-bold">Payment Type <span
                        class="text-danger">*</span></label>
                <select name="payment_type" id="payment_type"
                        class="form-control @error('payment_type') is-invalid @enderror" required>
                    <option value="">Select Payment Type</option>
                    @php
                        $paymentTypes = [
                            'Bank Transfer' => 'Bank Transfer',
                            'Cash' => 'Cash',
                            'PayPal' => 'PayPal',
                            'Stripe' => 'Stripe',
                        ];
                        $selectedType = old('payment_type') ?? ($model->payment_type ?? '');
                    @endphp
                    @foreach($paymentTypes as $key => $value)
                        <option value="{{ $key }}" {{ $selectedType == $key ? 'selected' : '' }}>
                            {{ $value }}
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

<!-- Bank Details (Only for Bank Transfer) -->
<div class="card mb-1" id="bank-details-section" style="display: none;">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fa fa-university me-2"></i>
            Bank Details
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            {{-- Bank/Building Society Name --}}
            <div class="col-md-12 mb-3">
                <label for="bank_name" class="form-label fw-bold">Bank/Building Society Name <span
                        class="text-danger">*</span></label>
                <input type="text" name="bank_name" id="bank_name"
                       class="form-control @error('bank_name') is-invalid @enderror"
                       value="{{ old('bank_name') ?? ($model->bank_name ?? '') }}"
                       placeholder="Enter Bank or Building Society Name">
                @error('bank_name')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Account Number --}}
            <div class="col-md-6 mb-3">
                <label for="account_number" class="form-label fw-bold">Account Number <span class="text-danger">*</span></label>
                <input type="text" name="account_number" id="account_number"
                       class="form-control @error('account_number') is-invalid @enderror"
                       value="{{ old('account_number') ?? ($model->account_number ?? '') }}"
                       placeholder="Enter 8-digit Account Number"
                       maxlength="8" minlength="8">
                @error('account_number')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">8-digit account number (e.g. 12345678)</small>
            </div>

            {{-- Sort Code --}}
            <div class="col-md-6 mb-3">
                <label for="sort_code" class="form-label fw-bold">Sort Code <span class="text-danger">*</span></label>
                <input type="text" name="sort_code" id="sort_code"
                       class="form-control @error('sort_code') is-invalid @enderror"
                       value="{{ old('sort_code') ?? ($model->sort_code ?? '') }}"
                       placeholder="XX-XX-XX"
                       maxlength="8">
                @error('sort_code')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">6-digit sort code (e.g. 12-34-56)</small>
            </div>

            {{-- IBAN Number --}}
            <div class="col-md-12 mb-3">
                <label for="iban_number" class="form-label fw-bold">IBAN Number</label>
                <input type="text" name="iban_number" id="iban_number"
                       class="form-control @error('iban_number') is-invalid @enderror"
                       value="{{ old('iban_number') ?? ($model->iban_number ?? '') }}"
                       placeholder="Enter IBAN Number (Optional)"
                       maxlength="34">
                @error('iban_number')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">International Bank Account Number (Optional, up to 34
                    characters)</small>
            </div>
        </div>
    </div>
</div>

<!-- Stripe Details (Only for Stripe) -->
<div class="card mb-1" id="stripe-details-section" style="display: none;">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fab fa-stripe me-2"></i>
            Stripe API Keys
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            {{-- Stripe Public Key --}}
            <div class="col-md-6 mb-3">
                <label for="stripe_public_key" class="form-label fw-bold">Stripe Public Key <span class="text-danger">*</span></label>
                <input type="text" name="stripe_public_key" id="stripe_public_key"
                       class="form-control @error('stripe_public_key') is-invalid @enderror"
                       value="{{ old('stripe_public_key') ?? ($model->stripe_public_key ?? '') }}"
                       placeholder="pk_live_...">
                @error('stripe_public_key')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">Your Stripe publishable key</small>
            </div>

            {{-- Stripe Secret Key --}}
            <div class="col-md-6 mb-3">
                <label for="stripe_secret_key" class="form-label fw-bold">Stripe Secret Key <span class="text-danger">*</span></label>
                <input type="password" name="stripe_secret_key" id="stripe_secret_key"
                       class="form-control @error('stripe_secret_key') is-invalid @enderror"
                       value="{{ old('stripe_secret_key') ?? ($model->stripe_secret_key ?? '') }}"
                       placeholder="sk_live_...">
                @error('stripe_secret_key')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">Your Stripe secret key (keep it secure)</small>
            </div>
        </div>
    </div>
</div>

<!-- PayPal Details (Only for PayPal) -->
<div class="card mb-1" id="paypal-details-section" style="display: none;">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fab fa-paypal me-2"></i>
            PayPal API Credentials
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            {{-- PayPal Client ID --}}
            <div class="col-md-6 mb-3">
                <label for="paypal_client_id" class="form-label fw-bold">PayPal Client ID <span class="text-danger">*</span></label>
                <input type="text" name="paypal_client_id" id="paypal_client_id"
                       class="form-control @error('paypal_client_id') is-invalid @enderror"
                       value="{{ old('paypal_client_id') ?? ($model->paypal_client_id ?? '') }}"
                       placeholder="AXxxx...">
                @error('paypal_client_id')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">Your PayPal Client ID</small>
            </div>

            {{-- PayPal Secret --}}
            <div class="col-md-6 mb-3">
                <label for="paypal_secret" class="form-label fw-bold">PayPal Secret <span class="text-danger">*</span></label>
                <input type="password" name="paypal_secret" id="paypal_secret"
                       class="form-control @error('paypal_secret') is-invalid @enderror"
                       value="{{ old('paypal_secret') ?? ($model->paypal_secret ?? '') }}"
                       placeholder="EJxxx...">
                @error('paypal_secret')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">Your PayPal Secret (keep it secure)</small>
            </div>
        </div>
    </div>
</div>

<!-- Cash Payment Notice (Only for Cash) -->
<div class="card mb-1" id="cash-notice-section" style="display: none;">
    <div class="card-body">
        <div class="alert alert-info mb-0">
            <i class="fa fa-info-circle me-2"></i>
            <strong>Cash Payment Method</strong><br>
            No additional details required for cash payments. This payment method will be available for cash transactions only.
        </div>
    </div>
</div>

<!-- Form Actions -->
<div class="row">
    <div class="col-12">
        <div class="form-group">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i>
                {{ isset($model->id) ? 'Update Payment Method' : 'Create Payment Method' }}
            </button>
            <a href="{{ route($url . 'index') }}" class="btn btn-secondary ml-2">
                <i class="fa fa-times"></i> Cancel
            </a>
        </div>
    </div>
</div>

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Toggle sections based on payment type
            function togglePaymentSections() {
                const paymentType = document.getElementById('payment_type').value;

                // Hide all sections first
                document.getElementById('bank-details-section').style.display = 'none';
                document.getElementById('stripe-details-section').style.display = 'none';
                document.getElementById('paypal-details-section').style.display = 'none';
                document.getElementById('cash-notice-section').style.display = 'none';

                // Clear required attributes from all fields
                document.getElementById('bank_name').required = false;
                document.getElementById('account_number').required = false;
                document.getElementById('sort_code').required = false;
                document.getElementById('stripe_public_key').required = false;
                document.getElementById('stripe_secret_key').required = false;
                document.getElementById('paypal_client_id').required = false;
                document.getElementById('paypal_secret').required = false;

                // Show relevant section and set required fields
                if (paymentType === 'Bank Transfer') {
                    document.getElementById('bank-details-section').style.display = 'block';
                    document.getElementById('bank_name').required = true;
                    document.getElementById('account_number').required = true;
                    document.getElementById('sort_code').required = true;
                } else if (paymentType === 'Stripe') {
                    document.getElementById('stripe-details-section').style.display = 'block';
                    document.getElementById('stripe_public_key').required = true;
                    document.getElementById('stripe_secret_key').required = true;
                } else if (paymentType === 'PayPal') {
                    document.getElementById('paypal-details-section').style.display = 'block';
                    document.getElementById('paypal_client_id').required = true;
                    document.getElementById('paypal_secret').required = true;
                } else if (paymentType === 'Cash') {
                    document.getElementById('cash-notice-section').style.display = 'block';
                }
            }

            // Initialize on page load
            togglePaymentSections();

            // Listen to payment type changes
            document.getElementById('payment_type').addEventListener('change', togglePaymentSections);

            // Sort code formatting
            const sortCodeField = document.getElementById('sort_code');
            if (sortCodeField) {
                sortCodeField.addEventListener('input', function () {
                    let value = this.value.replace(/\D/g, ''); // Remove non-digits

                    if (value.length > 6) {
                        value = value.substring(0, 6);
                    }

                    // Format as XX-XX-XX
                    if (value.length >= 3) {
                        value = value.substring(0, 2) + '-' + value.substring(2);
                    }
                    if (value.length >= 6) {
                        value = value.substring(0, 5) + '-' + value.substring(5);
                    }

                    this.value = value;
                });
            }

            // Account number validation
            const accountField = document.getElementById('account_number');
            if (accountField) {
                accountField.addEventListener('input', function () {
                    // Only allow digits
                    this.value = this.value.replace(/\D/g, '');

                    if (this.value.length > 8) {
                        this.value = this.value.substring(0, 8);
                    }
                });

                accountField.addEventListener('blur', function () {
                    if (this.value.length > 0 && this.value.length < 8) {
                        this.classList.add('is-invalid');
                        let feedback = this.parentNode.querySelector('.custom-invalid-feedback');
                        if (!feedback) {
                            feedback = document.createElement('div');
                            feedback.className = 'invalid-feedback custom-invalid-feedback';
                            this.parentNode.appendChild(feedback);
                        }
                        feedback.textContent = 'Account number must be exactly 8 digits.';
                    } else {
                        this.classList.remove('is-invalid');
                        const feedback = this.parentNode.querySelector('.custom-invalid-feedback');
                        if (feedback) {
                            feedback.remove();
                        }
                    }
                });
            }

            // IBAN formatting and validation
            const ibanField = document.getElementById('iban_number');
            if (ibanField) {
                ibanField.addEventListener('input', function () {
                    // Convert to uppercase and remove spaces
                    this.value = this.value.toUpperCase().replace(/\s/g, '');

                    if (this.value.length > 34) {
                        this.value = this.value.substring(0, 34);
                    }
                });

                ibanField.addEventListener('blur', function () {
                    const iban = this.value;
                    if (iban && iban.length > 0) {
                        // Basic IBAN validation
                        const ibanRegex = /^[A-Z]{2}[0-9]{2}[A-Z0-9]{4}[0-9]{7}([A-Z0-9]?){0,16}$/;
                        if (!ibanRegex.test(iban)) {
                            this.classList.add('is-invalid');
                            let feedback = this.parentNode.querySelector('.custom-invalid-feedback');
                            if (!feedback) {
                                feedback = document.createElement('div');
                                feedback.className = 'invalid-feedback custom-invalid-feedback';
                                this.parentNode.appendChild(feedback);
                            }
                            feedback.textContent = 'Please enter a valid IBAN format.';
                        } else {
                            this.classList.remove('is-invalid');
                            const feedback = this.parentNode.querySelector('.custom-invalid-feedback');
                            if (feedback) {
                                feedback.remove();
                            }
                        }
                    }
                });
            }

            // Auto-capitalize bank name
            const bankField = document.getElementById('bank_name');
            if (bankField) {
                bankField.addEventListener('blur', function () {
                    this.value = this.value.replace(/\b\w/g, l => l.toUpperCase());
                });
            }
        });
    </script>
@endpush
