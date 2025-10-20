{{-- resources/views/penalties/_form.blade.php --}}

<div class="row">
    {{-- Select Agreement --}}
    <div class="col-md-12">
        <div class="form-group">
            <label for="agreement_id">Select Agreement <span class="text-danger">*</span></label>
            <select name="agreement_id" id="agreement_id"
                    class="form-control @error('agreement_id') is-invalid @enderror" required>
                <option value="">Select Agreement</option>
                @foreach($agreements as $agreement)
                    <option value="{{ $agreement->id }}"
                        {{ (old('agreement_id') ?? (isset($model) ? $model->agreement_id : '')) == $agreement->id ? 'selected' : '' }}>
                        {{ $agreement->driver->full_name }} - {{ $agreement->car->registration }}
                        ({{ $agreement->company->name }}) -
                        {{ $agreement->start_date->format('M d, Y') }} to {{ $agreement->end_date->format('M d, Y') }}
                    </option>
                @endforeach
            </select>
            @error('agreement_id')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Date --}}
    <div class="col-md-6">
        <div class="form-group">
            <label for="date">Date <span class="text-danger">*</span></label>
            <input type="date" name="date" id="date"
                   class="form-control @error('date') is-invalid @enderror"
                   value="{{ old('date') ?? (isset($model) ? $model->date?->format('Y-m-d') : '') }}" required>
            @error('date')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Due Date --}}
    <div class="col-md-6">
        <div class="form-group">
            <label for="due_date">Due Date <span class="text-danger">*</span></label>
            <input type="date" name="due_date" id="due_date"
                   class="form-control @error('due_date') is-invalid @enderror"
                   value="{{ old('due_date') ?? (isset($model) ? $model->due_date?->format('Y-m-d') : '') }}" required>
            @error('due_date')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Amount --}}
    <div class="col-md-6">
        <div class="form-group">
            <label for="amount">Amount <span class="text-danger">*</span></label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">£</span>
                </div>
                <input type="number" name="amount" id="amount"
                       class="form-control @error('amount') is-invalid @enderror"
                       value="{{ old('amount') ?? (isset($model) ? $model->amount : '') }}"
                       step="0.01" min="0" placeholder="0.00" required>
                @error('amount')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    {{-- Status --}}
    <div class="col-md-6">
        <div class="form-group">
            <label for="status_id">Status <span class="text-danger">*</span></label>
            <select name="status_id" id="status_id" class="form-control @error('status_id') is-invalid @enderror"
                    required>
                <option value="">Select Status</option>
                @foreach($statuses as $status)
                    <option value="{{ $status->id }}"
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

    {{-- Document --}}
    <div class="col-md-12">
        <div class="form-group">
            <label for="document">Document</label>
            <input type="file" name="document" id="document"
                   class="form-control @error('document') is-invalid @enderror"
                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
            @if(isset($model) && $model->document)
                <small class="text-muted mt-1 d-block">
                    Current Document:
                    <a href="{{ asset('uploads/penalties/' . $model->document) }}" target="_blank" class="text-primary">
                        <i class="fa fa-file"></i> View Document
                    </a>
                </small>
            @endif
            <small class="text-muted">Accepted formats: PDF, JPG, PNG, DOC, DOCX (Max: 5MB)</small>
            @error('document')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

{{-- Submit Button --}}
<div class="row mt-4">
    <div class="col-12">
        <div class="form-group">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i>
                {{ isset($model->id) ? 'Update Penalty' : 'Create Penalty' }}
            </button>
            <a href="{{ route($url . 'index') }}" class="btn btn-secondary ml-2">
                <i class="fa fa-times"></i> Cancel
            </a>
        </div>
    </div>
</div>

@push('js')
    <script>
        // Form validation
        document.getElementById('due_date').addEventListener('change', function () {
            const penaltyDate = document.getElementById('date').value;
            const dueDate = this.value;

            if (penaltyDate && dueDate && new Date(dueDate) <= new Date(penaltyDate)) {
                alert('Due date must be after penalty date');
                this.value = '';
            }
        });

        document.getElementById('date').addEventListener('change', function () {
            const dueDate = document.getElementById('due_date').value;
            const penaltyDate = this.value;

            if (dueDate && penaltyDate && new Date(penaltyDate) >= new Date(dueDate)) {
                alert('Penalty date must be before due date');
                this.value = '';
            }
        });

        // Auto-populate fields based on agreement selection
        document.getElementById('agreement_id').addEventListener('change', function () {
            if (this.value) {
                // You can add AJAX call here to get more agreement details if needed
                const selectedText = this.options[this.selectedIndex].text;

                // Auto-set penalty date to today if not set
                const dateField = document.getElementById('date');
                if (!dateField.value) {
                    const today = new Date().toISOString().split('T')[0];
                    dateField.value = today;
                }

                // Auto-set due date to 30 days from penalty date if not set
                const dueDateField = document.getElementById('due_date');
                if (!dueDateField.value && dateField.value) {
                    const penaltyDate = new Date(dateField.value);
                    penaltyDate.setDate(penaltyDate.getDate() + 30);
                    dueDateField.value = penaltyDate.toISOString().split('T')[0];
                }
            }
        });

        // Amount formatting
        document.getElementById('amount').addEventListener('blur', function () {
            if (this.value) {
                this.value = parseFloat(this.value).toFixed(2);
            }
        });

        // File size validation
        document.getElementById('document').addEventListener('change', function () {
            if (this.files[0]) {
                const fileSize = this.files[0].size / 1024 / 1024; // Convert to MB
                if (fileSize > 5) {
                    alert('File size should not exceed 5MB');
                    this.value = '';
                }
            }
        });
    </script>
@endpush
