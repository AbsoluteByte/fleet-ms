<!-- Basic Information -->
<div class="card mb-2">
    <div class="card-header" style="position: static; width: 100%; z-index: unset;">
        <h5 class="card-title mb-0">
            <i class="fa fa-info-circle me-2"></i>
            Agreement Details
        </h5>
    </div>
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger mb-3">
                <strong>Please fix the following:</strong>
                <ul class="mb-0 mt-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if(! empty($driverProfileIncomplete) && ! empty($missingDriverFields))
            <div class="alert alert-warning mb-3">
                <i class="fa fa-exclamation-triangle me-1"></i>
                <strong>Driver profile incomplete.</strong>
                Complete the following before saving this agreement:
                {{ implode(', ', $missingDriverFields) }}.
                @if(! empty($model->driver_id))
                    <a href="{{ route('drivers.edit', $model->driver_id) }}" class="alert-link">Edit driver profile</a>
                @endif
            </div>
        @endif
        @php
            $reservationPrefill = $reservationPrefill ?? null;
            $linkedReservationId = old('reservation_id', $reservationPrefill['reservation_id'] ?? null);
        @endphp
        @if($linkedReservationId)
            <input type="hidden" name="reservation_id" value="{{ $linkedReservationId }}">
            <div class="alert alert-info mb-3">
                <i class="fa fa-info-circle me-1"></i>
                Creating agreement from reservation #{{ $linkedReservationId }}. Review the pre-filled details below, then save.
            </div>
        @endif
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="car_id" class="form-label">Vehicle *</label>
                    <select name="car_id" id="car_id" class="form-control select-search @error('car_id') is-invalid @enderror" required>
                        <option value="">Select Vehicle</option>
                        @foreach($cars as $car)
                            @php
                                $activeInsurance = $car->currentActiveInsurance();
                            @endphp
                            <option value="{{ $car->id }}"
                                    data-company-id="{{ $car->company_id }}"
                                    data-has-active-insurance="{{ $car->isInsuranceCurrentlyActive() ? '1' : '0' }}"
                                    data-insurance-provider-name="{{ optional($activeInsurance?->insuranceProvider)->provider_name }}"
                                    data-insurance-policy-number="{{ optional($activeInsurance?->insuranceProvider)->policy_number }}"
                                    data-insurance-expiry="{{ optional($activeInsurance?->expiry_date)->format('d M, Y') }}"
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
                    <select name="company_id" id="company_id" class="form-control select-search @error('company_id') is-invalid @enderror" required>
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
                    <select name="driver_id" id="driver_id" class="form-control select-search @error('driver_id') is-invalid @enderror" required>
                        <option value="">Select Driver</option>
                        @include('backend.drivers._select_options', [
                            'drivers' => $drivers,
                            'selectedId' => old('driver_id') ?? ($model->driver_id ?? null),
                            'showInactiveLabel' => true,
                        ])
                    </select>
                    @error('driver_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div id="driver-active-agreement-warning" class="alert alert-warning mt-2 mb-0" style="display: none;" role="alert">
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>Warning:</strong> This driver already has an active agreement.
                        <ul id="driver-active-agreement-list" class="mb-0 mt-2"></ul>
                        <p class="mb-0 mt-2 small">You can still create this agreement if that is intended.</p>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="paying_company_name" class="form-label">Paying Company Name</label>
                    <input type="text"
                           name="paying_company_name"
                           id="paying_company_name"
                           class="form-control @error('paying_company_name') is-invalid @enderror"
                           value="{{ old('paying_company_name', $model->paying_company_name ?? '') }}"
                           maxlength="255"
                           placeholder="e.g. ABC Ltd">
                    <small class="form-text text-muted">
                        Optional. Company that pays when the agreement is in the client's name.
                    </small>
                    @error('paying_company_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="payment_bank_account_id" class="form-label">Driver payment bank account</label>
                    @include('backend.payments.partials.bank-account-select', [
                        'name' => 'payment_bank_account_id',
                        'id' => 'payment_bank_account_id',
                        'bankAccounts' => $bankAccounts ?? collect(),
                        'selected' => old('payment_bank_account_id', $model->payment_bank_account_id ?? null),
                        'wrapperClass' => '',
                        'errorKey' => 'payment_bank_account_id',
                    ])
                    <small class="form-text text-muted">Bank account the driver should pay rent into.</small>
                </div>
            </div>

            <div class="col-md-6">
                <div class="mb-3">
                    <label for="status_id" class="form-label">Status *</label>
                    <select name="status_id" id="status_id" class="form-control @error('status_id') is-invalid @enderror" required>
                        <option value="">Select Status</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status->id }}"
                                    data-status-name="{{ $status->name }}"
                                {{ (old('status_id') ?? (isset($model) ? $model->status_id : '')) == $status->id ? 'selected' : '' }}>
                                {{ $status->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('status_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-6" id="agreement-closing-section" style="display: none;">
                <div class="mb-3">
                    <label for="closing_date" class="form-label">Closing date &amp; time <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="closing_date" id="closing_date"
                           class="form-control @error('closing_date') is-invalid @enderror"
                           value="{{ old('closing_date') ?? (isset($model) && $model->closing_date ? $model->closing_date->format('Y-m-d\TH:i') : '') }}">
                    @error('closing_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-6" id="agreement-refund-details-section" style="display: none;">
                <div class="mb-3">
                    <label for="refund_person_name" class="form-label">Refund Person Name</label>
                    <input type="text" name="refund_person_name" id="refund_person_name"
                           class="form-control @error('refund_person_name') is-invalid @enderror"
                           value="{{ old('refund_person_name', isset($model) ? ($model->refund_person_name ?? $model->driver?->full_name ?? '') : '') }}">
                    @error('refund_person_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-6" id="agreement-refund-sort-code-section" style="display: none;">
                <div class="mb-3">
                    <label for="refund_sort_code" class="form-label">Sort Code</label>
                    <input type="text" name="refund_sort_code" id="refund_sort_code"
                           class="form-control @error('refund_sort_code') is-invalid @enderror"
                           value="{{ old('refund_sort_code', isset($model) ? ($model->refund_sort_code ?? '') : '') }}">
                    @error('refund_sort_code')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-6" id="agreement-refund-account-section" style="display: none;">
                <div class="mb-3">
                    <label for="refund_account_number" class="form-label">Account Number</label>
                    <input type="text" name="refund_account_number" id="refund_account_number"
                           class="form-control @error('refund_account_number') is-invalid @enderror"
                           value="{{ old('refund_account_number', isset($model) ? ($model->refund_account_number ?? '') : '') }}">
                    @error('refund_account_number')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-6" id="parent-agreement-section" style="display: none;">
                <div class="mb-3">
                    <label for="parent_agreement_id" class="form-label">Original agreement *</label>
                    <select name="parent_agreement_id" id="parent_agreement_id"
                            class="form-control select-search @error('parent_agreement_id') is-invalid @enderror">
                        <option value="">Select original agreement</option>
                    </select>
                    <small class="text-muted d-block mt-1">Rent and deposit remain on the original agreement. Select the driver's active hire agreement.</small>
                    @error('parent_agreement_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-6" id="swap-original-agreement-section" style="display: none;">
                <div class="mb-3">
                    <label for="upgraded_from_agreement_id" class="form-label">Original agreement *</label>
                    <select name="upgraded_from_agreement_id" id="upgraded_from_agreement_id"
                            class="form-control select-search @error('upgraded_from_agreement_id') is-invalid @enderror">
                        <option value="">Select original agreement</option>
                    </select>
                    <small class="text-muted d-block mt-1">Original will be terminated. Deposit carries over; invoices follow the original billing dates at the new rent.</small>
                    @error('upgraded_from_agreement_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-4">
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

            <div class="col-md-4">
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

            <div class="col-md-4">
                <div class="mb-3">
                    <label for="mutual_detail_slip_document" class="form-label">Mutual Detail Slip <span class="text-muted font-weight-normal">(multiple files)</span></label>
                    <input type="file" name="mutual_detail_slip_document[]" id="mutual_detail_slip_document"
                           class="form-control @error('mutual_detail_slip_document') is-invalid @enderror"
                           accept=".pdf,.jpg,.jpeg,.png"
                           multiple>
                    @if(isset($model) && $model->id && $model->mutualDetailSlipFileNames() !== [])
                        @foreach($model->mutualDetailSlipFileNames() as $slipName)
                            <small class="text-muted d-block mt-1">Current file {{ $loop->iteration }}:
                                <x-document-actions
                                    :view-url="asset('uploads/agreement_documents/' . $slipName)"
                                    style="list-item"
                                />
                            </small>
                        @endforeach
                    @endif
                    <div class="form-text">Accepted formats: PDF, JPG, JPEG, PNG (Max: 5MB per file)</div>
                    @error('mutual_detail_slip_document')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    @error('mutual_detail_slip_document.*')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
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

            @if(isset($model) && $model->id)
                @php
                    $deductionsLocked = $model->depositRefund !== null;
                    $deductionRows = old('deductions');
                    if ($deductionRows === null) {
                        $deductionRows = $model->deductions->map(fn ($deduction) => [
                            'amount' => $deduction->amount,
                            'notes' => $deduction->notes,
                        ])->values()->all();
                    }
                @endphp
                <div class="col-12">
                    <input type="hidden" name="deductions_present" value="1">
                    <div class="agreement-deductions-panel mb-3">
                        <div class="agreement-deductions-panel__header">
                            <div class="agreement-deductions-panel__heading">
                                <span class="agreement-deductions-panel__icon">
                                    <i class="fa fa-minus-circle"></i>
                                </span>
                                <div>
                                    <h6 class="agreement-deductions-panel__title">Deposit Deductions</h6>
                                    <p class="agreement-deductions-panel__subtitle">Add charges that will be deducted when the deposit is settled.</p>
                                </div>
                            </div>
                            @unless($deductionsLocked)
                                <button type="button" class="btn btn-primary agreement-deductions-panel__add" id="add-deduction-row">
                                    <i class="fa fa-plus me-1"></i> Add deduction
                                </button>
                            @endunless
                        </div>

                        <div class="agreement-deductions-panel__body">
                            @if($deductionsLocked)
                                <div class="alert alert-warning py-2 mb-3">
                                    <i class="fa fa-lock me-1"></i>
                                    Deductions are locked because a deposit settlement has been recorded.
                                </div>
                            @endif

                            <div id="deductions-repeater">
                                @foreach($deductionRows as $index => $deduction)
                                    <div class="deduction-row mb-2">
                                        <div class="row g-2 align-items-end">
                                            <div class="col-md-3">
                                                <label class="form-label">Amount</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">£</span>
                                                    <input type="number" class="form-control"
                                                           name="deductions[{{ $index }}][amount]"
                                                           value="{{ $deduction['amount'] ?? '' }}"
                                                           min="0.01" step="0.01" required
                                                           @readonly($deductionsLocked)>
                                                </div>
                                            </div>
                                            <div class="{{ $deductionsLocked ? 'col-md-9' : 'col-md-8' }}">
                                                <label class="form-label">Notes</label>
                                                <input type="text" class="form-control"
                                                       name="deductions[{{ $index }}][notes]"
                                                       value="{{ $deduction['notes'] ?? '' }}"
                                                       placeholder="Reason for deduction"
                                                       maxlength="2000"
                                                       @readonly($deductionsLocked)>
                                            </div>
                                            @unless($deductionsLocked)
                                                <div class="col-md-1">
                                                    <button type="button" class="btn btn-outline-danger remove-deduction-row" title="Remove deduction">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </div>
                                            @endunless
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @error('deductions')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                            @error('deductions.*.amount')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                @php
                    $damageTypes = \App\Models\AgreementAdditionalCharge::types();
                    $additionalChargeRows = old('additional_charges');
                    if ($additionalChargeRows === null) {
                        $additionalChargeRows = $model->additionalCharges->map(fn ($charge) => [
                            'id' => $charge->id,
                            'type' => $charge->type,
                            'amount' => $charge->amount,
                            'notes' => $charge->notes,
                            'locked' => $charge->invoice_id !== null,
                        ])->values()->all();
                    } else {
                        $existingChargeIds = $model->additionalCharges->keyBy('id');
                        $additionalChargeRows = collect($additionalChargeRows)->map(function ($charge) use ($existingChargeIds) {
                            $chargeId = $charge['id'] ?? null;
                            $charge['locked'] = $chargeId && $existingChargeIds->has($chargeId)
                                && $existingChargeIds->get($chargeId)->invoice_id !== null;

                            return $charge;
                        })->values()->all();
                    }
                @endphp
                <div class="col-12">
                    <input type="hidden" name="additional_charges_present" value="1">
                    <div class="agreement-deductions-panel mb-3">
                        <div class="agreement-deductions-panel__header">
                            <div class="agreement-deductions-panel__heading">
                                <span class="agreement-deductions-panel__icon">
                                    <i class="fa fa-plus-circle"></i>
                                </span>
                                <div>
                                    <h6 class="agreement-deductions-panel__title">Damages</h6>
                                    <p class="agreement-deductions-panel__subtitle">Add vehicle-related costs during the hire period. Each damage entry creates an invoice for the driver.</p>
                                </div>
                            </div>
                            <button type="button" class="btn btn-primary agreement-deductions-panel__add" id="add-additional-charge-row">
                                <i class="fa fa-plus me-1"></i> Add damage
                            </button>
                        </div>

                        <div class="agreement-deductions-panel__body">
                            <div id="additional-charges-repeater">
                                @foreach($additionalChargeRows as $index => $charge)
                                    @php
                                        $chargeLocked = ! empty($charge['locked']);
                                    @endphp
                                    <div class="deduction-row mb-2 additional-charge-row">
                                        <div class="row g-2 align-items-end">
                                            @if(! empty($charge['id']))
                                                <input type="hidden" name="additional_charges[{{ $index }}][id]" value="{{ $charge['id'] }}">
                                            @endif
                                            <div class="col-md-3">
                                                <label class="form-label">Type</label>
                                                <select class="form-control"
                                                        name="additional_charges[{{ $index }}][type]"
                                                        required
                                                        @disabled($chargeLocked)>
                                                    @foreach($damageTypes as $typeValue => $typeLabel)
                                                        <option value="{{ $typeValue }}"
                                                            @selected(($charge['type'] ?? \App\Models\AgreementAdditionalCharge::TYPE_MISCELLANEOUS_CHARGES) === $typeValue)>
                                                            {{ $typeLabel }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @if($chargeLocked)
                                                    <input type="hidden" name="additional_charges[{{ $index }}][type]" value="{{ $charge['type'] ?? \App\Models\AgreementAdditionalCharge::TYPE_MISCELLANEOUS_CHARGES }}">
                                                @endif
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Amount</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">£</span>
                                                    <input type="number" class="form-control"
                                                           name="additional_charges[{{ $index }}][amount]"
                                                           value="{{ $charge['amount'] ?? '' }}"
                                                           min="0.01" step="0.01" required
                                                           @readonly($chargeLocked)>
                                                </div>
                                            </div>
                                            <div class="{{ $chargeLocked ? 'col-md-7' : 'col-md-6' }}">
                                                <label class="form-label">Notes</label>
                                                <input type="text" class="form-control"
                                                       name="additional_charges[{{ $index }}][notes]"
                                                       value="{{ $charge['notes'] ?? '' }}"
                                                       placeholder="Reason for damage (e.g. tyres, body repair)"
                                                       maxlength="2000"
                                                       @readonly($chargeLocked)>
                                            </div>
                                            @unless($chargeLocked)
                                                <div class="col-md-1">
                                                    <button type="button" class="btn btn-outline-danger remove-additional-charge-row" title="Remove damage">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </div>
                                            @endunless
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @error('additional_charges')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                            @error('additional_charges.*.amount')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                            @error('additional_charges.*.type')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Financial Information -->
<div class="card mb-2" id="agreement-financial-section">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fa fa-pound-sign me-2"></i>
            Financial Details
        </h5>
    </div>
    <div class="card-body">
        <input type="hidden" name="auto_schedule_collections" value="0">
        <div class="row">
            <div class="col-md-2">
                <div class="mb-1">
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
            <div class="col-md-2">
                <div class="mb-1">
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
            <div class="col-md-3">
                <div class="mb-1">
                    <label for="collection_type" class="form-label">Collection Type *</label>
                    <select name="collection_type" id="collection_type" class="form-control @error('collection_type') is-invalid @enderror" required>
                        <option value="">Select Collection Type</option>
                        <option value="weekly" {{ (old('collection_type') ?? (isset($model) ? $model->collection_type : '')) == 'weekly' ? 'selected' : '' }}>Weekly</option>
                        <option value="monthly" {{ (old('collection_type') ?? (isset($model) ? $model->collection_type : '')) == 'monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="static" {{ (old('collection_type') ?? (isset($model) ? $model->collection_type : '')) == 'static' ? 'selected' : '' }}>One-time</option>
                    </select>
                    @error('collection_type')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-1">
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
            <div class="col-md-2">
                <div class="mb-1">
                    <label for="billing_anchor_date" class="form-label">Regular rent due from</label>
                    <input type="date" name="billing_anchor_date" id="billing_anchor_date"
                           class="form-control @error('billing_anchor_date') is-invalid @enderror"
                           value="{{ old('billing_anchor_date') ?? (isset($model) && $model->billing_anchor_date ? $model->billing_anchor_date->format('Y-m-d') : '') }}">
                    @error('billing_anchor_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-12">
                <small class="form-text text-muted mb-1">
                    Optional. e.g. pickup Friday, regular rent from Monday; or pickup on the 25th, regular rent from the 5th each month.
                    Recurring rent uses the rent interval above; collections also start from this date when auto-scheduled.
                </small>
                <div id="billing_anchor_preview" class="form-text text-primary" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

@if(($canManageDiscount ?? false) === true)
<!-- Discount Information -->
<div class="card mb-2" id="agreement-discount-section">
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
                    $oneTimeDiscountEnabled = (bool) old(
                        'discount_is_one_time',
                        isset($model) ? $model->discount_is_one_time : false
                    );
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

        <div class="discount-mode-wrap mt-2">
            <input type="hidden" name="discount_is_one_time" value="0">
            <input type="checkbox" class="discount-mode-checkbox" name="discount_is_one_time"
                   id="discount_is_one_time" value="1" autocomplete="off"
                   {{ $oneTimeDiscountEnabled ? 'checked' : '' }}>
            <label for="discount_is_one_time"
                   class="discount-mode-toggle {{ $oneTimeDiscountEnabled ? 'is-active' : '' }}"
                   id="discount-one-time-toggle">
                <span class="discount-mode-toggle__icon"><i class="fa fa-bolt"></i></span>
                <span class="discount-mode-toggle__content">
                    <strong>One-time discount</strong>
                    <small id="discount-one-time-help">
                        {{ $oneTimeDiscountEnabled ? 'Applies only to the next rent invoice' : 'Off — discount repeats on rent invoices' }}
                    </small>
                </span>
                <span class="discount-mode-toggle__state" id="discount-one-time-state">
                    {{ $oneTimeDiscountEnabled ? 'Enabled' : 'Off' }}
                </span>
            </label>
            @error('discount_is_one_time')
            <div class="text-danger">{{ $message }}</div>
            @enderror
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

        <!-- Company's insurance (read-only from car record) -->
        <div id="vehicle-insurance-section" style="display: none;">
            <div class="row">
                <div class="col-12">
                    <div class="mb-0">
                        <div id="vehicle-insurance-empty" class="vehicle-insurance-panel vehicle-insurance-panel--empty" style="display: none;">
                            <div class="vehicle-insurance-panel__icon vehicle-insurance-panel__icon--muted">
                                <i class="fa fa-car"></i>
                            </div>
                            <div>
                                <div class="vehicle-insurance-panel__title">No vehicle selected</div>
                                <div class="vehicle-insurance-panel__subtitle">Choose a vehicle above to view its current insurance details.</div>
                            </div>
                        </div>

                        <div id="vehicle-insurance-active" class="vehicle-insurance-panel vehicle-insurance-panel--active" style="display: none;">
                            <div class="vehicle-insurance-panel__header">
                                <div class="vehicle-insurance-panel__heading">
                                    <div class="vehicle-insurance-panel__icon vehicle-insurance-panel__icon--active">
                                        <i class="fa fa-shield-alt"></i>
                                    </div>
                                    <div>
                                        <div class="vehicle-insurance-panel__title">Active vehicle insurance</div>
                                        <div class="vehicle-insurance-panel__subtitle">Read-only — synced from the car record</div>
                                    </div>
                                </div>
                                <span class="vehicle-insurance-status-badge vehicle-insurance-status-badge--active">
                                    <i class="fa fa-check-circle"></i> Active
                                </span>
                            </div>
                            <div class="vehicle-insurance-details">
                                <div class="vehicle-insurance-detail">
                                    <div class="vehicle-insurance-detail__label">
                                        <i class="fa fa-building"></i> Insurance provider
                                    </div>
                                    <div class="vehicle-insurance-detail__value" id="vehicle-insurance-provider-name">—</div>
                                </div>
                                <div class="vehicle-insurance-detail">
                                    <div class="vehicle-insurance-detail__label">
                                        <i class="fa fa-file-alt"></i> Policy number
                                    </div>
                                    <div class="vehicle-insurance-detail__value vehicle-insurance-detail__value--mono" id="vehicle-insurance-policy-number">—</div>
                                </div>
                                <div class="vehicle-insurance-detail">
                                    <div class="vehicle-insurance-detail__label">
                                        <i class="fa fa-calendar-alt"></i> Expiry date
                                    </div>
                                    <div class="vehicle-insurance-detail__value" id="vehicle-insurance-expiry">—</div>
                                </div>
                            </div>
                        </div>

                        <div id="vehicle-insurance-inactive" class="vehicle-insurance-panel vehicle-insurance-panel--inactive" style="display: none;">
                            <div class="vehicle-insurance-panel__header">
                                <div class="vehicle-insurance-panel__heading">
                                    <div class="vehicle-insurance-panel__icon vehicle-insurance-panel__icon--warning">
                                        <i class="fa fa-exclamation-triangle"></i>
                                    </div>
                                    <div>
                                        <div class="vehicle-insurance-panel__title">No active insurance on this vehicle</div>
                                        <div class="vehicle-insurance-panel__subtitle">This car can still be used on the agreement, but it is not currently insured on the fleet policy.</div>
                                    </div>
                                </div>
                                <span class="vehicle-insurance-status-badge vehicle-insurance-status-badge--inactive">
                                    Not active
                                </span>
                            </div>
                        </div>
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
                                <small class="text-muted d-block mt-1">Current file {{ $loop->iteration }}:
                                    <x-document-actions
                                        :view-url="asset('uploads/insurance_documents/' . $proofName)"
                                        style="list-item"
                                    />
                                </small>
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

<!-- Optional Payment -->
@php
    $agreementPaymentMethods = ['Bank Transfer', 'Cash', 'Cheque', 'Card Payment', 'Direct Debit'];
    $reservationPrefill = $reservationPrefill ?? null;
    $defaultPaymentRows = [
        ['payment_method' => '', 'bank_account_id' => '', 'payment_date' => now()->toDateString(), 'amount' => '', 'notes' => ''],
    ];
    if ($reservationPrefill && ($reservationPrefill['add_payment'] ?? false) && ! old('agreement_payments')) {
        $defaultPaymentRows = $reservationPrefill['agreement_payment_rows'] ?? [[
            'payment_method' => $reservationPrefill['payment_method'] ?? '',
            'bank_account_id' => $reservationPrefill['bank_account_id'] ?? '',
            'payment_date' => $reservationPrefill['payment_date'] ?? now()->toDateString(),
            'amount' => $reservationPrefill['amount_paid'],
            'notes' => 'Payment from reservation #'.$reservationPrefill['reservation_id'],
        ]];
    }
    $agreementPaymentRows = old('agreement_payments', $defaultPaymentRows);
    $agreementPaymentRows = is_array($agreementPaymentRows) && $agreementPaymentRows !== [] ? array_values($agreementPaymentRows) : [
        ['payment_method' => '', 'bank_account_id' => '', 'payment_date' => now()->toDateString(), 'amount' => '', 'notes' => ''],
    ];
    $agreementPaymentAllowed = $agreementPaymentAllowed ?? true;
    $agreementPaymentLimit = $agreementPaymentLimit ?? null;
@endphp
<div class="card mb-2" id="agreement-payment-section">
    <div class="card-header d-flex justify-content-between align-items-center" style="position: static; width: 100%; z-index: unset; border-bottom: 0 !important; padding-bottom: 0 !important;">
        <h5 class="card-title mb-0">
            <i class="fa fa-credit-card me-2"></i>
            Add Payment
        </h5>
        <div class="form-check mb-0">
            <input type="hidden" name="add_payment" value="0">
            <input class="form-check-input" type="checkbox" name="add_payment" id="add_payment" value="1"
                {{ old('add_payment', $reservationPrefill['add_payment'] ?? false) ? 'checked' : '' }}
                {{ $agreementPaymentAllowed ? '' : 'disabled' }}>
            <label class="form-check-label" for="add_payment">
                Add payment with this agreement
            </label>
        </div>
    </div>
    <div class="card-body">
        @if(! $agreementPaymentAllowed)
            <div class="alert alert-info mb-0">
                Payments can only be added when this agreement has an unpaid invoice balance.
            </div>
        @endif
        <div id="agreement-payment-fields"
             data-edit-mode="{{ isset($model->id) ? '1' : '0' }}"
             data-server-limit="{{ $agreementPaymentLimit !== null ? number_format((float) $agreementPaymentLimit, 2, '.', '') : '' }}"
             style="display: none;">
            <div class="alert alert-info py-2" id="agreement-payment-limit-message"></div>
            <div id="agreement-payment-rows">
                @foreach($agreementPaymentRows as $paymentIndex => $paymentRow)
                    <div class="agreement-payment-row border rounded p-2 mb-2" data-payment-row>
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                                <select name="agreement_payments[{{ $paymentIndex }}][payment_method]"
                                        class="form-control @error('agreement_payments.'.$paymentIndex.'.payment_method') is-invalid @enderror"
                                        data-payment-method>
                                    <option value="">Select Method</option>
                                    @foreach($agreementPaymentMethods as $paymentMethod)
                                        <option value="{{ $paymentMethod }}" {{ ($paymentRow['payment_method'] ?? '') === $paymentMethod ? 'selected' : '' }}>
                                            {{ $paymentMethod }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('agreement_payments.'.$paymentIndex.'.payment_method')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 mb-2">
                                @include('backend.payments.partials.bank-account-select', [
                                    'bankAccounts' => $bankAccounts ?? collect(),
                                    'selected' => old('agreement_payments.'.$paymentIndex.'.bank_account_id', $paymentRow['bank_account_id'] ?? null),
                                    'name' => 'agreement_payments['.$paymentIndex.'][bank_account_id]',
                                    'id' => 'agreement_payment_bank_account_'.$paymentIndex,
                                    'errorKey' => 'agreement_payments.'.$paymentIndex.'.bank_account_id',
                                    'wrapperClass' => 'bank-account-field d-none',
                                ])
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" name="agreement_payments[{{ $paymentIndex }}][payment_date]"
                                       class="form-control @error('agreement_payments.'.$paymentIndex.'.payment_date') is-invalid @enderror"
                                       value="{{ $paymentRow['payment_date'] ?? now()->toDateString() }}"
                                       data-payment-date>
                                @error('agreement_payments.'.$paymentIndex.'.payment_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="form-label">Amount <span class="text-danger">*</span></label>
                                <input type="number" name="agreement_payments[{{ $paymentIndex }}][amount]"
                                       class="form-control agreement-payment-amount @error('agreement_payments.'.$paymentIndex.'.amount') is-invalid @enderror"
                                       value="{{ $paymentRow['amount'] ?? '' }}" min="0.01" step="0.01" placeholder="0.00"
                                       data-payment-amount>
                                @error('agreement_payments.'.$paymentIndex.'.amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3 mb-2 d-flex align-items-end">
                                <button type="button" class="btn btn-outline-danger btn-sm agreement-payment-remove" data-remove-payment>
                                    Remove
                                </button>
                            </div>
                            <div class="col-12 mb-2">
                                <label class="form-label">Payment Notes</label>
                                <textarea name="agreement_payments[{{ $paymentIndex }}][notes]" rows="2"
                                          class="form-control @error('agreement_payments.'.$paymentIndex.'.notes') is-invalid @enderror"
                                          placeholder="Optional payment notes"
                                          data-payment-notes>{{ $paymentRow['notes'] ?? '' }}</textarea>
                                @error('agreement_payments.'.$paymentIndex.'.notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm" id="agreement-payment-add-more">
                <i class="fa fa-plus"></i> Add More
            </button>
            <small class="text-muted">
                Payments from this form will only be applied to this agreement's unpaid rent/deposit invoices.
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
    <style>
        .agreement-deductions-panel {
            overflow: hidden;
            background: #fff;
            border: 1px solid #ebe9f1;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(34, 41, 47, 0.05);
        }

        .agreement-deductions-panel__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 16px 18px;
            background: rgba(115, 103, 240, 0.06);
            border-bottom: 1px solid #ebe9f1;
        }

        .agreement-deductions-panel__heading {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .agreement-deductions-panel__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            flex: 0 0 38px;
            border-radius: 9px;
            color: #7367f0;
            background: rgba(115, 103, 240, 0.13);
            font-size: 16px;
        }

        .agreement-deductions-panel__title {
            margin: 0;
            color: #5e5873;
            font-size: 15px;
            font-weight: 600;
        }

        .agreement-deductions-panel__subtitle {
            margin: 3px 0 0;
            color: #82868b;
            font-size: 12px;
            line-height: 1.4;
        }

        .agreement-deductions-panel__add {
            flex-shrink: 0;
            padding: 0.45rem 0.85rem;
            font-size: 12px;
        }

        .agreement-deductions-panel__body {
            padding: 16px 18px;
        }

        .agreement-deductions-panel .deduction-row {
            padding: 12px;
            background: #fafafa;
            border: 1px solid #ebe9f1;
            border-radius: 8px;
        }

        .agreement-deductions-panel .deduction-row:last-child {
            margin-bottom: 0 !important;
        }

        .agreement-deductions-panel .deduction-row .form-label {
            margin-bottom: 5px;
            color: #5e5873;
            font-size: 12px;
            font-weight: 500;
        }

        .agreement-deductions-panel .remove-deduction-row {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 38px;
            padding: 0.5rem;
        }

        @media (max-width: 767.98px) {
            .agreement-deductions-panel__header {
                align-items: flex-start;
                flex-direction: column;
            }

            .agreement-deductions-panel__add {
                width: 100%;
            }
        }

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

        .discount-mode-checkbox {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .discount-mode-toggle {
            display: flex;
            align-items: center;
            gap: 12px;
            max-width: 520px;
            margin: 0;
            padding: 13px 15px;
            border: 1px solid #d9d8f3;
            border-radius: 8px;
            background: #fff;
            color: #5e5873;
            cursor: pointer;
            transition: border-color .15s ease, background-color .15s ease, box-shadow .15s ease;
        }

        .discount-mode-toggle:hover {
            border-color: #7367f0;
            box-shadow: 0 2px 10px rgba(115, 103, 240, .08);
        }

        .discount-mode-toggle.is-active {
            border-color: #7367f0;
            background: #f8f7ff;
            box-shadow: 0 2px 10px rgba(115, 103, 240, .12);
        }

        .discount-mode-toggle__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            flex: 0 0 34px;
            border-radius: 50%;
            background: rgba(115, 103, 240, .12);
            color: #7367f0;
        }

        .discount-mode-toggle__content {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            min-width: 0;
        }

        .discount-mode-toggle__content small {
            color: #82808f;
            margin-top: 2px;
        }

        .discount-mode-toggle__state {
            padding: 4px 9px;
            border-radius: 999px;
            background: #ebe9f1;
            color: #6e6b7b;
            font-size: 11px;
            font-weight: 600;
        }

        .discount-mode-toggle.is-active .discount-mode-toggle__state {
            background: #7367f0;
            color: #fff;
        }

        @media (max-width: 767.98px) {
            .discount-type-wrap,
            .discount-value-wrap {
                min-width: 100%;
                flex-basis: 100%;
            }
        }

        .vehicle-insurance-panel {
            border: 1px solid #e4e6f1;
            border-radius: 10px;
            background: #fff;
            padding: 16px 18px;
            margin-top: 4px;
        }

        .vehicle-insurance-panel--empty {
            display: flex;
            align-items: center;
            gap: 14px;
            background: #fafbfc;
            border-style: dashed;
        }

        .vehicle-insurance-panel--active {
            border-color: #cfe8dc;
            background: linear-gradient(180deg, #f8fdfb 0%, #ffffff 100%);
            box-shadow: 0 2px 12px rgba(40, 167, 69, 0.06);
        }

        .vehicle-insurance-panel--inactive {
            border-color: #f5d9a8;
            background: linear-gradient(180deg, #fffaf2 0%, #ffffff 100%);
            box-shadow: 0 2px 12px rgba(255, 159, 67, 0.08);
        }

        .vehicle-insurance-panel__header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .vehicle-insurance-panel--inactive .vehicle-insurance-panel__header {
            margin-bottom: 0;
        }

        .vehicle-insurance-panel__heading {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            min-width: 0;
        }

        .vehicle-insurance-panel__icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 16px;
        }

        .vehicle-insurance-panel__icon--active {
            background: #e8f7ef;
            color: #28a745;
        }

        .vehicle-insurance-panel__icon--warning {
            background: #fff1dd;
            color: #ff9f43;
        }

        .vehicle-insurance-panel__icon--muted {
            background: #f1f2f6;
            color: #8a8fa3;
        }

        .vehicle-insurance-panel__title {
            font-size: 15px;
            font-weight: 600;
            color: #4b4b5a;
            line-height: 1.3;
        }

        .vehicle-insurance-panel__subtitle {
            font-size: 12px;
            color: #8a8fa3;
            margin-top: 2px;
            line-height: 1.45;
        }

        .vehicle-insurance-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.02em;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .vehicle-insurance-status-badge--active {
            background: #e8f7ef;
            color: #1f8f4e;
        }

        .vehicle-insurance-status-badge--inactive {
            background: #fff1dd;
            color: #c77700;
        }

        .vehicle-insurance-details {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .vehicle-insurance-detail {
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid #e8ebf3;
            border-radius: 8px;
            padding: 12px 14px;
            min-width: 0;
        }

        .vehicle-insurance-detail__label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #8a8fa3;
            margin-bottom: 6px;
        }

        .vehicle-insurance-detail__label i {
            margin-right: 4px;
            opacity: 0.85;
        }

        .vehicle-insurance-detail__value {
            font-size: 14px;
            font-weight: 600;
            color: #3f3f4d;
            line-height: 1.4;
            word-break: break-word;
        }

        .vehicle-insurance-detail__value--mono {
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 13px;
            letter-spacing: 0.02em;
        }

        @media (max-width: 991.98px) {
            .vehicle-insurance-details {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 767.98px) {
            .vehicle-insurance-panel__header {
                flex-direction: column;
            }

            .vehicle-insurance-status-badge {
                align-self: flex-start;
            }
        }
    </style>
@endpush

@push('js')
    <script src="{{ asset('app-assets/js/scripts/fleetiq-validate-agreement.js') }}?v=20260624"></script>
    <script>
        const replacementVehicleStatusId = @json($replacementVehicleStatusId ?? null);
        const swapStatusId = @json($swapStatusId ?? null);
        const originalAgreements = @json($originalAgreements ?? []);
        const driversActiveAgreements = @json($driversActiveAgreements ?? []);
        const isAgreementCreate = @json(! isset($model) || ! $model->id);
        const selectedParentAgreementId = @json(old('parent_agreement_id') ?? (isset($model) ? $model->parent_agreement_id : null));
        const selectedSwapOriginalAgreementId = @json(old('upgraded_from_agreement_id') ?? (isset($model) ? $model->upgraded_from_agreement_id : null));
        const isExistingSwapAgreement = @json(isset($model) && $model->id && $model->upgraded_from_agreement_id);

        const oneTimeDiscountCheckbox = document.getElementById('discount_is_one_time');
        const oneTimeDiscountToggle = document.getElementById('discount-one-time-toggle');
        const oneTimeDiscountState = document.getElementById('discount-one-time-state');
        const oneTimeDiscountHelp = document.getElementById('discount-one-time-help');

        function syncOneTimeDiscountToggle() {
            if (! oneTimeDiscountCheckbox || ! oneTimeDiscountToggle) {
                return;
            }

            const enabled = oneTimeDiscountCheckbox.checked;
            oneTimeDiscountToggle.classList.toggle('is-active', enabled);
            oneTimeDiscountState.textContent = enabled ? 'Enabled' : 'Off';
            oneTimeDiscountHelp.textContent = enabled
                ? 'Applies only to the next rent invoice'
                : 'Off — discount repeats on rent invoices';
        }

        if (oneTimeDiscountCheckbox) {
            oneTimeDiscountCheckbox.addEventListener('change', syncOneTimeDiscountToggle);
            syncOneTimeDiscountToggle();
        }

        const deductionsRepeater = document.getElementById('deductions-repeater');
        const addDeductionButton = document.getElementById('add-deduction-row');
        let deductionIndex = deductionsRepeater ? deductionsRepeater.querySelectorAll('.deduction-row').length : 0;

        if (addDeductionButton && deductionsRepeater) {
            addDeductionButton.addEventListener('click', function () {
                const row = document.createElement('div');
                row.className = 'deduction-row mb-2';
                row.innerHTML = `
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">£</span>
                                <input type="number" class="form-control" name="deductions[${deductionIndex}][amount]" min="0.01" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Notes</label>
                            <input type="text" class="form-control" name="deductions[${deductionIndex}][notes]" placeholder="Reason for deduction" maxlength="2000">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-outline-danger remove-deduction-row" title="Remove deduction">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
                deductionIndex += 1;
                deductionsRepeater.appendChild(row);
            });

            deductionsRepeater.addEventListener('click', function (event) {
                const removeButton = event.target.closest('.remove-deduction-row');
                if (removeButton) {
                    removeButton.closest('.deduction-row').remove();
                }
            });
        }

        const additionalChargesRepeater = document.getElementById('additional-charges-repeater');
        const addAdditionalChargeButton = document.getElementById('add-additional-charge-row');
        let additionalChargeIndex = additionalChargesRepeater ? additionalChargesRepeater.querySelectorAll('.additional-charge-row').length : 0;

        if (addAdditionalChargeButton && additionalChargesRepeater) {
            addAdditionalChargeButton.addEventListener('click', function () {
                const row = document.createElement('div');
                row.className = 'deduction-row mb-2 additional-charge-row';
                row.innerHTML = `
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Type</label>
                            <select class="form-control" name="additional_charges[${additionalChargeIndex}][type]" required>
                                <option value="insurance_excess">Insurance Excess</option>
                                <option value="miscellaneous_charges">Miscellaneous Charges</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">£</span>
                                <input type="number" class="form-control" name="additional_charges[${additionalChargeIndex}][amount]" min="0.01" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Notes</label>
                            <input type="text" class="form-control" name="additional_charges[${additionalChargeIndex}][notes]" placeholder="Reason for damage (e.g. tyres, body repair)" maxlength="2000">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-outline-danger remove-additional-charge-row" title="Remove damage">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
                additionalChargeIndex += 1;
                additionalChargesRepeater.appendChild(row);
            });

            additionalChargesRepeater.addEventListener('click', function (event) {
                const removeButton = event.target.closest('.remove-additional-charge-row');
                if (removeButton) {
                    removeButton.closest('.additional-charge-row').remove();
                }
            });
        }

        const replacementVehicleFinancialFieldIds = ['agreed_rent', 'collection_type', 'rent_interval', 'deposit_amount', 'billing_anchor_date'];
        const replacementVehicleSectionIds = ['agreement-financial-section', 'agreement-discount-section', 'agreement-payment-section'];

        function setReplacementVehicleSectionFieldsEnabled(enabled) {
            replacementVehicleSectionIds.forEach(function (sectionId) {
                const section = document.getElementById(sectionId);

                if (!section) {
                    return;
                }

                section.querySelectorAll('input, select, textarea').forEach(function (field) {
                    if (field.name === 'auto_schedule_collections') {
                        return;
                    }

                    field.disabled = !enabled;
                });
            });
        }

        function isClosingStatusSelected() {
            const statusSelect = document.getElementById('status_id');

            if (!statusSelect || !statusSelect.selectedOptions.length) {
                return false;
            }

            const statusName = String(statusSelect.selectedOptions[0].dataset.statusName || '').toLowerCase();

            return statusName === 'expired' || statusName === 'terminated';
        }

        function toggleClosingDateSection() {
            const section = document.getElementById('agreement-closing-section');
            const refundNameSection = document.getElementById('agreement-refund-details-section');
            const refundSortCodeSection = document.getElementById('agreement-refund-sort-code-section');
            const refundAccountSection = document.getElementById('agreement-refund-account-section');
            const show = isClosingStatusSelected();

            if (section) {
                section.style.display = show ? '' : 'none';
            }

            if (refundNameSection) {
                refundNameSection.style.display = show ? '' : 'none';
            }

            if (refundSortCodeSection) {
                refundSortCodeSection.style.display = show ? '' : 'none';
            }

            if (refundAccountSection) {
                refundAccountSection.style.display = show ? '' : 'none';
            }

            setFieldRequired('closing_date', show);

            if (show) {
                prefillRefundPersonNameIfEmpty();
            }
        }

        function selectedDriverDisplayName() {
            const driverSelect = document.getElementById('driver_id');

            if (!driverSelect || !driverSelect.selectedOptions.length) {
                return '';
            }

            return String(driverSelect.selectedOptions[0].textContent || '').trim();
        }

        function prefillRefundPersonNameIfEmpty() {
            const field = document.getElementById('refund_person_name');

            if (!field || String(field.value || '').trim() !== '') {
                return;
            }

            const driverName = selectedDriverDisplayName();

            if (driverName) {
                field.value = driverName;
            }
        }

        function isReplacementVehicleStatusSelected() {
            const statusSelect = document.getElementById('status_id');

            if (!statusSelect || replacementVehicleStatusId === null) {
                return false;
            }

            return String(statusSelect.value) === String(replacementVehicleStatusId);
        }

        function isSwapStatusSelected() {
            const statusSelect = document.getElementById('status_id');

            if (!statusSelect || swapStatusId === null) {
                return false;
            }

            return String(statusSelect.value) === String(swapStatusId);
        }

        function setFieldRequired(fieldId, required) {
            const field = document.getElementById(fieldId);

            if (!field) {
                return;
            }

            if (required) {
                field.setAttribute('required', 'required');
            } else {
                field.removeAttribute('required');
            }
        }

        function updateDriverActiveAgreementWarning() {
            const warning = document.getElementById('driver-active-agreement-warning');
            const list = document.getElementById('driver-active-agreement-list');
            const driverSelect = document.getElementById('driver_id');

            if (!warning || !list || !driverSelect || !isAgreementCreate) {
                return;
            }

            const driverId = driverSelect.value;

            if (!driverId || isReplacementVehicleStatusSelected() || isSwapStatusSelected()) {
                warning.style.display = 'none';
                list.innerHTML = '';

                return;
            }

            const agreements = driversActiveAgreements[driverId] || driversActiveAgreements[Number(driverId)] || [];

            if (!agreements.length) {
                warning.style.display = 'none';
                list.innerHTML = '';

                return;
            }

            list.innerHTML = agreements.map(function (agreement) {
                if (agreement.url) {
                    return '<li><a href="' + agreement.url + '">' + agreement.label + '</a></li>';
                }

                return '<li>' + agreement.label + '</li>';
            }).join('');
            warning.style.display = 'block';
        }

        function populateOriginalAgreementOptions(selectId, selectedId) {
            const parentSelect = document.getElementById(selectId || 'parent_agreement_id');
            const driverSelect = document.getElementById('driver_id');

            if (!parentSelect || !driverSelect) {
                return;
            }

            const driverId = driverSelect.value;
            const preferredSelected = selectedId != null && selectedId !== ''
                ? String(selectedId)
                : null;
            const currentValue = preferredSelected
                || parentSelect.value
                || (selectId === 'upgraded_from_agreement_id'
                    ? (selectedSwapOriginalAgreementId != null ? String(selectedSwapOriginalAgreementId) : '')
                    : (selectedParentAgreementId != null ? String(selectedParentAgreementId) : ''));
            const filtered = originalAgreements.filter(function (agreement) {
                return String(agreement.driver_id) === String(driverId);
            });
            const $parentSelect = typeof $ !== 'undefined' ? $(parentSelect) : null;
            const usesSelect2 = $parentSelect
                && $.fn.select2
                && $parentSelect.hasClass('select2-hidden-accessible');

            if (usesSelect2) {
                $parentSelect.empty().append(new Option('Select original agreement', '', false, false));

                filtered.forEach(function (agreement) {
                    const option = new Option(agreement.label, agreement.id, false, String(agreement.id) === currentValue);
                    option.dataset.depositAmount = agreement.deposit_amount;
                    option.dataset.agreedRent = agreement.agreed_rent;
                    option.dataset.rentInterval = agreement.rent_interval || '';
                    option.dataset.collectionType = agreement.collection_type || '';
                    option.dataset.endDate = agreement.end_date || '';
                    $parentSelect.append(option);
                });

                const stillValid = filtered.some(function (agreement) {
                    return String(agreement.id) === currentValue;
                });

                $parentSelect.val(stillValid ? currentValue : '').trigger('change.select2');
                return;
            }

            parentSelect.innerHTML = '<option value="">Select original agreement</option>';

            filtered.forEach(function (agreement) {
                const option = document.createElement('option');
                option.value = agreement.id;
                option.textContent = agreement.label;
                option.dataset.depositAmount = agreement.deposit_amount;
                option.dataset.agreedRent = agreement.agreed_rent;
                option.dataset.rentInterval = agreement.rent_interval || '';
                option.dataset.collectionType = agreement.collection_type || '';
                option.dataset.endDate = agreement.end_date || '';

                if (String(agreement.id) === currentValue) {
                    option.selected = true;
                }

                parentSelect.appendChild(option);
            });
        }

        function applySwapOriginalAgreementDefaults() {
            const swapSelect = document.getElementById('upgraded_from_agreement_id');
            const depositInput = document.getElementById('deposit_amount');
            const endDateInput = document.getElementById('end_date');
            const rentIntervalSelect = document.getElementById('rent_interval');
            const collectionTypeSelect = document.getElementById('collection_type');

            if (!swapSelect || !swapSelect.value) {
                return;
            }

            const option = swapSelect.options[swapSelect.selectedIndex];
            if (!option) {
                return;
            }

            if (depositInput && option.dataset.depositAmount != null) {
                depositInput.value = option.dataset.depositAmount;
            }

            if (endDateInput && option.dataset.endDate) {
                endDateInput.value = option.dataset.endDate;
            }

            if (rentIntervalSelect && option.dataset.rentInterval) {
                setSelectValue(rentIntervalSelect, option.dataset.rentInterval);
            }

            if (collectionTypeSelect && option.dataset.collectionType) {
                setSelectValue(collectionTypeSelect, option.dataset.collectionType);
            }
        }

        function toggleReplacementVehicleMode() {
            const isReplacementVehicle = isReplacementVehicleStatusSelected();
            const isSwap = isSwapStatusSelected();
            const parentSection = document.getElementById('parent-agreement-section');
            const swapSection = document.getElementById('swap-original-agreement-section');
            const financialSection = document.getElementById('agreement-financial-section');
            const discountSection = document.getElementById('agreement-discount-section');
            const paymentSection = document.getElementById('agreement-payment-section');
            const parentSelect = document.getElementById('parent_agreement_id');
            const swapSelect = document.getElementById('upgraded_from_agreement_id');
            const depositInput = document.getElementById('deposit_amount');
            const addPaymentCheckbox = document.getElementById('add_payment');

            if (parentSection) {
                parentSection.style.display = isReplacementVehicle ? 'block' : 'none';
            }

            if (swapSection) {
                swapSection.style.display = isSwap ? 'block' : 'none';
            }

            if (financialSection) {
                financialSection.style.display = isReplacementVehicle ? 'none' : 'block';
            }

            if (discountSection) {
                discountSection.style.display = isReplacementVehicle ? 'none' : 'block';
            }

            if (paymentSection) {
                paymentSection.style.display = (isReplacementVehicle || isSwap) ? 'none' : 'block';
            }

            replacementVehicleFinancialFieldIds.forEach(function (fieldId) {
                if (fieldId === 'deposit_amount' && (isSwap || isExistingSwapAgreement)) {
                    setFieldRequired(fieldId, false);
                    return;
                }

                setFieldRequired(fieldId, !isReplacementVehicle);
            });

            setReplacementVehicleSectionFieldsEnabled(!isReplacementVehicle);

            if (depositInput) {
                const lockDeposit = isSwap || isExistingSwapAgreement;
                depositInput.readOnly = lockDeposit;
                if (lockDeposit) {
                    depositInput.classList.add('bg-light');
                } else if (!isReplacementVehicle) {
                    depositInput.classList.remove('bg-light');
                }
            }

            if (parentSelect) {
                if (isReplacementVehicle) {
                    parentSelect.setAttribute('required', 'required');
                    populateOriginalAgreementOptions('parent_agreement_id', selectedParentAgreementId);
                } else {
                    parentSelect.removeAttribute('required');
                    setSelectValue(parentSelect, '');
                }
            }

            if (swapSelect) {
                if (isSwap) {
                    swapSelect.setAttribute('required', 'required');
                    populateOriginalAgreementOptions('upgraded_from_agreement_id', selectedSwapOriginalAgreementId);
                    applySwapOriginalAgreementDefaults();
                } else {
                    swapSelect.removeAttribute('required');
                    if (!isExistingSwapAgreement) {
                        setSelectValue(swapSelect, '');
                    }
                }
            }

            if (addPaymentCheckbox) {
                if (isReplacementVehicle || isSwap) {
                    addPaymentCheckbox.checked = false;
                }

                addPaymentCheckbox.disabled = isReplacementVehicle || isSwap;

                toggleAgreementPaymentFields();
            }

            updateDriverActiveAgreementWarning();
        }

        function setSelectValue(selectOrId, value) {
            const select = typeof selectOrId === 'string'
                ? document.getElementById(selectOrId.replace(/^#/, ''))
                : selectOrId;

            if (!select) {
                return;
            }

            const normalizedValue = value == null ? '' : String(value);

            if (typeof $ !== 'undefined' && $.fn.select2 && $(select).hasClass('select2-hidden-accessible')) {
                $(select).val(normalizedValue).trigger('change');
                return;
            }

            select.value = normalizedValue;
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function updateVehicleInsuranceDisplay() {
            const carSelect = document.getElementById('car_id');
            const emptyPanel = document.getElementById('vehicle-insurance-empty');
            const activePanel = document.getElementById('vehicle-insurance-active');
            const inactivePanel = document.getElementById('vehicle-insurance-inactive');
            const providerNameEl = document.getElementById('vehicle-insurance-provider-name');
            const policyNumberEl = document.getElementById('vehicle-insurance-policy-number');
            const expiryEl = document.getElementById('vehicle-insurance-expiry');

            if (!carSelect || !activePanel || !inactivePanel) {
                return;
            }

            const selectedOption = carSelect.options[carSelect.selectedIndex];
            const hasCar = selectedOption && selectedOption.value !== '';
            const hasActiveInsurance = hasCar
                && selectedOption.getAttribute('data-has-active-insurance') === '1';

            if (!hasCar) {
                if (emptyPanel) {
                    emptyPanel.style.display = 'flex';
                }
                activePanel.style.display = 'none';
                inactivePanel.style.display = 'none';
                return;
            }

            if (emptyPanel) {
                emptyPanel.style.display = 'none';
            }

            if (hasActiveInsurance) {
                activePanel.style.display = 'block';
                inactivePanel.style.display = 'none';

                if (providerNameEl) {
                    providerNameEl.textContent = selectedOption.getAttribute('data-insurance-provider-name') || '—';
                }
                if (policyNumberEl) {
                    policyNumberEl.textContent = selectedOption.getAttribute('data-insurance-policy-number') || '—';
                }
                if (expiryEl) {
                    expiryEl.textContent = selectedOption.getAttribute('data-insurance-expiry') || '—';
                }
                return;
            }

            activePanel.style.display = 'none';
            inactivePanel.style.display = 'block';
        }

        function syncFromVehicle() {
            const carSelect = document.getElementById('car_id');

            if (!carSelect) {
                return;
            }

            const selectedOption = carSelect.options[carSelect.selectedIndex];
            const companyId = selectedOption ? selectedOption.getAttribute('data-company-id') : null;
            const hasActiveInsurance = selectedOption
                && selectedOption.getAttribute('data-has-active-insurance') === '1';

            if (hasActiveInsurance) {
                const companyRadio = document.getElementById('using_own_insurance_company');

                if (companyRadio) {
                    companyRadio.checked = true;
                }

                toggleInsuranceSections();
            }

            if (companyId) {
                setSelectValue('company_id', companyId);
            }

            updateVehicleInsuranceDisplay();
        }

        function toggleInsuranceSections() {
            const usingOwnInsuranceClient = document.getElementById('using_own_insurance_client');
            const usingOwnInsuranceCompany = document.getElementById('using_own_insurance_company');
            const vehicleInsuranceSection = document.getElementById('vehicle-insurance-section');
            const ownSection = document.getElementById('own-insurance-section');

            if (usingOwnInsuranceClient.checked) {
                vehicleInsuranceSection.style.display = 'none';
                ownSection.style.display = 'block';
            } else if (usingOwnInsuranceCompany.checked) {
                vehicleInsuranceSection.style.display = 'block';
                ownSection.style.display = 'none';
                clearOwnInsuranceFields();
                updateVehicleInsuranceDisplay();
            }
        }

        function toggleAgreementPaymentFields() {
            const addPaymentCheckbox = document.getElementById('add_payment');
            const paymentFields = document.getElementById('agreement-payment-fields');

            if (!addPaymentCheckbox || !paymentFields) {
                return;
            }

            paymentFields.style.display = addPaymentCheckbox.checked ? 'block' : 'none';
            updateAgreementPaymentLimits();
        }

        @php
            $agreementBankAccountsForJs = ($bankAccounts ?? collect())->map(function ($account) {
                return [
                    'id' => $account->id,
                    'bank_name' => $account->bank_name,
                    'account_number' => $account->account_number,
                ];
            })->values();

            $defaultCardBankAccountIdForJs = ($bankAccounts ?? collect())
                ->firstWhere('account_number', \App\Models\BankAccount::DEFAULT_CARD_ACCOUNT_NUMBER);
            $defaultCardBankAccountIdForJs = $defaultCardBankAccountIdForJs
                ? $defaultCardBankAccountIdForJs->id
                : null;
        @endphp
        const agreementPaymentMethods = @json($agreementPaymentMethods);
        const agreementBankAccounts = @json($agreementBankAccountsForJs);
        const defaultCardBankAccountId = @json($defaultCardBankAccountIdForJs);
        const defaultAgreementPaymentDate = @json(now()->toDateString());

        function requiresBankAccount(method) {
            return method === 'Bank Transfer' || method === 'Card Payment';
        }

        function money(value) {
            return '£' + Number(value || 0).toFixed(2);
        }

        function agreementPaymentRows() {
            return Array.from(document.querySelectorAll('[data-payment-row]'));
        }

        function agreementPaymentTotal(exceptInput = null) {
            return agreementPaymentRows().reduce(function(total, row) {
                const input = row.querySelector('[data-payment-amount]');

                if (!input || input === exceptInput) {
                    return total;
                }

                return total + Number(input.value || 0);
            }, 0);
        }

        function agreementPaymentLimit() {
            const fields = document.getElementById('agreement-payment-fields');

            if (!fields) {
                return 0;
            }

            const serverLimit = fields.dataset.serverLimit;

            if (serverLimit !== '') {
                return Number(serverLimit || 0);
            }

            return Number(document.getElementById('agreed_rent')?.value || 0)
                + Number(document.getElementById('deposit_amount')?.value || 0);
        }

        function bankAccountFieldHtml(index, selectedId) {
            if (!agreementBankAccounts.length) {
                return '<div class="col-12 mb-2 bank-account-field d-none" data-bank-account-field>' +
                    '<label class="form-label">Bank Account <span class="text-danger">*</span></label>' +
                    '<div class="alert alert-warning mb-0 py-2">No bank accounts configured. <a href="{{ route('bank-accounts.index') }}">Add bank accounts</a></div>' +
                '</div>';
            }

            const options = agreementBankAccounts.map(function(account) {
                const selected = String(selectedId || '') === String(account.id) ? ' selected' : '';

                return '<option value="' + account.id + '"' + selected + '>' + account.bank_name + '</option>';
            }).join('');

            return '<div class="col-12 mb-2 bank-account-field d-none" data-bank-account-field>' +
                '<label class="form-label">Bank Account <span class="text-danger">*</span></label>' +
                '<select name="agreement_payments[' + index + '][bank_account_id]" class="form-control" data-bank-account-select>' +
                    '<option value="">Select Bank Account</option>' + options +
                '</select>' +
            '</div>';
        }

        function toggleBankAccountFieldForRow(row) {
            if (!row) {
                return;
            }

            const methodSelect = row.querySelector('[data-payment-method]');
            const bankField = row.querySelector('[data-bank-account-field]');
            const bankSelect = row.querySelector('[data-bank-account-select]');
            const needsBank = methodSelect && requiresBankAccount(methodSelect.value);

            if (bankField) {
                bankField.classList.toggle('d-none', !needsBank);
            }

            if (bankSelect) {
                bankSelect.required = needsBank && agreementBankAccounts.length > 0;

                if (!needsBank) {
                    bankSelect.value = '';
                } else if (methodSelect.value === 'Card Payment' && !bankSelect.value && defaultCardBankAccountId) {
                    bankSelect.value = String(defaultCardBankAccountId);
                }
            }
        }

        function bindAgreementPaymentRow(row) {
            const methodSelect = row.querySelector('[data-payment-method]');

            if (methodSelect) {
                methodSelect.addEventListener('change', function() {
                    toggleBankAccountFieldForRow(row);
                });
            }

            toggleBankAccountFieldForRow(row);
        }

        function bindAllAgreementPaymentRows() {
            agreementPaymentRows().forEach(function(row) {
                bindAgreementPaymentRow(row);
            });
        }

        function paymentRowTemplate(index) {
            const methodOptions = agreementPaymentMethods.map(function(method) {
                return '<option value="' + method + '">' + method + '</option>';
            }).join('');

            return '<div class="agreement-payment-row border rounded p-2 mb-2" data-payment-row>' +
                '<div class="row">' +
                    '<div class="col-md-3 mb-2">' +
                        '<label class="form-label">Payment Method <span class="text-danger">*</span></label>' +
                        '<select name="agreement_payments[' + index + '][payment_method]" class="form-control" data-payment-method>' +
                            '<option value="">Select Method</option>' + methodOptions +
                        '</select>' +
                    '</div>' +
                    bankAccountFieldHtml(index, '') +
                    '<div class="col-md-3 mb-2">' +
                        '<label class="form-label">Payment Date <span class="text-danger">*</span></label>' +
                        '<input type="date" name="agreement_payments[' + index + '][payment_date]" class="form-control" value="' + defaultAgreementPaymentDate + '" data-payment-date>' +
                    '</div>' +
                    '<div class="col-md-3 mb-2">' +
                        '<label class="form-label">Amount <span class="text-danger">*</span></label>' +
                        '<input type="number" name="agreement_payments[' + index + '][amount]" class="form-control agreement-payment-amount" min="0.01" step="0.01" placeholder="0.00" data-payment-amount>' +
                    '</div>' +
                    '<div class="col-md-3 mb-2 d-flex align-items-end">' +
                        '<button type="button" class="btn btn-outline-danger btn-sm agreement-payment-remove" data-remove-payment>Remove</button>' +
                    '</div>' +
                    '<div class="col-12 mb-2">' +
                        '<label class="form-label">Payment Notes</label>' +
                        '<textarea name="agreement_payments[' + index + '][notes]" rows="2" class="form-control" placeholder="Optional payment notes" data-payment-notes></textarea>' +
                    '</div>' +
                '</div>' +
            '</div>';
        }

        function reindexAgreementPaymentRows() {
            agreementPaymentRows().forEach(function(row, index) {
                row.querySelectorAll('[name]').forEach(function(input) {
                    input.name = input.name.replace(/agreement_payments\[\d+\]/, 'agreement_payments[' + index + ']');
                });
            });
        }

        function updateAgreementPaymentLimits(changedInput = null) {
            const fields = document.getElementById('agreement-payment-fields');
            const addMore = document.getElementById('agreement-payment-add-more');
            const message = document.getElementById('agreement-payment-limit-message');
            const limit = agreementPaymentLimit();
            const rows = agreementPaymentRows();
            let total = agreementPaymentTotal();

            rows.forEach(function(row) {
                const amountInput = row.querySelector('[data-payment-amount]');

                if (!amountInput) {
                    return;
                }

                const otherTotal = agreementPaymentTotal(amountInput);
                const maxAllowed = Math.max(limit - otherTotal, 0);
                amountInput.max = maxAllowed.toFixed(2);

                if ((!changedInput || changedInput === amountInput) && Number(amountInput.value || 0) > maxAllowed) {
                    amountInput.value = maxAllowed > 0 ? maxAllowed.toFixed(2) : '';
                }
            });

            total = agreementPaymentTotal();
            const remaining = Math.max(limit - total, 0);

            if (message) {
                message.textContent = 'Payment limit: ' + money(limit) + '. Added: ' + money(total) + '. Remaining: ' + money(remaining) + '.';
            }

            if (addMore) {
                addMore.style.display = remaining > 0.009 ? '' : 'none';
            }

            if (fields) {
                fields.querySelectorAll('[data-remove-payment]').forEach(function(button) {
                    button.style.visibility = rows.length > 1 ? 'visible' : 'hidden';
                });
            }
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
            toggleReplacementVehicleMode();
            toggleClosingDateSection();
            toggleInsuranceSections();
            toggleAgreementPaymentFields();
            updateVehicleInsuranceDisplay();
            updateDriverActiveAgreementWarning();

            document.getElementById('status_id')?.addEventListener('change', function () {
                toggleReplacementVehicleMode();
                toggleClosingDateSection();
            });
            document.getElementById('driver_id')?.addEventListener('change', function() {
                updateDriverActiveAgreementWarning();
                prefillRefundPersonNameIfEmpty();

                if (isReplacementVehicleStatusSelected()) {
                    populateOriginalAgreementOptions('parent_agreement_id', selectedParentAgreementId);
                }

                if (isSwapStatusSelected()) {
                    populateOriginalAgreementOptions('upgraded_from_agreement_id', selectedSwapOriginalAgreementId);
                    applySwapOriginalAgreementDefaults();
                }
            });

            if (typeof $ !== 'undefined') {
                $('#driver_id').on('change', function() {
                    updateDriverActiveAgreementWarning();
                    prefillRefundPersonNameIfEmpty();

                    if (isReplacementVehicleStatusSelected()) {
                        populateOriginalAgreementOptions('parent_agreement_id', selectedParentAgreementId);
                    }

                    if (isSwapStatusSelected()) {
                        populateOriginalAgreementOptions('upgraded_from_agreement_id', selectedSwapOriginalAgreementId);
                        applySwapOriginalAgreementDefaults();
                    }
                });
                $('#status_id').on('change', function () {
                    toggleReplacementVehicleMode();
                    toggleClosingDateSection();
                });
                $('#upgraded_from_agreement_id').on('change', function () {
                    applySwapOriginalAgreementDefaults();
                });
            }

            document.getElementById('upgraded_from_agreement_id')?.addEventListener('change', applySwapOriginalAgreementDefaults);

            document.getElementById('add_payment')?.addEventListener('change', toggleAgreementPaymentFields);
            document.getElementById('agreed_rent')?.addEventListener('input', function() {
                updateAgreementPaymentLimits();
            });
            document.getElementById('deposit_amount')?.addEventListener('input', function() {
                updateAgreementPaymentLimits();
            });
            document.getElementById('agreement-payment-add-more')?.addEventListener('click', function() {
                const rowsContainer = document.getElementById('agreement-payment-rows');

                if (!rowsContainer) {
                    return;
                }

                rowsContainer.insertAdjacentHTML('beforeend', paymentRowTemplate(agreementPaymentRows().length));
                const newRow = rowsContainer.lastElementChild;
                if (newRow) {
                    bindAgreementPaymentRow(newRow);
                }
                updateAgreementPaymentLimits();
            });
            document.getElementById('agreement-payment-rows')?.addEventListener('change', function(event) {
                if (event.target.matches('[data-payment-method]')) {
                    toggleBankAccountFieldForRow(event.target.closest('[data-payment-row]'));
                }
            });
            document.getElementById('agreement-payment-rows')?.addEventListener('input', function(event) {
                if (event.target.matches('[data-payment-amount]')) {
                    updateAgreementPaymentLimits(event.target);
                }
            });
            document.getElementById('agreement-payment-rows')?.addEventListener('click', function(event) {
                if (!event.target.matches('[data-remove-payment]')) {
                    return;
                }

                const row = event.target.closest('[data-payment-row]');

                if (row && agreementPaymentRows().length > 1) {
                    row.remove();
                    reindexAgreementPaymentRows();
                    updateAgreementPaymentLimits();
                }
            });
            updateAgreementPaymentLimits();
            bindAllAgreementPaymentRows();
            document.getElementById('using_own_insurance_client').addEventListener('change', toggleInsuranceSections);
            document.getElementById('using_own_insurance_company').addEventListener('change', toggleInsuranceSections);
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

            if (typeof $ !== 'undefined') {
                $('#car_id').on('change', syncFromVehicle);
            } else {
                document.getElementById('car_id')?.addEventListener('change', syncFromVehicle);
            }

            const agreementFormEl = document.getElementById('formCreateAgreement') || document.getElementById('formEditAgreement');

            if (agreementFormEl && window.FleetiqFormValidation && window.validateAgreementForm) {
                FleetiqFormValidation.attach(agreementFormEl, function (form, errors) {
                    if (typeof toggleReplacementVehicleMode === 'function') {
                        toggleReplacementVehicleMode();
                    }
                    validateAgreementForm(form, errors);
                });
            }

            window.fleetiqAgreementValidation = {
                replacementVehicleStatusId: replacementVehicleStatusId,
                swapStatusId: swapStatusId
            };

            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            const mileageOutInput = document.getElementById('mileage_out');
            const mileageInInput = document.getElementById('mileage_in');
            const ownInsuranceStartDate = document.getElementById('own_insurance_start_date');
            const ownInsuranceEndDate = document.getElementById('own_insurance_end_date');

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

            if (ownInsuranceStartDate && ownInsuranceEndDate) {
                ownInsuranceStartDate.addEventListener('blur', validateInsuranceDates);
                ownInsuranceEndDate.addEventListener('blur', validateInsuranceDates);
            }
            if (mileageInInput && mileageOutInput) {
                mileageInInput.addEventListener('change', validateMileage);
            }

            function previousAnchorBeforeJs(anchorDate, rentInterval) {
                const date = new Date(anchorDate.getTime());

                switch ((rentInterval || '').toLowerCase()) {
                    case 'weekly':
                        date.setDate(date.getDate() - 7);
                        break;
                    case 'quarterly':
                        date.setMonth(date.getMonth() - 3);
                        break;
                    case 'yearly':
                        date.setFullYear(date.getFullYear() - 1);
                        break;
                    default:
                        date.setMonth(date.getMonth() - 1);
                        break;
                }

                return date;
            }

            function daysBetween(fromDate, toDate) {
                const from = Date.UTC(fromDate.getFullYear(), fromDate.getMonth(), fromDate.getDate());
                const to = Date.UTC(toDate.getFullYear(), toDate.getMonth(), toDate.getDate());

                return Math.round((to - from) / (1000 * 60 * 60 * 24));
            }

            function updateBillingAnchorPreview() {
                const preview = document.getElementById('billing_anchor_preview');
                const startInput = document.getElementById('start_date');
                const anchorInput = document.getElementById('billing_anchor_date');
                const rentInput = document.getElementById('agreed_rent');
                const intervalSelect = document.getElementById('rent_interval');

                if (!preview || !startInput || !anchorInput || !rentInput || !intervalSelect) {
                    return;
                }

                const anchorValue = anchorInput.value;
                const startValue = startInput.value;
                const agreedRent = Number(rentInput.value);
                const rentInterval = intervalSelect.value;

                if (!anchorValue || !startValue || !agreedRent || !rentInterval) {
                    preview.style.display = 'none';
                    preview.textContent = '';

                    return;
                }

                const startDate = new Date(startValue.slice(0, 10) + 'T00:00:00');
                const anchorDate = new Date(anchorValue + 'T00:00:00');

                if (anchorDate <= startDate) {
                    preview.style.display = 'none';
                    preview.textContent = '';

                    return;
                }

                const partialDays = daysBetween(startDate, anchorDate);
                const previousAnchor = previousAnchorBeforeJs(anchorDate, rentInterval);
                const periodDays = daysBetween(previousAnchor, anchorDate);

                if (partialDays <= 0 || periodDays <= 0) {
                    preview.style.display = 'none';
                    preview.textContent = '';

                    return;
                }

                const amount = Math.round((agreedRent / periodDays * partialDays) * 100) / 100;
                preview.textContent = 'Estimated first prorated amount: £' + amount.toFixed(2) + ' (until ' + anchorValue + ')';
                preview.style.display = 'block';
            }

            ['start_date', 'billing_anchor_date', 'agreed_rent', 'rent_interval'].forEach(function(fieldId) {
                const field = document.getElementById(fieldId);

                if (!field) {
                    return;
                }

                field.addEventListener('change', updateBillingAnchorPreview);
                field.addEventListener('input', updateBillingAnchorPreview);
            });

            updateBillingAnchorPreview();
        });

    </script>
@endpush
