@extends('layouts.admin', ['title' => 'Profile Setup'])
@section('content')
    <section id="basic-datatable">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ $singular }}</h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            <form action="{{ route('profile.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @include($dir . '_form_profile_setup')
                            </form>
                        </div><!-- /.card-body -->
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
