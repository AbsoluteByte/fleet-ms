{{-- resources/views/backend/packages/index.blade.php --}}
@extends('layouts.admin', ['title' => $plural])

@section('content')
    <section id="basic-datatable">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ $plural }}</h4>
                        <a class="btn btn-primary float-right" href="{{ route($url . 'create') }}">
                            <i class="fa fa-plus"></i> Add {{ $singular }}
                        </a>
                    </div>
                    <hr>
                    <div class="card-content">
                        <div class="card-body card-dashboard">
                            @include('alerts')
                            <div class="table-responsive">
                                <table id="dataTable" class="table datatable table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Price</th>
                                        <th>Billing Period</th>
                                        <th>Limits</th>
                                        <th>Trial Days</th>
                                        <th>Features</th>
                                        <th>Subscriptions</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($packages as $package)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <strong>{{ $package->name }}</strong>
                                                @if($package->description)
                                                    <br><small class="text-muted">{{ Str::limit($package->description, 50) }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <strong class="text-success">{{ $package->getPriceFormatted() }}</strong>
                                            </td>
                                            <td>
                                                <span class="badge badge-info">{{ ucfirst($package->billing_period) }}</span>
                                            </td>
                                            <td>
                                                <small>
                                                    <i class="fa fa-users"></i> {{ $package->getUsersLimit() }}<br>
                                                    <i class="fa fa-car"></i> {{ $package->getVehiclesLimit() }}<br>
                                                    <i class="fa fa-user"></i> {{ $package->getDriversLimit() }}
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge badge-warning">{{ $package->trial_days }} days</span>
                                            </td>
                                            <td>
                                                @if($package->has_notifications)
                                                    <i class="fa fa-bell text-success" title="Notifications"></i>
                                                @endif
                                                @if($package->has_reports)
                                                    <i class="fa fa-chart-bar text-success" title="Reports"></i>
                                                @endif
                                                @if($package->has_api_access)
                                                    <i class="fa fa-code text-success" title="API Access"></i>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-primary">{{ $package->subscriptions_count }}</span>
                                            </td>
                                            <td>
                                                @if($package->is_active)
                                                    <span class="badge badge-success">Active</span>
                                                @else
                                                    <span class="badge badge-danger">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route($url . 'show', $package->id) }}"
                                                       class="btn btn-sm btn-info" title="View">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route($url . 'edit', $package->id) }}"
                                                       class="btn btn-sm btn-primary" title="Edit">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route($url . 'toggle-status', $package->id) }}"
                                                          method="POST" style="display: inline;">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-warning"
                                                                title="Toggle Status">
                                                            <i class="fa fa-power-off"></i>
                                                        </button>
                                                    </form>
                                                    <form action="{{ route($url . 'destroy', $package->id) }}"
                                                          method="POST" style="display: inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger"
                                                                onclick="return confirm('Are you sure? This will fail if package has active subscriptions.')"
                                                                title="Delete">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center">No packages found</td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/tables/datatable/datatables.min.css') }}">
@endsection

@section('js')
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.bootstrap4.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            $('#dataTable').DataTable({
                processing: true,
                responsive: true,
                order: [[0, 'desc']]
            });
        });
    </script>
@endsection
