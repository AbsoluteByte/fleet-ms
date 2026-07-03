@extends('layouts.admin', ['title' => 'Add reservation'])
@section('css')
    <style>
        .reservation-form-card > .card-header {
            padding-bottom: 1.25rem;
        }
    </style>
@endsection
@section('content')
    <section>
        <div class="row">
            <div class="col-12">
                <div class="card reservation-form-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Add reservation</h4>
                        <a href="{{ route('reservations.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fa fa-arrow-left"></i> Back to reservations
                        </a>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            @include('alerts')
                            <form method="POST" action="{{ route('reservations.store') }}" id="formCreateReservation"
                                  enctype="multipart/form-data" novalidate>
                                @csrf

                                @include('backend.reservations._driver_section', [
                                    'drivers' => $drivers,
                                    'driver' => $driver,
                                    'selectedDriverId' => $selectedDriverId,
                                    'driverMode' => $driverMode,
                                    'minimalDriverForm' => true,
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
                                                            {{ (string) old('car_id') === (string) $car->id ? 'selected' : '' }}>
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
                                                       value="{{ old('reservation_date', now()->toDateString()) }}">
                                                @error('reservation_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6 form-group">
                                                <label for="pick_up_date">Pick up date <span
                                                            class="text-danger">*</span></label>
                                                <input type="date" name="pick_up_date" id="pick_up_date"
                                                       class="form-control @error('pick_up_date') is-invalid @enderror"
                                                       required value="{{ old('pick_up_date') }}">
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
                                                       value="{{ old('agreed_rent') }}">
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
                                                       value="{{ old('agreed_advance') }}">
                                                @error('agreed_advance')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-4 form-group">
                                                <label for="amount_paid">Amount paid <span
                                                            class="text-danger">*</span></label>
                                                <input type="number" name="amount_paid" id="amount_paid"
                                                       class="form-control @error('amount_paid') is-invalid @enderror"
                                                       step="0.01" min="0" required
                                                       value="{{ old('amount_paid') }}">
                                                @error('amount_paid')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            @include('backend.reservations._payment_fields', [
                                                'bankAccounts' => $bankAccounts,
                                            ])
                                            <div class="col-md-12 form-group">
                                                <label for="balance_payable_on_pickup_display">Balance payable on pick up</label>
                                                <input type="text" id="balance_payable_on_pickup_display" class="form-control"
                                                       readonly tabindex="-1" value="">
                                                <small class="text-muted">Auto-calculated from agreed rent + agreed advance −
                                                    amount paid.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mb-0">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-check"></i> Save reservation
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
    <script src="{{ asset('app-assets/js/scripts/fleetiq-validate-reservation.js') }}?v=20260625"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('formCreateReservation');
            if (form && window.FleetiqFormValidation && window.validateReservationForm) {
                FleetiqFormValidation.attach(form, function (f, errors) {
                    return validateReservationForm(f, errors, { minimalNewDriver: true });
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
                const hasAny = ['#agreed_rent', '#agreed_advance', '#amount_paid'].some(function (sel) {
                    return String($(sel).val()).trim() !== '';
                });
                if (!hasAny) {
                    $('#balance_payable_on_pickup_display').val('');
                    return;
                }
                const bal = Math.max(0, Math.round((rent + advance - paid) * 100) / 100);
                $('#balance_payable_on_pickup_display').val(bal.toFixed(2));
            }

            $('#agreed_rent, #agreed_advance, #amount_paid').on('input change', refreshBalanceDisplay);
            refreshBalanceDisplay();
        });
    </script>
@endsection
