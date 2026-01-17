<div class="row">
    <!-- Basic Information -->
    <div class="col-md-6">
        <div class="form-group">
            <label for="name">Package Name *</label>
            <input type="text" name="name" id="name"
                   class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name', $model->name ?? '') }}" required>
            @error('name')
            <div class="small text-danger">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="price">Price (£) *</label>
            <input type="number" name="price" id="price" step="0.01"
                   class="form-control @error('price') is-invalid @enderror"
                   value="{{ old('price', $model->price ?? '') }}" required>
            @error('price')
            <div class="small text-danger">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="billing_period">Billing Period *</label>
            <select name="billing_period" id="billing_period"
                    class="form-control @error('billing_period') is-invalid @enderror" required>
                <option value="">-- Select Period --</option>
                <option value="monthly" {{ old('billing_period', $model->billing_period ?? '') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                <option value="quarterly" {{ old('billing_period', $model->billing_period ?? '') == 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                <option value="yearly" {{ old('billing_period', $model->billing_period ?? '') == 'yearly' ? 'selected' : '' }}>Yearly</option>
            </select>
            @error('billing_period')
            <div class="small text-danger">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="trial_days">Trial Days *</label>
            <input type="number" name="trial_days" id="trial_days"
                   class="form-control @error('trial_days') is-invalid @enderror"
                   value="{{ old('trial_days', $model->trial_days ?? 30) }}" required>
            @error('trial_days')
            <div class="small text-danger">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-12">
        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" rows="3"
                      class="form-control @error('description') is-invalid @enderror">{{ old('description', $model->description ?? '') }}</textarea>
            @error('description')
            <div class="small text-danger">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <!-- Limits -->
    <div class="col-12">
        <h5 class="mt-3 mb-3">Resource Limits</h5>
        <small class="text-muted">Use -1 for unlimited</small>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="max_users">Max Users *</label>
            <input type="number" name="max_users" id="max_users"
                   class="form-control @error('max_users') is-invalid @enderror"
                   value="{{ old('max_users', $model->max_users ?? 5) }}" required>
            @error('max_users')
            <div class="small text-danger">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="max_vehicles">Max Vehicles *</label>
            <input type="number" name="max_vehicles" id="max_vehicles"
                   class="form-control @error('max_vehicles') is-invalid @enderror"
                   value="{{ old('max_vehicles', $model->max_vehicles ?? 10) }}" required>
            @error('max_vehicles')
            <div class="small text-danger">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="max_drivers">Max Drivers *</label>
            <input type="number" name="max_drivers" id="max_drivers"
                   class="form-control @error('max_drivers') is-invalid @enderror"
                   value="{{ old('max_drivers', $model->max_drivers ?? 10) }}" required>
            @error('max_drivers')
            <div class="small text-danger">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <!-- Features -->
    <div class="col-12">
        <h5 class="mt-3 mb-3">Features</h5>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" name="has_notifications" id="has_notifications"
                       class="custom-control-input"
                    {{ old('has_notifications', $model->has_notifications ?? true) ? 'checked' : '' }}>
                <label class="custom-control-label" for="has_notifications">
                    <i class="fa fa-bell"></i> Email & SMS Notifications
                </label>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" name="has_reports" id="has_reports"
                       class="custom-control-input"
                    {{ old('has_reports', $model->has_reports ?? false) ? 'checked' : '' }}>
                <label class="custom-control-label" for="has_reports">
                    <i class="fa fa-chart-bar"></i> Advanced Reports
                </label>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" name="has_api_access" id="has_api_access"
                       class="custom-control-input"
                    {{ old('has_api_access', $model->has_api_access ?? false) ? 'checked' : '' }}>
                <label class="custom-control-label" for="has_api_access">
                    <i class="fa fa-code"></i> API Access
                </label>
            </div>
        </div>
    </div>

    <!-- Additional Features -->
    <div class="col-12">
        <h5 class="mt-3 mb-3">Additional Features</h5>
        <small class="text-muted">Add custom features (one per line)</small>
    </div>

    <div class="col-12">
        <div id="features-container">
            @php
                $existingFeatures = old('features', $model->features ?? []);
                if (empty($existingFeatures)) {
                    $existingFeatures = [''];
                }
            @endphp

            @foreach($existingFeatures as $index => $feature)
                <div class="feature-item mb-2">
                    <div class="input-group">
                        <input type="text" name="features[]"
                               class="form-control"
                               placeholder="e.g., Priority Support"
                               value="{{ $feature }}">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-danger remove-feature"
                                    onclick="removeFeature(this)">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addFeature()">
            <i class="fa fa-plus"></i> Add Feature
        </button>
    </div>

    <!-- Status -->
    <div class="col-12">
        <h5 class="mt-3 mb-3">Status</h5>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" name="is_active" id="is_active"
                       class="custom-control-input"
                    {{ old('is_active', $model->is_active ?? true) ? 'checked' : '' }}>
                <label class="custom-control-label" for="is_active">
                    <strong>Active Package</strong>
                </label>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <button type="submit" class="btn btn-primary waves-effect waves-light">
            <i class="fa fa-save mr-1"></i> Submit
        </button>
        <a href="{{ route($url . 'index') }}" class="btn btn-secondary waves-effect">
            <i class="fa fa-times mr-1"></i> Cancel
        </a>
    </div>
</div>

@push('js')
    <script>
        let featureIndex = {{ count($existingFeatures ?? []) }};

        function addFeature() {
            const container = document.getElementById('features-container');
            const newFeature = `
            <div class="feature-item mb-2">
                <div class="input-group">
                    <input type="text" name="features[]"
                           class="form-control"
                           placeholder="e.g., Priority Support">
                    <div class="input-group-append">
                        <button type="button" class="btn btn-danger remove-feature"
                                onclick="removeFeature(this)">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
            container.insertAdjacentHTML('beforeend', newFeature);
            featureIndex++;
        }

        function removeFeature(button) {
            button.closest('.feature-item').remove();
        }
    </script>
@endpush
