<div class="row">
    <div class="col-md-6 form-group">
        <label for="company_id">Company <span class="text-danger">*</span></label>
        <select name="company_id" id="company_id"
                class="form-control @error('company_id') is-invalid @enderror" required>
            <option value="">Select Company</option>
            @foreach($companies as $company)
                <option value="{{ $company->id }}"
                    {{ (string) old('company_id', $model->company_id ?? '') === (string) $company->id ? 'selected' : '' }}>
                    {{ $company->name }}
                </option>
            @endforeach
        </select>
        @error('company_id')
        <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 form-group">
        <label for="bank_name">Bank Name <span class="text-danger">*</span></label>
        <input type="text" name="bank_name" id="bank_name"
               class="form-control @error('bank_name') is-invalid @enderror"
               value="{{ old('bank_name', $model->bank_name ?? '') }}"
               placeholder="Enter bank name" required>
        @error('bank_name')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 form-group">
        <label for="short_name">Short Name</label>
        <input type="text" name="short_name" id="short_name"
               class="form-control @error('short_name') is-invalid @enderror"
               value="{{ old('short_name', $model->short_name ?? '') }}"
               placeholder="Short label shown on payments (optional)">
        @error('short_name')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 form-group">
        <label for="account_number">Account Number <span class="text-danger">*</span></label>
        <input type="text" name="account_number" id="account_number"
               class="form-control @error('account_number') is-invalid @enderror"
               value="{{ old('account_number', $model->account_number ?? '') }}"
               placeholder="Enter account number" required>
        @error('account_number')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="form-group mb-0">
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-save"></i>
        {{ isset($model->id) ? 'Update Bank Account' : 'Create Bank Account' }}
    </button>
    <a href="{{ route($url . 'index') }}" class="btn btn-secondary ml-2">
        <i class="fa fa-times"></i> Cancel
    </a>
</div>
