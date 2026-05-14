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
                   accept=".pdf,.jpg,.jpeg,.png"
                   data-has-v5="{{ isset($model) && $model->id && $model->v5_document ? '1' : '0' }}">
            @if(isset($model) && $model->id && $model->v5_document)
                <small class="text-muted">Current: <a href="{{ route('cars.view.v5', $model) }}" target="_blank" rel="noopener">View Document</a></small>
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

    {{-- Seller Name --}}
    @php
        $fleetStatus = isset($model) && $model->id ? ($model->fleet_status ?? 'available_for_rent') : 'available_for_rent';
    @endphp
    <div class="col-md-6">
        <input type="hidden" id="car_current_fleet_status" value="{{ $fleetStatus }}" autocomplete="off">
        <div class="form-group">
            <label for="seller_name">Seller Name</label>
            <input type="text" name="seller_name" id="seller_name"
                   class="form-control @error('seller_name') is-invalid @enderror"
                   value="{{ old('seller_name') ?? (isset($model) && $model->id ? $model->seller_name : '') }}"
                   placeholder="e.g. John Smith (optional)">
            @error('seller_name')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-12">
        <div class="form-group">
            <label for="seller_notes">Seller Notes</label>
            <textarea name="seller_notes" id="seller_notes" rows="3" placeholder="Optional"
                class="form-control @error('seller_notes') is-invalid @enderror">{{ old('seller_notes', isset($model) && $model->id ? ($model->seller_notes ?? '') : '') }}</textarea>
            @error('seller_notes')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    @php
        $latestFuturePhv = null;
        if (isset($model) && $model->id && $model->phvs->isNotEmpty()) {
            $latestFuturePhv = $model->phvs
                ->filter(fn ($phv) => $phv->expiry_date && $phv->expiry_date->copy()->startOfDay()->gte(now()->startOfDay()))
                ->sortByDesc(fn ($phv) => [$phv->expiry_date->timestamp, $phv->id])
                ->first();
        }
        $phvStatus = old('phv_status', isset($model) && $model->id ? ($model->phv_status ?? 'need_to_apply') : 'need_to_apply');
        $showPhvStatusControls = $phvStatus !== 'phv_active'
            || ! $latestFuturePhv
            || $latestFuturePhv->expiry_date->copy()->startOfDay()->lte(now()->addMonth()->startOfDay());
        $phvAppliedDate = old('phv_applied_date');
        if ($phvAppliedDate === null) {
            $phvAppliedDate = isset($model) && $model->id && $model->phv_applied_date
                ? $model->phv_applied_date->format('Y-m-d')
                : '';
        }
    @endphp
    <input type="hidden" name="phv_status" id="phv_status" value="{{ $phvStatus }}">

    <div class="col-md-6" id="phv-applied-date-wrapper" style="display: {{ $showPhvStatusControls && $phvStatus === 'applied' ? 'block' : 'none' }};">
        <div class="form-group">
            <label for="phv_applied_date">PHV Applied Date</label>
            <input type="date" name="phv_applied_date" id="phv_applied_date"
                class="form-control @error('phv_applied_date') is-invalid @enderror"
                value="{{ $phvAppliedDate }}">
            @error('phv_applied_date')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-12" id="damaged-notes-wrapper" style="display: {{ $fleetStatus === 'damaged' ? 'block' : 'none' }};">
        <div class="form-group">
            <label for="damaged_notes">Damaged Notes</label>
            <textarea name="damaged_notes" id="damaged_notes" rows="3" placeholder="Enter damage details"
                class="form-control @error('damaged_notes') is-invalid @enderror">{{ old('damaged_notes', isset($model) && $model->id ? ($model->damaged_notes ?? '') : '') }}</textarea>
            @error('damaged_notes')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    @php
        $logBookDate = old('log_book_applied_date');
        if ($logBookDate === null) {
            if (isset($model) && $model->id && $model->log_book_applied_date) {
                $logBookDate = $model->log_book_applied_date->format('Y-m-d');
            } else {
                $logBookDate = '';
            }
        }
        $logBookAppliedVal = filter_var(
            old('log_book_applied', (isset($model) && $model->id) ? ($model->log_book_applied ?? false) : false),
            FILTER_VALIDATE_BOOLEAN
        );
        $carHasV5Document = isset($model) && $model->id && $model->v5_document;
    @endphp
    <div class="col-12 @if($carHasV5Document) d-none @endif" id="log-book-ui-wrapper">
        <div class="form-group mb-2">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="log_book_applied" name="log_book_applied" value="1"
                    {{ $logBookAppliedVal ? 'checked' : '' }}
                    @if($carHasV5Document) disabled @endif>
                <label class="form-check-label" for="log_book_applied">Log book applied</label>
            </div>
        </div>
        <div id="log-book-section"
            style="display: {{ $logBookAppliedVal ? 'block' : 'none' }};">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="log_book_applied_date">Applied Date</label>
                        <input type="date" name="log_book_applied_date" id="log_book_applied_date"
                            class="form-control @error('log_book_applied_date') is-invalid @enderror"
                            value="{{ $logBookDate }}"
                            @if($carHasV5Document) disabled @endif>
                        @error('log_book_applied_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="old_log_book">Old log book <span class="text-muted font-weight-normal">(multiple files)</span></label>
                        <input type="file" name="old_log_book[]" id="old_log_book"
                            class="form-control @error('old_log_book') is-invalid @enderror"
                            accept=".pdf,.jpg,.jpeg,.png"
                            multiple
                            @if($carHasV5Document) disabled @endif>
                        @if(isset($model) && $model->id && $model->oldLogBookFileNames() !== [])
                            @foreach($model->oldLogBookFileNames() as $lbName)
                                <small class="text-muted d-block mt-1">Current file {{ $loop->iteration }}: <a href="{{ asset('uploads/cars/log_book/' . $lbName) }}" target="_blank">View</a></small>
                            @endforeach
                        @endif
                        @error('old_log_book')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @error('old_log_book.*')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if($carHasV5Document)
        <div id="log-book-preservation-fields">
            <input type="hidden" name="log_book_applied" value="{{ $logBookAppliedVal ? '1' : '0' }}">
            @if($logBookAppliedVal)
                <input type="hidden" name="log_book_applied_date" value="{{ $logBookDate }}">
            @endif
        </div>
    @endif
    <div id="log-book-js-preservation"></div>
</div>

@php
    $isCarEdit = isset($model) && $model->id;
    if (is_array($oldMots = old('mots'))) {
        $motsForMain = collect($oldMots)->values();
        if ($motsForMain->isEmpty()) {
            $motsForMain = collect([[]]);
        }
        $motsOlder = collect();
        $useMotsSplit = false;
    } elseif ($isCarEdit && $model->mots->isNotEmpty()) {
        $motsForMain = $model->mots->take(1);
        $motsOlder = $model->mots->slice(1)->values();
        $useMotsSplit = true;
    } else {
        $motsForMain = collect([[]]);
        $motsOlder = collect();
        $useMotsSplit = false;
    }
    $showMotViewAll = $isCarEdit && $useMotsSplit && $motsOlder->isNotEmpty();
    $motMainCount = $motsForMain->count();
    $motHiddenStartIndex = $motMainCount;
@endphp

{{-- MOT Information Section --}}
<div class="row mt-1">
    <div class="col-12">
        <h5 class="mb-1 d-flex flex-wrap align-items-center justify-content-between">
            <span>
                <i class="fa fa-tools"></i> MOT Information
            </span>
            <span>
                @if($showMotViewAll)
                <button type="button" class="btn btn-sm btn-outline-primary mr-1" data-toggle="modal" data-target="#editMotHistoryModal">
                    View All
                </button>
                @endif
                <button type="button" class="btn btn-sm btn-success" onclick="addMOT()">
                    <i class="fa fa-plus"></i> Add MOT
                </button>
            </span>
        </h5>

        <div class="card">
            <div class="card-body">
                <div id="mots-container">
                    @foreach($motsForMain as $index => $mot)
                        <div class="mot-item row border-bottom pb-3 mb-1" data-index="{{ $index }}">
                            @if(isset($mot->id))
                                <input type="hidden" name="mots[{{ $index }}][id]" value="{{ $mot->id }}">
                            @endif

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Expiry Date</label>
                                    <input type="date" name="mots[{{ $index }}][expiry_date]"
                                           class="form-control @error('mots.'.$index.'.expiry_date') is-invalid @enderror"
                                           value="{{ old('mots.'.$index.'.expiry_date') ?? (isset($mot['expiry_date']) ? \Carbon\Carbon::parse($mot['expiry_date'])->format('Y-m-d') : '') }}">
                                    @error('mots.'.$index.'.expiry_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Amount</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">£</span>
                                        </div>
                                        <input type="number" name="mots[{{ $index }}][amount]"
                                               class="form-control @error('mots.'.$index.'.amount') is-invalid @enderror"
                                               value="{{ old('mots.'.$index.'.amount') ?? (is_object($mot) && isset($mot->amount) ? $mot->amount : ($mot['amount'] ?? '')) }}"
                                               step="0.01" min="0">
                                        @error('mots.'.$index.'.amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Term</label>
                                    <input type="text" name="mots[{{ $index }}][term]"
                                           class="form-control @error('mots.'.$index.'.term') is-invalid @enderror"
                                           value="{{ old('mots.'.$index.'.term') ?? (is_object($mot) && isset($mot->term) ? $mot->term : ($mot['term'] ?? '')) }}"
                                           placeholder="e.g. 12 months">
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
                                    @if((is_object($mot) && $mot->document) || (isset($mot['document']) && $mot['document']))
                                        <small class="text-muted">Current:
                                            @if(isset($model) && $model->id && is_object($mot) && isset($mot->id))
                                                <a href="{{ route('cars.mots.download', [$model, $mot->id]) }}" target="_blank">View</a>
                                            @else
                                                <a href="{{ asset('uploads/cars/mot_documents/' . (is_object($mot) ? $mot->document : $mot['document'])) }}" target="_blank">View</a>
                                            @endif
                                        </small>
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
                <div id="mots-preserved" class="d-none">
                    @if($isCarEdit && $useMotsSplit && $motsOlder->isNotEmpty())
                        @php $hMot = $motHiddenStartIndex; @endphp
                        @foreach($motsOlder as $motP)
                            <div class="mot-preserved" data-record-id="{{ $motP->id }}">
                                <input type="hidden" name="mots[{{ $hMot }}][id]" value="{{ $motP->id }}">
                                <input type="hidden" name="mots[{{ $hMot }}][expiry_date]" value="{{ $motP->expiry_date->format('Y-m-d') }}">
                                <input type="hidden" name="mots[{{ $hMot }}][amount]" value="{{ $motP->amount }}">
                                <input type="hidden" name="mots[{{ $hMot }}][term]" value="{{ e($motP->term) }}">
                            </div>
                            @php $hMot++; @endphp
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if($showMotViewAll)
<div class="modal fade" id="editMotHistoryModal" tabindex="-1" role="dialog" aria-labelledby="editMotHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editMotHistoryModalLabel">Previous MOT records</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Expiry Date</th>
                                <th>Amount</th>
                                <th>Term</th>
                                <th>Document</th>
                                <th class="text-right" style="width:80px">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($motsOlder as $motH)
                            <tr data-hist-mot-id="{{ $motH->id }}">
                                <td>{{ $motH->expiry_date->format('d M, Y') }}</td>
                                <td>£{{ number_format($motH->amount, 2) }}</td>
                                <td>{{ $motH->term }}</td>
                                <td>
                                    @if($motH->document)
                                        <a href="{{ route('cars.mots.download', [$model, $motH->id]) }}" target="_blank" class="btn btn-sm btn-outline-primary">View</a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteCarHistoryMot({{ $model->id }}, {{ $motH->id }})" title="Delete">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@php
    if (is_array($oldRts = old('road_taxes'))) {
        $roadTaxesForMain = collect($oldRts)->values();
        if ($roadTaxesForMain->isEmpty()) {
            $roadTaxesForMain = collect([[]]);
        }
        $roadTaxesOlder = collect();
        $useRoadTaxSplit = false;
    } elseif ($isCarEdit && $model->roadTaxes->isNotEmpty()) {
        $roadTaxesForMain = $model->roadTaxes->take(1);
        $roadTaxesOlder = $model->roadTaxes->slice(1)->values();
        $useRoadTaxSplit = true;
    } else {
        $roadTaxesForMain = collect([[]]);
        $roadTaxesOlder = collect();
        $useRoadTaxSplit = false;
    }
    $showRoadTaxViewAll = $isCarEdit && $useRoadTaxSplit && $roadTaxesOlder->isNotEmpty();
    $rtMainCount = $roadTaxesForMain->count();
    $rtHiddenStartIndex = $rtMainCount;
@endphp

{{-- Road Tax Information Section --}}
<div class="row mt-1">
    <div class="col-12">
        <h5 class="mb-1 d-flex flex-wrap align-items-center justify-content-between">
            <span>
                <i class="fa fa-road"></i> Road Tax Information
            </span>
            <span>
                @if($showRoadTaxViewAll)
                <button type="button" class="btn btn-sm btn-outline-primary mr-1" data-toggle="modal" data-target="#editRoadTaxHistoryModal">
                    View All
                </button>
                @endif
                @if($isCarEdit)
                    @if($model->sorn_applied)
                        <button type="button" id="carSornToolbarBtn" class="btn btn-sm btn-success mr-1" data-toggle="modal" data-target="#sornDetailsModal" title="View SORN details" data-sorn-toolbar-state="applied">
                            <i class="fa fa-check"></i> SORN Applied
                        </button>
                    @else
                        <button type="button" id="carSornToolbarBtn" class="btn btn-sm btn-outline-success mr-1" data-toggle="modal" data-target="#applySornModal" data-sorn-toolbar-state="apply">
                            <i class="fa fa-road"></i> Apply SORN
                        </button>
                    @endif
                    @if($model->sornHistories->isNotEmpty())
                        <button type="button" class="btn btn-sm btn-outline-primary mr-1" data-toggle="modal" data-target="#sornHistoryModal">
                            SORN History
                        </button>
                    @endif
                @endif
                <button type="button" class="btn btn-sm btn-success" onclick="addRoadTax()">
                    <i class="fa fa-plus"></i> Add Road Tax
                </button>
            </span>
        </h5>

        <div class="card">
            <div class="card-body">
                @if($isCarEdit && $model->sorn_applied)
                <div id="roadtax-sorn-notice" class="text-center py-2">
                    <p class="mb-0 text-muted"><i class="fa fa-info-circle mr-50"></i> Road tax fields are hidden while SORN is active. Click <strong>Add Road Tax</strong> to enter a new record.</p>
                </div>
                @endif
                <div id="roadtax-container" @if($isCarEdit && $model->sorn_applied) class="d-none" @endif>
                    @if(!($isCarEdit && $model->sorn_applied))
                    @foreach($roadTaxesForMain as $index => $roadTax)
                        <div class="roadtax-item row border-bottom pb-3 mb-1" data-index="{{ $index }}">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Start Date</label>
                                    <input type="date" name="road_taxes[{{ $index }}][start_date]"
                                           class="form-control @error('road_taxes.'.$index.'.start_date') is-invalid @enderror"
                                           value="{{ old('road_taxes.'.$index.'.start_date') ?? (isset($roadTax['start_date']) ? \Carbon\Carbon::parse($roadTax['start_date'])->format('Y-m-d') : '') }}">
                                    @error('road_taxes.'.$index.'.start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Term</label>
                                    <select name="road_taxes[{{ $index }}][term]"
                                            class="form-control @error('road_taxes.'.$index.'.term') is-invalid @enderror">
                                        <option value="">Select Term</option>
                                        @php
                                            $selectedTerm = old('road_taxes.'.$index.'.term') ?? (is_object($roadTax) && isset($roadTax->term) ? $roadTax->term : ($roadTax['term'] ?? ''));
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
                                    <label>Amount</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">£</span>
                                        </div>
                                        <input type="number" name="road_taxes[{{ $index }}][amount]"
                                               class="form-control @error('road_taxes.'.$index.'.amount') is-invalid @enderror"
                                               value="{{ old('road_taxes.'.$index.'.amount') ?? (is_object($roadTax) && isset($roadTax->amount) ? $roadTax->amount : ($roadTax['amount'] ?? '')) }}"
                                               step="0.01" min="0">
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
                    @endif
                </div>
                <div id="roadtax-preserved" class="d-none">
                    @if($isCarEdit && $useRoadTaxSplit && $roadTaxesOlder->isNotEmpty())
                        @php $hRt = $rtHiddenStartIndex; @endphp
                        @foreach($roadTaxesOlder as $rtP)
                            <div class="roadtax-preserved" data-record-id="{{ $rtP->id }}">
                                <input type="hidden" name="road_taxes[{{ $hRt }}][start_date]" value="{{ $rtP->start_date->format('Y-m-d') }}">
                                <input type="hidden" name="road_taxes[{{ $hRt }}][term]" value="{{ e($rtP->term) }}">
                                <input type="hidden" name="road_taxes[{{ $hRt }}][amount]" value="{{ $rtP->amount }}">
                            </div>
                            @php $hRt++; @endphp
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if($isCarEdit && ! $model->sorn_applied)
<div class="modal fade" id="applySornModal" tabindex="-1" role="dialog" aria-labelledby="applySornModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="applySornModalLabel">
                    <i class="fa fa-road text-success mr-50"></i> Apply SORN
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="mb-0 text-body">Are you sure you want to apply SORN for this car?</p>
                <p class="small text-muted mt-1 mb-0">If you continue, FleetIQ will record this vehicle as off the road. You can open <strong>GOV.UK</strong> from the confirmation message afterwards if you still need to complete the statutory notification there.</p>
                <div class="form-group mt-2 mb-0">
                    <label for="apply_sorn_proof" class="d-block">SORN proof (optional)</label>
                    <input type="file" name="sorn_proof" id="apply_sorn_proof" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png">
                    <span class="form-text text-muted small">Upload a supporting document (PDF or image, max 10MB).</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="applySornConfirmBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="sornAppliedSuccessModal" tabindex="-1" role="dialog" aria-labelledby="sornAppliedSuccessModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="sornAppliedSuccessModalLabel">
                    <i class="fa fa-check-circle text-success mr-50"></i> SORN recorded
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="mb-0 text-body">This car is now marked as SORN in FleetIQ.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<template id="tplSornAppliedModals">
    <div class="modal fade" id="sornDetailsModal" tabindex="-1" role="dialog" aria-labelledby="sornDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 32rem;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mb-0" id="sornDetailsModalLabel">
                        <i class="fa fa-check-circle text-success mr-50"></i> SORN applied
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-0 text-body" id="sornDetailsModalBodyLine" style="line-height: 1.65;"></p>
                    <p class="mb-0 mt-2 d-none" id="sornDetailsModalProofLine"><a href="#" id="sornDetailsModalProofLink" target="_blank" rel="noopener noreferrer">View SORN proof</a></p>
                </div>
                <div class="modal-footer flex-wrap">
                    <button type="button" class="btn btn-outline-danger mr-auto mb-1 mb-sm-0" id="sornDetailsEndSornBtn">End SORN</button>
                    <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="endSornConfirmModal" tabindex="-1" role="dialog" aria-labelledby="endSornConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mb-0" id="endSornConfirmModalLabel">
                        <i class="fa fa-exclamation-triangle text-warning mr-50"></i> End SORN?
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-0 text-body">Are you sure you want to remove the SORN status for this car in FleetIQ?</p>
                    <p class="mt-3 mb-0 rounded p-75" style="font-size:1.0625rem;line-height:1.58;color:#1e293b;"><strong style="color:#0f172a;">Reminder:</strong> Before this vehicle is used or kept on a public road again, you must pay vehicle tax (road tax) and meet the usual legal requirements (e.g. MOT and insurance where applicable).</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="endSornConfirmBtn">Yes, end SORN</button>
                </div>
            </div>
        </div>
    </div>
</template>
@endif

@if($isCarEdit && $model->sorn_applied)
@php
    $sornDetailsWho = $model->sornAppliedBy?->name;
    $sornDetailsWhen = $model->sorn_applied_at
        ? $model->sorn_applied_at->format('d M Y') . ' at ' . $model->sorn_applied_at->format('h:i A')
        : null;
@endphp
<div class="modal fade" id="sornDetailsModal" tabindex="-1" role="dialog" aria-labelledby="sornDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 32rem;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="sornDetailsModalLabel">
                    <i class="fa fa-check-circle text-success mr-50"></i> SORN applied
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="mb-0 text-body" style="line-height: 1.65;">
                    @if($sornDetailsWho)
                        <strong>{{ $sornDetailsWho }}</strong> applied for SORN for this car
                        @if($sornDetailsWhen)
                            on <strong>{{ $sornDetailsWhen }}</strong>
                        @endif
                        .
                    @else
                        SORN was recorded for this car
                        @if($sornDetailsWhen)
                            on <strong>{{ $sornDetailsWhen }}</strong>
                        @endif
                        .
                    @endif
                </p>
                @if($model->sorn_document)
                    <p class="mb-0 mt-2">
                        <a href="{{ asset('uploads/cars/sorn_documents/'.$model->sorn_document) }}" target="_blank" rel="noopener noreferrer">View SORN proof</a>
                    </p>
                @endif
            </div>
            <div class="modal-footer flex-wrap">
                <button type="button" class="btn btn-outline-danger mr-auto mb-1 mb-sm-0" id="sornDetailsEndSornBtn">End SORN</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="endSornConfirmModal" tabindex="-1" role="dialog" aria-labelledby="endSornConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="endSornConfirmModalLabel">
                    <i class="fa fa-exclamation-triangle text-warning mr-50"></i> End SORN?
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="mb-0 text-body">Are you sure you want to remove the SORN status for this car in FleetIQ?</p>
                <p class="mt-3 mb-0 rounded p-75" style="font-size:1.0625rem;line-height:1.58;color:#1e293b;"><strong style="color:#0f172a;">Reminder:</strong> Before this vehicle is used or kept on a public road again, you must pay vehicle tax (road tax) and meet the usual legal requirements (e.g. MOT and insurance where applicable).</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="endSornConfirmBtn">Yes, end SORN</button>
            </div>
        </div>
    </div>
</div>
@endif

@if($isCarEdit && $model->sornHistories->isNotEmpty())
<div class="modal fade" id="sornHistoryModal" tabindex="-1" role="dialog" aria-labelledby="sornHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sornHistoryModalLabel">SORN History</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>SORN Started</th>
                                <th>Started By</th>
                                <th>SORN Ended</th>
                                <th>Ended By</th>
                                <th>Duration</th>
                                <th>Proof</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($model->sornHistories as $sornH)
                            <tr>
                                <td>{{ $sornH->sorn_started_at->format('d M Y, h:i A') }}</td>
                                <td>{{ $sornH->startedBy?->name ?? '—' }}</td>
                                <td>
                                    @if($sornH->sorn_ended_at)
                                        {{ $sornH->sorn_ended_at->format('d M Y, h:i A') }}
                                    @else
                                        <span class="badge badge-success">Active</span>
                                    @endif
                                </td>
                                <td>{{ $sornH->endedBy?->name ?? '—' }}</td>
                                <td>
                                    @php
                                        $end = $sornH->sorn_ended_at ?? now();
                                        $diff = $sornH->sorn_started_at->diff($end);
                                        $parts = [];
                                        if ($diff->y) $parts[] = $diff->y . 'y';
                                        if ($diff->m) $parts[] = $diff->m . 'mo';
                                        if ($diff->d) $parts[] = $diff->d . 'd';
                                        if (empty($parts)) $parts[] = 'less than a day';
                                    @endphp
                                    {{ implode(' ', $parts) }}
                                </td>
                                <td>
                                    @if($sornH->sorn_document)
                                        <a href="{{ asset('uploads/cars/sorn_documents/'.$sornH->sorn_document) }}" target="_blank" rel="noopener noreferrer" title="View proof"><i class="fa fa-file"></i></a>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@if($isCarEdit)
<div id="sornApplyOverlay" class="d-none" style="position:fixed;inset:0;z-index:10000;background:rgba(15,23,42,0.55);align-items:center;justify-content:center;flex-direction:column;">
    <div class="bg-white rounded shadow p-4 text-center" style="min-width:260px;border-radius:12px;">
        <div class="spinner-border text-success mb-3" role="status" aria-hidden="true"></div>
        <div class="font-weight-500 text-dark">Saving…</div>
        <div class="small text-muted mt-1">Please wait</div>
    </div>
</div>
@endif

@if($showRoadTaxViewAll)
<div class="modal fade" id="editRoadTaxHistoryModal" tabindex="-1" role="dialog" aria-labelledby="editRoadTaxHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editRoadTaxHistoryModalLabel">Previous road tax records</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Start Date</th>
                                <th>Term</th>
                                <th>Amount</th>
                                <th class="text-right" style="width:80px">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($roadTaxesOlder as $rtH)
                            <tr data-hist-rt-id="{{ $rtH->id }}">
                                <td>{{ $rtH->start_date->format('d M, Y') }}</td>
                                <td>{{ $rtH->term }}</td>
                                <td>£{{ number_format($rtH->amount, 2) }}</td>
                                <td class="text-right">
                                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteCarHistoryRoadTax({{ $model->id }}, {{ $rtH->id }})" title="Delete">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@php
    if (is_array($oldPhvs = old('phvs'))) {
        $phvsForMain = collect($oldPhvs)->values();
        if ($phvsForMain->isEmpty()) {
            $phvsForMain = collect([[]]);
        }
        $phvsOlder = collect();
        $usePhvSplit = false;
    } elseif ($isCarEdit && $model->phvs->isNotEmpty()) {
        $phvsForMain = $model->phvs->take(1);
        $phvsOlder = $model->phvs->slice(1)->values();
        $usePhvSplit = true;
    } else {
        $phvsForMain = collect([[]]);
        $phvsOlder = collect();
        $usePhvSplit = false;
    }
    $showPhvViewAll = $isCarEdit && $usePhvSplit && $phvsOlder->isNotEmpty();
    $phvMainCount = $phvsForMain->count();
    $phvHiddenStartIndex = $phvMainCount;
@endphp

{{-- PHV Information Section --}}
<div class="row mt-1">
    <div class="col-12">
        <h5 class="mb-1 d-flex flex-wrap align-items-center justify-content-between">
            <span>
                <i class="fa fa-taxi"></i> PHV Information
            </span>
            <span>
                @if($showPhvViewAll)
                <button type="button" class="btn btn-sm btn-outline-primary mr-1" data-toggle="modal" data-target="#editPhvHistoryModal">
                    View All
                </button>
                @endif
                <button type="button" class="btn btn-sm btn-success" onclick="addPHV()">
                    <i class="fa fa-plus"></i> Add PHV
                </button>
            </span>
        </h5>

        <div class="card">
            <div class="card-body">
                <div id="phv-container">
                    @foreach($phvsForMain as $index => $phv)
                        <div class="phv-item row border-bottom pb-3 mb-1" data-index="{{ $index }}">
                            @if(isset($phv->id))
                                <input type="hidden" name="phvs[{{ $index }}][id]" value="{{ $phv->id }}">
                            @endif

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Council</label>
                                    <select name="phvs[{{ $index }}][counsel_id]"
                                            class="form-control @error('phvs.'.$index.'.counsel_id') is-invalid @enderror">
                                        <option value="">Select Council</option>
                                        @foreach($counsels as $counsel)
                                            @php
                                                $selectedCounsel = old('phvs.'.$index.'.counsel_id') ?? (is_object($phv) && isset($phv->counsel_id) ? $phv->counsel_id : ($phv['counsel_id'] ?? ''));
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
                                    <label>Amount</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">£</span>
                                        </div>
                                        <input type="number" name="phvs[{{ $index }}][amount]"
                                               class="form-control @error('phvs.'.$index.'.amount') is-invalid @enderror"
                                               value="{{ old('phvs.'.$index.'.amount') ?? (is_object($phv) && isset($phv->amount) ? $phv->amount : ($phv['amount'] ?? '')) }}"
                                               step="0.01" min="0">
                                        @error('phvs.'.$index.'.amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Start Date</label>
                                    <input type="date" name="phvs[{{ $index }}][start_date]"
                                           class="form-control @error('phvs.'.$index.'.start_date') is-invalid @enderror"
                                           value="{{ old('phvs.'.$index.'.start_date') ?? (isset($phv['start_date']) ? \Carbon\Carbon::parse($phv['start_date'])->format('Y-m-d') : (is_object($phv) && $phv->start_date ? $phv->start_date->format('Y-m-d') : '')) }}">
                                    @error('phvs.'.$index.'.start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Expiry Date</label>
                                    <input type="date" name="phvs[{{ $index }}][expiry_date]"
                                           class="form-control @error('phvs.'.$index.'.expiry_date') is-invalid @enderror"
                                           value="{{ old('phvs.'.$index.'.expiry_date') ?? (isset($phv['expiry_date']) ? \Carbon\Carbon::parse($phv['expiry_date'])->format('Y-m-d') : (is_object($phv) && $phv->expiry_date ? $phv->expiry_date->format('Y-m-d') : '')) }}">
                                    @error('phvs.'.$index.'.expiry_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Notify (days)</label>
                                    <input type="number" name="phvs[{{ $index }}][notify_before_expiry]"
                                           class="form-control @error('phvs.'.$index.'.notify_before_expiry') is-invalid @enderror"
                                           value="{{ old('phvs.'.$index.'.notify_before_expiry') ?? (is_object($phv) && isset($phv->notify_before_expiry) ? $phv->notify_before_expiry : ($phv['notify_before_expiry'] ?? '')) }}"
                                           min="1">
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
                                    @if((is_object($phv) && $phv->document) || (isset($phv['document']) && $phv['document']))
                                        <small class="text-muted">Current:
                                            @if(isset($model) && $model->id && is_object($phv) && isset($phv->id))
                                                <a href="{{ route('cars.phvs.download', [$model, $phv->id]) }}" target="_blank">View</a>
                                            @else
                                                <a href="{{ asset('uploads/cars/phv_documents/' . (is_object($phv) ? $phv->document : $phv['document'])) }}" target="_blank">View</a>
                                            @endif
                                        </small>
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
                <div id="phv-preserved" class="d-none">
                    @if($isCarEdit && $usePhvSplit && $phvsOlder->isNotEmpty())
                        @php $hPhv = $phvHiddenStartIndex; @endphp
                        @foreach($phvsOlder as $phvP)
                            <div class="phv-preserved" data-record-id="{{ $phvP->id }}">
                                <input type="hidden" name="phvs[{{ $hPhv }}][id]" value="{{ $phvP->id }}">
                                <input type="hidden" name="phvs[{{ $hPhv }}][counsel_id]" value="{{ $phvP->counsel_id }}">
                                <input type="hidden" name="phvs[{{ $hPhv }}][amount]" value="{{ $phvP->amount }}">
                                <input type="hidden" name="phvs[{{ $hPhv }}][start_date]" value="{{ $phvP->start_date->format('Y-m-d') }}">
                                <input type="hidden" name="phvs[{{ $hPhv }}][expiry_date]" value="{{ $phvP->expiry_date->format('Y-m-d') }}">
                                <input type="hidden" name="phvs[{{ $hPhv }}][notify_before_expiry]" value="{{ $phvP->notify_before_expiry }}">
                                <input type="hidden" name="phvs[{{ $hPhv }}][phv_applied]" value="{{ $phvP->phv_applied ? 1 : 0 }}">
                                <input type="hidden" name="phvs[{{ $hPhv }}][phv_applied_date]" value="{{ $phvP->phv_applied_date ? $phvP->phv_applied_date->format('Y-m-d') : '' }}">
                            </div>
                            @php $hPhv++; @endphp
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if($showPhvViewAll)
<div class="modal fade" id="editPhvHistoryModal" tabindex="-1" role="dialog" aria-labelledby="editPhvHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPhvHistoryModalLabel">Previous PHV records</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Council</th>
                                <th>Start</th>
                                <th>Expiry</th>
                                <th>Amount</th>
                                <th>Notify</th>
                                <th>Applied</th>
                                <th>Document</th>
                                <th class="text-right" style="width:80px">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($phvsOlder as $phvH)
                            <tr data-hist-phv-id="{{ $phvH->id }}">
                                <td>{{ $phvH->counsel->name ?? 'N/A' }}</td>
                                <td>{{ $phvH->start_date->format('d M, Y') }}</td>
                                <td>{{ $phvH->expiry_date->format('d M, Y') }}</td>
                                <td>£{{ number_format($phvH->amount, 2) }}</td>
                                <td>{{ $phvH->notify_before_expiry }} days</td>
                                <td>
                                    {{ $phvH->phv_applied ? 'Yes' : 'No' }}
                                    @if($phvH->phv_applied_date)
                                        <br><small>{{ $phvH->phv_applied_date->format('d M, Y') }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($phvH->document)
                                        <a href="{{ route('cars.phvs.download', [$model, $phvH->id]) }}" target="_blank" class="btn btn-sm btn-outline-primary">View</a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteCarHistoryPhv({{ $model->id }}, {{ $phvH->id }})" title="Delete">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@php
    $latestInsuranceForForm = null;
    $olderInsurancesForModal = collect();
    $showInsuranceViewAll = false;
    $latestInsuranceStatusName = null;
    if (isset($model) && $model->id && $model->insurances->isNotEmpty()) {
        $sortedInsurances = $model->insurances
            ->sortByDesc(fn ($insurance) => [optional($insurance->created_at)->timestamp ?? 0, $insurance->id])
            ->values();
        $latestInsuranceStatusName = strtolower(trim((string) optional(optional($sortedInsurances->first())->status)->name));
        if (in_array($latestInsuranceStatusName, ['cancelled', 'canceled'], true)) {
            $olderInsurancesForModal = $sortedInsurances;
            $latestInsuranceForForm = null;
        } else {
            $olderInsurancesForModal = $sortedInsurances->count() > 1 ? $sortedInsurances->slice(1)->values() : collect();
            $latestInsuranceForForm = $sortedInsurances->first();
        }
        $showInsuranceViewAll = $olderInsurancesForModal->isNotEmpty();
    }
    $defaultHasInsurance = isset($model) && $model->id
        ? (isset($latestInsuranceForForm) && $latestInsuranceForForm !== null)
        : false;
    $hasInsuranceChecked = (bool) old('has_insurance', $defaultHasInsurance);
    $hasExistingInsuranceDocument = (bool) ($latestInsuranceForForm && $latestInsuranceForForm->insurance_document);
@endphp

{{-- Insurance Information Section - OPTIONAL --}}
<div class="row mt-1">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-wrap justify-content-between align-items-center">
                    <div class="d-flex flex-wrap align-items-center mb-1 mb-md-0">
                        <h5 class="card-title mb-0 mr-3">
                            <i class="fa fa-shield-alt"></i> Insurance Information
                        </h5>
                        <div class="form-check mb-0">
                            <input type="checkbox" class="form-check-input" id="has_insurance" name="has_insurance"
                                {{ $hasInsuranceChecked ? 'checked' : '' }}>
                            <label class="form-check-label" for="has_insurance">
                                <strong>Add Insurance</strong>
                            </label>
                        </div>
                    </div>
                </div>

                @if(isset($model) && $model->id && $showInsuranceViewAll)
                    <button type="button" class="btn btn-sm btn-outline-primary mb-1 mb-md-0" data-toggle="modal" data-target="#editInsuranceHistoryModal">
                        View All
                    </button>
                @endif

            </div>
            <div class="card-body" id="insurance-section" style="display: none;">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="insurance_provider_id">Insurance Provider</label>
                            <select name="insurance_provider_id" id="insurance_provider_id" class="form-control @error('insurance_provider_id') is-invalid @enderror">
                                <option value="">Select Provider</option>
                                @foreach($insuranceProviders as $provider)
                                    <option value="{{ $provider->id }}"
                                            data-company-id="{{ $provider->company_id }}"
                                        {{ (old('insurance_provider_id') ?? ($latestInsuranceForForm ? $latestInsuranceForForm->insurance_provider_id : '')) == $provider->id ? 'selected' : '' }}>
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
                            <label for="insurance_status_id">Status</label>
                            <select name="insurance_status_id" id="insurance_status_id" class="form-control @error('insurance_status_id') is-invalid @enderror">
                                <option value="">Select Status</option>
                                @foreach($statuses->sortByDesc(fn($status) => strtolower($status->name) === 'applied') as $status)
                                    @if(!in_array(strtolower($status->name), ['expired', 'pending renewal']))
                                        <option value="{{ $status->id }}"
                                            data-status-name="{{ strtolower($status->name) }}"
                                            {{ strtolower($status->name) === 'applied' && $latestInsuranceStatusName === 'active' ? 'disabled' : '' }}
                                            {{ in_array(strtolower($status->name), ['cancelled', 'canceled'], true) && $latestInsuranceStatusName !== 'active' ? 'disabled' : '' }}
                                            {{ (old('insurance_status_id') ?? ($latestInsuranceForForm ? $latestInsuranceForForm->status_id : '')) == $status->id ? 'selected' : '' }}>
                                            {{ $status->name }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            @error('insurance_status_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6" id="insurance-applied-date-wrapper" style="display: none;">
                        <div class="form-group">
                            <label for="insurance_applied_date">Date Applied</label>
                            <input type="date" name="insurance_applied_date" id="insurance_applied_date"
                                   class="form-control @error('insurance_applied_date') is-invalid @enderror"
                                   value="{{ old('insurance_applied_date') ?? ($latestInsuranceForForm && optional($latestInsuranceForForm->status)->name === 'Applied' && $latestInsuranceForForm->applied_date ? $latestInsuranceForForm->applied_date->format('Y-m-d') : '') }}">
                            @error('insurance_applied_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6" id="insurance-cancelled-date-wrapper" style="display: none;">
                        <div class="form-group">
                            <label for="insurance_canceled_date">Canceled Date</label>
                            <input type="date" name="insurance_canceled_date" id="insurance_canceled_date"
                                   class="form-control @error('insurance_canceled_date') is-invalid @enderror"
                                   value="{{ old('insurance_canceled_date') }}">
                            @error('insurance_canceled_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6" id="insurance-start-date-wrapper">
                        <div class="form-group">
                            <label for="insurance_start_date">Start Date</label>
                            <input type="date" name="insurance_start_date" id="insurance_start_date"
                                   class="form-control @error('insurance_start_date') is-invalid @enderror"
                                   value="{{ old('insurance_start_date') ?? ($latestInsuranceForForm && $latestInsuranceForForm->start_date ? $latestInsuranceForForm->start_date->format('Y-m-d') : '') }}">
                            @error('insurance_start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6" id="insurance-expiry-date-wrapper">
                        <div class="form-group">
                            <label for="insurance_expiry_date">Expiry Date</label>
                            <input type="date" name="insurance_expiry_date" id="insurance_expiry_date"
                                   class="form-control @error('insurance_expiry_date') is-invalid @enderror"
                                   value="{{ old('insurance_expiry_date') ?? ($latestInsuranceForForm && $latestInsuranceForForm->expiry_date ? $latestInsuranceForForm->expiry_date->format('Y-m-d') : '') }}">
                            @error('insurance_expiry_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6" id="insurance-notify-before-expiry-wrapper">
                        <div class="form-group">
                            <label for="insurance_notify_before_expiry">Notify Before Expiry (days)</label>
                            <input type="number" name="insurance_notify_before_expiry" id="insurance_notify_before_expiry"
                                   class="form-control @error('insurance_notify_before_expiry') is-invalid @enderror"
                                   value="{{ old('insurance_notify_before_expiry') ?? ($latestInsuranceForForm && $latestInsuranceForForm->notify_before_expiry ? $latestInsuranceForForm->notify_before_expiry : '30') }}"
                                   min="1">
                            @error('insurance_notify_before_expiry')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6" id="insurance-document-wrapper">
                        <div class="form-group">
                            <label for="insurance_document">Insurance Document</label>
                            <input type="file" name="insurance_document" id="insurance_document"
                                   class="form-control @error('insurance_document') is-invalid @enderror"
                                   accept=".pdf,.jpg,.jpeg,.png">
                            @if($latestInsuranceForForm && $latestInsuranceForForm->insurance_document)
                                <small class="text-muted">Current: <a href="{{ asset('uploads/cars/insurance_documents/' . $latestInsuranceForForm->insurance_document) }}" target="_blank">View Document</a></small>
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

@if(isset($model) && $model->id && $showInsuranceViewAll)
<div class="modal fade" id="editInsuranceHistoryModal" tabindex="-1" role="dialog" aria-labelledby="editInsuranceHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editInsuranceHistoryModalLabel">Previous insurance records</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Provider</th>
                                <th>Start</th>
                                <th>Expiry</th>
                                <th>Canceled Date</th>
                                <th>Document</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($olderInsurancesForModal as $insuranceH)
                            @php
                                $insuranceStatusName = strtolower(trim((string) optional($insuranceH->status)->name));
                            @endphp
                            @if($insuranceStatusName !== 'applied')
                            <tr>
                                <td>{{ $insuranceH->insuranceProvider->provider_name ?? '—' }}</td>
                                <td>{{ $insuranceH->start_date->format('d M, Y') }}</td>
                                <td>{{ $insuranceH->expiry_date->format('d M, Y') }}</td>
                                <td>{{ ($insuranceH->status && in_array(strtolower($insuranceH->status->name), ['cancelled', 'canceled'], true) && $insuranceH->canceled_date) ? $insuranceH->canceled_date->format('d M, Y') : '—' }}</td>
                                <td>
                                    @if($insuranceH->insurance_document)
                                        <a href="{{ asset('uploads/cars/insurance_documents/' . $insuranceH->insurance_document) }}" target="_blank" class="btn btn-sm btn-outline-primary">View</a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@php
    $activeReservationForForm = isset($model) && $model->id ? $model->activeReservation() : null;
    $reserveCarChecked = old('reserve_car', $activeReservationForForm ? 1 : 0);
@endphp

{{-- Reservation Information --}}
<div class="row mt-1" id="reservation-card-wrapper">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="reserve_car" name="reserve_car" value="1" {{ $reserveCarChecked ? 'checked' : '' }}>
                    <label class="form-check-label" for="reserve_car"><strong>Reserve this car for a customer</strong></label>
                </div>
            </div>
            <div class="card-body" id="reservation-section" style="display: none;">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="reservation_customer_name">Customer Name</label>
                            <input type="text" name="reservation_customer_name" id="reservation_customer_name" class="form-control @error('reservation_customer_name') is-invalid @enderror" value="{{ old('reservation_customer_name', $activeReservationForForm->customer_name ?? '') }}">
                            @error('reservation_customer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="reservation_customer_phone">Customer Phone</label>
                            <input type="text" name="reservation_customer_phone" id="reservation_customer_phone" class="form-control @error('reservation_customer_phone') is-invalid @enderror" value="{{ old('reservation_customer_phone', $activeReservationForForm->customer_phone ?? '') }}">
                            @error('reservation_customer_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="reservation_customer_email">Customer Email</label>
                            <input type="email" name="reservation_customer_email" id="reservation_customer_email" class="form-control @error('reservation_customer_email') is-invalid @enderror" value="{{ old('reservation_customer_email', $activeReservationForForm->customer_email ?? '') }}">
                            @error('reservation_customer_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="reservation_date">Reservation Date</label>
                            <input type="date" name="reservation_date" id="reservation_date" class="form-control @error('reservation_date') is-invalid @enderror" value="{{ old('reservation_date', $activeReservationForForm?->reservation_date?->format('Y-m-d')) }}">
                            @error('reservation_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="reservation_available_from_date">Available From</label>
                            <input type="date" name="reservation_available_from_date" id="reservation_available_from_date" class="form-control @error('reservation_available_from_date') is-invalid @enderror" value="{{ old('reservation_available_from_date', $activeReservationForForm?->available_from_date?->format('Y-m-d')) }}">
                            @error('reservation_available_from_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group mb-0">
                            <label for="reservation_terms_conditions">Terms & Conditions</label>
                            <textarea name="reservation_terms_conditions" id="reservation_terms_conditions" rows="3" class="form-control @error('reservation_terms_conditions') is-invalid @enderror">{{ old('reservation_terms_conditions', $activeReservationForForm->terms_conditions ?? '') }}</textarea>
                            @error('reservation_terms_conditions')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
        let motIndex = {{ $motMainCount + ($useMotsSplit ? $motsOlder->count() : 0) }};
        let roadTaxIndex = {{ $rtMainCount + ($useRoadTaxSplit ? $roadTaxesOlder->count() : 0) }};
        let phvIndex = {{ $phvMainCount + ($usePhvSplit ? $phvsOlder->count() : 0) }};

        const carsApiBase = {!! json_encode(url('/admin/cars')) !!};
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

        function deleteCarHistoryMot(carId, motId) {
            if (!confirm('Delete this MOT record?')) return;
            fetch(carsApiBase + '/' + carId + '/mots/' + motId, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            }).then(function (r) {
                if (!r.ok) throw new Error();
                return r.json();
            }).then(function () {
                window.location.reload();
            }).catch(function () {
                alert('Could not delete this record.');
            });
        }

        function deleteCarHistoryRoadTax(carId, roadTaxId) {
            if (!confirm('Delete this road tax record?')) return;
            fetch(carsApiBase + '/' + carId + '/road-taxes/' + roadTaxId, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            }).then(function (r) {
                if (!r.ok) throw new Error();
                return r.json();
            }).then(function () {
                window.location.reload();
            }).catch(function () {
                alert('Could not delete this record.');
            });
        }

        function deleteCarHistoryPhv(carId, phvId) {
            if (!confirm('Delete this PHV record?')) return;
            fetch(carsApiBase + '/' + carId + '/phvs/' + phvId, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            }).then(function (r) {
                if (!r.ok) throw new Error();
                return r.json();
            }).then(function () {
                window.location.reload();
            }).catch(function () {
                alert('Could not delete this record.');
            });
        }

        @if($isCarEdit)
        (function () {
            var endSornUrl = {!! json_encode(route('cars.end-sorn', $model)) !!};

            function escapeHtml(str) {
                if (str == null || str === '') {
                    return '';
                }
                var d = document.createElement('div');
                d.textContent = String(str);
                return d.innerHTML;
            }

            function buildSornDetailsBodyHtml(byName, atFormatted) {
                if (byName) {
                    var html = '<strong>' + escapeHtml(byName) + '</strong> applied for SORN for this car';
                    if (atFormatted) {
                        html += ' on <strong>' + escapeHtml(atFormatted) + '</strong>';
                    }
                    return html + '.';
                }
                var fallback = 'SORN was recorded for this car';
                if (atFormatted) {
                    fallback += ' on <strong>' + escapeHtml(atFormatted) + '</strong>';
                }
                return fallback + '.';
            }

            function attachFleetiqEndSornHandlers() {
                var sornDetailsEndBtn = document.getElementById('sornDetailsEndSornBtn');
                if (sornDetailsEndBtn && !sornDetailsEndBtn.dataset.fleetiqBound && window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) {
                    sornDetailsEndBtn.dataset.fleetiqBound = '1';
                    sornDetailsEndBtn.addEventListener('click', function () {
                        window.jQuery('#sornDetailsModal').one('hidden.bs.modal', function () {
                            window.jQuery('#endSornConfirmModal').modal('show');
                        });
                        window.jQuery('#sornDetailsModal').modal('hide');
                    });
                }
                var endSornConfirmBtnEl = document.getElementById('endSornConfirmBtn');
                if (endSornConfirmBtnEl && !endSornConfirmBtnEl.dataset.fleetiqBound) {
                    endSornConfirmBtnEl.dataset.fleetiqBound = '1';
                    endSornConfirmBtnEl.addEventListener('click', function () {
                        var overlay = document.getElementById('sornApplyOverlay');
                        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) {
                            window.jQuery('#endSornConfirmModal').modal('hide');
                        }
                        if (overlay) {
                            overlay.classList.remove('d-none');
                            overlay.classList.add('d-flex');
                        }
                        fetch(endSornUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'Content-Type': 'application/json',
                            },
                            credentials: 'same-origin',
                        }).then(function (r) {
                            return r.json().then(function (data) {
                                if (!r.ok) {
                                    throw new Error((data && data.message) || 'Request failed');
                                }
                                return data;
                            });
                        }).then(function (data) {
                            if (data.ok) {
                                window.location.reload();
                            } else {
                                throw new Error();
                            }
                        }).catch(function (err) {
                            if (overlay) {
                                overlay.classList.add('d-none');
                                overlay.classList.remove('d-flex');
                            }
                            alert(err.message || 'Could not end SORN. Please try again.');
                        });
                    });
                }
            }

            function mountSornModalsAfterApply(byName, atFormatted, proofUrl) {
                if (document.getElementById('sornDetailsModal')) {
                    return;
                }
                var tpl = document.getElementById('tplSornAppliedModals');
                if (!tpl || !tpl.content) {
                    return;
                }
                var frag = tpl.content.cloneNode(true);
                var p = frag.querySelector('#sornDetailsModalBodyLine');
                if (p) {
                    p.innerHTML = buildSornDetailsBodyHtml(byName, atFormatted);
                }
                var proofLine = frag.querySelector('#sornDetailsModalProofLine');
                var proofLink = frag.querySelector('#sornDetailsModalProofLink');
                if (proofLine && proofLink && proofUrl) {
                    proofLink.href = proofUrl;
                    proofLine.classList.remove('d-none');
                }
                document.body.appendChild(frag);
            }

            function promoteSornToolbarButton() {
                var toolbarBtn = document.getElementById('carSornToolbarBtn');
                if (!toolbarBtn || toolbarBtn.getAttribute('data-sorn-toolbar-state') === 'applied') {
                    return;
                }
                toolbarBtn.setAttribute('data-sorn-toolbar-state', 'applied');
                toolbarBtn.type = 'button';
                toolbarBtn.className = 'btn btn-sm btn-success mr-1';
                toolbarBtn.setAttribute('data-toggle', 'modal');
                toolbarBtn.setAttribute('data-target', '#sornDetailsModal');
                toolbarBtn.setAttribute('title', 'View SORN details');
                toolbarBtn.innerHTML = '<i class="fa fa-check"></i> SORN Applied';
            }

            function removeApplySornModal() {
                var applyModalEl = document.getElementById('applySornModal');
                if (applyModalEl) {
                    applyModalEl.remove();
                }
            }

            @if(!$model->sorn_applied)
            var applySornUrl = {!! json_encode(route('cars.apply-sorn', $model)) !!};
            var applySornConfirmBtnEl = document.getElementById('applySornConfirmBtn');
            if (applySornConfirmBtnEl) {
                applySornConfirmBtnEl.addEventListener('click', function () {
                    var overlay = document.getElementById('sornApplyOverlay');
                    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) {
                        window.jQuery('#applySornModal').modal('hide');
                    } else {
                        var m = document.getElementById('applySornModal');
                        if (m) {
                            m.classList.remove('show');
                            m.setAttribute('aria-hidden', 'true');
                            m.style.display = 'none';
                            document.body.classList.remove('modal-open');
                            var bd = document.querySelectorAll('.modal-backdrop');
                            bd.forEach(function (el) {
                                el.remove();
                            });
                        }
                    }
                    if (overlay) {
                        overlay.classList.remove('d-none');
                        overlay.classList.add('d-flex');
                    }
                    var fd = new FormData();
                    fd.append('_token', csrfToken);
                    var proofInput = document.getElementById('apply_sorn_proof');
                    if (proofInput && proofInput.files && proofInput.files.length) {
                        fd.append('sorn_proof', proofInput.files[0]);
                    }
                    fetch(applySornUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        body: fd,
                    }).then(function (r) {
                        return r.json().then(function (data) {
                            if (!r.ok) {
                                var msg = (data && data.message) || 'Request failed';
                                if (data && data.errors && data.errors.sorn_proof) {
                                    msg = data.errors.sorn_proof.join(' ');
                                }
                                throw new Error(msg);
                            }
                            return data;
                        });
                    }).then(function (data) {
                        if (overlay) {
                            overlay.classList.add('d-none');
                            overlay.classList.remove('d-flex');
                        }
                        if (!(data.ok && data.gov_sorn_url)) {
                            throw new Error();
                        }
                        mountSornModalsAfterApply(data.sorn_applied_by_name, data.sorn_applied_at_formatted, data.sorn_proof_url || null);
                        removeApplySornModal();
                        promoteSornToolbarButton();
                        attachFleetiqEndSornHandlers();
                        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) {
                            window.jQuery('#sornAppliedSuccessModal').modal('show');
                        }
                    }).catch(function (err) {
                        if (overlay) {
                            overlay.classList.add('d-none');
                            overlay.classList.remove('d-flex');
                        }
                        alert(err.message || 'Could not apply SORN. Please try again.');
                    });
                });
            }
            @else
            attachFleetiqEndSornHandlers();
            @endif
        })();
        @endif

        const todayYmd = new Date().toISOString().slice(0, 10);
        const hasExistingInsuranceDocument = @json($hasExistingInsuranceDocument);

        function shouldHideLogBookForV5() {
            const el = document.getElementById('v5_document');
            if (!el) return false;
            if (el.getAttribute('data-has-v5') === '1') return true;
            return !!(el.files && el.files.length);
        }

        function toggleLogBookSection() {
            if (shouldHideLogBookForV5()) return;
            const cb = document.getElementById('log_book_applied');
            const section = document.getElementById('log-book-section');
            const dateInput = document.getElementById('log_book_applied_date');
            if (!cb || !section) return;
            if (cb.checked) {
                section.style.display = 'block';
                if (dateInput && !dateInput.value) {
                    dateInput.value = todayYmd;
                }
            } else {
                section.style.display = 'none';
            }
        }

        function applyLogBookV5Rules() {
            const wrapper = document.getElementById('log-book-ui-wrapper');
            const preservation = document.getElementById('log-book-preservation-fields');
            const jsPres = document.getElementById('log-book-js-preservation');
            const cb = document.getElementById('log_book_applied');
            const dateInput = document.getElementById('log_book_applied_date');
            const fileInput = document.getElementById('old_log_book');
            if (!wrapper || !jsPres) return;

            const hide = shouldHideLogBookForV5();
            const appliedWas = cb && cb.checked ? '1' : '0';
            const dateWas = dateInput ? (dateInput.value || '') : '';

            if (hide) {
                wrapper.classList.add('d-none');
                if (cb) cb.disabled = true;
                if (dateInput) dateInput.disabled = true;
                if (fileInput) fileInput.disabled = true;

                if (preservation) {
                    jsPres.innerHTML = '';
                } else {
                    jsPres.innerHTML = '';
                    const h1 = document.createElement('input');
                    h1.type = 'hidden';
                    h1.name = 'log_book_applied';
                    h1.value = appliedWas;
                    jsPres.appendChild(h1);
                    if (appliedWas === '1') {
                        const h2 = document.createElement('input');
                        h2.type = 'hidden';
                        h2.name = 'log_book_applied_date';
                        h2.value = dateWas;
                        jsPres.appendChild(h2);
                    }
                }
            } else {
                wrapper.classList.remove('d-none');
                if (cb) cb.disabled = false;
                if (dateInput) dateInput.disabled = false;
                if (fileInput) fileInput.disabled = false;
                jsPres.innerHTML = '';
                toggleLogBookSection();
            }
        }

        function toggleReservationSection() {
            const cb = document.getElementById('reserve_car');
            const section = document.getElementById('reservation-section');
            const reservationDate = document.getElementById('reservation_date');
            if (!cb || !section) return;
            if (document.getElementById('car_current_fleet_status')?.value === 'damaged') {
                cb.checked = false;
                section.style.display = 'none';
                return;
            }
            section.style.display = cb.checked ? 'block' : 'none';
            if (cb.checked && reservationDate && !reservationDate.value) {
                reservationDate.value = todayYmd;
            }
        }

        function toggleDamagedStatusSections() {
            const isDamaged = document.getElementById('car_current_fleet_status')?.value === 'damaged';
            const damagedNotes = document.getElementById('damaged-notes-wrapper');
            const reservationCard = document.getElementById('reservation-card-wrapper');
            const reserveCheckbox = document.getElementById('reserve_car');

            if (damagedNotes) {
                damagedNotes.style.display = isDamaged ? 'block' : 'none';
            }

            if (reservationCard) {
                reservationCard.style.display = isDamaged ? 'none' : 'flex';
            }

            if (isDamaged && reserveCheckbox) {
                reserveCheckbox.checked = false;
            }

            toggleReservationSection();
        }

        function preventEnterFormSubmit() {
            const submitButton = document.querySelector('button[type="submit"]');
            const form = submitButton ? submitButton.closest('form') : null;
            if (!form) return;

            form.addEventListener('keydown', function (event) {
                if (event.key !== 'Enter') return;

                const target = event.target;
                const tagName = target.tagName ? target.tagName.toLowerCase() : '';
                const inputType = (target.getAttribute('type') || '').toLowerCase();
                const isTextArea = tagName === 'textarea';
                const isButton = tagName === 'button' || inputType === 'button' || inputType === 'submit';

                if (!isTextArea && !isButton) {
                    event.preventDefault();
                }
            });
        }

        function togglePhvStatusFields() {
            const phvStatus = document.getElementById('phv_status');
            const appliedDateWrapper = document.getElementById('phv-applied-date-wrapper');
            if (!phvStatus || !appliedDateWrapper) return;

            appliedDateWrapper.style.display = phvStatus.value === 'applied' ? 'block' : 'none';
        }

        function hidePhvStatusForNewActiveLicense(expiryInput) {
            const row = expiryInput.closest('.phv-item');
            const isExistingRow = row && row.querySelector('input[type="hidden"][name$="[id]"]');
            if (isExistingRow || !expiryInput.value) return;

            const expiryDate = new Date(expiryInput.value + 'T00:00:00');
            const today = new Date(todayYmd + 'T00:00:00');
            if (expiryDate < today) return;

            const phvStatus = document.getElementById('phv_status');
            const statusWrapper = document.getElementById('phv-status-wrapper');
            const appliedDateWrapper = document.getElementById('phv-applied-date-wrapper');
            if (phvStatus) {
                phvStatus.value = 'phv_active';
            }
            if (statusWrapper) {
                statusWrapper.style.display = 'none';
            }
            if (appliedDateWrapper) {
                appliedDateWrapper.style.display = 'none';
            }
        }

        function bindPhvExpiryStatusAutomation(scope) {
            (scope || document).querySelectorAll('.phv-item input[name$="[expiry_date]"]').forEach(function (expiryInput) {
                expiryInput.addEventListener('change', function () {
                    hidePhvStatusForNewActiveLicense(expiryInput);
                });
            });
        }

        const allInsuranceProviders = @json($insuranceProviders->map(function($provider) {
            return [
                'id' => $provider->id,
                'company_id' => $provider->company_id,
                'provider_name' => $provider->provider_name
            ];
        }));

        function filterInsuranceProviders() {
            const companyId = document.getElementById('company_id').value;
            const insuranceProviderSelect = document.getElementById('insurance_provider_id');
            const selectedProviderId = insuranceProviderSelect.value;

            insuranceProviderSelect.innerHTML = '<option value="">Select Provider</option>';

            if (companyId) {
                const filteredProviders = allInsuranceProviders.filter(provider => provider.company_id == companyId);

                filteredProviders.forEach(provider => {
                    const option = document.createElement('option');
                    option.value = provider.id;
                    option.textContent = provider.provider_name;
                    option.setAttribute('data-company-id', provider.company_id);

                    if (provider.id == selectedProviderId) {
                        option.selected = true;
                    }

                    insuranceProviderSelect.appendChild(option);
                });
            }
        }

        // ✅ Insurance Section Toggle
        function toggleInsuranceSection() {
            const hasInsuranceCheckbox = document.getElementById('has_insurance');
            const insuranceSection = document.getElementById('insurance-section');
            const insuranceProvider = document.getElementById('insurance_provider_id');
            const insuranceStatus = document.getElementById('insurance_status_id');
            const insuranceStart = document.getElementById('insurance_start_date');
            const insuranceExpiry = document.getElementById('insurance_expiry_date');
            const insuranceNotify = document.getElementById('insurance_notify_before_expiry');
            const insuranceAppliedDate = document.getElementById('insurance_applied_date');

            if (hasInsuranceCheckbox.checked) {
                insuranceSection.style.display = 'block';
                insuranceProvider.setAttribute('required', 'required');
                insuranceStatus.setAttribute('required', 'required');
            } else {
                insuranceSection.style.display = 'none';
                insuranceProvider.removeAttribute('required');
                insuranceStatus.removeAttribute('required');
                insuranceStart.removeAttribute('required');
                insuranceExpiry.removeAttribute('required');
                insuranceNotify.removeAttribute('required');
                insuranceAppliedDate.removeAttribute('required');
            }

            toggleInsuranceStatusFields();
        }

        function toggleInsuranceStatusFields() {
            const hasInsuranceCheckbox = document.getElementById('has_insurance');
            const insuranceStatusSelect = document.getElementById('insurance_status_id');
            const selectedOption = insuranceStatusSelect ? insuranceStatusSelect.options[insuranceStatusSelect.selectedIndex] : null;
            const selectedStatusName = (selectedOption && selectedOption.dataset.statusName ? selectedOption.dataset.statusName : '').trim().toLowerCase();
            const isApplied = selectedStatusName === 'applied';
            const isCancelled = selectedStatusName === 'cancelled' || selectedStatusName === 'canceled';

            const appliedDateWrapper = document.getElementById('insurance-applied-date-wrapper');
            const cancelledDateWrapper = document.getElementById('insurance-cancelled-date-wrapper');
            const startDateWrapper = document.getElementById('insurance-start-date-wrapper');
            const expiryDateWrapper = document.getElementById('insurance-expiry-date-wrapper');
            const notifyWrapper = document.getElementById('insurance-notify-before-expiry-wrapper');
            const documentWrapper = document.getElementById('insurance-document-wrapper');

            const insuranceStart = document.getElementById('insurance_start_date');
            const insuranceExpiry = document.getElementById('insurance_expiry_date');
            const insuranceNotify = document.getElementById('insurance_notify_before_expiry');
            const insuranceAppliedDate = document.getElementById('insurance_applied_date');
            const insuranceCanceledDate = document.getElementById('insurance_canceled_date');
            const insuranceDocument = document.getElementById('insurance_document');

            const showInsuranceFields = hasInsuranceCheckbox && hasInsuranceCheckbox.checked;
            if (!showInsuranceFields) {
                appliedDateWrapper.style.display = 'none';
                cancelledDateWrapper.style.display = 'none';
                startDateWrapper.style.display = 'none';
                expiryDateWrapper.style.display = 'none';
                notifyWrapper.style.display = 'none';
                documentWrapper.style.display = 'none';
                insuranceStart.removeAttribute('required');
                insuranceExpiry.removeAttribute('required');
                insuranceNotify.removeAttribute('required');
                insuranceAppliedDate.removeAttribute('required');
                insuranceCanceledDate.removeAttribute('required');
                insuranceDocument.removeAttribute('required');
                return;
            }

            appliedDateWrapper.style.display = isApplied ? 'block' : 'none';
            cancelledDateWrapper.style.display = isCancelled ? 'block' : 'none';
            startDateWrapper.style.display = (isApplied || isCancelled) ? 'none' : 'block';
            expiryDateWrapper.style.display = (isApplied || isCancelled) ? 'none' : 'block';
            notifyWrapper.style.display = (isApplied || isCancelled) ? 'none' : 'block';
            const shouldShowDocumentField = !isApplied && !(isCancelled && hasExistingInsuranceDocument);
            documentWrapper.style.display = shouldShowDocumentField ? 'block' : 'none';

            if (isApplied) {
                insuranceAppliedDate.setAttribute('required', 'required');
                insuranceStart.removeAttribute('required');
                insuranceExpiry.removeAttribute('required');
                insuranceNotify.removeAttribute('required');
                insuranceCanceledDate.removeAttribute('required');
                insuranceDocument.removeAttribute('required');
            } else if (isCancelled) {
                insuranceAppliedDate.removeAttribute('required');
                insuranceStart.removeAttribute('required');
                insuranceExpiry.removeAttribute('required');
                insuranceNotify.removeAttribute('required');
                insuranceCanceledDate.setAttribute('required', 'required');
                insuranceDocument.removeAttribute('required');
            } else {
                insuranceAppliedDate.removeAttribute('required');
                insuranceCanceledDate.removeAttribute('required');
                insuranceStart.setAttribute('required', 'required');
                insuranceExpiry.setAttribute('required', 'required');
                insuranceNotify.setAttribute('required', 'required');
                insuranceDocument.removeAttribute('required');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            filterInsuranceProviders();
            toggleInsuranceSection();
            applyLogBookV5Rules();
            toggleDamagedStatusSections();
            toggleReservationSection();
            togglePhvStatusFields();
            bindPhvExpiryStatusAutomation(document);
            preventEnterFormSubmit();
            (function defaultEmptyAppliedDate() {
                if (shouldHideLogBookForV5()) return;
                const dateInput = document.getElementById('log_book_applied_date');
                const cb = document.getElementById('log_book_applied');
                if (cb && cb.checked && dateInput && !dateInput.value) {
                    dateInput.value = todayYmd;
                }
            })();

            document.getElementById('company_id').addEventListener('change', filterInsuranceProviders);
            document.getElementById('has_insurance').addEventListener('change', toggleInsuranceSection);
            document.getElementById('insurance_status_id').addEventListener('change', toggleInsuranceStatusFields);
            const v5DocInput = document.getElementById('v5_document');
            if (v5DocInput) {
                v5DocInput.addEventListener('change', applyLogBookV5Rules);
            }
            document.getElementById('log_book_applied').addEventListener('change', applyLogBookV5Rules);
            document.getElementById('reserve_car').addEventListener('change', toggleReservationSection);
            document.getElementById('phv_status').addEventListener('change', togglePhvStatusFields);
        });

        function addMOT() {
            const container = document.getElementById('mots-container');
            const newMOT = `
        <div class="mot-item row border-bottom pb-3 mb-1" data-index="${motIndex}">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Expiry Date</label>
                    <input type="date" name="mots[${motIndex}][expiry_date]" class="form-control">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Amount</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">£</span>
                        </div>
                        <input type="number" name="mots[${motIndex}][amount]" class="form-control" step="0.01" min="0">
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Term</label>
                    <input type="text" name="mots[${motIndex}][term]" class="form-control" placeholder="e.g. 12 months">
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
            var sornNotice = document.getElementById('roadtax-sorn-notice');
            if (sornNotice) {
                sornNotice.classList.add('d-none');
            }
            const container = document.getElementById('roadtax-container');
            container.classList.remove('d-none');
            const newRoadTax = `
        <div class="roadtax-item row border-bottom pb-3 mb-1" data-index="${roadTaxIndex}">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="road_taxes[${roadTaxIndex}][start_date]" class="form-control">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Term</label>
                    <select name="road_taxes[${roadTaxIndex}][term]" class="form-control">
                        <option value="">Select Term</option>
                        <option value="6 months">6 Months</option>
                        <option value="12 months">12 Months</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Amount</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">£</span>
                        </div>
                        <input type="number" name="road_taxes[${roadTaxIndex}][amount]" class="form-control" step="0.01" min="0">
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

            let counselOptionsHtml = '<option value="">Select Council</option>';
            counselOptions.forEach(counsel => {
                counselOptionsHtml += `<option value="${counsel.id}">${counsel.name}</option>`;
            });

            const newPHV = `
        <div class="phv-item row border-bottom pb-3 mb-1" data-index="${phvIndex}">
            <div class="col-md-2">
                <div class="form-group">
                    <label>Council</label>
                    <select name="phvs[${phvIndex}][counsel_id]" class="form-control">
                        ${counselOptionsHtml}
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Amount</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">£</span>
                        </div>
                        <input type="number" name="phvs[${phvIndex}][amount]" class="form-control" step="0.01" min="0">
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="phvs[${phvIndex}][start_date]" class="form-control">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Expiry Date</label>
                    <input type="date" name="phvs[${phvIndex}][expiry_date]" class="form-control">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Notify (days)</label>
                    <input type="number" name="phvs[${phvIndex}][notify_before_expiry]" class="form-control" min="1">
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
            bindPhvExpiryStatusAutomation(container.lastElementChild);
            phvIndex++;
        }

        function removePHV(button) {
            button.closest('.phv-item').remove();
        }

        // VIN validation
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
