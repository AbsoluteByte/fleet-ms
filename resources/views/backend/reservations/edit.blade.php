@extends('layouts.admin', ['title' => 'Edit reservation'])
@section('css')
    <style>
        .reservation-form-card > .card-header {
            padding-bottom: 1.25rem;
        }
    </style>
@endsection
@section('content')
    @php
        $pickUpDefault = $reservation->effectivePickUpDate()?->format('Y-m-d') ?? '';
    @endphp
    <section>
        <div class="row">
            <div class="col-12">
                <div class="card reservation-form-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Edit reservation</h4>
                        <div class="d-flex align-items-center">
                            <button type="button"
                                    class="btn btn-primary btn-sm mr-1"
                                    id="btnCreateAgreementFromReservation"
                                    @if($driverProfileIncomplete) disabled title="Complete and save driver details first" @endif>
                                <i class="fa fa-file-contract"></i> Create Agreement
                            </button>
                            <a href="{{ route('reservations.index') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fa fa-arrow-left"></i> Back to reservations
                            </a>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            @include('alerts')
                            <form method="POST" action="{{ route('reservations.update', $reservation) }}"
                                  id="formEditReservation" enctype="multipart/form-data" novalidate>
                                @csrf
                                @method('PUT')

                                @include('backend.reservations._driver_section', [
                                    'drivers' => $drivers,
                                    'driver' => $driver,
                                    'selectedDriverId' => $selectedDriverId,
                                    'driverMode' => $driverMode,
                                    'driverProfileIncomplete' => $driverProfileIncomplete,
                                    'missingDriverFields' => $missingDriverFields,
                                ])

                                <div class="card mb-2">
                                    <div class="card-header" style="position: static; width: 100%; z-index: unset; border-bottom: 0 !important; padding-bottom: 0 !important;">
                                        <h5 class="card-title mb-0">Reservation details</h5>
                                    </div>
                                    <div class="card-body" style="margin-top: 0 !important;">
                                        <div class="row">
                                            <div class="col-md-6 form-group">
                                                <label for="reservation_car_id">Car</label>
                                                <select name="car_id" id="reservation_car_id"
                                                        class="form-control select-search @error('car_id') is-invalid @enderror">
                                                    <option value="">— Optional —</option>
                                                    @foreach($cars as $car)
                                                        <option value="{{ $car->id }}"
                                                            {{ (string) old('car_id', $reservation->car_id) === (string) $car->id ? 'selected' : '' }}>
                                                            {{ $car->registration }}
                                                            — {{ $car->carModel->name ?? '' }}</option>
                                                    @endforeach
                                                </select>
                                                @error('car_id')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6 form-group">
                                                <label for="reservation_date">Reservation date <span
                                                            class="text-danger">*</span></label>
                                                <input type="date" name="reservation_date" id="reservation_date"
                                                       class="form-control @error('reservation_date') is-invalid @enderror"
                                                       required
                                                       value="{{ old('reservation_date', $reservation->reservation_date?->format('Y-m-d')) }}">
                                                @error('reservation_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6 form-group">
                                                <label for="pick_up_date">Pick up date <span
                                                            class="text-danger">*</span></label>
                                                <input type="date" name="pick_up_date" id="pick_up_date"
                                                       class="form-control @error('pick_up_date') is-invalid @enderror"
                                                       required value="{{ old('pick_up_date', $pickUpDefault) }}">
                                                @error('pick_up_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-4 form-group">
                                                <label for="agreed_rent">Agreed rent <span
                                                            class="text-danger">*</span></label>
                                                <input type="number" name="agreed_rent" id="agreed_rent"
                                                       class="form-control @error('agreed_rent') is-invalid @enderror"
                                                       step="0.01" min="0" required
                                                       value="{{ old('agreed_rent', $reservation->agreed_rent ?? 0) }}">
                                                @error('agreed_rent')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-4 form-group">
                                                <label for="agreed_advance">Agreed advance <span
                                                            class="text-danger">*</span></label>
                                                <input type="number" name="agreed_advance" id="agreed_advance"
                                                       class="form-control @error('agreed_advance') is-invalid @enderror"
                                                       step="0.01" min="0" required
                                                       value="{{ old('agreed_advance', $reservation->agreed_advance ?? 0) }}">
                                                @error('agreed_advance')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-4 form-group">
                                                <label for="amount_paid_display">Amount paid</label>
                                                <input type="text" id="amount_paid_display" class="form-control" readonly tabindex="-1" value="">
                                                <small class="text-muted">Total of deposit payment rows below.</small>
                                            </div>
                                            @include('backend.reservations._payment_fields', [
                                                'bankAccounts' => $bankAccounts,
                                                'reservation' => $reservation,
                                            ])
                                            <div class="col-md-12 form-group">
                                                <label for="balance_payable_on_pickup_display">Balance payable on pick up</label>
                                                <input type="text" id="balance_payable_on_pickup_display" class="form-control"
                                                       readonly tabindex="-1" value="0.00">
                                                <small class="text-muted">Auto-calculated from agreed rent + agreed advance −
                                                    amount paid.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mb-0">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-check"></i> Update reservation
                                    </button>
                                    <a href="{{ route('reservations.index') }}" class="btn btn-outline-secondary ml-1">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('js')
    <script src="{{ asset('app-assets/js/scripts/fleetiq-validate-driver.js') }}?v=20260625"></script>
    <script src="{{ asset('app-assets/js/scripts/fleetiq-validate-reservation.js') }}?v=20260703"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('formEditReservation');
            if (form && window.FleetiqFormValidation && window.validateReservationForm) {
                FleetiqFormValidation.attach(form, validateReservationForm);
            }

            var createAgreementBtn = document.getElementById('btnCreateAgreementFromReservation');
            if (createAgreementBtn && form) {
                createAgreementBtn.addEventListener('click', function () {
                    var errors = [];
                    if (!window.validateReservationForAgreement || !validateReservationForAgreement(form, errors)) {
                        if (window.FleetiqFormValidation) {
                            FleetiqFormValidation.showErrors(form, errors);
                        }
                        return;
                    }

                    var params = new URLSearchParams();
                    params.set('reservation_id', '{{ $reservation->id }}');

                    var driverIdField = form.querySelector('[name="driver_id"]');
                    if (driverIdField && driverIdField.value) {
                        params.set('driver_id', driverIdField.value);
                    }

                    ['car_id', 'pick_up_date', 'agreed_rent', 'amount_paid'].forEach(function (name) {
                        var field = form.querySelector('[name="' + name + '"]');
                        if (field && field.value) {
                            params.set(name, field.value);
                        }
                    });

                    var advanceField = form.querySelector('[name="agreed_advance"]');
                    if (advanceField && advanceField.value) {
                        params.set('deposit_amount', advanceField.value);
                    }

                    window.location.href = '{{ route('agreements.create') }}?' + params.toString();
                });
            }
        });
    </script>
    <script>
        $(document).ready(function () {
            function parseMoney(id) {
                const v = parseFloat(String($(id).val()).replace(',', '.'));
                return isNaN(v) ? 0 : v;
            }

            function refreshBalanceDisplay() {
                const rent = parseMoney('#agreed_rent');
                const advance = parseMoney('#agreed_advance');
                const paid = parseMoney('#amount_paid');
                $('#amount_paid_display').val(paid > 0 ? paid.toFixed(2) : '');
                const bal = Math.max(0, Math.round((rent + advance - paid) * 100) / 100);
                $('#balance_payable_on_pickup_display').val(bal.toFixed(2));
            }

            window.refreshReservationBalanceDisplay = refreshBalanceDisplay;

            $('#agreed_rent, #agreed_advance').on('input change', refreshBalanceDisplay);
            refreshBalanceDisplay();
        });
    </script>
@endsection
