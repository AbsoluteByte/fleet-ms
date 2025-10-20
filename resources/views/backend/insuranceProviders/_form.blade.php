<div class="row">
    <!-- Provider Information -->
    <div class="card mb-2">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fa fa-shield-alt me-2"></i>
                Insurance Provider Information
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                {{-- Company Selection --}}
                <div class="col-md-6 mb-3">
                    <label for="company_id" class="form-label fw-bold">Company <span class="text-danger">*</span></label>
                    <select name="company_id" id="company_id"
                            class="form-control @error('company_id') is-invalid @enderror" required>
                        <option value="">Select Company</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}"
                                {{ (old('company_id') ?? ($model->company_id ?? '')) == $company->id ? 'selected' : '' }}>
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('company_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Provider Name --}}
                <div class="col-md-6 mb-3">
                    <label for="provider_name" class="form-label fw-bold">Provider Name <span class="text-danger">*</span></label>
                    <input type="text" name="provider_name" id="provider_name"
                           class="form-control @error('provider_name') is-invalid @enderror"
                           value="{{ old('provider_name') ?? ($model->provider_name ?? '') }}"
                           placeholder="Enter Insurance Provider Name" required>
                    @error('provider_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Insurance Type --}}
                <div class="col-md-6 mb-3">
                    <label for="insurance_type" class="form-label fw-bold">Insurance Type <span class="text-danger">*</span></label>
                    <select name="insurance_type" id="insurance_type"
                            class="form-control @error('insurance_type') is-invalid @enderror" required>
                        <option value="">Select Insurance Type</option>
                        @php
                            $insuranceTypes = [
                                'Comprehensive' => 'Comprehensive',
                                'Third Party' => 'Third Party',
                                'Third Party Fire & Theft' => 'Third Party Fire & Theft',
                                'Commercial' => 'Commercial',
                                'Fleet' => 'Fleet',
                                'Public Liability' => 'Public Liability',
                                'Professional Indemnity' => 'Professional Indemnity',
                                'Motor Trade' => 'Motor Trade'
                            ];
                            $selectedType = old('insurance_type') ?? ($model->insurance_type ?? '');
                        @endphp
                        @foreach($insuranceTypes as $key => $value)
                            <option value="{{ $key }}" {{ $selectedType == $key ? 'selected' : '' }}>
                                {{ $value }}
                            </option>
                        @endforeach
                    </select>
                    @error('insurance_type')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Amount --}}
                <div class="col-md-6 mb-3">
                    <label for="amount" class="form-label fw-bold">Amount <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">£</span>
                        </div>
                        <input type="number" name="amount" id="amount"
                               class="form-control @error('amount') is-invalid @enderror"
                               value="{{ old('amount') ?? ($model->amount ?? '') }}"
                               placeholder="0.00" step="0.01" min="0" required>
                        @error('amount')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <small class="form-text text-muted">Enter the insurance premium amount</small>
                </div>

                {{-- Policy Number --}}
                <div class="col-md-6 mb-3">
                    <label for="policy_number" class="form-label fw-bold">Policy Number <span class="text-danger">*</span></label>
                    <input type="text" name="policy_number" id="policy_number"
                           class="form-control @error('policy_number') is-invalid @enderror"
                           value="{{ old('policy_number') ?? ($model->policy_number ?? '') }}"
                           placeholder="Enter Policy Number" required>
                    @error('policy_number')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Expiry Date --}}
                <div class="col-md-6 mb-3">
                    <label for="expiry_date" class="form-label fw-bold">Expiry Date <span class="text-danger">*</span></label>
                    <input type="date" name="expiry_date" id="expiry_date"
                           class="form-control @error('expiry_date') is-invalid @enderror"
                           value="{{ old('expiry_date') ?? (isset($model) && $model->expiry_date ? $model->expiry_date->format('Y-m-d') : '') }}" required>
                    @error('expiry_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Status --}}
                <div class="col-md-6 mb-3">
                    <label for="status_id" class="form-label fw-bold">Status <span class="text-danger">*</span></label>
                    <select name="status_id" id="status_id"
                            class="form-control @error('status_id') is-invalid @enderror" required>
                        <option value="">Select Status</option>
                        @php
                            try {
                                $statuses = \App\Models\Status::where('type', 'insurance')->select('name', 'id', 'color')->get();
                                $selectedStatus = old('status_id') ?? ($model->status_id ?? '');
                            } catch (\Exception $e) {
                                // Fallback if Status model doesn't exist
                                $statuses = collect([
                                    (object)['id' => 1, 'name' => 'Active', 'color' => '#28a745'],
                                    (object)['id' => 2, 'name' => 'Expired', 'color' => '#dc3545'],
                                    (object)['id' => 3, 'name' => 'Pending Renewal', 'color' => '#ffc107'],
                                    (object)['id' => 4, 'name' => 'Cancelled', 'color' => '#6c757d']
                                ]);
                                $selectedStatus = old('status_id') ?? ($model->status_id ?? '');
                            }
                        @endphp
                        @foreach($statuses as $status)
                            <option value="{{ $status->id }}"
                                    data-color="{{ $status->color }}"
                                {{ $selectedStatus == $status->id ? 'selected' : '' }}>
                                {{ $status->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('status_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Form Actions -->
<div class="row">
    <div class="col-12">
        <div class="form-group">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i>
                {{ isset($model->id) ? 'Update Insurance Provider' : 'Create Insurance Provider' }}
            </button>
            <a href="{{ route($url . 'index') }}" class="btn btn-secondary ml-2">
                <i class="fa fa-times"></i> Cancel
            </a>
        </div>
    </div>
</div>

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Format phone number
            const phoneField = document.getElementById('contact_phone');
            if (phoneField) {
                phoneField.addEventListener('input', function() {
                    let value = this.value.replace(/[^\d\+\-\s\(\)]/g, '');
                    this.value = value;
                });
            }
            // Expiry date validation
            const expiryField = document.getElementById('expiry_date');
            if (expiryField) {
                expiryField.addEventListener('change', function() {
                    const selectedDate = new Date(this.value);
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);

                    if (selectedDate <= today) {
                        alert('Expiry date should be in the future.');
                        // Don't clear the field, just warn the user
                    }
                });
            }

            // Amount validation
            const amountField = document.getElementById('amount');
            if (amountField) {
                amountField.addEventListener('input', function() {
                    const value = parseFloat(this.value);
                    if (value < 0) {
                        this.value = 0;
                    }
                });
            }

            // Policy number formatting
            const policyField = document.getElementById('policy_number');
            if (policyField) {
                policyField.addEventListener('input', function() {
                    this.value = this.value.toUpperCase();
                });
            }

            // Auto-capitalize provider name
            const providerField = document.getElementById('provider_name');
            if (providerField) {
                providerField.addEventListener('blur', function() {
                    this.value = this.value.replace(/\b\w/g, l => l.toUpperCase());
                });
            }

            // Status color preview
            const statusField = document.getElementById('status_id');
            if (statusField) {
                statusField.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const color = selectedOption.getAttribute('data-color');

                    if (color) {
                        this.style.borderLeftColor = color;
                        this.style.borderLeftWidth = '4px';
                    } else {
                        this.style.borderLeftColor = '';
                        this.style.borderLeftWidth = '';
                    }
                });

                // Set initial color if editing
                statusField.dispatchEvent(new Event('change'));
            }

            // Form validation before submit
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const requiredFields = ['company_id', 'provider_name', 'insurance_type', 'amount', 'policy_number', 'expiry_date', 'status_id'];
                    let hasErrors = false;

                    requiredFields.forEach(fieldId => {
                        const field = document.getElementById(fieldId);
                        if (field && !field.value.trim()) {
                            field.classList.add('is-invalid');
                            hasErrors = true;
                        } else if (field) {
                            field.classList.remove('is-invalid');
                        }
                    });

                    if (hasErrors) {
                        e.preventDefault();
                        alert('Please fill in all required fields.');
                        return false;
                    }

                    // Validate amount
                    const amount = document.getElementById('amount');
                    if (amount && (parseFloat(amount.value) <= 0)) {
                        e.preventDefault();
                        alert('Please enter a valid amount greater than 0.');
                        amount.focus();
                        return false;
                    }
                });
            }

            // Character counter for notes
            const notesField = document.getElementById('notes');
            if (notesField) {
                const maxLength = 1000;
                const counter = document.createElement('small');
                counter.className = 'form-text text-muted';
                counter.id = 'notes-counter';
                notesField.parentNode.appendChild(counter);

                function updateCounter() {
                    const remaining = maxLength - notesField.value.length;
                    counter.textContent = `${notesField.value.length}/${maxLength} characters`;

                    if (remaining < 100) {
                        counter.className = 'form-text text-warning';
                    } else if (remaining < 0) {
                        counter.className = 'form-text text-danger';
                    } else {
                        counter.className = 'form-text text-muted';
                    }
                }

                notesField.addEventListener('input', updateCounter);
                updateCounter(); // Initial count
            }
        });
    </script>
@endpush
