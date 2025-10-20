@extends('layouts.admin', ['title' => 'ProfileDetail'])
@section('content')
    <section id="basic-datatable">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    @include('alerts')
                    <div class="card-header card_color_custom">
                        <h4 class="card-title">{{ $singular }}</h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            <form action="{{ url(route('update-profile', auth()->user()->id)) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                @include($dir . '_form')
                            </form>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            <form action="{{ url(route('change-password', auth()->user()->id)) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('POST')
                                @include($dir . '_form_change_pass')
                            </form>
                        </div><!-- /.card-body -->
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
