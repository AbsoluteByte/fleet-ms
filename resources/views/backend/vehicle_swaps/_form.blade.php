@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Car> $oldCars */
    /** @var \Illuminate\Support\Collection<int, \App\Models\Car> $replacementCars */
    use App\Models\VehicleSwap;

    $reasonOld = old('reason_for_swap', '');
    $phvlTypeOld = old('phvl_issue_type', '');
    $selectedOldCarId = old('old_car_id');
    $replacementCars = $replacementCars->reject(
        fn ($car) => $selectedOldCarId && (int) $car->id === (int) $selectedOldCarId
    );
@endphp

<div class="alert alert-info">
    Vehicle swaps are recorded as agreement car changes. The old vehicle must have an <strong>active agreement</strong>.
    After the swap you can generate a permission letter from the new agreement.
</div>

<div class="row">
    <div class="col-md-6 form-group">
        <label for="swap_old_car_id">Old car (active agreement) <span class="text-danger">*</span></label>
        <select name="old_car_id" id="swap_old_car_id"
                class="form-control select-search @error('old_car_id') is-invalid @enderror" required>
            <option value="">— Select —</option>
            @forelse($oldCars as $car)
                @php
                    $agreement = $car->activeAgreement ?? null;
                    $driverLabel = $agreement?->driver
                        ? trim($agreement->driver->first_name.' '.$agreement->driver->last_name)
                        : null;
                @endphp
                <option value="{{ $car->id }}"
                    {{ (string) old('old_car_id') === (string) $car->id ? 'selected' : '' }}>
                    {{ $car->registration }} — {{ $car->carModel->name ?? '' }}@if($driverLabel) ({{ $driverLabel }})@endif
                </option>
            @empty
                <option value="" disabled>No vehicles with an active agreement</option>
            @endforelse
        </select>
        @error('old_car_id')
        <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6 form-group">
        <label for="swap_new_car_id">Swapped with <span class="text-danger">*</span></label>
        <select name="swapped_with_car_id" id="swap_new_car_id"
                class="form-control select-search @error('swapped_with_car_id') is-invalid @enderror" required>
            <option value="">— Select —</option>
            @foreach($replacementCars as $car)
                <option value="{{ $car->id }}"
                    {{ (string) old('swapped_with_car_id') === (string) $car->id ? 'selected' : '' }}>
                    {{ $car->registration }} — {{ $car->carModel->name ?? '' }}</option>
            @endforeach
        </select>
        @error('swapped_with_car_id')
        <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 form-group">
        <label for="agreed_rent">New agreed rent <span class="text-danger">*</span></label>
        <input type="number" name="agreed_rent" id="agreed_rent"
               class="form-control @error('agreed_rent') is-invalid @enderror"
               step="0.01" min="0" required
               value="{{ old('agreed_rent') }}">
        @error('agreed_rent')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 form-group">
        <label for="reason_for_swap">Reason for swap <span class="text-danger">*</span></label>
        <select name="reason_for_swap" id="reason_for_swap"
                class="form-control @error('reason_for_swap') is-invalid @enderror" required>
            <option value="">— Select —</option>
            @foreach(VehicleSwap::reasonLabels() as $value => $label)
                <option value="{{ $value }}" {{ (string) $reasonOld === (string) $value ? 'selected' : '' }}>
                    {{ $label }}</option>
            @endforeach
        </select>
        @error('reason_for_swap')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 form-group d-none" id="swap_phvl_issue_type_wrap">
        <label for="phvl_issue_type">PHVL issue type <span class="text-danger">*</span></label>
        <select name="phvl_issue_type" id="phvl_issue_type"
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

    <div class="col-md-12 form-group d-none" id="swap_phvl_issue_notes_wrap">
        <label for="phvl_issue_notes">PHVL issue notes <span class="text-danger">*</span></label>
        <textarea name="phvl_issue_notes" id="phvl_issue_notes" rows="3"
                  class="form-control @error('phvl_issue_notes') is-invalid @enderror">{{ old('phvl_issue_notes') }}</textarea>
        @error('phvl_issue_notes')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-12 form-group d-none" id="swap_reason_notes_wrap">
        <label for="reason_notes">Reason notes <span class="text-danger">*</span></label>
        <textarea name="reason_notes" id="reason_notes" rows="3"
                  class="form-control @error('reason_notes') is-invalid @enderror">{{ old('reason_notes') }}</textarea>
        @error('reason_notes')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
