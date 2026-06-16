@php
    $prefillCarId = $prefillCarId ?? null;
    $prefillTargetStatus = $prefillTargetStatus ?? null;
    $editCurrentStatus = ! empty($editCurrentStatus);
    $selectedCarId = old('car_id', $prefillCarId);
    $selectedTargetStatus = old('target_status', $prefillTargetStatus);
@endphp

@if(! $editCurrentStatus)
<div id="fleet_step1" class="@if($selectedTargetStatus) d-none @endif">
    <h5 class="border-bottom pb-2 mb-3 text-center">Select vehicle and new status to continue</h5>
    <div class="row">
        <div class="col-md-6 form-group">
            <label for="fleet_wizard_car_id">Car <span class="text-danger">*</span></label>
            <select name="car_id" id="fleet_wizard_car_id" required
                    class="form-control select-search @error('car_id') is-invalid @enderror">
                <option value="">— Select car —</option>
                @foreach($cars as $car)
                    <option value="{{ $car->id }}"
                        data-fleet-status="{{ $car->fleet_status ?? 'available_for_rent' }}"
                        {{ (string) $selectedCarId === (string) $car->id ? 'selected' : '' }}>
                        {{ $car->registration }} — {{ $car->carModel->name ?? '' }}</option>
                @endforeach
            </select>
            @error('car_id')
            <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-6 form-group">
            <label for="fleet_target_status">Car status <span class="text-danger">*</span></label>
            <select name="target_status" id="fleet_target_status" required
                    class="form-control @error('target_status') is-invalid @enderror">
                <option value="">— Select status —</option>
                @foreach($fleetLabels as $key => $label)
                    <option value="{{ $key }}" {{ $selectedTargetStatus === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('target_status')
            <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="text-right mt-3">
        <button type="button" class="btn btn-primary" id="fleet_wizard_next">
            Next <i class="fa fa-arrow-right"></i>
        </button>
    </div>
</div>
@endif
