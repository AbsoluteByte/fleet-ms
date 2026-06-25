@extends('layouts.admin', ['title' => 'Driver Payments'])
@section('content')
    <section id="basic-datatable">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Driver Payments</h4>
                        <a class="btn btn-primary float-right" href="{{ route('payments.create') }}">
                            <i class="fa fa-plus"></i> Add Payment
                        </a>
                    </div>
                    <div class="card-content">
                        <div class="card-body card-dashboard">
                            @include('alerts')
                            <div class="table-responsive">
                                <table id="dataTable" class="table datatable table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th>Driver</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Invoices</th>
                                        <th>Payments</th>
                                        <th>Total Due</th>
                                        <th>Credit</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($drivers as $driver)
                                        <tr>
                                            <td>
                                                <strong>{{ $driver->selectOptionLabel() ?: 'N/A' }}</strong>
                                            </td>
                                            <td>{{ $driver->email ?? 'N/A' }}</td>
                                            <td>{{ $driver->phone_number ?? 'N/A' }}</td>
                                            <td>{{ $driver->invoices_count }}</td>
                                            <td>{{ $driver->payments_count }}</td>
                                            <td>
                                                <strong class="{{ $driver->total_due > 0 ? 'text-danger' : 'text-muted' }}">
                                                    £{{ number_format($driver->total_due, 2) }}
                                                </strong>
                                            </td>
                                            <td>
                                                <strong class="{{ $driver->credit_amount > 0 ? 'text-success' : 'text-muted' }}">
                                                    £{{ number_format($driver->credit_amount, 2) }}
                                                </strong>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('payments.driver', $driver) }}" class="btn btn-sm btn-outline-info">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('payments.create', ['driver_id' => $driver->id]) }}" class="btn btn-sm btn-outline-primary">
                                                        <i class="fa fa-plus"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                <i class="fa fa-user fa-3x mb-3"></i>
                                                <br>
                                                No drivers found.
                                            </td>
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
            });
        });
    </script>
@endsection
