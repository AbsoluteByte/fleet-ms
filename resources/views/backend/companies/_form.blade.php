{{-- resources/views/companies/_form.blade.php --}}

<div class="row">
    {{-- Company Name --}}
    <div class="col-md-6 mb-3">
        <label for="name" class="form-label fw-bold">Company Name <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name"
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name') ?? ($model->name ?? '') }}"
               placeholder="Enter Company Name" required>
        @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Director Name --}}
    <div class="col-md-6 mb-3">
        <label for="director_name" class="form-label fw-bold">Director Name <span class="text-danger">*</span></label>
        <input type="text" name="director_name" id="director_name"
               class="form-control @error('director_name') is-invalid @enderror"
               value="{{ old('director_name') ?? ($model->director_name ?? '') }}"
               placeholder="Enter Director Name" required>
        @error('director_name')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Company Logo --}}
    <div class="col-md-12 mb-4">
        <label for="logo" class="form-label fw-bold">Company Logo</label>
        <input type="file" name="logo" id="logo"
               class="form-control @error('logo') is-invalid @enderror"
               accept=".jpg,.jpeg,.png,.gif">
        @if(isset($model) && $model->logo)
            <div class="mt-2">
                <span class="text-muted d-block mb-1">Current Logo:</span>
                <a href="{{ asset('uploads/companies/' . $model->logo) }}" target="_blank">
                    <img src="{{ asset('uploads/companies/' . $model->logo) }}" alt="Company Logo"
                         class="img-thumbnail" style="max-width: 150px; max-height: 100px;">
                </a>
            </div>
        @endif
        @error('logo')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="form-text text-muted">Supported: JPG, JPEG, PNG, GIF. Max: 2MB</small>
    </div>

    {{-- Address Line 1 --}}
    <div class="col-md-6 mb-3">
        <label for="address_line_1" class="form-label fw-bold">Address Line 1 <span class="text-danger">*</span></label>
        <input type="text" name="address_line_1" id="address_line_1"
               class="form-control @error('address_line_1') is-invalid @enderror"
               value="{{ old('address_line_1') ?? ($model->address_line_1 ?? '') }}"
               placeholder="Enter Address Line 1" required>
        @error('address_line_1')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Address Line 2 --}}
    <div class="col-md-6 mb-3">
        <label for="address_line_2" class="form-label fw-bold">Address Line 2</label>
        <input type="text" name="address_line_2" id="address_line_2"
               class="form-control @error('address_line_2') is-invalid @enderror"
               value="{{ old('address_line_2') ?? ($model->address_line_2 ?? '') }}"
               placeholder="Enter Address Line 2 (Optional)">
        @error('address_line_2')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Postcode --}}
    <div class="col-md-4 mb-3">
        <label for="postcode" class="form-label fw-bold">Postcode <span class="text-danger">*</span></label>
        <input type="text" name="postcode" id="postcode"
               class="form-control @error('postcode') is-invalid @enderror"
               value="{{ old('postcode') ?? ($model->postcode ?? '') }}"
               placeholder="e.g. SW1A 1AA" required>
        @error('postcode')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Town --}}
    <div class="col-md-4 mb-3">
        <label for="town" class="form-label fw-bold">Town <span class="text-danger">*</span></label>
        <input type="text" name="town" id="town"
               class="form-control @error('town') is-invalid @enderror"
               value="{{ old('town') ?? ($model->town ?? '') }}"
               placeholder="Enter Town" required>
        @error('town')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- County --}}
    <div class="col-md-4 mb-3">
        <label for="county" class="form-label fw-bold">County <span class="text-danger">*</span></label>
        <input type="text" name="county" id="county"
               class="form-control @error('county') is-invalid @enderror"
               value="{{ old('county') ?? ($model->county ?? '') }}"
               placeholder="Enter County" required>
        @error('county')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Country --}}
    <div class="col-md-6 mb-3">
        <label for="country_id" class="form-label fw-bold">Country <span class="text-danger">*</span></label>
        <select name="country_id" id="country_id"
                class="form-control @error('country_id') is-invalid @enderror" required>
            <option value="">-- Select Country --</option>
            @php
                try {
                    $countries = \App\Models\Country::select('name', 'id')->get()->pluck('name', 'id');
                    $selectedCountry = old('country_id') ?? ($model->country_id ?? '');
                } catch (\Exception $e) {
                    $countries = collect();
                    $selectedCountry = old('country_id') ?? '';
                }
            @endphp
            @foreach($countries as $id => $name)
                <option value="{{ $id }}" {{ $selectedCountry == $id ? 'selected' : '' }}>
                    {{ $name }}
                </option>
            @endforeach
        </select>
        @error('country_id')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Phone --}}
    <div class="col-md-6 mb-3">
        <label for="phone" class="form-label fw-bold">Phone Number <span class="text-danger">*</span></label>
        <input type="text" name="phone" id="phone"
               class="form-control @error('phone') is-invalid @enderror"
               value="{{ old('phone') ?? ($model->phone ?? '') }}"
               placeholder="e.g. +44 20 7946 0958" required>
        @error('phone')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Email --}}
    <div class="col-md-12 mb-4">
        <label for="email" class="form-label fw-bold">Email Address <span class="text-danger">*</span></label>
        <input type="email" name="email" id="email"
               class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email') ?? ($model->email ?? '') }}"
               placeholder="e.g. info@company.com" required>
        @error('email')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

{{-- Submit Buttons --}}
<div class="row mt-4">
    <div class="col-12">
        <div class="form-group">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i>
                {{ isset($model->id) ? 'Update Company' : 'Create Company' }}
            </button>
            <a href="{{ route($url . 'index') }}" class="btn btn-secondary ml-2">
                <i class="fa fa-times"></i> Cancel
            </a>
        </div>
    </div>
</div>

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Format postcode automatically
            const postcodeField = document.getElementById('postcode');
            if (postcodeField) {
                postcodeField.addEventListener('input', function() {
                    let value = this.value.replace(/\s+/g, '').toUpperCase();
                    if (value.length > 3) {
                        value = value.substring(0, value.length - 3) + ' ' + value.substring(value.length - 3);
                    }
                    this.value = value;
                });
            }

            // Format phone number
            const phoneField = document.getElementById('phone');
            if (phoneField) {
                phoneField.addEventListener('input', function() {
                    // Allow numbers, +, -, spaces, and parentheses
                    let value = this.value.replace(/[^\d\+\-\s\(\)]/g, '');
                    this.value = value;
                });
            }

            // Email validation
            const emailField = document.getElementById('email');
            if (emailField) {
                emailField.addEventListener('blur', function() {
                    const email = this.value.trim();
                    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                    // Remove any existing custom validation
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

            // Preview uploaded logo
            const logoField = document.getElementById('logo');
            if (logoField) {
                logoField.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        // Validate file size (2MB = 2 * 1024 * 1024 bytes)
                        if (file.size > 2 * 1024 * 1024) {
                            alert('File size must be less than 2MB');
                            this.value = '';
                            return;
                        }

                        // Validate file type
                        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                        if (!allowedTypes.includes(file.type)) {
                            alert('Please upload a valid image file (JPG, JPEG, PNG, GIF)');
                            this.value = '';
                            return;
                        }

                        const reader = new FileReader();
                        reader.onload = function(e) {
                            // Remove existing preview
                            const existingPreview = document.querySelector('.logo-preview');
                            if (existingPreview) {
                                existingPreview.remove();
                            }

                            // Create new preview
                            const previewContainer = document.createElement('div');
                            previewContainer.className = 'mt-2 logo-preview';

                            const previewLabel = document.createElement('span');
                            previewLabel.className = 'text-muted d-block mb-1';
                            previewLabel.textContent = 'Preview:';

                            const preview = document.createElement('img');
                            preview.src = e.target.result;
                            preview.className = 'img-thumbnail';
                            preview.style.maxWidth = '150px';
                            preview.style.maxHeight = '100px';

                            previewContainer.appendChild(previewLabel);
                            previewContainer.appendChild(preview);

                            // Insert after the file input
                            logoField.parentNode.appendChild(previewContainer);
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            // Auto-capitalize company and director names
            const nameField = document.getElementById('name');
            if (nameField) {
                nameField.addEventListener('blur', function() {
                    this.value = this.value.replace(/\b\w/g, l => l.toUpperCase());
                });
            }

            const directorField = document.getElementById('director_name');
            if (directorField) {
                directorField.addEventListener('blur', function() {
                    this.value = this.value.replace(/\b\w/g, l => l.toUpperCase());
                });
            }

            // Auto-capitalize address fields
            const townField = document.getElementById('town');
            if (townField) {
                townField.addEventListener('blur', function() {
                    this.value = this.value.replace(/\b\w/g, l => l.toUpperCase());
                });
            }

            const countyField = document.getElementById('county');
            if (countyField) {
                countyField.addEventListener('blur', function() {
                    this.value = this.value.replace(/\b\w/g, l => l.toUpperCase());
                });
            }
        });
    </script>
@endsection
