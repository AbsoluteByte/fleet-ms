@extends('layouts.admin', ['title' => 'Create'])
@section('content')
    <section id="basic-datatable">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header card_color_custom">
                        <h4 class="card-title">{{ $singular }}</h4>
                        <a class="btn btn-primary float-right" href="{{ route($url . 'index') }}"><i
                                class="fa fa-arrow-circle-left"></i> Back</a>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            <form action="{{ route($url . 'store') }}" method="POST" enctype="multipart/form-data">
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
