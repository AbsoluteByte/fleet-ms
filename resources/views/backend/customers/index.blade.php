@extends('layouts.admin', ['title' => $plural])

@section('content')
    <section id="basic-datatable">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ $plural }}</h4>
                        <a class="btn btn-primary float-right" href="{{ route($url . 'create') }}"><i
                                class="fa fa-plus"></i>
                            Add {{ $singular }}</a>
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
                                        <th>Company</th>
                                        <th>Admin</th>
                                        <th>Package</th>
                                        <th>Status</th>
                                        <th>Subscription</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($tenants as $tenant)
                                        @php
                                            $admin = $tenant->users->first();
                                            $subscription = $tenant->subscription;
                                        @endphp
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <strong>{{ $tenant->company_name }}</strong>
                                            </td>
                                            <td>
                                                {{ $admin->name ?? 'N/A' }}<br>
                                                <small class="text-muted">{{ $admin->email ?? '' }}</small>
                                            </td>
                                            <td>
                                                @if($subscription && $subscription->package)
                                                    <span class="badge badge-info">
                                                    {{ $subscription->package->name }}
                                                </span><br>
                                                    <small>£{{ number_format($subscription->package->price, 2) }}</small>
                                                @elseif($subscription)
                                                    <span class="badge badge-warning">Package Missing</span>
                                                    @if(config('app.debug'))
                                                        <br><small>Sub ID: {{ $subscription->id }}, Pkg
                                                            ID: {{ $subscription->package_id }}</small>
                                                    @endif
                                                @else
                                                    <span class="badge badge-secondary">No Subscription</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($tenant->isActive())
                                                    <span class="badge badge-success">Active</span>
                                                @else
                                                    <span class="badge badge-danger">Suspended</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($subscription)
                                                    @if($subscription->isTrialing())
                                                        <span class="badge badge-warning">Trial</span><br>
                                                        <small>{{ $subscription->trialDaysRemaining() }} days
                                                            left</small>
                                                    @elseif($subscription->isActive())
                                                        <span class="badge badge-success">Active</span>
                                                    @elseif($subscription->isSuspended())
                                                        <span class="badge badge-danger">Suspended</span>
                                                    @else
                                                        <span
                                                            class="badge badge-secondary">{{ ucfirst($subscription->status) }}</span>
                                                    @endif
                                                @else
                                                    <span class="badge badge-secondary">N/A</span>
                                                @endif
                                            </td>
                                            <td>{{ $tenant->created_at->format('d M, Y') }}</td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route($url . 'show', $tenant->id) }}"
                                                       class="btn btn-sm btn-info" title="View">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route($url . 'edit', $tenant->id) }}"
                                                       class="btn btn-sm btn-primary" title="Edit">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route($url . 'destroy', $tenant->id) }}"
                                                          method="POST" style="display: inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger"
                                                                onclick="return confirm('Are you sure?')"
                                                                title="Delete">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center">No customers found</td>
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
                order: [[0, 'desc']] // Latest first
            });
        });
    </script>
@endsection
