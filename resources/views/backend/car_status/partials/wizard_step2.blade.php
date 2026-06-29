@php
    use App\Models\VehicleSwap;

    $fleetLabels = $fleetLabels ?? [];
    $prefillCarId = $prefillCarId ?? null;
    $prefillTargetStatus = $prefillTargetStatus ?? null;
    $prefillStatusPayload = is_array($prefillStatusPayload ?? null) ? $prefillStatusPayload : [];
    $editCurrentStatus = ! empty($editCurrentStatus);
    $activeTargetStatus = old('target_status', $prefillTargetStatus);
    $activeCarId = old('car_id', $prefillCarId);
    $payloadOld = fn (string $key, $default = '') => old('payload.'.$key, $prefillStatusPayload[$key] ?? $default);

    $swapReasonOld = old('reason_for_swap', '');
    $phvlTypeOld = old('phvl_issue_type', '');

    $step2OldCarId = $activeCarId;
    $step2OldTarget = $activeTargetStatus;
    $step2Reg = '';
    if ($step2OldCarId !== null && $step2OldCarId !== '') {
        $step2Car = $cars->firstWhere('id', $step2OldCarId);
        $step2Reg = $step2Car?->registration ?? '';
    }
    $step2StatusLabel = ($step2OldTarget && isset($fleetLabels[$step2OldTarget])) ? $fleetLabels[$step2OldTarget] : '';
    $step2SummaryText = ($step2Reg !== '' && $step2StatusLabel !== '')
        ? ($editCurrentStatus
            ? "{$step2Reg} — editing current {$step2StatusLabel} details"
            : "{$step2Reg} status is updating to {$step2StatusLabel}")
        : '';
    $existingSoldDocs = ($editCurrentStatus && $activeTargetStatus === 'sold' && is_array($prefillStatusPayload['documents'] ?? null))
        ? array_values(array_filter($prefillStatusPayload['documents'], fn ($doc) => is_string($doc) && $doc !== ''))
        : [];

    $carFleetFlags = $carFleetFlags ?? [];
    $fleetAvailWarnShow = false;
    $fleetAvailWarnItems = [];
    if ($step2OldTarget === 'available_for_rent' && $step2OldCarId !== null && $step2OldCarId !== '') {
        $wf = $carFleetFlags[$step2OldCarId] ?? null;
        if (is_array($wf)) {
            if (! empty($wf['active_reservation'])) {
                $fleetAvailWarnItems[] = 'This car has an <strong>active reservation</strong>. Submitting will cancel that reservation and mark the car available for rent.';
                $fleetAvailWarnShow = true;
            }
            if (! empty($wf['active_swap'])) {
                $fleetAvailWarnItems[] = 'This car is in an <strong>active vehicle swap</strong>. Submitting will remove the swap and update fleet status for the vehicles involved.';
                $fleetAvailWarnShow = true;
            }
        }
    }
@endphp

<div id="fleet_step2" class="{{ $activeTargetStatus ? '' : 'd-none' }}">
    <h5 id="fleet_step2_summary" class="border-bottom pb-2 mb-3 text-center">{{ $step2SummaryText }}</h5>

    <input type="hidden" name="swapped_with_car_id" id="fleet_hidden_swapped_with_car_id"
           value="{{ old('swapped_with_car_id') }}">

    {{-- Available for rent --}}
    <div class="fleet-status-panel {{ $activeTargetStatus === 'available_for_rent' ? '' : 'd-none' }}"
         data-status="available_for_rent">
        <div id="fleet_available_rent_warning"
             class="alert alert-warning mb-3 {{ $fleetAvailWarnShow ? '' : 'd-none' }}"
             role="alert">
            @if($fleetAvailWarnShow)
                <strong>Warning:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($fleetAvailWarnItems as $item)
                        <li>{!! $item !!}</li>
                    @endforeach
                </ul>
            @endif
        </div>
        <p class="text-muted mb-0">Confirm marking this vehicle as available for rent. Submit to save.</p>
    </div>

    {{-- PHVL Preparation --}}
    <div class="fleet-status-panel {{ $activeTargetStatus === \App\Models\Car::FLEET_STATUS_PREPARATION_FOR_PHVL ? '' : 'd-none' }}"
         data-status="{{ \App\Models\Car::FLEET_STATUS_PREPARATION_FOR_PHVL }}">
        <p class="text-muted mb-0">Confirm marking this vehicle as PHVL preparation. Submit to save.</p>
    </div>

    {{-- Non-Compliant --}}
    <div class="fleet-status-panel {{ $activeTargetStatus === \App\Models\Car::FLEET_STATUS_NON_COMPLIANT ? '' : 'd-none' }}"
         data-status="{{ \App\Models\Car::FLEET_STATUS_NON_COMPLIANT }}">
        <p class="text-muted mb-0">Confirm marking this vehicle as non-compliant. Submit to save.</p>
    </div>

    {{-- Reserved --}}
    <div class="fleet-status-panel {{ $activeTargetStatus === 'reserved' ? '' : 'd-none' }}" data-status="reserved">
        <div class="row">
            <div class="col-md-4 form-group">
                <label for="fleet_rsv_customer_name">Client's name <span class="text-danger">*</span></label>
                <input type="text" name="customer_name" id="fleet_rsv_customer_name"
                       class="form-control @error('customer_name') is-invalid @enderror"
                       maxlength="255" value="{{ old('customer_name') }}">
                @error('customer_name')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4 form-group">
                <label for="fleet_rsv_customer_phone">Contact</label>
                <input type="text" name="customer_phone" id="fleet_rsv_customer_phone"
                       class="form-control @error('customer_phone') is-invalid @enderror"
                       maxlength="50" value="{{ old('customer_phone') }}">
                @error('customer_phone')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4 form-group">
                <label for="fleet_rsv_customer_email">Email</label>
                <input type="email" name="customer_email" id="fleet_rsv_customer_email"
                       class="form-control @error('customer_email') is-invalid @enderror"
                       maxlength="255" value="{{ old('customer_email') }}">
                @error('customer_email')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 form-group">
                <label for="fleet_rsv_reservation_date">Reservation date <span class="text-danger">*</span></label>
                <input type="date" name="reservation_date" id="fleet_rsv_reservation_date"
                       class="form-control @error('reservation_date') is-invalid @enderror"
                       value="{{ old('reservation_date', now()->toDateString()) }}">
                @error('reservation_date')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 form-group">
                <label for="fleet_rsv_pick_up_date">Pick up date <span class="text-danger">*</span></label>
                <input type="date" name="pick_up_date" id="fleet_rsv_pick_up_date"
                       class="form-control @error('pick_up_date') is-invalid @enderror"
                       value="{{ old('pick_up_date') }}">
                @error('pick_up_date')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4 form-group">
                <label for="fleet_rsv_agreed_rent">Agreed rent <span class="text-danger">*</span></label>
                <input type="number" name="agreed_rent" id="fleet_rsv_agreed_rent"
                       class="form-control @error('agreed_rent') is-invalid @enderror"
                       step="0.01" min="0" value="{{ old('agreed_rent') }}">
                @error('agreed_rent')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4 form-group">
                <label for="fleet_rsv_agreed_advance">Agreed advance <span class="text-danger">*</span></label>
                <input type="number" name="agreed_advance" id="fleet_rsv_agreed_advance"
                       class="form-control @error('agreed_advance') is-invalid @enderror"
                       step="0.01" min="0" value="{{ old('agreed_advance') }}">
                @error('agreed_advance')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4 form-group">
                <label for="fleet_rsv_amount_paid">Amount paid <span class="text-danger">*</span></label>
                <input type="number" name="amount_paid" id="fleet_rsv_amount_paid"
                       class="form-control @error('amount_paid') is-invalid @enderror"
                       step="0.01" min="0" value="{{ old('amount_paid') }}">
                @error('amount_paid')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-12 form-group">
                <label for="fleet_rsv_balance_payable_display">Balance payable on pick up</label>
                <input type="text" id="fleet_rsv_balance_payable_display" class="form-control"
                       readonly tabindex="-1" value="">
                <small class="text-muted">Auto-calculated from agreed rent + agreed advance − amount paid.</small>
            </div>
        </div>
    </div>

    {{-- Vehicle swap (replacement = Step 1 car; old car selected here) --}}
    <div class="fleet-status-panel {{ $activeTargetStatus === 'vehicle_swap' ? '' : 'd-none' }}"
         data-status="vehicle_swap">
        <div class="row">
            <div class="col-md-12 form-group">
                <label for="fleet_swap_old_car_id">Old car (driver’s previous vehicle) <span
                            class="text-danger">*</span></label>
                <select name="old_car_id" id="fleet_swap_old_car_id"
                        class="form-control select-search @error('old_car_id') is-invalid @enderror">
                    <option value="">— Select —</option>
                    @foreach($cars as $car)
                        <option value="{{ $car->id }}"
                            {{ (string) old('old_car_id') === (string) $car->id ? 'selected' : '' }}>
                            {{ $car->registration }} — {{ $car->carModel->name ?? '' }}</option>
                    @endforeach
                </select>
                @error('old_car_id')
                <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                @error('car_id')
                <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4 form-group">
                <label for="fleet_swap_customer_name">Client's name <span class="text-danger">*</span></label>
                <input type="text" name="customer_name" id="fleet_swap_customer_name"
                       class="form-control @error('customer_name') is-invalid @enderror"
                       maxlength="255" value="{{ old('customer_name') }}">
                @error('customer_name')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4 form-group">
                <label for="fleet_swap_customer_phone">Contact</label>
                <input type="text" name="customer_phone" id="fleet_swap_customer_phone"
                       class="form-control @error('customer_phone') is-invalid @enderror"
                       maxlength="50" value="{{ old('customer_phone') }}">
                @error('customer_phone')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4 form-group">
                <label for="fleet_swap_customer_email">Email</label>
                <input type="email" name="customer_email" id="fleet_swap_customer_email"
                       class="form-control @error('customer_email') is-invalid @enderror"
                       maxlength="255" value="{{ old('customer_email') }}">
                @error('customer_email')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 form-group">
                <label for="fleet_swap_reservation_date">Reservation date <span class="text-danger">*</span></label>
                <input type="date" name="reservation_date" id="fleet_swap_reservation_date"
                       class="form-control @error('reservation_date') is-invalid @enderror"
                       value="{{ old('reservation_date', now()->toDateString()) }}">
                @error('reservation_date')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 form-group">
                <label for="fleet_swap_pick_up_date">Pick up date <span class="text-danger">*</span></label>
                <input type="date" name="pick_up_date" id="fleet_swap_pick_up_date"
                       class="form-control @error('pick_up_date') is-invalid @enderror"
                       value="{{ old('pick_up_date') }}">
                @error('pick_up_date')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4 form-group">
                <label for="fleet_swap_agreed_rent">Agreed rent <span class="text-danger">*</span></label>
                <input type="number" name="agreed_rent" id="fleet_swap_agreed_rent"
                       class="form-control @error('agreed_rent') is-invalid @enderror"
                       step="0.01" min="0" value="{{ old('agreed_rent') }}">
                @error('agreed_rent')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4 form-group">
                <label for="fleet_swap_agreed_advance">Agreed advance <span class="text-danger">*</span></label>
                <input type="number" name="agreed_advance" id="fleet_swap_agreed_advance"
                       class="form-control @error('agreed_advance') is-invalid @enderror"
                       step="0.01" min="0" value="{{ old('agreed_advance') }}">
                @error('agreed_advance')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4 form-group">
                <label for="fleet_swap_amount_paid">Amount paid <span class="text-danger">*</span></label>
                <input type="number" name="amount_paid" id="fleet_swap_amount_paid"
                       class="form-control @error('amount_paid') is-invalid @enderror"
                       step="0.01" min="0" value="{{ old('amount_paid') }}">
                @error('amount_paid')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-12 form-group">
                <label for="fleet_swap_balance_payable_display">Balance payable on pick up</label>
                <input type="text" id="fleet_swap_balance_payable_display" class="form-control"
                       readonly tabindex="-1" value="">
                <small class="text-muted">Auto-calculated from agreed rent + agreed advance − amount paid.</small>
            </div>

            <div class="col-md-6 form-group">
                <label for="fleet_swap_reason_for_swap">Reason for swap <span class="text-danger">*</span></label>
                <select name="reason_for_swap" id="fleet_swap_reason_for_swap"
                        class="form-control @error('reason_for_swap') is-invalid @enderror">
                    <option value="">— Select —</option>
                    @foreach(VehicleSwap::reasonLabels() as $value => $label)
                        <option value="{{ $value }}" {{ (string) $swapReasonOld === (string) $value ? 'selected' : '' }}>
                            {{ $label }}</option>
                    @endforeach
                </select>
                @error('reason_for_swap')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 form-group d-none" id="fleet_swap_phvl_issue_type_wrap">
                <label for="fleet_swap_phvl_issue_type">PHVL issue type <span class="text-danger">*</span></label>
                <select name="phvl_issue_type" id="fleet_swap_phvl_issue_type"
                        class="form-control @error('phvl_issue_type') is-invalid @enderror">
                    <option value="">— Select —</option>
                    @foreach(VehicleSwap::phvlIssueTypeLabels() as $value => $label)
                        <option value="{{ $value }}" {{ (string) $phvlTypeOld === (string) $value ? 'selected' : '' }}>
                            {{ $label }}</option>
                    @endforeach
                </select>
                @error('phvl_issue_type')
                <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-12 form-group d-none" id="fleet_swap_phvl_issue_notes_wrap">
                <label for="fleet_swap_phvl_issue_notes">PHVL issue notes <span class="text-danger">*</span></label>
                <textarea name="phvl_issue_notes" id="fleet_swap_phvl_issue_notes" rows="3"
                          class="form-control @error('phvl_issue_notes') is-invalid @enderror">{{ old('phvl_issue_notes') }}</textarea>
                @error('phvl_issue_notes')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-12 form-group d-none" id="fleet_swap_reason_notes_wrap">
                <label for="fleet_swap_reason_notes">Reason notes <span class="text-danger">*</span></label>
                <textarea name="reason_notes" id="fleet_swap_reason_notes" rows="3"
                          class="form-control @error('reason_notes') is-invalid @enderror">{{ old('reason_notes') }}</textarea>
                @error('reason_notes')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    {{-- Damaged --}}
    <div class="fleet-status-panel {{ $activeTargetStatus === 'damaged' ? '' : 'd-none' }}" data-status="damaged">
        <div class="row">
            <div class="col-md-6 form-group">
                <label for="fleet_damaged_damage_date">Damage date <span class="text-danger">*</span></label>
                <input type="date" name="payload[damage_date]" id="fleet_damaged_damage_date"
                       class="form-control @error('payload.damage_date') is-invalid @enderror"
                       value="{{ old('payload.damage_date') }}">
                @error('payload.damage_date')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 form-group">
                <label for="fleet_damaged_driver_id">Driver</label>
                <select name="payload[driver_id]" id="fleet_damaged_driver_id"
                        class="form-control select-search @error('payload.driver_id') is-invalid @enderror">
                    <option value="">— Optional —</option>
                    @include('backend.drivers._select_options', [
                        'drivers' => $drivers,
                        'selectedId' => $payloadOld('driver_id'),
                    ])
                </select>
                @error('payload.driver_id')
                <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 form-group">
                <label for="fleet_damaged_insurance_status">Insurance <span class="text-danger">*</span></label>
                <select name="payload[insurance_status]" id="fleet_damaged_insurance_status"
                        class="form-control @error('payload.insurance_status') is-invalid @enderror">
                    <option value="">— Select —</option>
                    <option value="company" {{ $payloadOld('insurance_status') === 'company' ? 'selected' : '' }}>
                        Company</option>
                    <option value="driver" {{ $payloadOld('insurance_status') === 'driver' ? 'selected' : '' }}>
                        Driver</option>
                </select>
                @error('payload.insurance_status')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 form-group">
                <label for="fleet_damaged_insurance_excess">Insurance excess amount <span
                            class="text-danger">*</span></label>
                <input type="number" name="payload[insurance_excess_amount]" id="fleet_damaged_insurance_excess"
                       class="form-control @error('payload.insurance_excess_amount') is-invalid @enderror"
                       step="0.01" min="0" value="{{ old('payload.insurance_excess_amount') }}">
                @error('payload.insurance_excess_amount')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 form-group">
                <label for="fleet_damaged_fault_type">Fault type <span class="text-danger">*</span></label>
                <select name="payload[fault_type]" id="fleet_damaged_fault_type"
                        class="form-control fleet-damaged-fault-select @error('payload.fault_type') is-invalid @enderror">
                    <option value="">— Select —</option>
                    <option value="fault" {{ old('payload.fault_type') === 'fault' ? 'selected' : '' }}>Fault</option>
                    <option value="non_fault" {{ old('payload.fault_type') === 'non_fault' ? 'selected' : '' }}>Non-fault</option>
                </select>
                @error('payload.fault_type')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 form-group">
                <label for="fleet_damaged_incident_date">Incident date <span class="text-danger">*</span></label>
                <input type="date" name="payload[incident_date]" id="fleet_damaged_incident_date"
                       class="form-control @error('payload.incident_date') is-invalid @enderror"
                       value="{{ old('payload.incident_date') }}">
                @error('payload.incident_date')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 form-group fleet-damaged-fault-only {{ old('payload.fault_type') === 'fault' ? '' : 'd-none' }}">
                <label for="fleet_damaged_excess_status">Excess status <span class="text-danger">*</span></label>
                <input type="text" name="payload[excess_status]" id="fleet_damaged_excess_status"
                       class="form-control @error('payload.excess_status') is-invalid @enderror"
                       maxlength="255" value="{{ old('payload.excess_status') }}">
                @error('payload.excess_status')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 form-group fleet-damaged-fault-only {{ old('payload.fault_type') === 'fault' ? '' : 'd-none' }}">
                <label for="fleet_damaged_fault_notes">Fault notes <span class="text-danger">*</span></label>
                <textarea name="payload[fault_notes]" id="fleet_damaged_fault_notes" rows="2"
                          class="form-control @error('payload.fault_notes') is-invalid @enderror">{{ old('payload.fault_notes') }}</textarea>
                @error('payload.fault_notes')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-12 form-group fleet-damaged-nonfault-only {{ old('payload.fault_type') === 'non_fault' ? '' : 'd-none' }}">
                <label for="fleet_damaged_claim_ref">Insurance claim reference <span class="text-danger">*</span></label>
                <input type="text" name="payload[insurance_claim_reference]" id="fleet_damaged_claim_ref"
                       class="form-control @error('payload.insurance_claim_reference') is-invalid @enderror"
                       maxlength="255" value="{{ old('payload.insurance_claim_reference') }}">
                @error('payload.insurance_claim_reference')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-12 form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="fleet_payload_mechanical"
                           name="payload[mechanical]" value="1"
                        {{ old('payload.mechanical') ? 'checked' : '' }}>
                    <label class="custom-control-label" for="fleet_payload_mechanical">Mechanical damage</label>
                </div>
            </div>
            <div class="col-md-12 form-group {{ old('payload.mechanical') ? '' : 'd-none' }}"
                 id="fleet_payload_mechanical_notes_wrap">
                <label for="fleet_payload_mechanical_notes">Mechanical notes <span class="text-danger">*</span></label>
                <textarea name="payload[mechanical_notes]" id="fleet_payload_mechanical_notes" rows="2"
                          class="form-control @error('payload.mechanical_notes') is-invalid @enderror">{{ old('payload.mechanical_notes') }}</textarea>
                @error('payload.mechanical_notes')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    {{-- Written off --}}
    @php
        $writtenOffDisposalOld = old('payload.disposal_outcome');
        $writtenOffDisposalOptions = \App\Services\CarStatusChangeService::WRITTEN_OFF_DISPOSAL_OUTCOMES;
    @endphp
    <div class="fleet-status-panel {{ $activeTargetStatus === 'written_off' ? '' : 'd-none' }}"
         data-status="written_off">
        <div class="row">
            <div class="col-md-6 form-group">
                <label for="fleet_written_disposal_outcome">Vehicle outcome <span class="text-danger">*</span></label>
                <select name="payload[disposal_outcome]" id="fleet_written_disposal_outcome"
                        class="form-control @error('payload.disposal_outcome') is-invalid @enderror">
                    <option value="">— Select —</option>
                    @foreach($writtenOffDisposalOptions as $outcomeValue => $outcomeLabel)
                        <option value="{{ $outcomeValue }}"
                            {{ $writtenOffDisposalOld === $outcomeValue ? 'selected' : '' }}>
                            {{ $outcomeLabel }}
                        </option>
                    @endforeach
                </select>
                @error('payload.disposal_outcome')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 form-group">
                <label for="fleet_written_driver_id">Driver</label>
                <select name="payload[driver_id]" id="fleet_written_driver_id"
                        class="form-control select-search @error('payload.driver_id') is-invalid @enderror">
                    <option value="">— Optional —</option>
                    @include('backend.drivers._select_options', [
                        'drivers' => $drivers,
                        'selectedId' => $payloadOld('driver_id'),
                    ])
                </select>
                @error('payload.driver_id')
                <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 form-group">
                <label for="fleet_written_insurance_status">Insurance <span class="text-danger">*</span></label>
                <select name="payload[insurance_status]" id="fleet_written_insurance_status"
                        class="form-control @error('payload.insurance_status') is-invalid @enderror">
                    <option value="">— Select —</option>
                    <option value="company" {{ $payloadOld('insurance_status') === 'company' ? 'selected' : '' }}>
                        Company</option>
                    <option value="driver" {{ $payloadOld('insurance_status') === 'driver' ? 'selected' : '' }}>
                        Driver</option>
                </select>
                @error('payload.insurance_status')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 form-group">
                <label for="fleet_written_insurance_excess">Insurance excess amount <span
                            class="text-danger">*</span></label>
                <input type="number" name="payload[insurance_excess_amount]" id="fleet_written_insurance_excess"
                       class="form-control @error('payload.insurance_excess_amount') is-invalid @enderror"
                       step="0.01" min="0" value="{{ old('payload.insurance_excess_amount') }}">
                @error('payload.insurance_excess_amount')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 form-group">
                <label for="fleet_written_fault_type">Fault type <span class="text-danger">*</span></label>
                <select name="payload[fault_type]" id="fleet_written_fault_type"
                        class="form-control fleet-written-fault-select @error('payload.fault_type') is-invalid @enderror">
                    <option value="">— Select —</option>
                    <option value="fault" {{ old('payload.fault_type') === 'fault' ? 'selected' : '' }}>Fault</option>
                    <option value="non_fault" {{ old('payload.fault_type') === 'non_fault' ? 'selected' : '' }}>Non-fault</option>
                </select>
                @error('payload.fault_type')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 form-group">
                <label for="fleet_written_incident_date">Incident date <span class="text-danger">*</span></label>
                <input type="date" name="payload[incident_date]" id="fleet_written_incident_date"
                       class="form-control @error('payload.incident_date') is-invalid @enderror"
                       value="{{ old('payload.incident_date') }}">
                @error('payload.incident_date')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 form-group fleet-written-fault-only {{ old('payload.fault_type') === 'fault' ? '' : 'd-none' }}">
                <label for="fleet_written_excess_status">Excess status <span class="text-danger">*</span></label>
                <input type="text" name="payload[excess_status]" id="fleet_written_excess_status"
                       class="form-control @error('payload.excess_status') is-invalid @enderror"
                       maxlength="255" value="{{ old('payload.excess_status') }}">
                @error('payload.excess_status')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 form-group fleet-written-fault-only {{ old('payload.fault_type') === 'fault' ? '' : 'd-none' }}">
                <label for="fleet_written_fault_notes">Fault notes <span class="text-danger">*</span></label>
                <textarea name="payload[fault_notes]" id="fleet_written_fault_notes" rows="2"
                          class="form-control @error('payload.fault_notes') is-invalid @enderror">{{ old('payload.fault_notes') }}</textarea>
                @error('payload.fault_notes')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-12 form-group fleet-written-nonfault-only {{ old('payload.fault_type') === 'non_fault' ? '' : 'd-none' }}">
                <label for="fleet_written_claim_ref">Insurance claim reference <span class="text-danger">*</span></label>
                <input type="text" name="payload[insurance_claim_reference]" id="fleet_written_claim_ref"
                       class="form-control @error('payload.insurance_claim_reference') is-invalid @enderror"
                       maxlength="255" value="{{ old('payload.insurance_claim_reference') }}">
                @error('payload.insurance_claim_reference')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-12 form-group">
                <label for="fleet_written_notes">Written-off notes</label>
                <textarea name="payload[written_notes]" id="fleet_written_notes" rows="2"
                          class="form-control @error('payload.written_notes') is-invalid @enderror">{{ old('payload.written_notes') }}</textarea>
                @error('payload.written_notes')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    {{-- Stolen --}}
    <div class="fleet-status-panel {{ $activeTargetStatus === 'stolen' ? '' : 'd-none' }}" data-status="stolen">
        <div class="row">
            <div class="col-md-6 form-group">
                <label for="fleet_stolen_driver_id">Driver</label>
                <select name="payload[driver_id]" id="fleet_stolen_driver_id"
                        class="form-control select-search @error('payload.driver_id') is-invalid @enderror">
                    <option value="">— Optional —</option>
                    @include('backend.drivers._select_options', [
                        'drivers' => $drivers,
                        'selectedId' => $payloadOld('driver_id'),
                    ])
                </select>
                @error('payload.driver_id')
                <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 form-group">
                <label for="fleet_stolen_insurance_status">Insurance <span class="text-danger">*</span></label>
                <select name="payload[insurance_status]" id="fleet_stolen_insurance_status"
                        class="form-control @error('payload.insurance_status') is-invalid @enderror">
                    <option value="">— Select —</option>
                    <option value="company" {{ $payloadOld('insurance_status') === 'company' ? 'selected' : '' }}>
                        Company</option>
                    <option value="driver" {{ $payloadOld('insurance_status') === 'driver' ? 'selected' : '' }}>
                        Driver</option>
                </select>
                @error('payload.insurance_status')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 form-group">
                <label for="fleet_stolen_insurance_excess">Insurance excess amount <span
                            class="text-danger">*</span></label>
                <input type="number" name="payload[insurance_excess_amount]" id="fleet_stolen_insurance_excess"
                       class="form-control @error('payload.insurance_excess_amount') is-invalid @enderror"
                       step="0.01" min="0" value="{{ $payloadOld('insurance_excess_amount') }}">
                @error('payload.insurance_excess_amount')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 form-group">
                <label for="fleet_stolen_claim_ref">Insurance claim reference <span class="text-danger">*</span></label>
                <input type="text" name="payload[insurance_claim_reference]" id="fleet_stolen_claim_ref"
                       class="form-control @error('payload.insurance_claim_reference') is-invalid @enderror"
                       maxlength="255" value="{{ $payloadOld('insurance_claim_reference') }}">
                @error('payload.insurance_claim_reference')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-12 form-group">
                <label for="fleet_stolen_notes">Notes</label>
                <textarea name="payload[notes]" id="fleet_stolen_notes" rows="2"
                          class="form-control @error('payload.notes') is-invalid @enderror">{{ $payloadOld('notes') }}</textarea>
                @error('payload.notes')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    {{-- For sale --}}
    <div class="fleet-status-panel {{ $activeTargetStatus === 'for_sale' ? '' : 'd-none' }}" data-status="for_sale">
        <p class="text-muted small mb-3">Dates are optional — you can add or update them later.</p>
        <div class="row">
            <div class="col-md-4 form-group">
                <label for="fleet_sale_prep_date">Preparation date</label>
                <input type="date" name="payload[preparation_date]" id="fleet_sale_prep_date"
                       class="form-control @error('payload.preparation_date') is-invalid @enderror"
                       value="{{ $payloadOld('preparation_date') }}">
                @error('payload.preparation_date')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4 form-group">
                <label for="fleet_sale_ready_date">Ready date</label>
                <input type="date" name="payload[ready_date]" id="fleet_sale_ready_date"
                       class="form-control @error('payload.ready_date') is-invalid @enderror"
                       value="{{ $payloadOld('ready_date') }}">
                @error('payload.ready_date')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4 form-group">
                <label for="fleet_sale_advertised_date">Advertised date</label>
                <input type="date" name="payload[advertised_date]" id="fleet_sale_advertised_date"
                       class="form-control @error('payload.advertised_date') is-invalid @enderror"
                       value="{{ $payloadOld('advertised_date') }}">
                @error('payload.advertised_date')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    {{-- Sold --}}
    <div class="fleet-status-panel {{ $activeTargetStatus === 'sold' ? '' : 'd-none' }}" data-status="sold">
        <div class="row">
            <div class="col-md-6 form-group">
                <label for="fleet_sold_sell_date">Sell date <span class="text-danger">*</span></label>
                <input type="date" name="payload[sell_date]" id="fleet_sold_sell_date"
                       class="form-control @error('payload.sell_date') is-invalid @enderror"
                       value="{{ $payloadOld('sell_date') }}">
                @error('payload.sell_date')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 form-group">
                <label for="fleet_sold_price">Sell price <span class="text-danger">*</span></label>
                <input type="number" name="payload[sell_price]" id="fleet_sold_price"
                       class="form-control @error('payload.sell_price') is-invalid @enderror"
                       step="0.01" min="0" value="{{ $payloadOld('sell_price') }}">
                @error('payload.sell_price')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 form-group">
                <label for="fleet_sold_payment_terms">Payment terms <span class="text-danger">*</span></label>
                <select name="payload[payment_terms]" id="fleet_sold_payment_terms"
                        class="form-control @error('payload.payment_terms') is-invalid @enderror">
                    <option value="">— Select —</option>
                    <option value="cash" {{ $payloadOld('payment_terms') === 'cash' ? 'selected' : '' }}>Cash</option>
                    <option value="bank" {{ $payloadOld('payment_terms') === 'bank' ? 'selected' : '' }}>Bank</option>
                    <option value="auto_total" {{ $payloadOld('payment_terms') === 'auto_total' ? 'selected' : '' }}>
                        Auto Total</option>
                </select>
                @error('payload.payment_terms')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 form-group">
                <label for="fleet_sold_buyer_name">Buyer name <span class="text-danger">*</span></label>
                <input type="text" name="payload[buyer_name]" id="fleet_sold_buyer_name"
                       class="form-control @error('payload.buyer_name') is-invalid @enderror"
                       maxlength="255" value="{{ $payloadOld('buyer_name') }}">
                @error('payload.buyer_name')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 form-group">
                <label for="fleet_sold_buyer_contact">Buyer contact <span class="text-danger">*</span></label>
                <input type="text" name="payload[buyer_contact]" id="fleet_sold_buyer_contact"
                       class="form-control @error('payload.buyer_contact') is-invalid @enderror"
                       maxlength="255" value="{{ $payloadOld('buyer_contact') }}">
                @error('payload.buyer_contact')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-12 form-group">
                <label for="fleet_sold_buyer_address">Buyer address <span class="text-danger">*</span></label>
                <textarea name="payload[buyer_address]" id="fleet_sold_buyer_address" rows="2"
                          class="form-control @error('payload.buyer_address') is-invalid @enderror">{{ $payloadOld('buyer_address') }}</textarea>
                @error('payload.buyer_address')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-12 form-group">
                <label for="fleet_sold_notes">Notes</label>
                <textarea name="payload[notes]" id="fleet_sold_notes" rows="3"
                          class="form-control @error('payload.notes') is-invalid @enderror">{{ $payloadOld('notes') }}</textarea>
                @error('payload.notes')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-12 form-group">
                <label for="fleet_sold_documents">Sale documents</label>
                @if($existingSoldDocs !== [])
                    <div class="mb-2">
                        <small class="text-muted d-block mb-1">Uploaded documents</small>
                        <ul class="mb-0 pl-3">
                            @foreach($existingSoldDocs as $docPath)
                                <li>
                                    <x-document-actions
                                        :view-url="asset($docPath)"
                                        style="list-item"
                                        view-text="View file"
                                    />
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <input type="file" name="sold_documents[]" id="fleet_sold_documents"
                       class="form-control @error('sold_documents.*') is-invalid @enderror"
                       multiple accept=".pdf,.jpg,.jpeg,.png">
                <div id="fleet_sold_documents_selected" class="small text-muted mt-50"></div>
                @error('sold_documents.*')
                <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <small class="text-muted">PDF or images, max 10 MB each. New files are added to existing documents.</small>
            </div>
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap">
        @unless($editCurrentStatus)
            <button type="button" class="btn btn-outline-secondary" id="fleet_wizard_back">
                <i class="fa fa-arrow-left"></i> Back
            </button>
        @else
            <a href="{{ route('cars.show', $prefillCarId) }}" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> Back to car
            </a>
        @endunless
        <button type="submit" class="btn btn-primary" id="fleet_wizard_submit">
            <i class="fa fa-check"></i> {{ $editCurrentStatus ? 'Save changes' : 'Submit' }}
        </button>
    </div>
</div>
