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
    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="form-group">
            <strong>Permission:</strong>
            <br>
            <br>
            @php
                $permissions = \Spatie\Permission\Models\Permission::get();
            @endphp
            @foreach($permissions as $permission)
                <label class="mr-1" style="{{ $loop->first ? 'float: left' : '' }}">
                    <input type="checkbox" name="permission[]" value="{{ $permission->id }}"
                        {{ $model->hasPermissionTo($permission) ? 'checked' : '' }}>
                    {{ $permission->name }}
                </label>
            @endforeach
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
