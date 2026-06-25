@extends('layouts.admin', ['title' => 'Available Cars by PHV'])

@section('content')
    <section id="basic-datatable">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">PHV Wise Cars Available for Rent</h4>
                        <a class="btn btn-secondary float-right" href="{{ route('cars.index') }}">Back to Fleet</a>
                    </div>
                    <div class="card-content">
                        <div class="card-body card-dashboard">
                            @include('alerts')
                            @forelse($cars as $counsel => $group)
                                <h5 class="mt-2 mb-1">{{ $counsel }}</h5>
                                <div class="table-responsive mb-3">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                        <tr>
                                            <th>Registration</th>
                                            <th>Company</th>
                                            <th>Model</th>
                                            <th>Available From</th>
                                            <th>Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($group as $car)
                                            <tr>
                                                <td><strong>{{ $car->registration }}</strong></td>
                                                <td>{{ $car->company->name ?? '—' }}</td>
                                                <td>{{ $car->carModel->name ?? '—' }}</td>
                                                <td>{{ $car->available_from_date ? $car->available_from_date->format('d M, Y') : 'Now' }}</td>
                                                <td>
                                                    <a href="{{ route('cars.show', $car) }}" class="btn btn-sm btn-outline-info"><i class="fa fa-eye"></i></a>
                                                    <a href="{{ route('cars.edit', $car) }}" class="btn btn-sm btn-outline-warning"><i class="fa fa-edit"></i></a>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @empty
                                <div class="text-center text-muted py-4">No cars are currently available for rent.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
