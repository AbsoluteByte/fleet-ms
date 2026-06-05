@php
    $fleetLabels = [
        'available_for_rent' => 'Available for Rent',
        'reserved' => 'Reserved',
        'vehicle_swap' => 'Vehicle Swap',
        'damaged' => 'Damaged',
        'written_off' => 'Written Off',
        'stolen' => 'Stolen',
        'for_sale' => 'For Sale',
        'sold' => 'Sold',
    ];
@endphp

@extends('layouts.admin', ['title' => 'Car Status'])

@section('content')
    <section>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center py-1">
                        <h4 class="card-title mb-0">Car Status</h4>
                        <a href="{{ route('cars.index') }}" class="btn btn-outline-secondary btn-sm mb-0">
                            <i class="fa fa-arrow-left"></i> Back to cars
                        </a>
                    </div>
                    <hr class="my-0">
                    <div class="card-body">
                        @include('alerts')
                        <form method="POST" action="{{ route('car-status.store') }}" id="fleet-status-form"
                              enctype="multipart/form-data"
                              data-old-target="{{ old('target_status') }}">
                            @csrf
                            <script type="application/json" id="fleet-car-fleet-flags-data">@json($carFleetFlags ?? [])</script>
                            @include('backend.car_status.partials.wizard_step1', ['fleetLabels' => $fleetLabels])
                            @include('backend.car_status.partials.wizard_step2', compact('cars', 'drivers', 'fleetLabels', 'carFleetFlags'))
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('js')
    @include('backend.car_status.partials.wizard_scripts')
@endsection
