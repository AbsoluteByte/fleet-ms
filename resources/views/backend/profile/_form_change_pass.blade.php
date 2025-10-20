<div class="row">
    <div class="col-sm-4">
        <div class="form-group">
            <label for="old_password">Old Password</label>
            <input type="password" name="old_password" id="old_password" class="form-control">
            @error('old_password')
            <div class="small text-danger">{!! $message !!}</div>
            @enderror
        </div>
    </div>
    <div class="col-sm-4">
        <div class="form-group">
            <label for="password">New Password</label>
            <input type="password" name="password" id="password" class="form-control">
            @error('password')
            <div class="small text-danger">{!! $message !!}</div>
            @enderror
        </div>
    </div>
    <div class="col-sm-4">
        <div class="form-group">
            <label for="password_confirmation">Conform Password</label>
            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control">
            @error('password_confirmation')
            <div class="small text-danger">{!! $message !!}</div>
            @enderror
        </div>
    </div>
</div>
<button type="submit" class="btn btn-primary waves-effect waves-light mt-2">Submit</button>
