{{-- resources/views/backend/customers/_form.blade.php --}}
<div class="row">
    <div class="col-sm-6">
        <div class="form-group">
            <label for="company_name">Company Name *</label>
            <input type="text"
                   name="company_name"
                   id="company_name"
                   class="form-control @error('company_name') is-invalid @enderror"
                   value="{{ old('company_name', $tenant->company_name ?? '') }}"
                   required>
            @error('company_name')
            <div class="small text-danger">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-sm-6">
        <div class="form-group">
            <label for="name">Admin Name *</label>
            <input type="text"
                   name="name"
                   id="name"
                   class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name', $model->name ?? '') }}"
                   required>
            @error('name')
            <div class="small text-danger">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-sm-6">
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email"
                   name="email"
                   id="email"
                   class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email', $model->email ?? '') }}"
                   required>
            @error('email')
            <div class="small text-danger">{{ $message }}</div>
            @enderror
        </div>
    </div>

    @if (Request::is('admin/customers/create'))
        {{-- ========== CREATE MODE ========== --}}
        <div class="col-sm-6">
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password"
                       name="password"
                       id="password"
                       class="form-control @error('password') is-invalid @enderror"
                       required>
                @error('password')
                <div class="small text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="col-sm-6">
            <div class="form-group">
                <label for="password_confirmation">Confirm Password *</label>
                <input type="password"
                       name="password_confirmation"
                       id="password_confirmation"
                       class="form-control @error('password_confirmation') is-invalid @enderror"
                       required>
                @error('password_confirmation')
                <div class="small text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="col-sm-6">
            <div class="form-group">
                <label for="package_id">Select Package *</label>
                <select name="package_id"
                        id="package_id"
                        class="form-control @error('package_id') is-invalid @enderror"
                        required>
                    <option value="">-- Select Package --</option>
                    @foreach($packages as $package)
                        <option value="{{ $package->id }}"
                            {{ old('package_id') == $package->id ? 'selected' : '' }}>
                            {{ $package->name }} - £{{ number_format($package->price, 2) }}/{{ $package->billing_period }}
                            ({{ $package->trial_days }} days trial)
                        </option>
                    @endforeach
                </select>
                @error('package_id')
                <div class="small text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    @else
        {{-- ========== EDIT MODE ========== --}}
        <div class="col-sm-6">
            <div class="form-group">
                <label for="password">New Password (optional)</label>
                <input type="password"
                       name="password"
                       id="password"
                       class="form-control @error('password') is-invalid @enderror"
                       placeholder="Leave blank to keep current password">
                <small class="text-muted">Leave blank if you don't want to change password</small>
                @error('password')
                <div class="small text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="col-sm-6">
            <div class="form-group">
                <label for="password_confirmation">Confirm New Password</label>
                <input type="password"
                       name="password_confirmation"
                       id="password_confirmation"
                       class="form-control @error('password_confirmation') is-invalid @enderror"
                       placeholder="Confirm new password">
                @error('password_confirmation')
                <div class="small text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="col-sm-6">
            <div class="form-group">
                <label for="package_id">Change Package</label>
                <select name="package_id" id="package_id" class="form-control">
                    <option value="">-- Keep Current Package --</option>
                    @foreach($packages as $package)
                        <option value="{{ $package->id }}"
                            {{ (isset($tenant->subscription) && $tenant->subscription->package_id == $package->id) ? 'selected' : '' }}>
                            {{ $package->name }} - £{{ number_format($package->price, 2) }}/{{ $package->billing_period }}
                        </option>
                    @endforeach
                </select>
                <small class="text-muted">
                    Current:
                    @if(isset($tenant->subscription))
                        <strong>{{ $tenant->subscription->package->name }}</strong>
                    @else
                        N/A
                    @endif
                </small>
            </div>
        </div>
    @endif
</div>

<div class="row mt-3">
    <div class="col-12">
        <button type="submit" class="btn btn-primary waves-effect waves-light">
            <i class="fa fa-save mr-1"></i>
            {{ Request::is('admin/customers/create') ? 'Create Customer' : 'Update Customer' }}
        </button>
        <a href="{{ route($url . 'index') }}" class="btn btn-secondary waves-effect">
            <i class="fa fa-times mr-1"></i> Cancel
        </a>
    </div>
</div>

@push('js')
    <script>
        // Show package details on selection
        $('#package_id').on('change', function() {
            const packageId = $(this).val();
            // You can add package feature details here if needed
            console.log('Selected package:', packageId);
        });
    </script>
@endpush
