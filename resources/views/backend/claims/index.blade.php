@extends('layouts.admin', ['title' => 'Claims'])
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
                                        <th>Car</th>
                                        <th>Case Date</th>
                                        <th>Incident Date</th>
                                        <th>Our Reference</th>
                                        <th>Case Reference</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($claims as $claim)
                                        <tr>
                                            <td>{{ $claim->car->registration }}</td>
                                            <td>{{ $claim->case_date->format('M d, Y') }}</td>
                                            <td>{{ $claim->incident_date->format('M d, Y') }}</td>
                                            <td>{{ $claim->our_reference }}</td>
                                            <td>{{ $claim->case_reference }}</td>
                                            <td>
                                <span class="badge" style="background-color: {{ $claim->status->color }}">
                                    {{ $claim->status->name }}
                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('claims.show', $claim) }}"
                                                       class="btn btn-sm btn-outline-info">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('claims.edit', $claim) }}"
                                                       class="btn btn-sm btn-outline-warning">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('claims.destroy', $claim) }}" method="POST"
                                                          style="display: inline;">
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
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="fa fa-exclamation-triangle fa-3x mb-3"></i>
                                                <br>
                                                No claims found. <a href="{{ route('claims.create') }}">Create your
                                                    first claim</a>
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
