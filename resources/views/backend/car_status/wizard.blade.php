@php
    $fleetLabels = [
        \App\Models\Car::FLEET_STATUS_PREPARATION_FOR_PHVL => 'PHVL Preparation',
        'available_for_rent' => 'Available for Rent',
        \App\Models\Car::FLEET_STATUS_NON_COMPLIANT => 'Non-Compliant',
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
                        @if(!empty($editCurrentStatus) && !empty($prefillCarId))
                            @php
                                $editCarRegistration = $cars->firstWhere('id', $prefillCarId)?->registration ?? 'this vehicle';
                            @endphp
                            <div class="alert alert-info mb-3" role="alert">
                                You are editing the current status details for <strong>{{ $editCarRegistration }}</strong>. Submit to save your changes.
                            </div>
                        @endif
                        @if($errors->any())
                            <ul class="text-danger small mb-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        @endif
                        <form method="POST"
                              action="{{ !empty($editCurrentStatus) && !empty($prefillCarId) ? route('car-status.current.update', $prefillCarId) : route('car-status.store') }}"
                              id="fleet-status-form"
                              enctype="multipart/form-data"
                              data-old-target="{{ old('target_status', $prefillTargetStatus ?? '') }}"
                              data-edit-mode="{{ !empty($editCurrentStatus) ? '1' : '0' }}"
                              data-prefill-status='@json($prefillStatusPayload ?? [])'>
                            @csrf
                            @if(!empty($editCurrentStatus) && !empty($prefillCarId))
                                @method('PUT')
                                <input type="hidden" name="edit_current_status" value="1">
                                <input type="hidden" name="car_id" value="{{ old('car_id', $prefillCarId) }}">
                                <input type="hidden" name="target_status" value="{{ old('target_status', $prefillTargetStatus) }}">
                            @endif
                            <script type="application/json" id="fleet-car-fleet-flags-data">@json($carFleetFlags ?? [])</script>
                            @include('backend.car_status.partials.wizard_step1', [
                                'fleetLabels' => $fleetLabels,
                                'prefillCarId' => $prefillCarId ?? null,
                                'prefillTargetStatus' => $prefillTargetStatus ?? null,
                                'editCurrentStatus' => !empty($editCurrentStatus),
                            ])
                            @include('backend.car_status.partials.wizard_step2', compact(
                                'cars',
                                'drivers',
                                'fleetLabels',
                                'carFleetFlags',
                                'prefillCarId',
                                'prefillTargetStatus',
                                'prefillStatusPayload',
                                'editCurrentStatus'
                            ))
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
