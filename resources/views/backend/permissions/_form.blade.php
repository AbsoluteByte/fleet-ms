<div class="row">
    <div class="col-sm-12">
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" name="name" id="name"  class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $model->name) }}">
            @error('name')
            <div class="small text-danger">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>
<button type="submit" class="btn btn-primary waves-effect waves-light mt-2">Submit</button>

@section('js')
@endsection

@push('css')
@endpush

@push('js')
@endpush
