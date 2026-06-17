<div class="row">
    <!-- Personal Information -->
    <div class="card mb-1">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fa fa-user me-2"></i>
                Personal Information
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-2">
                        <label for="first_name" class="form-label">First Name *</label>
                        <input type="text" name="first_name" id="first_name"
                               class="form-control @error('first_name') is-invalid @enderror"
                               value="{{ old('first_name') ?? ($model->first_name ?? '') }}"
                               placeholder="Enter First Name" required>
                        @error('first_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-2">
                        <label for="middle_name" class="form-label">Middle Name</label>
                        <input type="text" name="middle_name" id="middle_name"
                               class="form-control @error('middle_name') is-invalid @enderror"
                               value="{{ old('middle_name') ?? ($model->middle_name ?? '') }}"
                               placeholder="Enter Middle Name">
                        @error('middle_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-2">
                        <label for="last_name" class="form-label">Last Name *</label>
                        <input type="text" name="last_name" id="last_name"
                               class="form-control @error('last_name') is-invalid @enderror"
                               value="{{ old('last_name') ?? ($model->last_name ?? '') }}"
                               placeholder="Enter Last Name" required>
                        @error('last_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-2">
                        <label for="dob" class="form-label">Date of Birth *</label>
                        <input type="date" name="dob" id="dob"
                               class="form-control @error('dob') is-invalid @enderror"
                               value="{{ old('dob') ?? (isset($model) && $model->dob ? $model->dob->format('Y-m-d') : '') }}"
                               required>
                        @error('dob')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-2">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" name="email" id="email"
                               class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email') ?? ($model->email ?? '') }}"
                               placeholder="e.g. driver@example.com" required>
                        @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-2">
                        <label for="phone_number" class="form-label">Phone Number *</label>
                        <input type="text" name="phone_number" id="phone_number"
                               class="form-control @error('phone_number') is-invalid @enderror"
                               value="{{ old('phone_number') ?? ($model->phone_number ?? '') }}"
                               placeholder="e.g. +44 123 456 7890" required>
                        @error('phone_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- NEW FIELD: NI Number --}}
                <div class="col-md-6">
                    <div class="mb-1">
                        <label for="ni_number" class="form-label">NI Number</label>
                        <input type="text" name="ni_number" id="ni_number"
                               class="form-control @error('ni_number') is-invalid @enderror"
                               value="{{ old('ni_number') ?? ($model->ni_number ?? '') }}"
                               placeholder="e.g. AB123456C" maxlength="9">
                        @error('ni_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">National Insurance Number (Optional)</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Address Information -->
    <div class="card mb-1">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-map-marker-alt me-2"></i>
                Address Information
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-2">
                        <label for="address1" class="form-label">Address Line 1 *</label>
                        <input type="text" name="address1" id="address1"
                               class="form-control @error('address1') is-invalid @enderror"
                               value="{{ old('address1') ?? ($model->address1 ?? '') }}"
                               placeholder="Enter Address Line 1" required>
                        @error('address1')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-2">
                        <label for="address2" class="form-label">Address Line 2</label>
                        <input type="text" name="address2" id="address2"
                               class="form-control @error('address2') is-invalid @enderror"
                               value="{{ old('address2') ?? ($model->address2 ?? '') }}"
                               placeholder="Enter Address Line 2 (Optional)">
                        @error('address2')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="mb-2">
                        <label for="post_code" class="form-label">Post Code *</label>
                        <input type="text" name="post_code" id="post_code"
                               class="form-control @error('post_code') is-invalid @enderror"
                               value="{{ old('post_code') ?? ($model->post_code ?? '') }}"
                               placeholder="e.g. SW1A 1AA" required>
                        @error('post_code')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="mb-2">
                        <label for="town" class="form-label">Town *</label>
                        <input type="text" name="town" id="town"
                               class="form-control @error('town') is-invalid @enderror"
                               value="{{ old('town') ?? ($model->town ?? '') }}"
                               placeholder="Enter Town" required>
                        @error('town')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- County field hidden per client request
                <div class="col-md-3">
                    <div class="mb-2">
                        <label for="county" class="form-label">County</label>
                        <input type="text" name="county" id="county"
                               class="form-control @error('county') is-invalid @enderror"
                               value="{{ old('county') ?? ($model->county ?? '') }}"
                               placeholder="Enter County (Optional)">
                        @error('county')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                --}}

                <div class="col-md-3">
                    <div class="mb-2">
                        <label for="country" class="form-label">Country *</label>
                        <select name="country_id" id="country"
                                class="form-control select-search @error('country') is-invalid @enderror" required>
                            <option value="">Select Country</option>
                            @php
                                try {
                                    $countries = \App\Models\Country::select('name', 'id')->get()->pluck('name', 'id');
                                    $defaultCountryId = $countries->search('United Kingdom');
                                    $selectedCountry = old('country_id') ?? ($model->country_id ?? ($defaultCountryId ?: ''));
                                } catch (\Exception $e) {
                                     $countries = collect();
                                     $selectedCountry = old('country_id') ?? '';
                                }
                            @endphp
                            @foreach($countries as $key => $name)
                                <option value="{{ $key }}" {{ $selectedCountry == $key ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                        @error('country')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- License Information -->
    <div class="card mb-1">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fa fa-id-card me-2"></i>
                License Information
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-2">
                        <label for="driver_license_number" class="form-label">Driver License Number *</label>
                        <input type="text" name="driver_license_number" id="driver_license_number"
                               class="form-control @error('driver_license_number') is-invalid @enderror"
                               value="{{ old('driver_license_number') ?? ($model->driver_license_number ?? '') }}"
                               placeholder="Enter Driver License Number" required>
                        @error('driver_license_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-2">
                        <label for="driver_license_expiry_date" class="form-label">Driver License Expiry Date *</label>
                        <input type="date" name="driver_license_expiry_date" id="driver_license_expiry_date"
                               class="form-control @error('driver_license_expiry_date') is-invalid @enderror"
                               value="{{ old('driver_license_expiry_date') ?? (isset($model) && $model->driver_license_expiry_date ? $model->driver_license_expiry_date->format('Y-m-d') : '') }}"
                               required>
                        @error('driver_license_expiry_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-2">
                        <label for="phd_license_number" class="form-label">PHD License Number</label>
                        <input type="text" name="phd_license_number" id="phd_license_number"
                               class="form-control @error('phd_license_number') is-invalid @enderror"
                               value="{{ old('phd_license_number') ?? ($model->phd_license_number ?? '') }}"
                               placeholder="Enter PHD License Number">
                        @error('phd_license_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-2">
                        <label for="phd_license_expiry_date" class="form-label">PHD License Expiry Date</label>
                        <input type="date" name="phd_license_expiry_date" id="phd_license_expiry_date"
                               class="form-control @error('phd_license_expiry_date') is-invalid @enderror"
                               value="{{ old('phd_license_expiry_date') ?? (isset($model) && $model->phd_license_expiry_date ? $model->phd_license_expiry_date->format('Y-m-d') : '') }}">
                        @error('phd_license_expiry_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Emergency Contact -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fa fa-phone me-2"></i>
                Emergency Contact
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-2">
                        <label for="next_of_kin" class="form-label">Next of Kin *</label>
                        <input type="text" name="next_of_kin" id="next_of_kin"
                               class="form-control @error('next_of_kin') is-invalid @enderror"
                               value="{{ old('next_of_kin') ?? ($model->next_of_kin ?? '') }}"
                               placeholder="Enter Next of Kin Name" required>
                        @error('next_of_kin')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-2">
                        <label for="next_of_kin_phone" class="form-label">Next of Kin Phone *</label>
                        <input type="text" name="next_of_kin_phone" id="next_of_kin_phone"
                               class="form-control @error('next_of_kin_phone') is-invalid @enderror"
                               value="{{ old('next_of_kin_phone') ?? ($model->next_of_kin_phone ?? '') }}"
                               placeholder="Enter Next of Kin Phone" required>
                        @error('next_of_kin_phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Documents -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-file-upload me-2"></i>
                Documents
            </h5>
        </div>
        <div class="card-body">
            @php
                $isDriverEdit = isset($model) && ! empty($model->id);
                $documentArchives = $documentArchives ?? collect();
                $driverDocumentFields = \App\Services\DriverPersistenceService::DOCUMENT_FIELD_LABELS;
            @endphp
            <div class="row">
                @foreach($driverDocumentFields as $field => $label)
                    <div class="col-md-4">
                        <div class="mb-2">
                            <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                            <input type="file" name="{{ $field }}" id="{{ $field }}"
                                   class="form-control @error($field) is-invalid @enderror"
                                   accept=".pdf,.jpg,.jpeg,.png">
                            @if($isDriverEdit && $model->{$field})
                                @include('components.car-document-actions', [
                                    'viewUrl' => asset('uploads/driver_licenses/' . $model->{$field}),
                                    'removeUrl' => route('drivers.documents.destroy', [$model, $field]),
                                    'label' => $label,
                                ])
                            @endif
                            @error($field)
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Supported: PDF, JPG, JPEG, PNG. Max: 2MB</small>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($isDriverEdit && $documentArchives->isNotEmpty())
                @once
                    @push('css')
                        <style>
                            .driver-archive-trigger {
                                display: inline-flex;
                                align-items: center;
                                gap: 0.65rem;
                                margin-top: 0.75rem;
                                padding: 0.5rem 0.75rem;
                                border: 1px solid rgba(115, 103, 240, 0.22);
                                border-radius: 0.428rem;
                                background: linear-gradient(135deg, rgba(115, 103, 240, 0.1) 0%, rgba(115, 103, 240, 0.04) 100%);
                                color: #5e50ee;
                                text-align: left;
                                transition: all 0.2s ease;
                            }

                            .driver-archive-trigger:hover,
                            .driver-archive-trigger:focus {
                                color: #4839eb;
                                background: linear-gradient(135deg, rgba(115, 103, 240, 0.16) 0%, rgba(115, 103, 240, 0.08) 100%);
                                border-color: rgba(115, 103, 240, 0.35);
                                box-shadow: 0 4px 18px rgba(115, 103, 240, 0.12);
                                outline: none;
                            }

                            .driver-archive-trigger__main {
                                display: flex;
                                align-items: center;
                                min-width: 0;
                            }

                            .driver-archive-trigger__icon {
                                display: inline-flex;
                                align-items: center;
                                justify-content: center;
                                width: 1.75rem;
                                height: 1.75rem;
                                margin-right: 0.5rem;
                                border-radius: 0.35rem;
                                background: #7367f0;
                                color: #fff;
                                flex-shrink: 0;
                                font-size: 0.75rem;
                            }

                            .driver-archive-trigger__label {
                                display: block;
                                font-size: 0.875rem;
                                font-weight: 600;
                                line-height: 1.2;
                                color: #4b4b4b;
                            }

                            .driver-archive-trigger__meta {
                                display: block;
                                margin-top: 0.1rem;
                                font-size: 0.7rem;
                                color: #6e6b7b;
                            }

                            .driver-archive-trigger__badge {
                                min-width: 1.35rem;
                                padding: 0.2rem 0.45rem;
                                font-size: 0.7rem;
                                font-weight: 600;
                                border-radius: 999px;
                                background: #7367f0;
                                color: #fff;
                                flex-shrink: 0;
                            }
                        </style>
                    @endpush
                @endonce
                <button type="button"
                        class="driver-archive-trigger"
                        data-toggle="modal"
                        data-target="#driverDocumentArchiveModal">
                    <span class="driver-archive-trigger__main">
                        <span class="driver-archive-trigger__icon" aria-hidden="true">
                            <i class="fa fa-archive"></i>
                        </span>
                        <span>
                            <span class="driver-archive-trigger__label">View Archive</span>
                            <span class="driver-archive-trigger__meta">
                                {{ $documentArchives->count() }} archived {{ $documentArchives->count() === 1 ? 'file' : 'files' }}
                            </span>
                        </span>
                    </span>
                    <span class="driver-archive-trigger__badge">{{ $documentArchives->count() }}</span>
                </button>
            @endif
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card mb-1">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fa fa-toggle-on me-2"></i>
                    Status
                </h5>
            </div>
            <div class="card-body">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" name="is_active" id="is_active"
                           class="custom-control-input"
                        {{ old('is_active', $model->is_active ?? true) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="is_active">
                        <strong>Active Driver</strong>
                    </label>
                    <small class="form-text text-muted d-block">
                        Uncheck to mark this driver as inactive. Inactive drivers will not appear in notifications or new agreement/reservation selections.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

@if(!empty($isDriverEdit))
    @include('components.fleetiq-delete-confirm-modal')
    @if(($documentArchives ?? collect())->isNotEmpty())
        @include('components.driver-document-archive-modal', ['documentArchives' => $documentArchives])
    @endif
@endif

<!-- Form Actions -->
@if (empty($hideFormActions))
<div class="row">
    <div class="col-12">
        <div class="form-group">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i>
                {{ isset($model->id) ? 'Update Driver' : 'Create Driver' }}
            </button>
            <a href="{{ route($url . 'index') }}" class="btn btn-secondary ml-2">
                <i class="fa fa-times"></i> Cancel
            </a>
        </div>
    </div>
</div>
@endif

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // NI Number formatting (UK format: AB123456C)
            const niField = document.getElementById('ni_number');
            if (niField) {
                niField.addEventListener('input', function () {
                    this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                    if (this.value.length > 9) {
                        this.value = this.value.substring(0, 9);
                    }
                });
            }

            // Format postcode automatically
            const postcodeField = document.getElementById('post_code');
            if (postcodeField) {
                postcodeField.addEventListener('input', function () {
                    let value = this.value.replace(/\s+/g, '').toUpperCase();
                    if (value.length > 3) {
                        value = value.substring(0, value.length - 3) + ' ' + value.substring(value.length - 3);
                    }
                    this.value = value;
                });
            }

            // Format phone numbers
            const phoneFields = ['phone_number', 'next_of_kin_phone'];
            phoneFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('input', function () {
                        let value = this.value.replace(/[^\d\+\-\s\(\)]/g, '');
                        this.value = value;
                    });
                }
            });

            // Email validation
            const emailField = document.getElementById('email');
            if (emailField) {
                emailField.addEventListener('blur', function () {
                    const email = this.value.trim();
                    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                    this.classList.remove('is-invalid');
                    const existingFeedback = this.parentNode.querySelector('.custom-invalid-feedback');
                    if (existingFeedback) {
                        existingFeedback.remove();
                    }

                    if (email && !emailPattern.test(email)) {
                        this.classList.add('is-invalid');
                        const feedback = document.createElement('div');
                        feedback.className = 'invalid-feedback custom-invalid-feedback';
                        feedback.textContent = 'Please enter a valid email address.';
                        this.parentNode.appendChild(feedback);
                    }
                });
            }


            // File upload validation
            const fileFields = @json(array_keys($driverDocumentFields ?? []));

            fileFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('change', function (e) {
                        const file = e.target.files[0];
                        if (file) {
                            // Validate file size (2MB)
                            if (file.size > 2 * 1024 * 1024) {
                                alert('File size must be less than 2MB');
                                this.value = '';
                                return;
                            }

                            // Validate file type
                            const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
                            if (!allowedTypes.includes(file.type)) {
                                alert('Please upload a valid file (PDF, JPG, JPEG, PNG)');
                                this.value = '';
                                return;
                            }
                        }
                    });
                }
            });

            // Auto-capitalize names
            const nameFields = ['first_name', 'middle_name', 'last_name', 'next_of_kin'];
            nameFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('blur', function () {
                        this.value = this.value.replace(/\b\w/g, l => l.toUpperCase());
                    });
                }
            });

            // Auto-capitalize address fields
            const addressFields = ['address1', 'address2', 'town'];
            addressFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('blur', function () {
                        this.value = this.value.replace(/\b\w/g, l => l.toUpperCase());
                    });
                }
            });

            // License number validation
            const licenseField = document.getElementById('driver_license_number');
            if (licenseField) {
                licenseField.addEventListener('input', function () {
                    this.value = this.value.toUpperCase();
                });
            }

            const phdLicenseField = document.getElementById('phd_license_number');
            if (phdLicenseField) {
                phdLicenseField.addEventListener('input', function () {
                    this.value = this.value.toUpperCase();
                });
            }

            @if(!empty($isDriverEdit))
            (function () {
                var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                var pending = { url: null };
                var $modal = window.jQuery;
                var confirmBtn = document.getElementById('fleetiqDeleteConfirmBtn');
                var titleEl = document.getElementById('fleetiqDeleteConfirmTitle');
                var bodyEl = document.getElementById('fleetiqDeleteConfirmBody');
                var btnTextEl = document.getElementById('fleetiqDeleteConfirmBtnText');

                document.addEventListener('click', function (e) {
                    var btn = e.target.closest('.car-doc-remove-btn');
                    if (!btn) {
                        return;
                    }

                    e.preventDefault();
                    pending.url = btn.getAttribute('data-remove-url');
                    var label = btn.getAttribute('data-doc-label') || 'document';

                    if (titleEl) {
                        titleEl.textContent = 'Remove document?';
                    }
                    if (bodyEl) {
                        bodyEl.textContent = 'Are you sure you want to remove this ' + label + '? The file will be deleted. You can upload a new file afterwards.';
                    }
                    if (btnTextEl) {
                        btnTextEl.textContent = 'Yes, remove document';
                    }
                    if ($modal && $modal.fn && $modal.fn.modal) {
                        $modal('#fleetiqDeleteConfirmModal').modal('show');
                    }
                });

                if (confirmBtn) {
                    confirmBtn.addEventListener('click', function () {
                        if (!pending.url) {
                            return;
                        }

                        confirmBtn.disabled = true;
                        fetch(pending.url, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                        }).then(function (response) {
                            if (!response.ok) {
                                throw new Error();
                            }

                            return response.json();
                        }).then(function () {
                            if ($modal && $modal.fn && $modal.fn.modal) {
                                $modal('#fleetiqDeleteConfirmModal').modal('hide');
                            }
                            window.location.reload();
                        }).catch(function () {
                            alert('Could not remove this document. Please try again.');
                        }).finally(function () {
                            confirmBtn.disabled = false;
                        });
                    });
                }
            })();
            @endif
        });
    </script>
@endpush
