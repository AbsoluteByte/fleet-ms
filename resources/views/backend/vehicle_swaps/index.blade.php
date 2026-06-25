@extends('layouts.admin', ['title' => 'Vehicle Swaps'])
@section('css')
    <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/tables/datatable/datatables.min.css') }}">
    <style>
        #basic-datatable .dataTables_wrapper .row:first-child {
            align-items: center;
            margin-bottom: 1rem;
        }

        #basic-datatable .dataTables_filter {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        #basic-datatable .dataTables_filter label {
            display: flex;
            align-items: center;
            margin-bottom: 0;
            white-space: nowrap;
            font-weight: 500;
        }

        #basic-datatable .dataTables_filter input[type="search"] {
            margin-left: 0.5rem;
            min-width: 240px;
            width: auto !important;
            padding: 0.5rem 0.75rem;
            border: 1px solid #d8d6de;
            border-radius: 0.357rem;
            line-height: 1.25;
        }

        #basic-datatable .dataTables_length label {
            display: flex;
            align-items: center;
            margin-bottom: 0;
            white-space: nowrap;
        }

        #basic-datatable .dataTables_length select {
            margin: 0 0.5rem;
            padding: 0.45rem 2rem 0.45rem 0.75rem;
            min-width: 4.5rem;
        }

        #vehicleSwapsTable.table thead th,
        #vehicleSwapsTable.table tbody td {
            vertical-align: middle;
            padding: 0.75rem 1rem;
        }

        #vehicleSwapsTable.table thead th {
            white-space: nowrap;
        }

        #vehicleSwapsTable.table td.text-money,
        #vehicleSwapsTable.table th.text-money {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        #vehicleSwapsTable.table td.text-nowrap-muted {
            max-width: 12rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        #vehicleSwapsTable.table .actions-col .btn-group .btn {
            padding: 0.45rem 0.65rem;
        }

        #vehicleSwapsTable.table .actions-col {
            white-space: nowrap;
            width: 1%;
        }
    </style>
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
                            <div class="table-responsive">
                                <table id="vehicleSwapsTable" class="table datatable table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th>Client</th>
                                        <th>Contact</th>
                                        <th>Old car</th>
                                        <th>Swapped with</th>
                                        <th>Reservation date</th>
                                        <th>Pick up date</th>
                                        <th class="text-money">Agreed rent</th>
                                        <th class="text-money">Amount paid</th>
                                        <th class="text-money" title="Balance payable on pick up">Balance</th>
                                        <th>Reason</th>
                                        <th class="actions-col">Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($swaps as $swap)
                                        @php
                                            $oldLabel = $swap->oldCar
                                                ? $swap->oldCar->registration.' — '.($swap->oldCar->carModel->name ?? '')
                                                : '—';
                                            $newLabel = $swap->swappedWithCar
                                                ? $swap->swappedWithCar->registration.' — '.($swap->swappedWithCar->carModel->name ?? '')
                                                : '—';
                                        @endphp
                                        <tr>
                                            <td><strong>{{ $swap->customer_name }}</strong></td>
                                            <td>{{ $swap->customer_phone ?: '—' }}</td>
                                            <td class="text-nowrap-muted" title="{{ $oldLabel !== '—' ? $oldLabel : '' }}">{{ $oldLabel }}</td>
                                            <td class="text-nowrap-muted" title="{{ $newLabel !== '—' ? $newLabel : '' }}">{{ $newLabel }}</td>
                                            <td data-order="{{ $swap->reservation_date?->timestamp ?? 0 }}">{{ $swap->reservation_date?->format('d/m/Y') ?? '—' }}</td>
                                            <td data-order="{{ $swap->pick_up_date?->timestamp ?? 0 }}">{{ $swap->pick_up_date?->format('d/m/Y') ?? '—' }}</td>
                                            <td class="text-money" data-order="{{ $swap->agreed_rent ?? 0 }}">{{ $swap->agreed_rent !== null ? number_format((float) $swap->agreed_rent, 2) : '—' }}</td>
                                            <td class="text-money" data-order="{{ $swap->amount_paid ?? 0 }}">{{ $swap->amount_paid !== null ? number_format((float) $swap->amount_paid, 2) : '—' }}</td>
                                            <td class="text-money" data-order="{{ $swap->balance_payable_on_pickup ?? 0 }}">{{ $swap->balance_payable_on_pickup !== null ? number_format((float) $swap->balance_payable_on_pickup, 2) : '—' }}</td>
                                            <td>{{ $swap->reasonLabel() }}</td>
                                            <td class="actions-col">
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('vehicle-swaps.edit', $swap) }}"
                                                       class="btn btn-sm btn-outline-warning"
                                                       title="Edit">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('vehicle-swaps.destroy', $swap) }}"
                                                          method="POST" class="d-inline"
                                                          onsubmit="return confirm('Delete this vehicle swap?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                                title="Delete">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="11" class="text-center text-muted py-4">
                                                No vehicle swaps yet. Use the Add vehicle swap button above to create one.
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
                autoWidth: false,
                pageLength: 25,
                order: [[5, 'desc']],
                columnDefs: [
                    {targets: [6, 7, 8], className: 'text-money'},
                    {targets: 10, orderable: false, searchable: false, className: 'actions-col text-center'},
                ],
            });
        });
    </script>
@endsection
