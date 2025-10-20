<div class="row">
    <div class="col-sm-6">
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name', $model->name) }}">
            @error('name')
            <div class="small text-danger">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-sm-6">
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email', $model->email) }}">
            @error('email')
            <div class="small text-danger">{{ $message }}</div>
            @enderror
        </div>
    </div>
    @if (Request::is('admin/users/create'))
        <div class="col-sm-6">
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password"
                       class="form-control @error('password') is-invalid @enderror"
                       value="{{ old('password', $model->password) }}">
                @error('password')
                <div class="small text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="col-sm-6">
            <div class="form-group">
                <label for="password_confirmation">Conform Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation"
                       class="form-control @error('password_confirmation') is-invalid @enderror"">
                @error('password_confirmation')
                <div class="small text-danger">{!! $message !!}</div>
                @enderror
            </div>
        </div>
    @endif

</div>
<button type="submit" class="btn btn-primary waves-effect waves-light mt-2">Submit</button>

@section('js')
@endsection

@push('css')
@endpush

@push('js')
@endpush
