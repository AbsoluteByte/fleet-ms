<div class="row">
    <div class="col-sm-6">
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', auth()->user()->name) }}">
            @error('name')
            <div class="small text-danger">{!! $message !!}</div>
            @enderror
        </div>
    </div>
    <div class="col-sm-6">
        <div class="form-group">
            <label for="name">Email</label>
            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', auth()->user()->email) }}">
            @error('email')
            <div class="small text-danger">{!! $message !!}</div>
            @enderror
        </div>
    </div>

    <div class="col-sm-6">
        <div class="form-group">
            <label for="picture">Picture</label>
            <input type="file" name="profile_image" class="form-control @error('profile_image') is-invalid @enderror" value="{{ auth()->user()->profile_image }}"/>
            @error('profile_image')
            <div class="small text-danger">{!! $message !!}</div>
            @enderror
        </div>
    </div>
    @if (auth()->user()->profile_image)
        <img width="10%" class="rounded" src="{{ asset('uploads/users/' . auth()->user()->profile_image) }}"/>
    @else
        <p>No image found</p>
    @endif
</div>
<button type="submit" class="btn btn-primary waves-effect waves-light mt-2">Submit</button>
