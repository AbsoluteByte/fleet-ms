@extends('layouts.admin', ['title' => 'Profile'])

@section('content')
    <section id="basic-datatable">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ $singular }}</h4>
                        <a class="btn btn-primary float-right" href=""><i
                                class="fa fa-arrow-circle-left"></i> Back</a>
                    </div>
                    <div class="card-content">
                        <div class="card-body card-dashboard">
                            <div class="row">
                                <div class="col-sm-12">
                                    <h3>Business Name: {{ $profile->business_name }}</h3>
                                    <div class="form-group">
                                        <strong>Business Type: </strong>{!! $profile->business_type !!}
                                    </div>
                                    <div class="form-group">
                                        <strong>Address: </strong>{!! $profile->address !!}
                                    </div>
                                    <div class="form-group">
                                        <strong>Phone: </strong>{!! $profile->phone !!}
                                    </div>

                                    @if($profile->logo)
                                        <p>Logo:</p>
                                        <img src="{{ asset('uploads/logo/' . $profile->logo) }}" alt="Logo"
                                             style="width: 150px; height: auto;">
                                    @else
                                        <p>No Logo Uploaded</p>
                                    @endif

                                    <hr>

                                    <p><strong>QR Code:</strong></p>
                                    @if($profile->qr_code)
                                        <img src="{{ asset($profile->qr_code) }}" alt="QR Code"
                                             style="width: 150px; height: auto;">
                                    @else
                                        <p>No QR Code Generated</p>
                                    @endif

                                    <p><strong>Review Link:</strong> <a href="{{ $profile->review_link }}"
                                                                        target="_blank">{{ $profile->review_link }}</a>
                                    </p>

                                    {{--<div class="form-group">
                                        <strong>Name: </strong>{!! $model->name !!}
                                    </div>--}}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
