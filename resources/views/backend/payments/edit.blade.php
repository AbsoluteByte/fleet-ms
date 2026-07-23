@extends('layouts.admin', ['title' => 'Edit Payment'])
@section('content')
    <section id="basic-datatable">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Edit {{ $singular }}</h4>
                        <a class="btn btn-primary float-right" href="{{ route('payments.show', $model) }}"><i
                                class="fa fa-arrow-circle-left"></i> Back</a>
                    </div>
                    <div class="card-content">
                        <div class="card-body">
                            @include('alerts')
                            @if($isPosted ?? false)
                                <div class="alert alert-warning">
                                    This payment is already posted. Saving will recalculate invoice allocations, driver credit, and daily financial sheet adjustments where applicable.
                                </div>
                            @endif
                            <form action="{{ route($url . 'update', $model->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                @include($dir . '_form', ['isEdit' => true, 'isPosted' => $isPosted ?? false])
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
