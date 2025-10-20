@extends('layouts.admin', ['title' => 'Agreements'])
@section('content')
    <section id="basic-datatable">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ $plural }}</h4>
                        <a class="btn btn-primary float-right" href="{{ route($url . 'create') }}"><i
                                class="fa fa-plus"></i>
                            Create New {{ $singular }}</a>
                    </div>
                    <hr>
                    <div class="card-content">
                        <div class="card-body card-dashboard">
                            @include('alerts')
                            <div class="table-responsive">
                                <table id="dataTable" class="table datatable table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th>Company</th>
                                        <th>Driver</th>
                                        <th>Car</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Rent</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($agreements as $agreement)
                                        <tr>
                                            <td>{{ $agreement->company->name }}</td>
                                            <td>{{ $agreement->driver->full_name }}</td>
                                            <td>{{ $agreement->car->registration }}</td>
                                            <td>{{ $agreement->start_date->format('M d, Y') }}</td>
                                            <td>{{ $agreement->end_date->format('M d, Y') }}</td>
                                            <td>£{{ number_format($agreement->agreed_rent, 2) }}</td>
                                            <td>
                                                <span class="badge" style="background-color: {{ $agreement->status->color }}">
                                                    {{ $agreement->status->name }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('agreements.show', $agreement) }}" class="btn btn-sm btn-outline-info">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('agreements.edit', $agreement) }}" class="btn btn-sm btn-outline-warning">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <a href="{{ route('agreements.pdf', $agreement) }}" class="btn btn-sm btn-outline-danger" target="_blank" title="Generate PDF">
                                                        <i class="fa fa-file-pdf-o"></i>
                                                    </a>
                                                    <form action="{{ route('agreements.destroy', $agreement) }}" method="POST" style="display: inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                                onclick="return confirm('Are you sure?')">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                <i class="fa fa-handshake fa-3x mb-3"></i>
                                                <br>
                                                No agreements found. <a href="{{ route('agreements.create') }}">Create your first agreement</a>
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
