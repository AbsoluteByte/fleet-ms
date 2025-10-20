<div class="row">
    <div class="col-sm-6">
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" name="name" id="name"  class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $model->name) }}">
            @error('name')
            <div class="small text-danger">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-sm-6">
        <div class="form-group">
            <label for="name">Email</label>
            <input type="email" name="email" id="email"  class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $model->email) }}">
            @error('email')
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
