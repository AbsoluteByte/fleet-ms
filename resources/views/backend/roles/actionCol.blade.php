<a href="{{ route($url.'edit', $model->id) }}" title="Edit User" class="btn btn-md btn-link">
    <i class="fa fa-edit"></i>
</a>

<a class="btn btn-md btn-link" title="Delete User" data-toggle="modal" href="#modal-{{ $model->id }}">
    <span class="fa fa-trash"></span>
</a>
<div class="modal fade" id="modal-{{ $model->id }}">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h4 class="modal-title">Delete</h4>
            </div>
            <div class="modal-body text-center text-warning">
                <div class="form-group">
                    <i class="fa fa-exclamation-circle fa-3x"></i>
                </div>
                <div class="form-group">
                    Are you Sure?
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <form action="{{ route($url . 'destroy', $model->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
{{--<a href="{{ route($url.'', $model->slug) }}" class="btn btn-sm btn-link">--}}
{{--    <i class="fa fa-link"></i>--}}
{{--</a>--}}
