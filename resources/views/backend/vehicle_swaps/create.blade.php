@extends('layouts.admin', ['title' => 'Add vehicle swap'])
@section('css')
    <style>
        .vehicle-swap-form-card > .card-header {
            padding-bottom: 1.25rem;
        }
    </style>
@endsection
@section('content')
    <section>
        <div class="row">
            <div class="col-12">
                <div class="card vehicle-swap-form-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Add vehicle swap</h4>
                        <a href="{{ route('vehicle-swaps.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fa fa-arrow-left"></i> Back to vehicle swaps
                        </a>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            @include('alerts')
                            <form method="POST" action="{{ route('vehicle-swaps.store') }}" id="formCreateVehicleSwap" novalidate>
                                @csrf
                                @include('backend.vehicle_swaps._form', [
                                    'oldCars' => $oldCars,
                                    'replacementCars' => $replacementCars,
                                ])
                                <div class="form-group mb-0">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-check"></i> Complete vehicle swap
                                    </button>
                                    <a href="{{ route('vehicle-swaps.index') }}" class="btn btn-outline-secondary ml-1">Cancel</a>
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
    @php use App\Models\VehicleSwap; @endphp
    <script src="{{ asset('app-assets/js/scripts/fleetiq-validate-vehicle-swap.js') }}?v=20260626"></script>
    <script>
        window.fleetiqVehicleSwapValidation = {
            reasonPhvl: @json(VehicleSwap::REASON_PHVL_ISSUES),
            reasonOthers: @json(VehicleSwap::REASON_OTHERS),
            phvlFailed: @json(VehicleSwap::PHVL_FAILED),
            phvlDocumentation: @json(VehicleSwap::PHVL_DOCUMENTATION),
        };

        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('formCreateVehicleSwap');
            if (form && window.FleetiqFormValidation && window.validateVehicleSwapForm) {
                FleetiqFormValidation.attach(form, validateVehicleSwapForm);
            }

            var reasonSelect = document.getElementById('reason_for_swap');
            var phvlTypeWrap = document.getElementById('swap_phvl_issue_type_wrap');
            var phvlNotesWrap = document.getElementById('swap_phvl_issue_notes_wrap');
            var reasonNotesWrap = document.getElementById('swap_reason_notes_wrap');
            var phvlTypeSelect = document.getElementById('phvl_issue_type');

            if (!reasonSelect) {
                return;
            }

            function refreshReasonFields() {
                var reason = reasonSelect.value;
                var showPhvl = reason === window.fleetiqVehicleSwapValidation.reasonPhvl;
                var showOtherNotes = reason === window.fleetiqVehicleSwapValidation.reasonOthers;

                phvlTypeWrap.classList.toggle('d-none', !showPhvl);
                reasonNotesWrap.classList.toggle('d-none', !showOtherNotes);

                var phvlType = phvlTypeSelect ? phvlTypeSelect.value : '';
                var needsPhvlNotes = showPhvl && (
                    phvlType === window.fleetiqVehicleSwapValidation.phvlFailed
                    || phvlType === window.fleetiqVehicleSwapValidation.phvlDocumentation
                );
                phvlNotesWrap.classList.toggle('d-none', !needsPhvlNotes);
            }

            reasonSelect.addEventListener('change', refreshReasonFields);
            if (phvlTypeSelect) {
                phvlTypeSelect.addEventListener('change', refreshReasonFields);
            }
            refreshReasonFields();
        });
    </script>
@endsection
