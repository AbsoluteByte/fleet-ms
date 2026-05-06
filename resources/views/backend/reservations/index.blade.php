@extends('layouts.admin', ['title' => 'Reservations'])
@section('css')
    <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/tables/datatable/datatables.min.css') }}">
    <style>
        /* Match Cars list: DataTables controls + readable grid */
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

        #reservationsTable.table thead th,
        #reservationsTable.table tbody td {
            vertical-align: middle;
            padding: 0.75rem 1rem;
        }

        #reservationsTable.table thead th {
            white-space: nowrap;
        }

        #reservationsTable.table td.text-money,
        #reservationsTable.table th.text-money {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        #reservationsTable.table td.text-nowrap-muted {
            max-width: 14rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        #reservationsTable.table .actions-col .btn-group .btn {
            padding: 0.45rem 0.65rem;
        }

        #reservationsTable.table .actions-col {
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
                        <h4 class="card-title">Reservations</h4>
                        <div class="float-right">
                            <a href="{{ route('reservations.create') }}" class="btn btn-primary btn-sm">
                                <i class="fa fa-plus"></i> Add reservation
                            </a>
                        </div>
                    </div>
                    <hr>
                    <div class="card-content">
                        <div class="card-body card-dashboard">
                            @include('alerts')
                            <div class="table-responsive">
                                <table id="reservationsTable" class="table datatable table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th>Client</th>
                                        <th>Contact</th>
                                        <th>Car</th>
                                        <th>Reservation date</th>
                                        <th>Pick up date</th>
                                        <th class="text-money">Agreed rent</th>
                                        <th class="text-money">Amount paid</th>
                                        <th class="text-money" title="Balance payable on pick up">Balance</th>
                                        <th class="actions-col">Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($reservations as $reservation)
                                        @php
                                            $pickUp = $reservation->effectivePickUpDate();
                                            $carLabel = $reservation->car
                                                ? $reservation->car->registration.' — '.($reservation->car->carModel->name ?? '')
                                                : '—';
                                        @endphp
                                        <tr>
                                            <td><strong>{{ $reservation->customer_name }}</strong></td>
                                            <td>{{ $reservation->customer_phone ?: '—' }}</td>
                                            <td class="text-nowrap-muted" title="{{ $carLabel !== '—' ? $carLabel : '' }}">{{ $carLabel }}</td>
                                            <td data-order="{{ $reservation->reservation_date?->timestamp ?? 0 }}">{{ $reservation->reservation_date?->format('d/m/Y') ?? '—' }}</td>
                                            <td data-order="{{ $pickUp?->timestamp ?? 0 }}">{{ $pickUp?->format('d/m/Y') ?? '—' }}</td>
                                            <td class="text-money" data-order="{{ $reservation->agreed_rent ?? 0 }}">{{ $reservation->agreed_rent !== null ? number_format((float) $reservation->agreed_rent, 2) : '—' }}</td>
                                            <td class="text-money" data-order="{{ $reservation->amount_paid ?? 0 }}">{{ $reservation->amount_paid !== null ? number_format((float) $reservation->amount_paid, 2) : '—' }}</td>
                                            <td class="text-money" data-order="{{ $reservation->balance_payable_on_pickup ?? 0 }}">{{ $reservation->balance_payable_on_pickup !== null ? number_format((float) $reservation->balance_payable_on_pickup, 2) : '—' }}</td>
                                            <td class="actions-col">
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('reservations.edit', $reservation) }}"
                                                       class="btn btn-sm btn-outline-warning"
                                                       title="Edit">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('reservations.destroy', $reservation) }}"
                                                          method="POST" class="d-inline"
                                                          onsubmit="return confirm('Delete this reservation?');">
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
                                            <td colspan="9" class="text-center text-muted py-4">
                                                No reservations yet. Use the Add reservation button above to create one.
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
            $('#reservationsTable').DataTable({
                processing: true,
                responsive: true,
                autoWidth: false,
                pageLength: 25,
                order: [[4, 'desc']],
                columnDefs: [
                    { targets: [5, 6, 7], className: 'text-money' },
                    { targets: 8, orderable: false, searchable: false, className: 'actions-col text-center' },
                ],
            });
        });
    </script>
@endsection
