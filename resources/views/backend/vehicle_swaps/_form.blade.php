@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Car> $cars */
    /** @var \App\Models\VehicleSwap|null $vehicleSwap */
    use App\Models\VehicleSwap;

    $reasonOld = old('reason_for_swap', $vehicleSwap?->reason_for_swap ?? '');
    $phvlTypeOld = old('phvl_issue_type', $vehicleSwap?->phvl_issue_type ?? '');
@endphp

<div class="row">
    <div class="col-md-6 form-group">
        <label for="swap_old_car_id">Old car <span class="text-danger">*</span></label>
        <select name="old_car_id" id="swap_old_car_id"
                class="form-control select-search @error('old_car_id') is-invalid @enderror" required>
            <option value="">— Select —</option>
            @foreach($cars as $car)
                <option value="{{ $car->id }}"
                    {{ (string) old('old_car_id', $vehicleSwap?->old_car_id ?? '') === (string) $car->id ? 'selected' : '' }}>
                    {{ $car->registration }} — {{ $car->carModel->name ?? '' }}</option>
            @endforeach
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
            @foreach($cars as $car)
                <option value="{{ $car->id }}"
                    {{ (string) old('swapped_with_car_id', $vehicleSwap?->swapped_with_car_id ?? '') === (string) $car->id ? 'selected' : '' }}>
                    {{ $car->registration }} — {{ $car->carModel->name ?? '' }}</option>
            @endforeach
        </select>
        @error('swapped_with_car_id')
        <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4 form-group">
        <label for="customer_name">Client's name <span class="text-danger">*</span></label>
        <input type="text" name="customer_name" id="customer_name"
               class="form-control @error('customer_name') is-invalid @enderror"
               required maxlength="255"
               value="{{ old('customer_name', $vehicleSwap?->customer_name ?? '') }}">
        @error('customer_name')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-4 form-group">
        <label for="customer_phone">Contact</label>
        <input type="text" name="customer_phone" id="customer_phone"
               class="form-control @error('customer_phone') is-invalid @enderror"
               maxlength="50" value="{{ old('customer_phone', $vehicleSwap?->customer_phone ?? '') }}">
        @error('customer_phone')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-4 form-group">
        <label for="customer_email">Email</label>
        <input type="email" name="customer_email" id="customer_email"
               class="form-control @error('customer_email') is-invalid @enderror"
               maxlength="255" value="{{ old('customer_email', $vehicleSwap?->customer_email ?? '') }}">
        @error('customer_email')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 form-group">
        <label for="reservation_date">Reservation date <span class="text-danger">*</span></label>
        <input type="date" name="reservation_date" id="reservation_date"
               class="form-control @error('reservation_date') is-invalid @enderror"
               required
               value="{{ old('reservation_date', $vehicleSwap ? $vehicleSwap->reservation_date?->format('Y-m-d') : now()->toDateString()) }}">
        @error('reservation_date')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6 form-group">
        <label for="pick_up_date">Pick up date <span class="text-danger">*</span></label>
        <input type="date" name="pick_up_date" id="pick_up_date"
               class="form-control @error('pick_up_date') is-invalid @enderror"
               required
               value="{{ old('pick_up_date', $vehicleSwap?->pick_up_date?->format('Y-m-d') ?? '') }}">
        @error('pick_up_date')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4 form-group">
        <label for="agreed_rent">Agreed rent <span class="text-danger">*</span></label>
        <input type="number" name="agreed_rent" id="agreed_rent"
               class="form-control @error('agreed_rent') is-invalid @enderror"
               step="0.01" min="0" required
               value="{{ old('agreed_rent', $vehicleSwap?->agreed_rent ?? '') }}">
        @error('agreed_rent')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-4 form-group">
        <label for="agreed_advance">Agreed advance <span class="text-danger">*</span></label>
        <input type="number" name="agreed_advance" id="agreed_advance"
               class="form-control @error('agreed_advance') is-invalid @enderror"
               step="0.01" min="0" required
               value="{{ old('agreed_advance', $vehicleSwap?->agreed_advance ?? '') }}">
        @error('agreed_advance')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-4 form-group">
        <label for="amount_paid">Amount paid <span class="text-danger">*</span></label>
        <input type="number" name="amount_paid" id="amount_paid"
               class="form-control @error('amount_paid') is-invalid @enderror"
               step="0.01" min="0" required
               value="{{ old('amount_paid', $vehicleSwap?->amount_paid ?? '') }}">
        @error('amount_paid')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-12 form-group">
        <label for="balance_payable_on_pickup_display">Balance payable on pick up</label>
        <input type="text" id="balance_payable_on_pickup_display" class="form-control"
               readonly tabindex="-1" value="">
        <small class="text-muted">Auto-calculated from agreed rent + agreed advance − amount paid.</small>
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
                  class="form-control @error('phvl_issue_notes') is-invalid @enderror">{{ old('phvl_issue_notes', $vehicleSwap?->phvl_issue_notes ?? '') }}</textarea>
        @error('phvl_issue_notes')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-12 form-group d-none" id="swap_reason_notes_wrap">
        <label for="reason_notes">Reason notes <span class="text-danger">*</span></label>
        <textarea name="reason_notes" id="reason_notes" rows="3"
                  class="form-control @error('reason_notes') is-invalid @enderror">{{ old('reason_notes', $vehicleSwap?->reason_notes ?? '') }}</textarea>
        @error('reason_notes')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
