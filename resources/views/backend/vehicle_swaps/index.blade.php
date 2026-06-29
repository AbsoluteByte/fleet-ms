@extends('layouts.admin', ['title' => 'Vehicle Swaps'])
@section('css')
    <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/tables/datatable/datatables.min.css') }}">
@endsection
@section('content')
    <section id="basic-datatable">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Vehicle Swaps</h4>
                        <div class="float-right">
                            <a href="{{ route('vehicle-swaps.create') }}" class="btn btn-primary btn-sm">
                                <i class="fa fa-plus"></i> Add vehicle swap
                            </a>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="card-body card-dashboard">
                            @include('alerts')
                            <p class="text-muted mb-2">Swaps are agreement car changes. Use the new agreement to generate permission letters.</p>
                            <div class="table-responsive">
                                <table id="vehicleSwapsTable" class="table datatable table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th>Driver</th>
                                        <th>Old car</th>
                                        <th>New car</th>
                                        <th>Swap date</th>
                                        <th class="text-right">New rent</th>
                                        <th>Reason</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($swaps as $swap)
                                        @php
                                            $oldCar = $swap->upgradedFromAgreement?->car;
                                            $oldLabel = $oldCar
                                                ? $oldCar->registration.' — '.($oldCar->carModel->name ?? '')
                                                : '—';
                                            $newLabel = $swap->car
                                                ? $swap->car->registration.' — '.($swap->car->carModel->name ?? '')
                                                : '—';
                                        @endphp
                                        <tr>
                                            <td><strong>{{ $swap->driver?->full_name ?: '—' }}</strong></td>
                                            <td>{{ $oldLabel }}</td>
                                            <td>{{ $newLabel }}</td>
                                            <td data-order="{{ $swap->start_date?->timestamp ?? 0 }}">{{ $swap->start_date?->format('d/m/Y') ?? '—' }}</td>
                                            <td class="text-right">{{ $swap->agreed_rent !== null ? number_format((float) $swap->agreed_rent, 2) : '—' }}</td>
                                            <td>{{ $swap->swapReasonLabel() ?? '—' }}</td>
                                            <td class="text-nowrap text-right">
                                                <a href="{{ route('agreements.show', $swap) }}"
                                                   class="btn btn-sm btn-outline-info" title="View agreement">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="{{ route('agreements.permission-letter', $swap) }}"
                                                   class="btn btn-sm btn-outline-primary" title="Permission letter"
                                                   target="_blank" rel="noopener noreferrer">
                                                    <i class="fa fa-file-text-o"></i> Permission letter
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                No vehicle swaps yet. Use Add vehicle swap to change a car on an active agreement.
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
@section('js')
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.bootstrap4.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            $('#vehicleSwapsTable').DataTable({
                processing: true,
                responsive: true,
                pageLength: 25,
                order: [[3, 'desc']],
                columnDefs: [
                    {targets: 6, orderable: false, searchable: false},
                ],
            });
        });
    </script>
@endsection
