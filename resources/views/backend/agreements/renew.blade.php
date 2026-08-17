@extends('layouts.admin', ['title' => 'Renew Agreement'])

@section('content')
    <section>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Renew Agreement</h4>
                    </div>
                    <div class="card-body">
                        @include('alerts')

                        <p class="text-muted mb-2">
                            Create a new Active agreement for the same customer and vehicle. The previous agreement stays in history as Expired.
                        </p>

                        <div class="table-responsive mb-2">
                            <table class="table table-sm table-bordered">
                                <tbody>
                                <tr>
                                    <th style="width: 220px;">Customer</th>
                                    <td>{{ $agreement->driver?->selectOptionLabel() ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Vehicle</th>
                                    <td>{{ $agreement->car?->registration ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Hire start date</th>
                                    <td>{{ optional($agreement->start_date)->format('d M Y') ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Agreement expiry date</th>
                                    <td>{{ optional($agreement->end_date)->format('d M Y') ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Current hire status</th>
                                    <td>{{ optional($agreement->status)->name ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Agreed rent</th>
                                    <td>£{{ number_format((float) $agreement->agreed_rent, 2) }} / {{ $agreement->rent_interval }}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <form method="POST" action="{{ route('agreements.renew.store', $agreement) }}">
                            @csrf
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="start_date">New start date</label>
                                    <input type="datetime-local"
                                           name="start_date"
                                           id="start_date"
                                           class="form-control @error('start_date') is-invalid @enderror"
                                           value="{{ old('start_date', $suggestedStart->format('Y-m-d\TH:i')) }}"
                                           required>
                                    @error('start_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="end_date">New expiry date</label>
                                    <input type="date"
                                           name="end_date"
                                           id="end_date"
                                           class="form-control @error('end_date') is-invalid @enderror"
                                           value="{{ old('end_date', $suggestedEnd->toDateString()) }}"
                                           required>
                                    @error('end_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">Create renewed agreement</button>
                            <a href="{{ route('agreements.show', $agreement) }}" class="btn btn-outline-secondary">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
