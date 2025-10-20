{{-- resources/views/claims/_form.blade.php --}}

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

    {{-- Select Insurance --}}
    <div class="col-md-6">
        <div class="form-group">
            <label for="insurance_provider_id">Select Insurance <span class="text-danger">*</span></label>
            <select name="insurance_provider_id" id="insurance_provider_id" class="form-control @error('insurance_provider_id') is-invalid @enderror" required>
                <option value="">Select Insurance Provider</option>
                @foreach($insuranceProviders as $provider)
                    <option value="{{ $provider->id }}"
                        {{ (old('insurance_provider_id') ?? (isset($model) ? $model->insurance_provider_id : '')) == $provider->id ? 'selected' : '' }}>
                        {{ $provider->provider_name }} - {{ $provider->insurance_type }} ({{ $provider->policy_number }})
                    </option>
                @endforeach
            </select>
            @error('insurance_provider_id')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Case Date --}}
    <div class="col-md-6">
        <div class="form-group">
            <label for="case_date">Case Date <span class="text-danger">*</span></label>
            <input type="date" name="case_date" id="case_date"
                   class="form-control @error('case_date') is-invalid @enderror"
                   value="{{ old('case_date') ?? (isset($model) ? $model->case_date?->format('Y-m-d') : '') }}" required>
            @error('case_date')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Incident Date --}}
    <div class="col-md-6">
        <div class="form-group">
            <label for="incident_date">Incident Date <span class="text-danger">*</span></label>
            <input type="date" name="incident_date" id="incident_date"
                   class="form-control @error('incident_date') is-invalid @enderror"
                   value="{{ old('incident_date') ?? (isset($model) ? $model->incident_date?->format('Y-m-d') : '') }}" required>
            @error('incident_date')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Our Reference --}}
    <div class="col-md-6">
        <div class="form-group">
            <label for="our_reference">Our Reference <span class="text-danger">*</span></label>
            <input type="text" name="our_reference" id="our_reference"
                   class="form-control @error('our_reference') is-invalid @enderror"
                   value="{{ old('our_reference') ?? (isset($model) ? $model->our_reference : '') }}"
                   placeholder="Enter our reference number" required>
            @error('our_reference')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Case Reference --}}
    <div class="col-md-6">
        <div class="form-group">
            <label for="case_reference">Case Reference <span class="text-danger">*</span></label>
            <input type="text" name="case_reference" id="case_reference"
                   class="form-control @error('case_reference') is-invalid @enderror"
                   value="{{ old('case_reference') ?? (isset($model) ? $model->case_reference : '') }}"
                   placeholder="Enter case reference number" required>
            @error('case_reference')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Courtesy Type --}}
    <div class="col-md-6">
        <div class="form-group">
            <label for="courtesy_type">Courtesy Type <span class="text-danger">*</span></label>
            <select name="courtesy_type" id="courtesy_type" class="form-control @error('courtesy_type') is-invalid @enderror" required>
                <option value="">Select Courtesy Type</option>
                @php
                    $courtesyTypes = [
                        'Courtesy Car Provided',
                        'No Courtesy Car',
                        'Taxi Provided',
                        'Public Transport Allowance',
                        'Hire Car',
                        'Self Arrangement',
                        'Other'
                    ];
                    $selectedType = old('courtesy_type') ?? (isset($model) ? $model->courtesy_type : '');
                @endphp
                @foreach($courtesyTypes as $type)
                    <option value="{{ $type }}" {{ $selectedType == $type ? 'selected' : '' }}>
                        {{ $type }}
                    </option>
                @endforeach
            </select>
            @error('courtesy_type')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Status --}}
    <div class="col-md-6">
        <div class="form-group">
            <label for="status_id">Status <span class="text-danger">*</span></label>
            <select name="status_id" id="status_id" class="form-control @error('status_id') is-invalid @enderror" required>
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

    {{-- Follow Up --}}
    <div class="col-md-12">
        <div class="form-group">
            <label for="follow_up">Follow Up</label>
            <textarea name="follow_up" id="follow_up"
                      class="form-control @error('follow_up') is-invalid @enderror"
                      rows="3" placeholder="Enter follow up details...">{{ old('follow_up') ?? (isset($model) ? $model->follow_up : '') }}</textarea>
            @error('follow_up')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Notes --}}
    <div class="col-md-12">
        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea name="notes" id="notes"
                      class="form-control @error('notes') is-invalid @enderror"
                      rows="4" placeholder="Additional notes and comments...">{{ old('notes') ?? (isset($model) ? $model->notes : '') }}</textarea>
            @error('notes')
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
                {{ isset($model) ? 'Update Claim' : 'Create Claim' }}
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
        document.getElementById('case_date').addEventListener('change', function() {
            const incidentDate = document.getElementById('incident_date').value;
            const caseDate = this.value;

            if (incidentDate && caseDate && new Date(caseDate) < new Date(incidentDate)) {
                alert('Case date should not be before incident date');
                this.value = '';
            }
        });

        document.getElementById('incident_date').addEventListener('change', function() {
            const caseDate = document.getElementById('case_date').value;
            const incidentDate = this.value;

            if (caseDate && incidentDate && new Date(incidentDate) > new Date(caseDate)) {
                alert('Incident date should not be after case date');
                this.value = '';
            }
        });

        // Auto-generate reference if needed
        document.getElementById('car_id').addEventListener('change', function() {
            const ourReferenceField = document.getElementById('our_reference');

            if (!ourReferenceField.value && this.value) {
                const carText = this.options[this.selectedIndex].text;
                const registration = carText.split(' - ')[0];
                const currentDate = new Date();
                const year = currentDate.getFullYear();
                const month = String(currentDate.getMonth() + 1).padStart(2, '0');
                const day = String(currentDate.getDate()).padStart(2, '0');

                // Generate reference like: ABC123-20240315-001
                const reference = `${registration}-${year}${month}${day}-001`;
                ourReferenceField.value = reference;
            }
        });
    </script>
@endpush
