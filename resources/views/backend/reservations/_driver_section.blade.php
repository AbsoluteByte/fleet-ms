@php
    $selectedDriverId = old('driver_id', $selectedDriverId ?? null);
    $driverMode = old('driver_mode', $driverMode ?? ($selectedDriverId ? 'existing' : 'new'));
@endphp

<div class="card mb-2" id="reservation-driver-section">
    <div class="card-header">
        <h5 class="card-title mb-0">Driver</h5>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label class="d-block mb-1">Client / driver <span class="text-danger">*</span></label>
            <div class="btn-group mb-2" role="group" aria-label="Driver mode">
                <label class="btn btn-outline-primary {{ $driverMode === 'existing' ? 'active' : '' }}">
                    <input type="radio" name="driver_mode" value="existing" class="d-none" autocomplete="off"
                           {{ $driverMode === 'existing' ? 'checked' : '' }}> Existing driver
                </label>
                <label class="btn btn-outline-primary {{ $driverMode === 'new' ? 'active' : '' }}">
                    <input type="radio" name="driver_mode" value="new" class="d-none" autocomplete="off"
                           {{ $driverMode === 'new' ? 'checked' : '' }}> New driver
                </label>
            </div>
            @error('driver_mode')
            <div class="text-danger small">{{ $message }}</div>
            @enderror
        </div>

        <div id="reservation-existing-driver-panel" class="{{ $driverMode === 'existing' ? '' : 'd-none' }}">
            <div class="form-group mb-0">
                <label for="reservation_driver_id">Select driver <span class="text-danger">*</span></label>
                <select name="driver_id" id="reservation_driver_id"
                        class="form-control select-search @error('driver_id') is-invalid @enderror">
                    <option value="">— Select driver —</option>
                    @foreach($drivers as $existingDriver)
                        <option value="{{ $existingDriver->id }}"
                            {{ (string) $selectedDriverId === (string) $existingDriver->id ? 'selected' : '' }}>
                            {{ $existingDriver->full_name }}
                            @if($existingDriver->phone_number)
                                — {{ $existingDriver->phone_number }}
                            @endif
                        </option>
                    @endforeach
                </select>
                @error('driver_id')
                <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                @if($drivers->isEmpty())
                    <small class="text-muted d-block mt-1">No drivers yet — use “New driver” to add one.</small>
                @endif
            </div>
        </div>

        <div id="reservation-new-driver-panel" class="{{ $driverMode === 'new' ? '' : 'd-none' }}">
            @include('backend.drivers._form', [
                'model' => $driver,
                'hideFormActions' => true,
            ])
        </div>
    </div>
</div>

@push('js')
    <script>
        (function () {
            function initReservationDriverModeToggle() {
                const section = document.getElementById('reservation-driver-section');
                const existingPanel = document.getElementById('reservation-existing-driver-panel');
                const newPanel = document.getElementById('reservation-new-driver-panel');
                const driverSelect = document.getElementById('reservation_driver_id');

                if (!section || !existingPanel || !newPanel) {
                    return;
                }

                function setPanelEnabled(panel, enabled) {
                    panel.querySelectorAll('input, select, textarea, button').forEach(function (el) {
                        if (el.name === 'driver_mode') {
                            return;
                        }
                        el.disabled = !enabled;
                    });
                }

                function refreshDriverMode() {
                    const checked = section.querySelector('input[name="driver_mode"]:checked');
                    const mode = checked ? checked.value : 'new';
                    const useExisting = mode === 'existing';

                    section.querySelectorAll('label.btn').forEach(function (label) {
                        const input = label.querySelector('input[name="driver_mode"]');
                        label.classList.toggle('active', input && input.checked);
                    });

                    existingPanel.classList.toggle('d-none', !useExisting);
                    newPanel.classList.toggle('d-none', useExisting);

                    setPanelEnabled(existingPanel, useExisting);
                    setPanelEnabled(newPanel, !useExisting);

                    if (driverSelect && window.jQuery && jQuery.fn.select2) {
                        jQuery(driverSelect).prop('disabled', !useExisting).trigger('change.select2');
                    }
                }

                section.querySelectorAll('input[name="driver_mode"]').forEach(function (input) {
                    input.addEventListener('change', refreshDriverMode);
                    input.addEventListener('click', refreshDriverMode);
                });

                section.querySelectorAll('label.btn').forEach(function (label) {
                    label.addEventListener('click', function () {
                        window.setTimeout(refreshDriverMode, 0);
                    });
                });

                refreshDriverMode();
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initReservationDriverModeToggle);
            } else {
                initReservationDriverModeToggle();
            }
        })();
    </script>
@endpush
