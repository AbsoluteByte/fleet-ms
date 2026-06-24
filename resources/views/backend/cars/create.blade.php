@extends('layouts.admin', ['title' => 'Create'])
@section('content')
    <section id="basic-datatable">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ $singular }}</h4>
                        <a class="btn btn-primary float-right" href="{{ route($url . 'index') }}"><i
                                class="fa fa-arrow-circle-left"></i> Back</a>
                    </div>
                    <hr>
                    <div class="card-content">
                        <div class="card-body">
                            @include('alerts')
                            <form action="{{ route($url . 'store') }}" method="POST" enctype="multipart/form-data"
                                  id="formCreateCar" novalidate>
                                @csrf
                                @method('POST')
                                @include($dir . '_form')
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('js')
    <script src="{{ asset('app-assets/js/scripts/fleetiq-validate-car.js') }}?v=20260624"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('formCreateCar');
            if (form && window.FleetiqFormValidation && window.validateCarForm) {
                FleetiqFormValidation.attach(form, validateCarForm);
            }
        });
    </script>
@endpush
