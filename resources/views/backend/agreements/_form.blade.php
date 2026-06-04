<!-- Basic Information -->
<div class="card mb-2">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fa fa-info-circle me-2"></i>
            Agreement Details
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="car_id" class="form-label">Vehicle *</label>
                    <select name="car_id" id="car_id" class="form-control @error('car_id') is-invalid @enderror" required>
                        <option value="">Select Vehicle</option>
                        @foreach($cars as $car)
                            <option value="{{ $car->id }}"
                                    data-company-id="{{ $car->company_id }}"
                                {{ (old('car_id') ?? (isset($model) ? $model->car_id : '')) == $car->id ? 'selected' : '' }}>
                                {{ $car->registration }} - {{ $car->carModel->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('car_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="company_id" class="form-label">Company *</label>
                    <select name="company_id" id="company_id" class="form-control @error('company_id') is-invalid @enderror" required>
                        <option value="">Select Company</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ (old('company_id') ?? (isset($model) ? $model->company_id : '')) == $company->id ? 'selected' : '' }}>
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('company_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-6">
                <div class="mb-3">
                    <label for="driver_id" class="form-label">Driver *</label>
                    <select name="driver_id" id="driver_id" class="form-control @error('driver_id') is-invalid @enderror" required>
                        <option value="">Select Driver</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}" {{ (old('driver_id') ?? (isset($model) ? $model->driver_id : '')) == $driver->id ? 'selected' : '' }}>
                                {{ $driver->full_name }} - {{ $driver->post_code ?: '—' }} - {{ $driver->phone_number ?: '—' }}
                            </option>
                        @endforeach
                    </select>
                    @error('driver_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-6">
                <div class="mb-3">
                    <label for="status_id" class="form-label">Status *</label>
                    <select name="status_id" id="status_id" class="form-control @error('status_id') is-invalid @enderror" required>
                        <option value="">Select Status</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status->id }}" {{ (old('status_id') ?? (isset($model) ? $model->status_id : '')) == $status->id ? 'selected' : '' }}>
                                {{ $status->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('status_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-6">
                <div class="mb-3">
                    <label for="start_date" class="form-label">Start Date &amp; Time *</label>
                    <input type="datetime-local" name="start_date" id="start_date"
                           class="form-control @error('start_date') is-invalid @enderror"
                           value="{{ old('start_date') ?? (isset($model) && $model->start_date ? $model->start_date->format('Y-m-d\TH:i') : '') }}" required>
                    @error('start_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-6">
                <div class="mb-3">
                    <label for="end_date" class="form-label">End Date *</label>
                    <input type="date" name="end_date" id="end_date"
                           class="form-control @error('end_date') is-invalid @enderror"
                           value="{{ old('end_date') ?? (isset($model) ? $model->end_date?->format('Y-m-d') : '') }}" required>
                    @error('end_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-6">
                <div class="mb-3">
                    <label for="termination_notice_date" class="form-label">Termination Notice Date</label>
                    <input type="date" name="termination_notice_date" id="termination_notice_date"
                           class="form-control @error('termination_notice_date') is-invalid @enderror"
                           value="{{ old('termination_notice_date') ?? (isset($model) && $model->termination_notice_date ? $model->termination_notice_date->format('Y-m-d') : '') }}">
                    @error('termination_notice_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-6">
                <div class="mb-3">
                    <label for="termination_available_from_date" class="form-label">Car Available From</label>
                    <input type="date" name="termination_available_from_date" id="termination_available_from_date"
                           class="form-control @error('termination_available_from_date') is-invalid @enderror"
                           value="{{ old('termination_available_from_date') ?? (isset($model) && $model->termination_available_from_date ? $model->termination_available_from_date->format('Y-m-d') : '') }}">
                    @error('termination_available_from_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-12">
                <div class="mb-3">
                    <label for="termination_notes" class="form-label">Termination Notes</label>
                    <textarea name="termination_notes" id="termination_notes" rows="2"
                              class="form-control @error('termination_notes') is-invalid @enderror">{{ old('termination_notes') ?? (isset($model) ? $model->termination_notes : '') }}</textarea>
                    @error('termination_notes')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Financial Information -->
<div class="card mb-2">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fa fa-pound-sign me-2"></i>
            Financial Details
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="agreed_rent" class="form-label">Agreed Rent *</label>
                    <div class="input-group">
                        <span class="input-group-text">£</span>
                        <input type="number" name="agreed_rent" id="agreed_rent"
                               class="form-control @error('agreed_rent') is-invalid @enderror"
                               value="{{ old('agreed_rent') ?? (isset($model) ? $model->agreed_rent : '') }}" step="0.01" min="0" required>
                    </div>
                    @error('agreed_rent')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="deposit_amount" class="form-label">Deposit Amount *</label>
                    <div class="input-group">
                        <span class="input-group-text">£</span>
                        <input type="number" name="deposit_amount" id="deposit_amount"
                               class="form-control @error('deposit_amount') is-invalid @enderror"
                               value="{{ old('deposit_amount') ?? (isset($model) ? $model->deposit_amount : '') }}" step="0.01" min="0" required>
                    </div>
                    @error('deposit_amount')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</div>

@if(($canManageDiscount ?? false) === true)
<!-- Discount Information -->
<div class="card mb-2">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fa fa-tag me-2"></i>
            Discount
        </h5>
    </div>
    <div class="card-body">
        <div class="discount-layout">
            <div class="discount-type-wrap">
                <label class="form-label d-block">Discount Type</label>
                @php
                    $selectedDiscountType = old('discount_type') ?? (isset($model) ? $model->discount_type : '');
                @endphp
                <div class="discount-type-group" role="group" aria-label="Discount type">
                    <input type="radio" class="discount-type-radio" name="discount_type" id="discount_type_percentage" value="percentage" autocomplete="off"
                        {{ $selectedDiscountType === 'percentage' ? 'checked' : '' }}>
                    <label class="discount-type-option mr-1 mb-1" for="discount_type_percentage">
                        <span class="discount-type-icon"><i class="fa fa-percent"></i></span>
                        <span>Percentage</span>
                    </label>

                    <input type="radio" class="discount-type-radio" name="discount_type" id="discount_type_fixed" value="fixed" autocomplete="off"
                        {{ $selectedDiscountType === 'fixed' ? 'checked' : '' }}>
                    <label class="discount-type-option mb-1" for="discount_type_fixed">
                        <span class="discount-type-icon"><i class="fa fa-gbp"></i></span>
                        <span>Fixed Amount</span>
                    </label>
                </div>
                @error('discount_type')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="discount-value-wrap">
                <label for="discount_value" class="form-label">Discount Value</label>
                <div class="input-group">
                    <span class="input-group-text" id="discount_value_prefix">%</span>
                    <input type="number" name="discount_value" id="discount_value"
                           class="form-control @error('discount_value') is-invalid @enderror"
                           value="{{ old('discount_value') ?? (isset($model) ? $model->discount_value : '') }}"
                           step="0.01" min="0" placeholder="0.00">
                </div>
                <small class="text-muted" id="discount_value_hint">Maximum 100%</small>
                @error('discount_value')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="form-group mt-2 mb-0">
            <label for="discount_notes" class="form-label">Discount Notes</label>
            <textarea name="discount_notes" id="discount_notes" rows="3"
                      class="form-control @error('discount_notes') is-invalid @enderror"
                      placeholder="Optional notes about this discount">{{ old('discount_notes', isset($model) ? $model->discount_notes : '') }}</textarea>
            @error('discount_notes')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>
@endif

<!-- NEW: Insurance Options Section -->
<div class="card mb-2">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fa fa-shield-alt me-2"></i>
            Insurance Options
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-12">
                <div class="mb-3">
                    <label class="form-label">Insurance provided by *</label>
                    <div class="d-flex gap-5">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="using_own_insurance" id="using_own_insurance_client"
                                   value="1" {{ (old('using_own_insurance') ?? (isset($model) ? $model->using_own_insurance : false)) ? 'checked' : '' }}>
                            <label class="form-check-label mr-2" for="using_own_insurance_client">
                                Client's
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="using_own_insurance" id="using_own_insurance_company"
                                   value="0" {{ !(old('using_own_insurance') ?? (isset($model) ? $model->using_own_insurance : false)) ? 'checked' : '' }}>
                            <label class="form-check-label" for="using_own_insurance_company">
                                Company's
                            </label>
                        </div>
                    </div>
                    @error('using_own_insurance')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Company's insurance (provider) -->
        <div id="provider-insurance-section" style="display: none;">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="insurance_provider_id" class="form-label">Insurance Provider *</label>
                        <select name="insurance_provider_id" id="insurance_provider_id" class="form-control @error('insurance_provider_id') is-invalid @enderror">
                            <option value="">Select Insurance Provider</option>
                            @foreach($insuranceProviders as $provider)
                                <option value="{{ $provider->id }}"
                                        data-company-id="{{ $provider->company_id }}"
                                    {{ (old('insurance_provider_id') ?? (isset($model) ? $model->insurance_provider_id : '')) == $provider->id ? 'selected' : '' }}>
                                    {{ $provider->provider_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('insurance_provider_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Client's insurance -->
        <div id="own-insurance-section" style="display: none;">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="own_insurance_provider_name" class="form-label">Provider Name *</label>
                        <input type="text" name="own_insurance_provider_name" id="own_insurance_provider_name"
                               class="form-control @error('own_insurance_provider_name') is-invalid @enderror"
                               value="{{ old('own_insurance_provider_name') ?? (isset($model) ? $model->own_insurance_provider_name : '') }}">
                        @error('own_insurance_provider_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="own_insurance_type" class="form-label">Insurance Type *</label>
                        <select name="own_insurance_type" id="own_insurance_type" class="form-control @error('own_insurance_type') is-invalid @enderror">
                            <option value="">Select Insurance Type</option>
                            <option value="Comprehensive" {{ (old('own_insurance_type') ?? (isset($model) ? $model->own_insurance_type : '')) == 'Comprehensive' ? 'selected' : '' }}>Comprehensive</option>
                            <option value="Third Party" {{ (old('own_insurance_type') ?? (isset($model) ? $model->own_insurance_type : '')) == 'Third Party' ? 'selected' : '' }}>Third Party</option>
                            <option value="Third Party Fire & Theft" {{ (old('own_insurance_type') ?? (isset($model) ? $model->own_insurance_type : '')) == 'Third Party Fire & Theft' ? 'selected' : '' }}>Third Party Fire & Theft</option>
                        </select>
                        @error('own_insurance_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="own_insurance_start_date" class="form-label">Insurance Start Date *</label>
                        <input type="date" name="own_insurance_start_date" id="own_insurance_start_date"
                               class="form-control @error('own_insurance_start_date') is-invalid @enderror"
                               value="{{ old('own_insurance_start_date') ?? (isset($model) ? $model->own_insurance_start_date?->format('Y-m-d') : '') }}">
                        @error('own_insurance_start_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="own_insurance_end_date" class="form-label">Insurance End Date *</label>
                        <input type="date" name="own_insurance_end_date" id="own_insurance_end_date"
                               class="form-control @error('own_insurance_end_date') is-invalid @enderror"
                               value="{{ old('own_insurance_end_date') ?? (isset($model) ? $model->own_insurance_end_date?->format('Y-m-d') : '') }}">
                        @error('own_insurance_end_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="own_insurance_policy_number" class="form-label">Policy Number *</label>
                        <input type="text" name="own_insurance_policy_number" id="own_insurance_policy_number"
                               class="form-control @error('own_insurance_policy_number') is-invalid @enderror"
                               value="{{ old('own_insurance_policy_number') ?? (isset($model) ? $model->own_insurance_policy_number : '') }}">
                        @error('own_insurance_policy_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="own_insurance_proof_document" class="form-label">Proof of Insurance Document <span class="text-muted font-weight-normal">(multiple files)</span></label>
                        <input type="file" name="own_insurance_proof_document[]" id="own_insurance_proof_document"
                               class="form-control @error('own_insurance_proof_document') is-invalid @enderror"
                               accept=".pdf,.jpg,.jpeg,.png"
                               multiple>
                        @if(isset($model) && $model->id && $model->ownInsuranceProofFileNames() !== [])
                            @foreach($model->ownInsuranceProofFileNames() as $proofName)
                                <small class="text-muted d-block mt-1">Current file {{ $loop->iteration }}: <a href="{{ asset('uploads/insurance_documents/' . $proofName) }}" target="_blank">View</a></small>
                            @endforeach
                        @endif
                        <div class="form-text">Accepted formats: PDF, JPG, JPEG, PNG (Max: 2MB per file)</div>
                        @error('own_insurance_proof_document')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @error('own_insurance_proof_document.*')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mileage Information -->
<div class="card mb-2">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fa fa-tachometer-alt me-2"></i>
            Mileage Information
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="mileage_out" class="form-label">Mileage Out</label>
                    <div class="input-group">
                        <input type="number" name="mileage_out" id="mileage_out"
                               class="form-control @error('mileage_out') is-invalid @enderror"
                               value="{{ old('mileage_out') ?? (isset($model) ? $model->mileage_out : '') }}" min="0">
                        <span class="input-group-text">miles</span>
                    </div>
                    @error('mileage_out')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-6">
                <div class="mb-3">
                    <label for="mileage_in" class="form-label">Mileage In</label>
                    <div class="input-group">
                        <input type="number" name="mileage_in" id="mileage_in"
                               class="form-control @error('mileage_in') is-invalid @enderror"
                               value="{{ old('mileage_in') ?? (isset($model) ? $model->mileage_in : '') }}" min="0">
                        <span class="input-group-text">miles</span>
                    </div>
                    @error('mileage_in')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Collection Schedule -->
<div class="card mb-2">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fa fa-calendar-alt me-2"></i>
            Collection Schedule
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="collection_type" class="form-label">Collection Type *</label>
                    <select name="collection_type" id="collection_type" class="form-control @error('collection_type') is-invalid @enderror" required>
                        <option value="">Select Collection Type</option>
                        <option value="weekly" {{ (old('collection_type') ?? (isset($model) ? $model->collection_type : '')) == 'weekly' ? 'selected' : '' }}>Weekly (Every 7 days)</option>
                        <option value="monthly" {{ (old('collection_type') ?? (isset($model) ? $model->collection_type : '')) == 'monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="static" {{ (old('collection_type') ?? (isset($model) ? $model->collection_type : '')) == 'static' ? 'selected' : '' }}>One-time Payment</option>
                    </select>
                    @error('collection_type')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-6">
                <div class="mb-3">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="auto_schedule_collections"
                               id="auto_schedule_collections" value="1"
                            {{ (old('auto_schedule_collections') ?? (isset($model) ? $model->auto_schedule_collections : true)) ? 'checked' : '' }}>
                        <label class="form-check-label" for="auto_schedule_collections">
                            Auto Schedule Collections
                        </label>
                    </div>
                    <small class="text-muted">Automatically create payment schedules based on collection type</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Optional Payment -->
<div class="card mb-2">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fa fa-credit-card me-2"></i>
            Add Payment
        </h5>
    </div>
    <div class="card-body">
        <input type="hidden" name="add_payment" value="0">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="add_payment" id="add_payment" value="1"
                {{ old('add_payment') ? 'checked' : '' }}>
            <label class="form-check-label" for="add_payment">
                Add payment with this agreement
            </label>
        </div>
        <div id="agreement-payment-fields" style="display: none;">
            <div class="row">
                <div class="col-md-4 mb-2">
                    <label for="agreement_payment_method" class="form-label">Payment Method</label>
                    @php
                        $selectedAgreementPaymentMethod = old('agreement_payment_method');
                        $agreementPaymentMethods = ['Bank Transfer', 'Cash', 'Cheque', 'Card Payment', 'Direct Debit'];
                    @endphp
                    <select name="agreement_payment_method" id="agreement_payment_method"
                            class="form-control @error('agreement_payment_method') is-invalid @enderror">
                        <option value="">Select Method</option>
                        @foreach($agreementPaymentMethods as $paymentMethod)
                            <option value="{{ $paymentMethod }}" {{ $selectedAgreementPaymentMethod === $paymentMethod ? 'selected' : '' }}>
                                {{ $paymentMethod }}
                            </option>
                        @endforeach
                    </select>
                    @error('agreement_payment_method')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4 mb-2">
                    <label for="agreement_payment_date" class="form-label">Payment Date</label>
                    <input type="date" name="agreement_payment_date" id="agreement_payment_date"
                           class="form-control @error('agreement_payment_date') is-invalid @enderror"
                           value="{{ old('agreement_payment_date', now()->toDateString()) }}">
                    @error('agreement_payment_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4 mb-2">
                    <label for="agreement_payment_amount" class="form-label">Amount</label>
                    <input type="number" name="agreement_payment_amount" id="agreement_payment_amount"
                           class="form-control @error('agreement_payment_amount') is-invalid @enderror"
                           value="{{ old('agreement_payment_amount') }}" min="0.01" step="0.01" placeholder="0.00">
                    @error('agreement_payment_amount')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 mb-2">
                    <label for="agreement_payment_notes" class="form-label">Payment Notes</label>
                    <textarea name="agreement_payment_notes" id="agreement_payment_notes" rows="2"
                              class="form-control @error('agreement_payment_notes') is-invalid @enderror"
                              placeholder="Optional payment notes">{{ old('agreement_payment_notes') }}</textarea>
                    @error('agreement_payment_notes')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <small class="text-muted">
                Payment will be auto-managed against this driver's active invoices. Any extra amount will remain as driver credit.
            </small>
        </div>
    </div>
</div>

<!-- Additional Information -->
<div class="card mb-2">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fa fa-clipboard me-2"></i>
            Additional Information
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="rent_interval" class="form-label">Rent Interval *</label>
                    <select name="rent_interval" id="rent_interval" class="form-control @error('rent_interval') is-invalid @enderror" required>
                        <option value="">Select Interval</option>
                        <option value="Weekly" {{ (old('rent_interval') ?? (isset($model) ? $model->rent_interval : '')) == 'Weekly' ? 'selected' : '' }}>Weekly</option>
                        <option value="Monthly" {{ (old('rent_interval') ?? (isset($model) ? $model->rent_interval : '')) == 'Monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="Quarterly" {{ (old('rent_interval') ?? (isset($model) ? $model->rent_interval : '')) == 'Quarterly' ? 'selected' : '' }}>Quarterly</option>
                        <option value="Yearly" {{ (old('rent_interval') ?? (isset($model) ? $model->rent_interval : '')) == 'Yearly' ? 'selected' : '' }}>Yearly</option>
                    </select>
                    @error('rent_interval')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label for="condition_report" class="form-label">Condition Report</label>
            <textarea name="condition_report" id="condition_report"
                      class="form-control @error('condition_report') is-invalid @enderror"
                      rows="3">{{ old('condition_report') ?? (isset($model) ? $model->condition_report : '') }}</textarea>
            @error('condition_report')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="notes" class="form-label">Notes</label>
            <textarea name="notes" id="notes"
                      class="form-control @error('notes') is-invalid @enderror"
                      rows="3">{{ old('notes') ?? (isset($model) ? $model->notes : '') }}</textarea>
            @error('notes')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<!-- Form Actions -->
<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary mr-1">
        <i class="fa fa-save me-2"></i>
        {{ isset($model->id) ? 'Update Agreement' : 'Create Agreement' }}
    </button>
    <a href="{{ route($url . 'index') }}" class="btn btn-secondary">
        <i class="fa fa-times me-2"></i>
        Cancel
    </a>
</div>

@push('css')
    <link rel="stylesheet" type="text/css"
          href="{{ asset('app-assets/vendors/css/forms/select/select2.min.css') }}">
    <style>
        .discount-type-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .discount-layout {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 14px;
        }

        .discount-type-wrap {
            flex: 0 1 auto;
            min-width: 360px;
        }

        .discount-value-wrap {
            flex: 0 1 320px;
            min-width: 260px;
            margin-bottom: 4px;
        }

        .discount-type-radio {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .discount-type-option {
            min-width: 180px;
            border: 1px solid #d9d8f3;
            border-radius: 8px;
            background: #fff;
            color: #5f5b92;
            padding: 10px 14px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .discount-type-icon {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            background: #f3f2ff;
            color: #7367f0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
        }

        .discount-type-option:hover {
            border-color: #7367f0;
            box-shadow: 0 2px 10px rgba(115, 103, 240, 0.08);
        }

        .discount-type-radio:checked + .discount-type-option {
            border-color: #7367f0;
            background: #f8f7ff;
            color: #4b4586;
            box-shadow: 0 0 0 2px rgba(115, 103, 240, 0.15);
        }

        @media (max-width: 767.98px) {
            .discount-type-wrap,
            .discount-value-wrap {
                min-width: 100%;
                flex-basis: 100%;
            }
        }
    </style>
@endpush

@push('js')
    <script src="{{ asset('app-assets/vendors/js/forms/select/select2.full.min.js') }}"></script>
    <script>
        // Store all insurance providers with their company IDs
        const allInsuranceProviders = @json($insuranceProviders->map(function($provider) {
            return [
                'id' => $provider->id,
                'company_id' => $provider->company_id,
                'provider_name' => $provider->provider_name
            ];
        }));

        // Filter insurance providers based on selected company
        function filterInsuranceProviders() {
            const companyId = document.getElementById('company_id').value;
            const insuranceProviderSelect = document.getElementById('insurance_provider_id');
            const selectedProviderId = insuranceProviderSelect.value; // Store current selection

            // Clear existing options except the first one
            insuranceProviderSelect.innerHTML = '<option value="">Select Insurance Provider</option>';

            if (companyId) {
                // Filter providers by company_id
                const filteredProviders = allInsuranceProviders.filter(provider => provider.company_id == companyId);

                // Add filtered options
                filteredProviders.forEach(provider => {
                    const option = document.createElement('option');
                    option.value = provider.id;
                    option.textContent = provider.provider_name;
                    option.setAttribute('data-company-id', provider.company_id);

                    // Restore selection if it matches
                    if (provider.id == selectedProviderId) {
                        option.selected = true;
                    }

                    insuranceProviderSelect.appendChild(option);
                });
            }
        }

        // Toggle insurance sections based on Client's / Company's selection
        function toggleInsuranceSections() {
            const usingOwnInsuranceClient = document.getElementById('using_own_insurance_client');
            const usingOwnInsuranceCompany = document.getElementById('using_own_insurance_company');
            const providerSection = document.getElementById('provider-insurance-section');
            const ownSection = document.getElementById('own-insurance-section');

            if (usingOwnInsuranceClient.checked) {
                providerSection.style.display = 'none';
                ownSection.style.display = 'block';
                document.getElementById('insurance_provider_id').value = '';
            } else if (usingOwnInsuranceCompany.checked) {
                providerSection.style.display = 'block';
                ownSection.style.display = 'none';
                clearOwnInsuranceFields();
            }
        }

        function toggleAgreementPaymentFields() {
            const addPaymentCheckbox = document.getElementById('add_payment');
            const paymentFields = document.getElementById('agreement-payment-fields');

            if (!addPaymentCheckbox || !paymentFields) {
                return;
            }

            paymentFields.style.display = addPaymentCheckbox.checked ? 'block' : 'none';
        }

        // Clear own insurance fields
        function clearOwnInsuranceFields() {
            document.getElementById('own_insurance_provider_name').value = '';
            document.getElementById('own_insurance_type').value = '';
            document.getElementById('own_insurance_start_date').value = '';
            document.getElementById('own_insurance_end_date').value = '';
            document.getElementById('own_insurance_policy_number').value = '';
            document.getElementById('own_insurance_proof_document').value = '';
        }

        function updateDiscountInputUI() {
            const percentage = document.getElementById('discount_type_percentage');
            const fixed = document.getElementById('discount_type_fixed');
            const input = document.getElementById('discount_value');
            const prefix = document.getElementById('discount_value_prefix');
            const hint = document.getElementById('discount_value_hint');

            if (!input || !prefix || !hint || !percentage || !fixed) {
                return;
            }

            if (percentage.checked) {
                prefix.textContent = '%';
                input.max = '100';
                hint.textContent = 'Maximum 100%';
            } else if (fixed.checked) {
                prefix.textContent = '£';
                input.removeAttribute('max');
                hint.textContent = 'Enter fixed discount amount';
            } else {
                prefix.textContent = '%';
                input.removeAttribute('max');
                hint.textContent = 'Select a discount type first';
            }
        }

        // Initialize toggle states
        document.addEventListener('DOMContentLoaded', function() {
            toggleInsuranceSections();
            toggleAgreementPaymentFields();

            filterInsuranceProviders();

            document.getElementById('add_payment')?.addEventListener('change', toggleAgreementPaymentFields);
            document.getElementById('using_own_insurance_client').addEventListener('change', toggleInsuranceSections);
            document.getElementById('using_own_insurance_company').addEventListener('change', toggleInsuranceSections);
            document.getElementById('company_id').addEventListener('change', filterInsuranceProviders);
            const discountPercentage = document.getElementById('discount_type_percentage');
            const discountFixed = document.getElementById('discount_type_fixed');
            const discountValue = document.getElementById('discount_value');
            if (discountPercentage && discountFixed && discountValue) {
                updateDiscountInputUI();
                discountPercentage.addEventListener('change', updateDiscountInputUI);
                discountFixed.addEventListener('change', updateDiscountInputUI);
                discountValue.addEventListener('input', function() {
                    if (discountPercentage.checked && this.value !== '' && Number(this.value) > 100) {
                        this.value = '100';
                    }
                });
            }

            if (typeof $ !== 'undefined' && $.fn.select2) {
                $('#car_id, #driver_id').select2({
                    width: '100%',
                    placeholder: 'Search…',
                });

                $('#car_id').on('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const companyId = selectedOption ? selectedOption.getAttribute('data-company-id') : null;

                    if (companyId) {
                        document.getElementById('company_id').value = companyId;
                        filterInsuranceProviders();
                    }
                });
            }
        });

        // Enhanced form validation
        document.addEventListener('DOMContentLoaded', function() {
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            const mileageOutInput = document.getElementById('mileage_out');
            const mileageInInput = document.getElementById('mileage_in');
            const ownInsuranceStartDate = document.getElementById('own_insurance_start_date');
            const ownInsuranceEndDate = document.getElementById('own_insurance_end_date');

            /*function validateDates() {
                const startDate = startDateInput.value;
                const endDate = endDateInput.value;

                if (startDate && endDate && new Date(endDate) <= new Date(startDate)) {
                    alert('End date must be after start date');
                    endDateInput.value = '';
                    return false;
                }
                return true;
            }*/

            function validateInsuranceDates() {
                const clientRadio = document.getElementById('using_own_insurance_client');
                if (!clientRadio || !clientRadio.checked) {
                    return true;
                }

                const startDate = ownInsuranceStartDate.value;
                const endDate = ownInsuranceEndDate.value;

                if (startDate && endDate && new Date(endDate) <= new Date(startDate)) {
                    alert('Insurance end date must be after start date');
                    ownInsuranceEndDate.value = '';
                    return false;
                }
                return true;
            }

            function validateMileage() {
                const mileageOut = parseInt(mileageOutInput.value) || 0;
                const mileageIn = parseInt(mileageInInput.value) || 0;

                if (mileageOut > 0 && mileageIn > 0 && mileageIn < mileageOut) {
                    alert('Mileage in should be greater than mileage out');
                    mileageInInput.value = '';
                    return false;
                }
                return true;
            }

            ownInsuranceStartDate.addEventListener('blur', validateInsuranceDates);
            ownInsuranceEndDate.addEventListener('blur', validateInsuranceDates);
            mileageInInput.addEventListener('change', validateMileage);

            // Set minimum date/time for start (now)
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            startDateInput.setAttribute('min', now.toISOString().slice(0, 16));

            // Auto populate agreed rent in collection amounts
            document.getElementById('agreed_rent').addEventListener('change', function() {
                const agreedRent = this.value;
                const amountInputs = document.querySelectorAll('input[name*="[amount]"]');
                amountInputs.forEach(input => {
                    if (!input.value) {
                        input.value = agreedRent;
                    }
                });
            });
        });

    </script>
@endpush
