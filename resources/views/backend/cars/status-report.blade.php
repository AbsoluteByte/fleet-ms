@extends('layouts.admin', ['title' => $statusLabel . ' Cars'])

@section('content')
    @php
        $isDamagedReport = $status === 'damaged';
    @endphp
    <section id="basic-datatable">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ $statusLabel }} Cars</h4>
                        <a class="btn btn-secondary float-right" href="{{ route('cars.index') }}">Back to Fleet</a>
                    </div>
                    <div class="card-content">
                        <div class="card-body card-dashboard">
                            @include('alerts')
                            <div class="table-responsive">
                                <table id="dataTable" class="table datatable table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th>Registration</th>
                                        <th>Company</th>
                                        <th>Model</th>
                                        <th>PHV Council</th>
                                        @if($isDamagedReport)
                                            <th>Damaged Notes</th>
                                        @else
                                            <th>Available From</th>
                                            <th>Reservation</th>
                                        @endif
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($cars as $car)
                                        @php
                                            $reservation = $car->activeReservation();
                                            $damagedNotes = trim($car->damaged_notes ?? '');
                                            $notesPreview = \Illuminate\Support\Str::limit($damagedNotes, 80);
                                            $hasLongDamagedNotes = \Illuminate\Support\Str::length($damagedNotes) > 80;
                                        @endphp
                                        <tr>
                                            <td><strong>{{ $car->registration }}</strong></td>
                                            <td>{{ $car->company->name ?? '—' }}</td>
                                            <td>{{ $car->carModel->name ?? '—' }}</td>
                                            <td>{{ $car->latestPhvCounselName() ?? '—' }}</td>
                                            @if($isDamagedReport)
                                                <td>
                                                    @if($damagedNotes)
                                                        <span>{{ $notesPreview }}</span>
                                                        @if($hasLongDamagedNotes)
                                                            <button type="button" class="btn btn-link btn-sm p-0 ml-50 align-baseline"
                                                                    data-toggle="modal" data-target="#damagedNotesModal{{ $car->id }}">
                                                                View
                                                            </button>
                                                        @endif
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                            @else
                                                <td>{{ $car->available_from_date ? $car->available_from_date->format('d M, Y') : 'Now' }}</td>
                                                <td>{{ $reservation ? $reservation->customer_name : '—' }}</td>
                                            @endif
                                            <td>
                                                <a href="{{ route('cars.show', $car) }}" class="btn btn-sm btn-outline-info"><i class="fa fa-eye"></i></a>
                                                <a href="{{ route('cars.edit', $car) }}" class="btn btn-sm btn-outline-warning"><i class="fa fa-edit"></i></a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $isDamagedReport ? 6 : 7 }}" class="text-center text-muted py-4">No {{ strtolower($statusLabel) }} cars found.</td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if($isDamagedReport)
                                @foreach($cars as $car)
                                    @php
                                        $damagedNotes = trim($car->damaged_notes ?? '');
                                        $hasLongDamagedNotes = \Illuminate\Support\Str::length($damagedNotes) > 80;
                                    @endphp
                                    @if($hasLongDamagedNotes)
                                        <div class="modal fade" id="damagedNotesModal{{ $car->id }}" tabindex="-1" role="dialog"
                                             aria-labelledby="damagedNotesModalLabel{{ $car->id }}" aria-hidden="true">
                                            <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="damagedNotesModalLabel{{ $car->id }}">
                                                            Damaged Notes - {{ $car->registration }}
                                                        </h5>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body p-0">
                                                        <div class="table-responsive">
                                                            <table class="table table-bordered mb-0">
                                                                <thead class="thead-light">
                                                                    <tr>
                                                                        <th>Damaged Notes</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <tr>
                                                                        <td style="white-space: pre-wrap;">{{ $damagedNotes }}</td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            @endif
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
            $('#dataTable').DataTable({ processing: true, responsive: true });
        });
    </script>
@endsection
