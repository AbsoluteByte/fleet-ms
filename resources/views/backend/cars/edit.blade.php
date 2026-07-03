@extends('layouts.admin', ['title' => 'Edit'])

@push('css')
    <style>
        .car-edit-locked-overlay {
            position: fixed;
            inset: 0;
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: rgba(15, 23, 42, 0.55);
        }

        .car-edit-locked-notice {
            width: 100%;
            max-width: 420px;
            border-radius: 12px;
        }

        .car-edit-locked-form {
            pointer-events: none;
            user-select: none;
            opacity: 0.55;
        }

        @if($model->isFleetStatusLockedForEditing())
        html.car-edit-locked-page,
        html.car-edit-locked-page body,
        html.car-edit-locked-page .app-content,
        html.car-edit-locked-page .content-wrapper,
        html.car-edit-locked-page .content-body {
            overflow: hidden !important;
        }
        @endif
    </style>
@endpush

@section('content')
    @php
        $carEditLocked = $model->isFleetStatusLockedForEditing();
        $carStatusLabel = $model->fleetStatusLabel();
        $editLatestMotId = $model->latestMot()?->id ?? '';
        $editLatestPhvId = $model->latestPhv()?->id ?? '';
        $editCurrentInsuranceStatus = '';
        $editHasInsuranceDocument = '0';
        $latestInsuranceForEdit = $model->insurances
            ->sortByDesc(fn ($insurance) => [optional($insurance->created_at)->timestamp ?? 0, $insurance->id])
            ->first();
        if ($latestInsuranceForEdit) {
            $editCurrentInsuranceStatus = strtolower(trim((string) optional($latestInsuranceForEdit->status)->name));
            $editHasInsuranceDocument = $latestInsuranceForEdit->insurance_document ? '1' : '0';
        }
    @endphp

    @if($carEditLocked)
        <div class="car-edit-locked-overlay" role="alert" aria-live="polite">
            <div class="bg-white shadow p-4 text-center car-edit-locked-notice">
                <div class="text-warning mb-2">
                    <i class="fa fa-lock fa-2x" aria-hidden="true"></i>
                </div>
                <h5 class="mb-2">Editing disabled</h5>
                <p class="text-muted mb-3">
                    This car is currently <strong>{{ $carStatusLabel }}</strong>.
                    To edit car details, please change the car status first.
                </p>
                <div class="d-flex flex-wrap justify-content-center" style="gap: 0.5rem;">
                    <a href="{{ route('car-status.create', ['car_id' => $model->id]) }}"
                       class="btn btn-primary">
                        <i class="fa fa-exchange"></i> Change car status
                    </a>
                    <a href="{{ route($url . 'index') }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Back to all cars
                    </a>
                </div>
            </div>
        </div>
    @endif

    <section id="basic-datatable">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            Edit {{ $singular }}
                            ({{ $model->car_reg ?? $model->registration }})
                        </h4>
                        <a class="btn btn-primary float-right" href="{{ route($url . 'index') }}"><i
                                class="fa fa-arrow-circle-left"></i> Back</a>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            @include('alerts')
                            <form action="{{ route($url . 'update', $model->id) }}" method="POST"
                                  enctype="multipart/form-data"
                                  id="formEditCar" novalidate
                                  data-latest-mot-id="{{ $editLatestMotId }}"
                                  data-latest-phv-id="{{ $editLatestPhvId }}"
                                  data-current-insurance-status="{{ $editCurrentInsuranceStatus }}"
                                  data-has-insurance-document="{{ $editHasInsuranceDocument }}"
                                  @if($carEditLocked) class="car-edit-locked-form" @endif
                                  @if($carEditLocked) onsubmit="return false;" @endif>
                                @csrf
                                @method('PUT')
                                <fieldset @if($carEditLocked) disabled @endif>
                                    @include($dir . '_form')
                                </fieldset>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@if($model->isFleetStatusLockedForEditing())
    @push('js')
        <script>
            document.documentElement.classList.add('car-edit-locked-page');
        </script>
    @endpush
@else
    @push('js')
        <script src="{{ asset('app-assets/js/scripts/fleetiq-validate-car.js') }}?v=20260703"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var form = document.getElementById('formEditCar');
                if (form && window.FleetiqFormValidation && window.validateCarForm) {
                    FleetiqFormValidation.attach(form, validateCarForm);
                }
            });
        </script>
    @endpush
@endif
