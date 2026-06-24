@extends('layouts.admin', ['title' => 'Edit'])
@section('content')
    <section id="basic-datatable">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Edit {{ $singular }}</h4>
                        <a class="btn btn-primary float-right" href="{{ route($url . 'index') }}"><i
                                class="fa fa-arrow-circle-left"></i> Back</a>
                    </div>
                    <hr>
                    <div class="card-content">
                        <div class="card-body">
                            <form action="{{ route($url . 'update', $model->id) }}" method="POST" enctype="multipart/form-data"
                                  id="formEditDriver" novalidate>
                                @csrf
                                @method('PUT')
                                @include($dir . '_form')
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('js')
    <script src="{{ asset('app-assets/js/scripts/fleetiq-validate-driver.js') }}?v=20260624"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('formEditDriver');
            if (form && window.FleetiqFormValidation && window.validateDriverForm) {
                FleetiqFormValidation.attach(form, validateDriverForm);
            }
        });
    </script>
@endsection
