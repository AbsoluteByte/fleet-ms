{{-- resources/views/expenses/_form.blade.php --}}

<div class="row">
    {{-- Select Car --}}
    <div class="col-md-6">
        <div class="form-group">
            <label for="car_id">Select Car <span class="text-danger">*</span></label>
            <select name="car_id" id="car_id" class="form-control @error('car_id') is-invalid @enderror" required>
                <option value="">Select Car</option>
                @foreach($cars as $car)
                    <option value="{{ $car->id }}"
                        {{ (old('car_id') ?? (isset($model) ? $model->car_id : '')) == $car->id ? 'selected' : '' }}>
                        {{ $car->registration }} - {{ $car->carModel->name }} ({{ $car->company->name }})
                    </option>
                @endforeach
            </select>
            @error('car_id')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Select Type --}}
    <div class="col-md-6">
        <div class="form-group">
            <label for="type">Select Type <span class="text-danger">*</span></label>
            <select name="type" id="type" class="form-control @error('type') is-invalid @enderror" required>
                <option value="">Select Expense Type</option>
                @php
                    $expenseTypes = [
                        'Fuel',
                        'Maintenance',
                        'Repair',
                        'Insurance',
                        'Road Tax',
                        'MOT',
                        'Service',
                        'Tyres',
                        'Oil Change',
                        'Brake Service',
                        'Car Wash',
                        'Parking',
                        'Tolls',
                        'Breakdown Recovery',
                        'Accident Damage',
                        'Replacement Parts',
                        'Labour Charges',
                        'Emergency Repair',
                        'Annual Service',
                        'Other'
                    ];
                    $selectedType = old('type') ?? (isset($model) ? $model->type : '');
                @endphp
                @foreach($expenseTypes as $type)
                    <option value="{{ $type }}" {{ $selectedType == $type ? 'selected' : '' }}>
                        {{ $type }}
                    </option>
                @endforeach
            </select>
            @error('type')
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

    {{-- Description --}}
    <div class="col-md-12">
        <div class="form-group">
            <label for="description">Description <span class="text-danger">*</span></label>
            <textarea name="description" id="description"
                      class="form-control @error('description') is-invalid @enderror"
                      rows="3" placeholder="Enter detailed description of the expense..." required>{{ old('description') ?? (isset($model) ? $model->description : '') }}</textarea>
            @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Document --}}
    <div class="col-md-12">
        <div class="form-group">
            <label for="document">Document/Receipt</label>
            <input type="file" name="document" id="document"
                   class="form-control @error('document') is-invalid @enderror"
                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
            @if(isset($model) && $model->document)
                <small class="text-muted mt-1 d-block">
                    Current Document:
                    <a href="{{ asset('uploads/expense_documents/' . $model->document) }}" target="_blank" class="text-primary">
                        <i class="fa fa-file"></i> View Document
                    </a>
                </small>
            @endif
            <small class="text-muted">Upload receipt or invoice (PDF, JPG, PNG, DOC, DOCX - Max: 5MB)</small>
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
                {{ isset($model->id) ? 'Update Expense' : 'Create Expense' }}
            </button>
            <a href="{{ route($url . 'index') }}" class="btn btn-secondary ml-2">
                <i class="fa fa-times"></i> Cancel
            </a>
        </div>
    </div>
</div>

@push('js')
    <script>
        // Amount formatting
        document.getElementById('amount').addEventListener('blur', function() {
            if (this.value) {
                this.value = parseFloat(this.value).toFixed(2);
            }
        });

        // VAT amount formatting
        document.getElementById('vat_amount').addEventListener('blur', function() {
            if (this.value) {
                this.value = parseFloat(this.value).toFixed(2);
            }
        });

        // Auto-calculate VAT (20% standard rate)
        document.getElementById('amount').addEventListener('input', function() {
            const amount = parseFloat(this.value) || 0;
            const vatField = document.getElementById('vat_amount');

            if (amount > 0 && !vatField.value) {
                // Calculate 20% VAT (included in total)
                const vatAmount = (amount * 0.2) / 1.2;
                vatField.value = vatAmount.toFixed(2);
            }
        });

        // Date validation (cannot be in future)
        document.getElementById('date').addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const today = new Date();
            today.setHours(23, 59, 59, 999); // Set to end of today

            if (selectedDate > today) {
                alert('Expense date cannot be in the future');
                this.value = '';
            }
        });

        // File size validation
        document.getElementById('document').addEventListener('change', function() {
            if (this.files[0]) {
                const fileSize = this.files[0].size / 1024 / 1024; // Convert to MB
                if (fileSize > 5) {
                    alert('File size should not exceed 5MB');
                    this.value = '';
                }
            }
        });

        // Auto-populate description based on expense type
        document.getElementById('type').addEventListener('change', function() {
            const descriptionField = document.getElementById('description');

            if (this.value && !descriptionField.value) {
                let autoDescription = '';

                switch(this.value) {
                    case 'Fuel':
                        autoDescription = 'Fuel purchase for vehicle';
                        break;
                    case 'Service':
                        autoDescription = 'Vehicle service and maintenance';
                        break;
                    case 'MOT':
                        autoDescription = 'MOT test for vehicle';
                        break;
                    case 'Insurance':
                        autoDescription = 'Vehicle insurance payment';
                        break;
                    case 'Road Tax':
                        autoDescription = 'Road tax payment';
                        break;
                    case 'Repair':
                        autoDescription = 'Vehicle repair work';
                        break;
                    case 'Tyres':
                        autoDescription = 'Tyre replacement or repair';
                        break;
                    default:
                        autoDescription = this.value + ' expense for vehicle';
                }

                descriptionField.value = autoDescription;
            }
        });

        // Set default date to today
        document.addEventListener('DOMContentLoaded', function() {
            const dateField = document.getElementById('date');
            if (!dateField.value) {
                const today = new Date().toISOString().split('T')[0];
                dateField.value = today;
            }
        });
    </script>
@endpush
