{{-- Basic Information Section --}}
<div class="row">
    <div class="col-12">
        <h5 class="mb-2"><i class="fa fa-info-circle"></i> Basic Information</h5>
    </div>

    {{-- Company Selection --}}
    <div class="col-md-6">
        <div class="form-group">
            <label for="company_id">Select Company <span class="text-danger">*</span></label>
            <select name="company_id" id="company_id" class="form-control @error('company_id') is-invalid @enderror" required>
                <option value="">Select Company</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}"
                        {{ (old('company_id') ?? (isset($model) && $model->id ? $model->company_id : '')) == $company->id ? 'selected' : '' }}>
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>
            @error('company_id')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Car Model Selection --}}
    <div class="col-md-6">
        <div class="form-group">
            <label for="car_model_id">Make/Model <span class="text-danger">*</span></label>
            <select name="car_model_id" id="car_model_id" class="form-control @error('car_model_id') is-invalid @enderror" required>
                <option value="">Select Model</option>
                @foreach($carModels as $carModel)
                    <option value="{{ $carModel->id }}"
                        {{ (old('car_model_id') ?? (isset($model) && $model->id ? $model->car_model_id : '')) == $carModel->id ? 'selected' : '' }}>
                        {{ $carModel->name }}
                    </option>
                @endforeach
            </select>
            @error('car_model_id')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Registration --}}
    <div class="col-md-6">
        <div class="form-group">
            <label for="registration">Registration <span class="text-danger">*</span></label>
            <input type="text" name="registration" id="registration"
                   class="form-control @error('registration') is-invalid @enderror"
                   value="{{ old('registration') ?? (isset($model) && $model->id ? $model->registration : '') }}"
                   placeholder="e.g. AB12 CDE" required>
            @error('registration')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Color --}}
    <div class="col-md-6">
        <div class="form-group">
            <label for="color">Color <span class="text-danger">*</span></label>
            <select name="color" id="color" class="form-control @error('color') is-invalid @enderror" required>
                <option value="">Select Color</option>
                @php
                    $colors = ['Black', 'White', 'Silver', 'Blue', 'Red', 'Grey', 'Green', 'Yellow', 'Orange', 'Purple', 'Brown', 'Gold'];
                    $selectedColor = old('color') ?? (isset($model) && $model->id ? $model->color : '');
                @endphp
                @foreach($colors as $color)
                    <option value="{{ $color }}" {{ $selectedColor == $color ? 'selected' : '' }}>{{ $color }}</option>
                @endforeach
            </select>
            @error('color')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- VIN --}}
    <div class="col-md-6">
        <div class="form-group">
            <label for="vin">VIN Number <span class="text-danger">*</span></label>
            <input type="text" name="vin" id="vin"
                   class="form-control @error('vin') is-invalid @enderror"
                   value="{{ old('vin') ?? (isset($model) && $model->id ? $model->vin : '') }}"
                   placeholder="17-character VIN" required>
            @error('vin')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- V5 Document --}}
    <div class="col-md-6">
        <div class="form-group">
            <label for="v5_document">V5 Document</label>
            <input type="file" name="v5_document" id="v5_document"
                   class="form-control @error('v5_document') is-invalid @enderror"
                   accept=".pdf,.jpg,.jpeg,.png">
            @if(isset($model) && $model->id && $model->v5_document)
                <small class="text-muted">Current: <a href="{{ asset('uploads/cars/' . $model->v5_document) }}" target="_blank">View Document</a></small>
            @endif
            @error('v5_document')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Manufacture Year --}}
    <div class="col-md-6">
        <div class="form-group">
            <label for="manufacture_year">Manufacture Year <span class="text-danger">*</span></label>
            <input type="number" name="manufacture_year" id="manufacture_year"
                   class="form-control @error('manufacture_year') is-invalid @enderror"
                   value="{{ old('manufacture_year') ?? (isset($model) && $model->id ? $model->manufacture_year : '') }}"
                   min="1900" max="{{ date('Y') }}" required>
            @error('manufacture_year')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Registration Year --}}
    <div class="col-md-6">
        <div class="form-group">
            <label for="registration_year">Registration Year <span class="text-danger">*</span></label>
            <input type="number" name="registration_year" id="registration_year"
                   class="form-control @error('registration_year') is-invalid @enderror"
                   value="{{ old('registration_year') ?? (isset($model) && $model->id ? $model->registration_year : '') }}"
                   min="1900" max="{{ date('Y') }}" required>
            @error('registration_year')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Purchase Date --}}
    <div class="col-md-6">
        <div class="form-group">
            <label for="purchase_date">Purchase Date <span class="text-danger">*</span></label>
            <input type="date" name="purchase_date" id="purchase_date"
                   class="form-control @error('purchase_date') is-invalid @enderror"
                   value="{{ old('purchase_date') ?? (isset($model) && $model->id && $model->purchase_date ? $model->purchase_date->format('Y-m-d') : '') }}" required>
            @error('purchase_date')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Purchase Price --}}
    <div class="col-md-6">
        <div class="form-group">
            <label for="purchase_price">Purchase Price <span class="text-danger">*</span></label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">£</span>
                </div>
                <input type="number" name="purchase_price" id="purchase_price"
                       class="form-control @error('purchase_price') is-invalid @enderror"
                       value="{{ old('purchase_price') ?? (isset($model) && $model->id ? $model->purchase_price : '') }}"
                       step="0.01" min="0" required>
                @error('purchase_price')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    {{-- Purchase Type --}}
    <div class="col-md-6">
        <div class="form-group">
            <label for="purchase_type">Purchase Type <span class="text-danger">*</span></label>
            <select name="purchase_type" id="purchase_type" class="form-control @error('purchase_type') is-invalid @enderror" required>
                <option value="">Select Type</option>
                <option value="imported" {{ (old('purchase_type') ?? (isset($model) && $model->id ? $model->purchase_type : '')) == 'imported' ? 'selected' : '' }}>Imported</option>
                <option value="uk" {{ (old('purchase_type') ?? (isset($model) && $model->id ? $model->purchase_type : '')) == 'uk' ? 'selected' : '' }}>UK</option>
            </select>
            @error('purchase_type')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

{{-- MOT Information Section --}}
<div class="row mt-1">
    <div class="col-12">
        <h5 class="mb-1">
            <i class="fa fa-tools"></i> MOT Information
            <button type="button" class="btn btn-sm btn-success float-right" onclick="addMOT()">
                <i class="fa fa-plus"></i> Add MOT
            </button>
        </h5>

        <div class="card">
            <div class="card-body">
                <div id="mots-container">
                    @php
                        if(old('mots')) {
                            $mots = old('mots');
                        } elseif(isset($model) && $model->id && $model->mots->count() > 0) {
                            $mots = $model->mots;
                        } else {
                            $mots = [[]]; // Empty array for new record
                        }
                    @endphp

                    @foreach($mots as $index => $mot)
                        <div class="mot-item row border-bottom pb-3 mb-1" data-index="{{ $index }}">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Expiry Date <span class="text-danger">*</span></label>
                                    <input type="date" name="mots[{{ $index }}][expiry_date]"
                                           class="form-control @error('mots.'.$index.'.expiry_date') is-invalid @enderror"
                                           value="{{ old('mots.'.$index.'.expiry_date') ?? (isset($mot['expiry_date']) ? \Carbon\Carbon::parse($mot['expiry_date'])->format('Y-m-d') : '') }}"
                                           required>
                                    @error('mots.'.$index.'.expiry_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Amount <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">£</span>
                                        </div>
                                        <input type="number" name="mots[{{ $index }}][amount]"
                                               class="form-control @error('mots.'.$index.'.amount') is-invalid @enderror"
                                               value="{{ old('mots.'.$index.'.amount') ?? ($mot['amount'] ?? '') }}"
                                               step="0.01" min="0" required>
                                        @error('mots.'.$index.'.amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Term <span class="text-danger">*</span></label>
                                    <input type="text" name="mots[{{ $index }}][term]"
                                           class="form-control @error('mots.'.$index.'.term') is-invalid @enderror"
                                           value="{{ old('mots.'.$index.'.term') ?? ($mot['term'] ?? '') }}"
                                           placeholder="e.g. 12 months" required>
                                    @error('mots.'.$index.'.term')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Document</label>
                                    <input type="file" name="mots[{{ $index }}][document]"
                                           class="form-control @error('mots.'.$index.'.document') is-invalid @enderror"
                                           accept=".pdf,.jpg,.jpeg,.png">
                                    @if(isset($mot['document']) && $mot['document'])
                                        <small class="text-muted">Current: <a href="{{ asset('uploads/cars/mot_documents/' . $mot['document']) }}" target="_blank">View</a></small>
                                    @endif
                                    @error('mots.'.$index.'.document')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-1">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div>
                                        @if($index > 0)
                                            <button type="button" class="btn btn-danger btn-sm" onclick="removeMOT(this)">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Road Tax Information Section --}}
<div class="row mt-1">
    <div class="col-12">
        <h5 class="mb-1">
            <i class="fa fa-road"></i> Road Tax Information
            <button type="button" class="btn btn-sm btn-success float-right" onclick="addRoadTax()">
                <i class="fa fa-plus"></i> Add Road Tax
            </button>
        </h5>

        <div class="card">
            <div class="card-body">
                <div id="roadtax-container">
                    @php
                        if(old('road_taxes')) {
                            $roadTaxes = old('road_taxes');
                        } elseif(isset($model) && $model->id && $model->roadTaxes->count() > 0) {
                            $roadTaxes = $model->roadTaxes;
                        } else {
                            $roadTaxes = [[]]; // Empty array for new record
                        }
                    @endphp

                    @foreach($roadTaxes as $index => $roadTax)
                        <div class="roadtax-item row border-bottom pb-3 mb-1" data-index="{{ $index }}">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Start Date <span class="text-danger">*</span></label>
                                    <input type="date" name="road_taxes[{{ $index }}][start_date]"
                                           class="form-control @error('road_taxes.'.$index.'.start_date') is-invalid @enderror"
                                           value="{{ old('road_taxes.'.$index.'.start_date') ?? (isset($roadTax['start_date']) ? \Carbon\Carbon::parse($roadTax['start_date'])->format('Y-m-d') : '') }}"
                                           required>
                                    @error('road_taxes.'.$index.'.start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Term <span class="text-danger">*</span></label>
                                    <select name="road_taxes[{{ $index }}][term]"
                                            class="form-control @error('road_taxes.'.$index.'.term') is-invalid @enderror"
                                            required>
                                        <option value="">Select Term</option>
                                        @php
                                            $selectedTerm = old('road_taxes.'.$index.'.term') ?? ($roadTax['term'] ?? '');
                                        @endphp
                                        <option value="6 months" {{ $selectedTerm == '6 months' ? 'selected' : '' }}>6 Months</option>
                                        <option value="12 months" {{ $selectedTerm == '12 months' ? 'selected' : '' }}>12 Months</option>
                                    </select>
                                    @error('road_taxes.'.$index.'.term')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Amount <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">£</span>
                                        </div>
                                        <input type="number" name="road_taxes[{{ $index }}][amount]"
                                               class="form-control @error('road_taxes.'.$index.'.amount') is-invalid @enderror"
                                               value="{{ old('road_taxes.'.$index.'.amount') ?? ($roadTax['amount'] ?? '') }}"
                                               step="0.01" min="0" required>
                                        @error('road_taxes.'.$index.'.amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div>
                                        @if($index > 0)
                                            <button type="button" class="btn btn-danger btn-sm" onclick="removeRoadTax(this)">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- PHV Information Section --}}
<div class="row mt-1">
    <div class="col-12">
        <h5 class="mb-1">
            <i class="fa fa-taxi"></i> PHV Information
            <button type="button" class="btn btn-sm btn-success float-right" onclick="addPHV()">
                <i class="fa fa-plus"></i> Add PHV
            </button>
        </h5>

        <div class="card">
            <div class="card-body">
                <div id="phv-container">
                    @php
                        if(old('phvs')) {
                            $phvs = old('phvs');
                        } elseif(isset($model) && $model->id && $model->phvs->count() > 0) {
                            $phvs = $model->phvs;
                        } else {
                            $phvs = [[]]; // Empty array for new record
                        }
                    @endphp

                    @foreach($phvs as $index => $phv)
                        <div class="phv-item row border-bottom pb-3 mb-1" data-index="{{ $index }}">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Counsel <span class="text-danger">*</span></label>
                                    <select name="phvs[{{ $index }}][counsel_id]"
                                            class="form-control @error('phvs.'.$index.'.counsel_id') is-invalid @enderror"
                                            required>
                                        <option value="">Select Counsel</option>
                                        @foreach($counsels as $counsel)
                                            @php
                                                $selectedCounsel = old('phvs.'.$index.'.counsel_id') ?? ($phv['counsel_id'] ?? '');
                                            @endphp
                                            <option value="{{ $counsel->id }}" {{ $selectedCounsel == $counsel->id ? 'selected' : '' }}>
                                                {{ $counsel->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('phvs.'.$index.'.counsel_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Amount <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">£</span>
                                        </div>
                                        <input type="number" name="phvs[{{ $index }}][amount]"
                                               class="form-control @error('phvs.'.$index.'.amount') is-invalid @enderror"
                                               value="{{ old('phvs.'.$index.'.amount') ?? ($phv['amount'] ?? '') }}"
                                               step="0.01" min="0" required>
                                        @error('phvs.'.$index.'.amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Start Date <span class="text-danger">*</span></label>
                                    <input type="date" name="phvs[{{ $index }}][start_date]"
                                           class="form-control @error('phvs.'.$index.'.start_date') is-invalid @enderror"
                                           value="{{ old('phvs.'.$index.'.start_date') ?? (isset($phv['start_date']) ? \Carbon\Carbon::parse($phv['start_date'])->format('Y-m-d') : '') }}"
                                           required>
                                    @error('phvs.'.$index.'.start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Expiry Date <span class="text-danger">*</span></label>
                                    <input type="date" name="phvs[{{ $index }}][expiry_date]"
                                           class="form-control @error('phvs.'.$index.'.expiry_date') is-invalid @enderror"
                                           value="{{ old('phvs.'.$index.'.expiry_date') ?? (isset($phv['expiry_date']) ? \Carbon\Carbon::parse($phv['expiry_date'])->format('Y-m-d') : '') }}"
                                           required>
                                    @error('phvs.'.$index.'.expiry_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Notify (days) <span class="text-danger">*</span></label>
                                    <input type="number" name="phvs[{{ $index }}][notify_before_expiry]"
                                           class="form-control @error('phvs.'.$index.'.notify_before_expiry') is-invalid @enderror"
                                           value="{{ old('phvs.'.$index.'.notify_before_expiry') ?? ($phv['notify_before_expiry'] ?? '') }}"
                                           min="1" required>
                                    @error('phvs.'.$index.'.notify_before_expiry')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Document</label>
                                    <input type="file" name="phvs[{{ $index }}][document]"
                                           class="form-control @error('phvs.'.$index.'.document') is-invalid @enderror"
                                           accept=".pdf,.jpg,.jpeg,.png">
                                    @if(isset($phv['document']) && $phv['document'])
                                        <small class="text-muted">Current: <a href="{{ asset('uploads/cars/phv_documents/' . $phv['document']) }}" target="_blank">View</a></small>
                                    @endif
                                    @error('phvs.'.$index.'.document')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-1">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div>
                                        @if($index > 0)
                                            <button type="button" class="btn btn-danger btn-sm" onclick="removePHV(this)">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Insurance Information Section --}}
<div class="row mt-1">
    <div class="col-12">
        <h5 class="mb-1"><i class="fa fa-shield-alt"></i> Insurance Information</h5>
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="insurance_provider_id">Insurance Provider <span class="text-danger">*</span></label>
                            <select name="insurance_provider_id" id="insurance_provider_id" class="form-control @error('insurance_provider_id') is-invalid @enderror" required>
                                <option value="">Select Provider</option>
                                @foreach($insuranceProviders as $provider)
                                    <option value="{{ $provider->id }}"
                                            data-company-id="{{ $provider->company_id }}"
                                        {{ (old('insurance_provider_id') ?? (isset($model) && $model->id && $model->insurances->first() ? $model->insurances->first()->insurance_provider_id : '')) == $provider->id ? 'selected' : '' }}>
                                        {{ $provider->provider_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('insurance_provider_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="insurance_status_id">Status <span class="text-danger">*</span></label>
                            <select name="insurance_status_id" id="insurance_status_id" class="form-control @error('insurance_status_id') is-invalid @enderror" required>
                                <option value="">Select Status</option>
                                @foreach($statuses as $status)
                                    <option value="{{ $status->id }}"
                                        {{ (old('insurance_status_id') ?? (isset($model) && $model->id && $model->insurances->first() ? $model->insurances->first()->status_id : '')) == $status->id ? 'selected' : '' }}>
                                        {{ $status->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('insurance_status_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="insurance_start_date">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="insurance_start_date" id="insurance_start_date"
                                   class="form-control @error('insurance_start_date') is-invalid @enderror"
                                   value="{{ old('insurance_start_date') ?? (isset($model) && $model->id && $model->insurances->first() ? $model->insurances->first()->start_date->format('Y-m-d') : '') }}" required>
                            @error('insurance_start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="insurance_expiry_date">Expiry Date <span class="text-danger">*</span></label>
                            <input type="date" name="insurance_expiry_date" id="insurance_expiry_date"
                                   class="form-control @error('insurance_expiry_date') is-invalid @enderror"
                                   value="{{ old('insurance_expiry_date') ?? (isset($model) && $model->id && $model->insurances->first() ? $model->insurances->first()->expiry_date->format('Y-m-d') : '') }}" required>
                            @error('insurance_expiry_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="insurance_notify_before_expiry">Notify Before Expiry (days) <span class="text-danger">*</span></label>
                            <input type="number" name="insurance_notify_before_expiry" id="insurance_notify_before_expiry"
                                   class="form-control @error('insurance_notify_before_expiry') is-invalid @enderror"
                                   value="{{ old('insurance_notify_before_expiry') ?? (isset($model) && $model->id && $model->insurances->first() ? $model->insurances->first()->notify_before_expiry : '30') }}"
                                   min="1" required>
                            @error('insurance_notify_before_expiry')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="insurance_document">Insurance Document</label>
                            <input type="file" name="insurance_document" id="insurance_document"
                                   class="form-control @error('insurance_document') is-invalid @enderror"
                                   accept=".pdf,.jpg,.jpeg,.png">
                            @if(isset($model) && $model->id && $model->insurances->first() && $model->insurances->first()->insurance_document)
                                <small class="text-muted">Current: <a href="{{ asset('uploads/cars/insurance_documents/' . $model->insurances->first()->insurance_document) }}" target="_blank">View Document</a></small>
                            @endif
                            @error('insurance_document')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Submit Button --}}
<div class="row mt-4">
    <div class="col-12">
        <div class="form-group">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i>
                {{ isset($model) && $model->id ? 'Update Car' : 'Create Car' }}
            </button>
            <a href="{{ route($url . 'index') }}" class="btn btn-secondary ml-2">
                <i class="fa fa-times"></i> Cancel
            </a>
        </div>
    </div>
</div>

@push('js')
    <script>
        let motIndex = {{ isset($mots) && is_countable($mots) ? count($mots) : 1 }};
        let roadTaxIndex = {{ isset($roadTaxes) && is_countable($roadTaxes) ? count($roadTaxes) : 1 }};
        let phvIndex = {{ isset($phvs) && is_countable($phvs) ? count($phvs) : 1 }};

        // Store all insurance providers with their company IDs
        const allInsuranceProviders = @json($insuranceProviders->map(function($provider) {
            return [
                'id' => $provider->id,
                'company_id' => $provider->company_id,
                'provider_name' => $provider->provider_name
            ];
        }));

        // Filter insurance providers based on selected company
        function filterInsuranceProviders() {
            const companyId = document.getElementById('company_id').value;
            const insuranceProviderSelect = document.getElementById('insurance_provider_id');
            const selectedProviderId = insuranceProviderSelect.value; // Store current selection

            // Clear existing options except the first one
            insuranceProviderSelect.innerHTML = '<option value="">Select Provider</option>';

            if (companyId) {
                // Filter providers by company_id
                const filteredProviders = allInsuranceProviders.filter(provider => provider.company_id == companyId);

                // Add filtered options
                filteredProviders.forEach(provider => {
                    const option = document.createElement('option');
                    option.value = provider.id;
                    option.textContent = provider.provider_name;
                    option.setAttribute('data-company-id', provider.company_id);

                    // Restore selection if it matches
                    if (provider.id == selectedProviderId) {
                        option.selected = true;
                    }

                    insuranceProviderSelect.appendChild(option);
                });
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Initial filter on page load
            filterInsuranceProviders();

            // Filter insurance providers when company changes
            document.getElementById('company_id').addEventListener('change', filterInsuranceProviders);
        });

        function addMOT() {
            const container = document.getElementById('mots-container');
            const newMOT = `
        <div class="mot-item row border-bottom pb-3 mb-1" data-index="${motIndex}">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Expiry Date <span class="text-danger">*</span></label>
                    <input type="date" name="mots[${motIndex}][expiry_date]" class="form-control" required>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Amount <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">£</span>
                        </div>
                        <input type="number" name="mots[${motIndex}][amount]" class="form-control" step="0.01" min="0" required>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Term <span class="text-danger">*</span></label>
                    <input type="text" name="mots[${motIndex}][term]" class="form-control" placeholder="e.g. 12 months" required>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Document</label>
                    <input type="file" name="mots[${motIndex}][document]" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                </div>
            </div>
            <div class="col-md-1">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div>
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeMOT(this)">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
            container.insertAdjacentHTML('beforeend', newMOT);
            motIndex++;
        }

        function removeMOT(button) {
            button.closest('.mot-item').remove();
        }

        function addRoadTax() {
            const container = document.getElementById('roadtax-container');
            const newRoadTax = `
        <div class="roadtax-item row border-bottom pb-3 mb-1" data-index="${roadTaxIndex}">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Start Date <span class="text-danger">*</span></label>
                    <input type="date" name="road_taxes[${roadTaxIndex}][start_date]" class="form-control" required>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Term <span class="text-danger">*</span></label>
                    <select name="road_taxes[${roadTaxIndex}][term]" class="form-control" required>
                        <option value="">Select Term</option>
                        <option value="6 months">6 Months</option>
                        <option value="12 months">12 Months</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Amount <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">£</span>
                        </div>
                        <input type="number" name="road_taxes[${roadTaxIndex}][amount]" class="form-control" step="0.01" min="0" required>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div>
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeRoadTax(this)">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
            container.insertAdjacentHTML('beforeend', newRoadTax);
            roadTaxIndex++;
        }

        function removeRoadTax(button) {
            button.closest('.roadtax-item').remove();
        }

        function addPHV() {
            const container = document.getElementById('phv-container');
            const counselOptions = @json($counsels->map(function($counsel) {
        return ['id' => $counsel->id, 'name' => $counsel->name];
    }));

            let counselOptionsHtml = '<option value="">Select Counsel</option>';
            counselOptions.forEach(counsel => {
                counselOptionsHtml += `<option value="${counsel.id}">${counsel.name}</option>`;
            });

            const newPHV = `
        <div class="phv-item row border-bottom pb-3 mb-1" data-index="${phvIndex}">
            <div class="col-md-2">
                <div class="form-group">
                    <label>Counsel <span class="text-danger">*</span></label>
                    <select name="phvs[${phvIndex}][counsel_id]" class="form-control" required>
                        ${counselOptionsHtml}
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Amount <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">£</span>
                        </div>
                        <input type="number" name="phvs[${phvIndex}][amount]" class="form-control" step="0.01" min="0" required>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Start Date <span class="text-danger">*</span></label>
                    <input type="date" name="phvs[${phvIndex}][start_date]" class="form-control" required>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Expiry Date <span class="text-danger">*</span></label>
                    <input type="date" name="phvs[${phvIndex}][expiry_date]" class="form-control" required>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Notify (days) <span class="text-danger">*</span></label>
                    <input type="number" name="phvs[${phvIndex}][notify_before_expiry]" class="form-control" min="1" required>
                </div>
            </div>
            <div class="col-md-1">
                <div class="form-group">
                    <label>Document</label>
                    <input type="file" name="phvs[${phvIndex}][document]" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                </div>
            </div>
            <div class="col-md-1">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div>
                        <button type="button" class="btn btn-danger btn-sm" onclick="removePHV(this)">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
            container.insertAdjacentHTML('beforeend', newPHV);
            phvIndex++;
        }

        function removePHV(button) {
            button.closest('.phv-item').remove();
        }

        // Validation for dates
        document.getElementById('insurance_expiry_date').addEventListener('change', function() {
            const startDate = document.getElementById('insurance_start_date').value;
            const endDate = this.value;

            if (startDate && endDate && new Date(endDate) <= new Date(startDate)) {
                alert('Insurance expiry date must be after start date');
                this.value = '';
            }
        });

        // VIN validation (17 characters)
        document.getElementById('vin').addEventListener('input', function() {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            if (this.value.length > 17) {
                this.value = this.value.substring(0, 17);
            }
        });

        // Registration formatting
        document.getElementById('registration').addEventListener('input', function() {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        });
    </script>
@endpush
