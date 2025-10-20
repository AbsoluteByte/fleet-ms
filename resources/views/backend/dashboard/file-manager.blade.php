@extends('layouts.admin', ['title' => 'File-Manager'])

@section('content')
    <section id="basic-datatable">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">File Manager</h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body card-dashboard">
                            @include('alerts')
                                <!-- File manager iframe for the user's folder -->
                                <iframe src="/file-manager/fm-button"
                                        style="width: 100%; height: 100vh; border: none;"></iframe>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('js')
    <!-- File manager styles -->
    <link rel="stylesheet" href="{{ asset('vendor/file-manager/css/file-manager.css') }}">
    <!-- File manager scripts -->
    <script src="{{ asset('vendor/file-manager/js/file-manager.js') }}"></script>
@endsection
