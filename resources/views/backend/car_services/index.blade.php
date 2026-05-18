@extends('layouts.admin', ['title' => 'Service'])

@section('css')
    @unless($selectedCar)
        <link rel="stylesheet" type="text/css"
              href="{{ asset('app-assets/vendors/css/forms/select/select2.min.css') }}">
        <style>
            .car-service-select-wrap {
                min-width: 280px;
                max-width: 420px;
            }

            .car-service-select-wrap .select2-container {
                width: 100% !important;
            }
        </style>
    @endunless
@endsection

@section('content')
    <section>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center py-1">
                        <h4 class="card-title mb-0"><i class="fa fa-wrench"></i> Service</h4>
                        <a href="{{ route('cars.index') }}" class="btn btn-outline-secondary btn-sm mb-0">
                            <i class="fa fa-arrow-left"></i> Back to cars
                        </a>
                    </div>
                    <hr class="my-0">
                    <div class="card-body">
                        @include('alerts')

                        @unless($selectedCar)
                            <div class="mb-3">
                                <form method="get" action="{{ route('car-services.index') }}" class="form-inline flex-wrap align-items-end">
                                    <div class="form-group mr-2 mb-2 car-service-select-wrap">
                                        <label for="car_id" class="mr-2">Select car</label>
                                        <select name="car_id" id="car_id" class="form-control" required>
                                            <option value="">— Choose —</option>
                                            @foreach($cars as $car)
                                                <option value="{{ $car->id }}">
                                                    {{ $car->registration }}
                                                    @if($car->carModel)
                                                        — {{ $car->carModel->name ?? '' }}
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary mb-2">
                                        Next
                                    </button>
                                </form>
                            </div>
                        @endunless

                        @if($selectedCar)
                            <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                                <h5 class="mb-0">Add service</h5>
                                <a href="{{ route('car-services.index') }}" class="btn btn-sm btn-outline-secondary mb-0">
                                    Change car
                                </a>
                            </div>
                            <p class="text-muted small mb-3">
                                {{ $selectedCar->registration }}
                                @if($selectedCar->carModel)
                                    — {{ $selectedCar->carModel->name ?? '' }}
                                @endif
                            </p>

                            @php
                                $latestService = $selectedCar->latestService();
                            @endphp
                            @if($latestService)
                                <div class="alert alert-info">
                                    Latest service: {{ $latestService->service_date->format('d M, Y') }}.
                                    Next service due: {{ $latestService->service_date->copy()->addMonths(3)->format('d M, Y') }}.
                                </div>
                            @endif

                            <form method="post" action="{{ route('car-services.store') }}" enctype="multipart/form-data" class="mb-4">
                                @csrf
                                <input type="hidden" name="car_id" value="{{ $selectedCar->id }}">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="service_date">Service date <span class="text-danger">*</span></label>
                                            <input type="date" name="service_date" id="service_date" class="form-control @error('service_date') is-invalid @enderror" value="{{ old('service_date') }}" required>
                                            @error('service_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="service_mileage">Mileage</label>
                                            <input type="number" name="service_mileage" id="service_mileage" class="form-control @error('service_mileage') is-invalid @enderror" value="{{ old('service_mileage') }}" min="0">
                                            @error('service_mileage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="service_document">Service document</label>
                                            <input type="file" name="service_document" id="service_document" class="form-control @error('service_document') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png">
                                            @error('service_document')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label for="service_notes">Service notes</label>
                                            <textarea name="service_notes" id="service_notes" rows="2" class="form-control @error('service_notes') is-invalid @enderror">{{ old('service_notes') }}</textarea>
                                            @error('service_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success">
                                    <i class="fa fa-save"></i> Save service
                                </button>
                            </form>

                            @if($services->isNotEmpty())
                                <h5 class="mb-2">Service history</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm">
                                        <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Mileage</th>
                                            <th>Notes</th>
                                            <th>Document</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($services as $index => $service)
                                            <tr class="service-history-row {{ $index >= $initialHistoryLimit ? 'd-none service-history-extra' : '' }}">
                                                <td>{{ $service->service_date->format('d M, Y') }}</td>
                                                <td>{{ $service->mileage !== null ? number_format($service->mileage) : '—' }}</td>
                                                <td style="white-space: pre-wrap; max-width: 280px;">{{ $service->notes ?: '—' }}</td>
                                                <td>
                                                    @if($service->document)
                                                        <a href="{{ asset('uploads/cars/service_documents/'.$service->document) }}" target="_blank" rel="noopener" class="mr-50">View</a>
                                                        <button type="button"
                                                                class="btn btn-link btn-sm text-danger p-0 car-doc-remove-btn"
                                                                data-remove-url="{{ route('car-services.document.destroy', $service) }}"
                                                                data-doc-label="Service document">
                                                            <i class="fa fa-times-circle mr-25"></i>Remove
                                                        </button>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @if($services->count() > $initialHistoryLimit)
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="service-history-show-more">
                                        Show more
                                    </button>
                                @endif
                            @else
                                <p class="text-muted mb-0">No service history yet for this car.</p>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if($selectedCar)
        @include('components.fleetiq-delete-confirm-modal')
    @endif
@endsection

@section('js')
    @unless($selectedCar)
        <script src="{{ asset('app-assets/vendors/js/forms/select/select2.full.min.js') }}"></script>
        <script>
            $(function () {
                $('#car_id').select2({
                    width: '100%',
                    placeholder: 'Search or select car',
                });
            });
        </script>
    @endunless
    @if($selectedCar && $services->count() > $initialHistoryLimit)
        <script>
            document.getElementById('service-history-show-more').addEventListener('click', function () {
                document.querySelectorAll('.service-history-extra').forEach(function (row) {
                    row.classList.remove('d-none');
                });
                this.style.display = 'none';
            });
        </script>
    @endif
    @if($selectedCar)
        <script>
            (function () {
                var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                var pending = { url: null };
                var $modal = window.jQuery;
                var confirmBtn = document.getElementById('fleetiqDeleteConfirmBtn');
                var titleEl = document.getElementById('fleetiqDeleteConfirmTitle');
                var bodyEl = document.getElementById('fleetiqDeleteConfirmBody');
                var btnTextEl = document.getElementById('fleetiqDeleteConfirmBtnText');

                document.addEventListener('click', function (e) {
                    var btn = e.target.closest('.car-doc-remove-btn');
                    if (!btn) return;
                    e.preventDefault();
                    pending.url = btn.getAttribute('data-remove-url');
                    var label = btn.getAttribute('data-doc-label') || 'document';
                    if (titleEl) titleEl.textContent = 'Remove document?';
                    if (bodyEl) bodyEl.textContent = 'Are you sure you want to remove this ' + label + '? The file will be deleted. You can upload a new file afterwards.';
                    if (btnTextEl) btnTextEl.textContent = 'Yes, remove document';
                    if ($modal && $modal.fn && $modal.fn.modal) $modal('#fleetiqDeleteConfirmModal').modal('show');
                });

                if (confirmBtn) {
                    confirmBtn.addEventListener('click', function () {
                        if (!pending.url) return;
                        confirmBtn.disabled = true;
                        fetch(pending.url, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        }).then(function (r) { if (!r.ok) throw new Error(); return r.json(); })
                          .then(function () {
                              if ($modal && $modal.fn && $modal.fn.modal) $modal('#fleetiqDeleteConfirmModal').modal('hide');
                              window.location.reload();
                          })
                          .catch(function () { alert('Could not remove this document. Please try again.'); })
                          .finally(function () { confirmBtn.disabled = false; });
                    });
                }
            })();
        </script>
    @endif
@endsection
