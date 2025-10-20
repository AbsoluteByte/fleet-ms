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
                                {{ $driver->full_name }}
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
                    <label for="car_id" class="form-label">Vehicle *</label>
                    <select name="car_id" id="car_id" class="form-control @error('car_id') is-invalid @enderror" required>
                        <option value="">Select Vehicle</option>
                        @foreach($cars as $car)
                            <option value="{{ $car->id }}" {{ (old('car_id') ?? (isset($model) ? $model->car_id : '')) == $car->id ? 'selected' : '' }}>
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
                    <label for="start_date" class="form-label">Start Date *</label>
                    <input type="date" name="start_date" id="start_date"
                           class="form-control @error('start_date') is-invalid @enderror"
                           value="{{ old('start_date') ?? (isset($model) ? $model->start_date?->format('Y-m-d') : '') }}" required>
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
                    <label class="form-label">Will you be using your own insurance? *</label>
                    <div class="d-flex gap-5">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="using_own_insurance" id="using_own_insurance_yes"
                                   value="1" {{ (old('using_own_insurance') ?? (isset($model) ? $model->using_own_insurance : false)) ? 'checked' : '' }}>
                            <label class="form-check-label mr-2" for="using_own_insurance_yes">
                                Yes
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="using_own_insurance" id="using_own_insurance_no"
                                   value="0" {{ !(old('using_own_insurance') ?? (isset($model) ? $model->using_own_insurance : false)) ? 'checked' : '' }}>
                            <label class="form-check-label" for="using_own_insurance_no">
                                No
                            </label>
                        </div>
                    </div>
                    @error('using_own_insurance')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Provider Insurance Section (shown when "No" is selected) -->
        <div id="provider-insurance-section" style="display: none;">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="insurance_provider_id" class="form-label">Insurance Provider *</label>
                        <select name="insurance_provider_id" id="insurance_provider_id" class="form-control @error('insurance_provider_id') is-invalid @enderror">
                            <option value="">Select Insurance Provider</option>
                            @foreach($insuranceProviders as $provider)
                                <option value="{{ $provider->id }}" {{ (old('insurance_provider_id') ?? (isset($model) ? $model->insurance_provider_id : '')) == $provider->id ? 'selected' : '' }}>
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

        <!-- Own Insurance Section (shown when "Yes" is selected) -->
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
                        <label for="own_insurance_proof_document" class="form-label">Proof of Insurance Document</label>
                        <input type="file" name="own_insurance_proof_document" id="own_insurance_proof_document"
                               class="form-control @error('own_insurance_proof_document') is-invalid @enderror"
                               accept=".pdf,.jpg,.jpeg,.png">
                        @if(isset($model) && $model->own_insurance_proof_document)
                            <div class="mt-2">
                                <small class="text-muted">Current file: {{ $model->own_insurance_proof_document }}</small>
                                <a href="{{ asset('uploads/insurance_documents/' . $model->own_insurance_proof_document) }}"
                                   target="_blank" class="btn btn-sm btn-outline-info ms-2">
                                    <i class="fa fa-eye"></i> View
                                </a>
                            </div>
                        @endif
                        <div class="form-text">Accepted formats: PDF, JPG, JPEG, PNG (Max: 2MB)</div>
                        @error('own_insurance_proof_document')
                        <div class="invalid-feedback">{{ $message }}</div>
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

<!-- Manual Collections (shown when auto schedule is disabled) -->
<div class="card mb-2" id="manual-collections" style="display: none;">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="fa fa-list me-2"></i>
            Manual Collections
        </h5>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addCollection()">
            <i class="fa fa-plus me-1"></i>
            Add Collection
        </button>
    </div>
    <div class="card-body">
        <div id="collections-container">
            @php
                $collections = old('collections');
                if (!$collections && isset($model) && $model->collections && !$model->auto_schedule_collections) {
                    $collections = $model->collections->where('is_auto_generated', false)->toArray();
                }
                if (empty($collections)) {
                    $collections = [[]];
                }
            @endphp

            @foreach($collections as $index => $collection)
                <div class="collection-item row border-bottom pb-3 mb-3" data-index="{{ $index }}">
                    <div class="col-md-3">
                        <label class="form-label">Collection Date *</label>
                        @php
                            $collectionDate = old('collections.'.$index.'.date');
                            if (!$collectionDate && is_array($collection) && isset($collection['date'])) {
                                $collectionDate = $collection['date'];
                            } elseif (!$collectionDate && is_object($collection) && isset($collection->date)) {
                                $collectionDate = $collection->date instanceof \Carbon\Carbon ? $collection->date->format('Y-m-d') : $collection->date;
                            }
                        @endphp
                        <input type="date" name="collections[{{ $index }}][date]"
                               class="form-control @error('collections.'.$index.'.date') is-invalid @enderror"
                               value="{{ $collectionDate }}">
                        @error('collections.'.$index.'.date')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Due Date *</label>
                        @php
                            $collectionDueDate = old('collections.'.$index.'.due_date');
                            if (!$collectionDueDate && is_array($collection) && isset($collection['due_date'])) {
                                $collectionDueDate = $collection['due_date'];
                            } elseif (!$collectionDueDate && is_object($collection) && isset($collection->due_date)) {
                                $collectionDueDate = $collection->due_date instanceof \Carbon\Carbon ? $collection->due_date->format('Y-m-d') : $collection->due_date;
                            }
                        @endphp
                        <input type="date" name="collections[{{ $index }}][due_date]"
                               class="form-control @error('collections.'.$index.'.due_date') is-invalid @enderror"
                               value="{{ $collectionDueDate }}">
                        @error('collections.'.$index.'.due_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Method *</label>
                        @php
                            $collectionMethod = old('collections.'.$index.'.method');
                            if (!$collectionMethod && is_array($collection) && isset($collection['method'])) {
                                $collectionMethod = $collection['method'];
                            } elseif (!$collectionMethod && is_object($collection) && isset($collection->method)) {
                                $collectionMethod = $collection->method;
                            }
                        @endphp
                        <select name="collections[{{ $index }}][method]"
                                class="form-control @error('collections.'.$index.'.method') is-invalid @enderror">
                            <option value="">Select Method</option>
                            <option value="Bank Transfer" {{ $collectionMethod == 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>
                            <option value="Cash" {{ $collectionMethod == 'Cash' ? 'selected' : '' }}>Cash</option>
                            <option value="Cheque" {{ $collectionMethod == 'Cheque' ? 'selected' : '' }}>Cheque</option>
                            <option value="Card Payment" {{ $collectionMethod == 'Card Payment' ? 'selected' : '' }}>Card Payment</option>
                            <option value="Direct Debit" {{ $collectionMethod == 'Direct Debit' ? 'selected' : '' }}>Direct Debit</option>
                        </select>
                        @error('collections.'.$index.'.method')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Amount *</label>
                        @php
                            $collectionAmount = old('collections.'.$index.'.amount');
                            if (!$collectionAmount && is_array($collection) && isset($collection['amount'])) {
                                $collectionAmount = $collection['amount'];
                            } elseif (!$collectionAmount && is_object($collection) && isset($collection->amount)) {
                                $collectionAmount = $collection->amount;
                            }
                        @endphp
                        <div class="input-group">
                            <span class="input-group-text">£</span>
                            <input type="number" name="collections[{{ $index }}][amount]"
                                   class="form-control @error('collections.'.$index.'.amount') is-invalid @enderror"
                                   value="{{ $collectionAmount }}" step="0.01" min="0">
                        </div>
                        @error('collections.'.$index.'.amount')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            @if($index > 0)
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeCollection(this)">
                                    <i class="fa fa-trash"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
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

@push('js')
    <script>
        let collectionIndex = {{ count($collections ?? []) }};

        // Toggle between auto and manual collection modes
        function toggleCollectionMode() {
            const autoScheduleCheckbox = document.getElementById('auto_schedule_collections');
            const manualSection = document.getElementById('manual-collections');

            if (autoScheduleCheckbox.checked) {
                manualSection.style.display = 'none';
            } else {
                manualSection.style.display = 'block';
            }
        }

        // Toggle insurance sections based on radio button selection
        function toggleInsuranceSections() {
            const usingOwnInsuranceYes = document.getElementById('using_own_insurance_yes');
            const usingOwnInsuranceNo = document.getElementById('using_own_insurance_no');
            const providerSection = document.getElementById('provider-insurance-section');
            const ownSection = document.getElementById('own-insurance-section');

            if (usingOwnInsuranceYes.checked) {
                providerSection.style.display = 'none';
                ownSection.style.display = 'block';
                // Clear provider insurance field
                document.getElementById('insurance_provider_id').value = '';
            } else if (usingOwnInsuranceNo.checked) {
                providerSection.style.display = 'block';
                ownSection.style.display = 'none';
                // Clear own insurance fields
                clearOwnInsuranceFields();
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

        // Initialize toggle states
        document.addEventListener('DOMContentLoaded', function() {
            toggleCollectionMode();
            toggleInsuranceSections();

            document.getElementById('auto_schedule_collections').addEventListener('change', toggleCollectionMode);
            document.getElementById('using_own_insurance_yes').addEventListener('change', toggleInsuranceSections);
            document.getElementById('using_own_insurance_no').addEventListener('change', toggleInsuranceSections);
        });

        function addCollection() {
            const container = document.getElementById('collections-container');
            const newCollection = `
                <div class="collection-item row border-bottom pb-3 mb-3" data-index="${collectionIndex}">
                    <div class="col-md-3">
                        <label class="form-label">Collection Date *</label>
                        <input type="date" name="collections[${collectionIndex}][date]" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Due Date *</label>
                        <input type="date" name="collections[${collectionIndex}][due_date]" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Method *</label>
                        <select name="collections[${collectionIndex}][method]" class="form-control">
                            <option value="">Select Method</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Cash">Cash</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Card Payment">Card Payment</option>
                            <option value="Direct Debit">Direct Debit</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Amount *</label>
                        <div class="input-group">
                            <span class="input-group-text">£</span>
                            <input type="number" name="collections[${collectionIndex}][amount]" class="form-control" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="button" class="btn btn-danger btn-sm" onclick="removeCollection(this)">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', newCollection);
            collectionIndex++;
        }

        function removeCollection(button) {
            button.closest('.collection-item').remove();
        }

        // Enhanced form validation
        document.addEventListener('DOMContentLoaded', function() {
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            const mileageOutInput = document.getElementById('mileage_out');
            const mileageInInput = document.getElementById('mileage_in');
            const ownInsuranceStartDate = document.getElementById('own_insurance_start_date');
            const ownInsuranceEndDate = document.getElementById('own_insurance_end_date');

            function validateDates() {
                const startDate = startDateInput.value;
                const endDate = endDateInput.value;

                if (startDate && endDate && new Date(endDate) <= new Date(startDate)) {
                    alert('End date must be after start date');
                    endDateInput.value = '';
                    return false;
                }
                return true;
            }

            function validateInsuranceDates() {
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

            startDateInput.addEventListener('change', validateDates);
            endDateInput.addEventListener('change', validateDates);
            ownInsuranceStartDate.addEventListener('change', validateInsuranceDates);
            ownInsuranceEndDate.addEventListener('change', validateInsuranceDates);
            mileageInInput.addEventListener('change', validateMileage);

            // Set minimum date for start date (today)
            const today = new Date().toISOString().split('T')[0];
            startDateInput.setAttribute('min', today);

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
