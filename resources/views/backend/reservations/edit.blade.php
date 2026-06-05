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
                        <a href="{{ route('reservations.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fa fa-arrow-left"></i> Back to reservations
                        </a>
                    </div>
                    <hr class="my-0">
                    <div class="card-content">
                        <div class="card-body">
                            @include('alerts')
                            <form method="POST" action="{{ route('reservations.update', $reservation) }}"
                                  id="formEditReservation" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')

                                @include('backend.reservations._driver_section', [
                                    'drivers' => $drivers,
                                    'driver' => $driver,
                                    'selectedDriverId' => $selectedDriverId,
                                    'driverMode' => $driverMode,
                                ])

                                <div class="card mb-2">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Reservation details</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 form-group">
                                                <label for="reservation_car_id">Car</label>
                                                <select name="car_id" id="reservation_car_id"
                                                        class="form-control @error('car_id') is-invalid @enderror">
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
                                                <label for="amount_paid">Amount paid <span
                                                            class="text-danger">*</span></label>
                                                <input type="number" name="amount_paid" id="amount_paid"
                                                       class="form-control @error('amount_paid') is-invalid @enderror"
                                                       step="0.01" min="0" required
                                                       value="{{ old('amount_paid', $reservation->amount_paid ?? 0) }}">
                                                @error('amount_paid')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
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
    <script>
        $(document).ready(function () {
            $('#reservation_car_id').select2({
                width: '100%',
                placeholder: 'Search or leave empty',
                allowClear: true,
            });

            $('#reservation_driver_id').select2({
                width: '100%',
                placeholder: 'Search driver',
                allowClear: true,
            });

            function parseMoney(id) {
                const v = parseFloat(String($(id).val()).replace(',', '.'));
                return isNaN(v) ? 0 : v;
            }

            function refreshBalanceDisplay() {
                const rent = parseMoney('#agreed_rent');
                const advance = parseMoney('#agreed_advance');
                const paid = parseMoney('#amount_paid');
                const bal = Math.max(0, Math.round((rent + advance - paid) * 100) / 100);
                $('#balance_payable_on_pickup_display').val(bal.toFixed(2));
            }

            $('#agreed_rent, #agreed_advance, #amount_paid').on('input change', refreshBalanceDisplay);
            refreshBalanceDisplay();
        });
    </script>
@endsection
