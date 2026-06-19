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
                       value="{{ old('password') }}">
                @error('password')
                <div class="small text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="col-sm-6">
            <div class="form-group">
                <label for="password_confirmation">Confirm Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation"
                       class="form-control @error('password_confirmation') is-invalid @enderror">
                @error('password_confirmation')
                <div class="small text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    @else
        @if((int) ($model->id ?? 0) !== (int) auth()->id())
            <div class="col-sm-12">
                <div class="custom-control custom-checkbox mb-2">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                           class="custom-control-input @error('is_active') is-invalid @enderror"
                        {{ old('is_active', $model->is_active ?? true) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="is_active">Active user (can log in)</label>
                    @error('is_active')
                    <div class="small text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        @endif

        <div class="col-sm-12">
            <hr>
            <h6 class="mb-1">Change password</h6>
            <small class="text-muted d-block mb-2">Leave blank to keep the current password.</small>
        </div>
        <div class="col-sm-6">
            <div class="form-group">
                <label for="password">New password</label>
                <input type="password" name="password" id="password"
                       class="form-control @error('password') is-invalid @enderror"
                       autocomplete="new-password">
                @error('password')
                <div class="small text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="col-sm-6">
            <div class="form-group">
                <label for="password_confirmation">Confirm new password</label>
                <input type="password" name="password_confirmation" id="password_confirmation"
                       class="form-control @error('password_confirmation') is-invalid @enderror"
                       autocomplete="new-password">
                @error('password_confirmation')
                <div class="small text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>
    @endif
</div>
<button type="submit" class="btn btn-primary waves-effect waves-light mt-2">Submit</button>
