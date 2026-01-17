@extends('layouts.admin', ['title' => 'Create ' . $singular])

@section('content')
    <section id="basic-datatable">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Create {{ $singular }}</h4>
                        <a class="btn btn-primary float-right" href="{{ route($url . 'index') }}">
                            <i class="fa fa-arrow-circle-left"></i> Back
                        </a>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            <form action="{{ route($url . 'store') }}" method="POST">
                                @csrf
                                @include($dir . '_form')
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
