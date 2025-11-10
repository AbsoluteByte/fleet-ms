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

                <div class="col-md-3">
                    <div class="mb-2">
                        <label for="county" class="form-label">County *</label>
                        <input type="text" name="county" id="county"
                               class="form-control @error('county') is-invalid @enderror"
                               value="{{ old('county') ?? ($model->county ?? '') }}"
                               placeholder="Enter County" required>
                        @error('county')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="mb-2">
                        <label for="country" class="form-label">Country *</label>
                        <select name="country_id" id="country"
                                class="form-control @error('country') is-invalid @enderror" required>
                            <option value="">Select Country</option>
                            @php
                                try {
                                    $countries = \App\Models\Country::select('name', 'id')->get()->pluck('name', 'id');
                                    $selectedCountry = old('country_id') ?? ($model->country_id ?? '');
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
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-2">
                        <label for="driver_license_document" class="form-label">Driver License Document</label>
                        <input type="file" name="driver_license_document" id="driver_license_document"
                               class="form-control @error('driver_license_document') is-invalid @enderror"
                               accept=".pdf,.jpg,.jpeg,.png">
                        @if(isset($model) && $model->driver_license_document)
                            <div class="mt-2">
                                <span class="text-muted d-block mb-1">Current Document:</span>
                                <a href="{{ asset('uploads/driver_licenses/' . $model->driver_license_document) }}"
                                   target="_blank" class="btn btn-sm btn-outline-info">
                                    <i class="fa fa-eye"></i> View Document
                                </a>
                            </div>
                        @endif
                        @error('driver_license_document')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Supported: PDF, JPG, JPEG, PNG. Max: 2MB</small>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-2">
                        <label for="driver_phd_license_document" class="form-label">PHD License Document</label>
                        <input type="file" name="driver_phd_license_document" id="driver_phd_license_document"
                               class="form-control @error('driver_phd_license_document') is-invalid @enderror"
                               accept=".pdf,.jpg,.jpeg,.png">
                        @if(isset($model) && $model->driver_phd_license_document)
                            <div class="mt-2">
                                <span class="text-muted d-block mb-1">Current Document:</span>
                                <a href="{{ asset('uploads/driver_licenses/' . $model->driver_phd_license_document) }}"
                                   target="_blank" class="btn btn-sm btn-outline-info">
                                    <i class="fa fa-eye"></i> View Document
                                </a>
                            </div>
                        @endif
                        @error('driver_phd_license_document')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Supported: PDF, JPG, JPEG, PNG. Max: 2MB</small>
                    </div>
                </div>

                {{-- NEW FIELD: PHD Card Document --}}
                <div class="col-md-4">
                    <div class="mb-2">
                        <label for="phd_card_document" class="form-label">PHD Card Document</label>
                        <input type="file" name="phd_card_document" id="phd_card_document"
                               class="form-control @error('phd_card_document') is-invalid @enderror"
                               accept=".pdf,.jpg,.jpeg,.png">
                        @if(isset($model) && $model->phd_card_document)
                            <div class="mt-2">
                                <span class="text-muted d-block mb-1">Current Document:</span>
                                <a href="{{ asset('uploads/driver_licenses/' . $model->phd_card_document) }}"
                                   target="_blank" class="btn btn-sm btn-outline-info">
                                    <i class="fa fa-eye"></i> View Document
                                </a>
                            </div>
                        @endif
                        @error('phd_card_document')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Supported: PDF, JPG, JPEG, PNG. Max: 2MB</small>
                    </div>
                </div>

                {{-- NEW FIELD: DVLA License Summary --}}
                <div class="col-md-4">
                    <div class="mb-2">
                        <label for="dvla_license_summary" class="form-label">DVLA License Summary</label>
                        <input type="file" name="dvla_license_summary" id="dvla_license_summary"
                               class="form-control @error('dvla_license_summary') is-invalid @enderror"
                               accept=".pdf,.jpg,.jpeg,.png">
                        @if(isset($model) && $model->dvla_license_summary)
                            <div class="mt-2">
                                <span class="text-muted d-block mb-1">Current Document:</span>
                                <a href="{{ asset('uploads/driver_licenses/' . $model->dvla_license_summary) }}"
                                   target="_blank" class="btn btn-sm btn-outline-info">
                                    <i class="fa fa-eye"></i> View Document
                                </a>
                            </div>
                        @endif
                        @error('dvla_license_summary')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Supported: PDF, JPG, JPEG, PNG. Max: 2MB</small>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-2">
                        <label for="proof_of_address_document" class="form-label">Proof of Address</label>
                        <input type="file" name="proof_of_address_document" id="proof_of_address_document"
                               class="form-control @error('proof_of_address_document') is-invalid @enderror"
                               accept=".pdf,.jpg,.jpeg,.png">
                        @if(isset($model) && $model->proof_of_address_document)
                            <div class="mt-2">
                                <span class="text-muted d-block mb-1">Current Document:</span>
                                <a href="{{ asset('uploads/driver_licenses/' . $model->proof_of_address_document) }}"
                                   target="_blank" class="btn btn-sm btn-outline-info">
                                    <i class="fa fa-eye"></i> View Document
                                </a>
                            </div>
                        @endif
                        @error('proof_of_address_document')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Supported: PDF, JPG, JPEG, PNG. Max: 2MB</small>
                    </div>
                </div>

                {{-- NEW FIELD: Misc Document --}}
                <div class="col-md-4">
                    <div class="mb-2">
                        <label for="misc_document" class="form-label">Miscellaneous Document</label>
                        <input type="file" name="misc_document" id="misc_document"
                               class="form-control @error('misc_document') is-invalid @enderror"
                               accept=".pdf,.jpg,.jpeg,.png">
                        @if(isset($model) && $model->misc_document)
                            <div class="mt-2">
                                <span class="text-muted d-block mb-1">Current Document:</span>
                                <a href="{{ asset('uploads/driver_licenses/' . $model->misc_document) }}"
                                   target="_blank" class="btn btn-sm btn-outline-info">
                                    <i class="fa fa-eye"></i> View Document
                                </a>
                            </div>
                        @endif
                        @error('misc_document')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Supported: PDF, JPG, JPEG, PNG. Max: 2MB</small>
                    </div>
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
                {{ isset($model->id) ? 'Update Driver' : 'Create Driver' }}
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

            // Date validation for driver license
            const licenseExpiryField = document.getElementById('driver_license_expiry_date');
            if (licenseExpiryField) {
                licenseExpiryField.addEventListener('change', function () {
                    const selectedDate = new Date(this.value);
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);

                    if (selectedDate <= today) {
                        alert('Driver license expiry date must be in the future.');
                        this.value = '';
                    }
                });
            }

            // Date validation for PHD license
            const phdExpiryField = document.getElementById('phd_license_expiry_date');
            if (phdExpiryField) {
                phdExpiryField.addEventListener('change', function () {
                    if (this.value) {
                        const selectedDate = new Date(this.value);
                        const today = new Date();
                        today.setHours(0, 0, 0, 0);

                        if (selectedDate <= today) {
                            alert('PHD license expiry date must be in the future.');
                            this.value = '';
                        }
                    }
                });
            }

            // Age validation for DOB
            const dobField = document.getElementById('dob');
            if (dobField) {
                dobField.addEventListener('change', function () {
                    const dob = new Date(this.value);
                    const today = new Date();
                    let age = today.getFullYear() - dob.getFullYear();
                    const monthDiff = today.getMonth() - dob.getMonth();

                    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                        age--;
                    }

                    if (age < 18) {
                        alert('Driver must be at least 18 years old.');
                        this.value = '';
                    } else if (age > 100) {
                        alert('Please enter a valid date of birth.');
                        this.value = '';
                    }
                });
            }

            // File upload validation
            const fileFields = [
                'driver_license_document',
                'driver_phd_license_document',
                'phd_card_document',
                'dvla_license_summary',
                'misc_document',
                'proof_of_address_document'
            ];

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
            const addressFields = ['address1', 'address2', 'town', 'county'];
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
        });
    </script>
@endpush
