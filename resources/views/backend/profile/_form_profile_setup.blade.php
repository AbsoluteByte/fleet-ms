<div class="row">
    <div class="col-sm-6">
        <div class="form-group">
            <label for="business_name">Business Name</label>
            <input type="text" name="business_name" id="business_name" class="form-control"
                   @error('business_name') is-invalid @enderror>
            @error('business_name')
            <div class="small text-danger">{!! $message !!}</div>
            @enderror
        </div>
    </div>
    <div class="col-sm-6">
        <div class="form-group">
            <label for="business_type">Business Type</label>
            <input type="text" name="business_type" id="business_type" class="form-control"
                   @error('business_type') is-invalid @enderror>
            @error('business_type')
            <div class="small text-danger">{!! $message !!}</div>
            @enderror
        </div>
    </div>
    <div class="col-sm-6">
        <div class="form-group">
            <label for="address">Address</label>
            <textarea name="address" id="address" class="form-control"
                      @error('address') is-invalid @enderror></textarea>
            @error('address')
            <div class="small text-danger">{!! $message !!}</div>
            @enderror
        </div>
    </div>

    <div class="col-sm-6">
        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="number"  name="phone" id="phone" class="form-control"
                   @error('phone') is-invalid @enderror>
            @error('phone')
            <div class="small text-danger">{!! $message !!}</div>
            @enderror
        </div>
    </div>
    <div class="col-sm-6">
        <div class="form-group">
            <label for="logo">Logo</label>
            <input type="file" name="logo" id="logo" class="form-control">
            @error('logo')
            <div class="small text-danger">{!! $message !!}</div>
            @enderror
        </div>
    </div>
</div>
<button type="submit" class="btn btn-primary waves-effect waves-light mt-2">Submit</button>
