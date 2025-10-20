<div class="row">
    <div class="col-md-12 mb-2">
        <label for="name" class="form-label">Name *</label>
        <input type="text" name="name" id="name"
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name') ?? ($model->name ?? '') }}"
               placeholder="Enter Car Model Name" required>
        @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<!-- Form Actions -->
<div class="row">
    <div class="col-12">
        <div class="form-group">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i>
                {{ isset($model->id) ? 'Update Counsels' : 'Create Counsels' }}
            </button>
            <a href="{{ route($url . 'index') }}" class="btn btn-secondary ml-2">
                <i class="fa fa-times"></i> Cancel
            </a>
        </div>
    </div>
</div>
