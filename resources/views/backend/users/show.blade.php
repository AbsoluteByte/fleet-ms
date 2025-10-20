@extends('layouts.admin', ['title' => 'User Details'])

@section('content')
    <section class="user-details">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-2 card-title">User Details</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr>
                                <th>Name</th>
                                <td>{{ $model->name }}</td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td>{{ $model->email }}</td>
                            </tr>
                            <tr>
                                <th>Roles</th>
                                <td>
                                    {{ $model->roles->pluck('name')->join(', ') ?: 'No role assigned' }}
                                </td>
                            </tr>
                            <tr>
                                <th>Created At</th>
                                <td>{{ $model->created_at->format('d M, Y h:i A') }}</td>
                            </tr>
                            <tr>
                                <th>Updated At</th>
                                <td>{{ $model->updated_at->format('d M, Y h:i A') }}</td>
                            </tr>
                        </table>

                        <a href="{{ route('users.index') }}" class="btn btn-secondary mt-3">
                            <i class="fa fa-arrow-left"></i> Back to Users
                        </a>
                        <a href="{{ route('users.edit', $model) }}" class="btn btn-warning mt-3">
                            <i class="fa fa-edit"></i> Edit User
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
