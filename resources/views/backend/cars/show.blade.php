@extends('layouts.admin', ['title' => 'Car Details'])

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">Car Details - {{ $car->registration }}</h3>
                        <div>
                            <a href="{{ route($url . 'edit', $car->id) }}" class="btn btn-primary btn-sm">
                                <i class="fa fa-edit"></i> Edit
                            </a>
                            <a href="{{ route($url . 'index') }}" class="btn btn-secondary btn-sm">
                                <i class="fa fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>

                    <div class="card-body">
                        <!-- Basic Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h4 class="border-bottom pb-2 mb-3">Basic Information</h4>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Company:</strong>
                                <p class="mb-0">{{ $car->company->name ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Car Model:</strong>
                                <p class="mb-0">{{ $car->carModel->name ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Registration:</strong>
                                <p class="mb-0">{{ $car->registration }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Color:</strong>
                                <p class="mb-0">{{ $car->color }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>VIN:</strong>
                                <p class="mb-0">{{ $car->vin }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Manufacture Year:</strong>
                                <p class="mb-0">{{ $car->manufacture_year }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Registration Year:</strong>
                                <p class="mb-0">{{ $car->registration_year }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Purchase Date:</strong>
                                <p class="mb-0">{{ \Carbon\Carbon::parse($car->purchase_date)->format('d M, Y') }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Purchase Price:</strong>
                                <p class="mb-0">£{{ number_format($car->purchase_price, 2) }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Purchase Type:</strong>
                                <p class="mb-0">
                                <span class="badge badge-{{ $car->purchase_type == 'imported' ? 'info' : 'success' }}">
                                    {{ ucfirst($car->purchase_type) }}
                                </span>
                                </p>
                            </div>
                            @if($car->v5_document)
                                <div class="col-md-6 mb-3">
                                    <strong>V5 Document:</strong>
                                    <p class="mb-0">
                                        <a href="{{ asset('uploads/cars/' . $car->v5_document) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fa fa-file-pdf"></i> View Document
                                        </a>
                                    </p>
                                </div>
                            @endif
                        </div>

                        <!-- MOT Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h4 class="border-bottom pb-2 mb-3">MOT Information</h4>
                            </div>
                            @if($car->mots && $car->mots->count() > 0)
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead class="thead-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Expiry Date</th>
                                                <th>Amount</th>
                                                <th>Term</th>
                                                <th>Document</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($car->mots as $index => $mot)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($mot->expiry_date)->format('d M, Y') }}</td>
                                                    <td>£{{ number_format($mot->amount, 2) }}</td>
                                                    <td>{{ $mot->term }}</td>
                                                    <td>
                                                        @if($mot->document)
                                                            <a href="{{ asset('uploads/cars/mot_documents/' . $mot->document) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                <i class="fa fa-file"></i> View
                                                            </a>
                                                        @else
                                                            <span class="text-muted">No Document</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @else
                                <div class="col-12">
                                    <p class="text-muted">No MOT records available</p>
                                </div>
                            @endif
                        </div>

                        <!-- Road Tax Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h4 class="border-bottom pb-2 mb-3">Road Tax Information</h4>
                            </div>
                            @if($car->roadTaxes && $car->roadTaxes->count() > 0)
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead class="thead-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Start Date</th>
                                                <th>Term</th>
                                                <th>Amount</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($car->roadTaxes as $index => $roadTax)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($roadTax->start_date)->format('d M, Y') }}</td>
                                                    <td>{{ $roadTax->term }}</td>
                                                    <td>£{{ number_format($roadTax->amount, 2) }}</td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @else
                                <div class="col-12">
                                    <p class="text-muted">No Road Tax records available</p>
                                </div>
                            @endif
                        </div>

                        <!-- PHV License Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h4 class="border-bottom pb-2 mb-3">PHV License Information</h4>
                            </div>
                            @if($car->phvs && $car->phvs->count() > 0)
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead class="thead-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Counsel</th>
                                                <th>Start Date</th>
                                                <th>Expiry Date</th>
                                                <th>Amount</th>
                                                <th>Notify Before</th>
                                                <th>Document</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($car->phvs as $index => $phv)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $phv->counsel->name ?? 'N/A' }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($phv->start_date)->format('d M, Y') }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($phv->expiry_date)->format('d M, Y') }}</td>
                                                    <td>£{{ number_format($phv->amount, 2) }}</td>
                                                    <td>{{ $phv->notify_before_expiry }} days</td>
                                                    <td>
                                                        @if($phv->document)
                                                            <a href="{{ asset('uploads/cars/phv_documents/' . $phv->document) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                <i class="fa fa-file"></i> View
                                                            </a>
                                                        @else
                                                            <span class="text-muted">No Document</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @else
                                <div class="col-12">
                                    <p class="text-muted">No PHV records available</p>
                                </div>
                            @endif
                        </div>

                        <!-- Insurance Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h4 class="border-bottom pb-2 mb-3">Insurance Information</h4>
                            </div>
                            @if($car->insurances && $car->insurances->count() > 0)
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead class="thead-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Provider</th>
                                                <th>Start Date</th>
                                                <th>Expiry Date</th>
                                                <th>Notify Before</th>
                                                <th>Status</th>
                                                <th>Document</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($car->insurances as $index => $insurance)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $insurance->insuranceProvider->name ?? 'N/A' }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($insurance->start_date)->format('d M, Y') }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($insurance->expiry_date)->format('d M, Y') }}</td>
                                                    <td>{{ $insurance->notify_before_expiry }} days</td>
                                                    <td>
                                                    <span class="badge badge-{{ $insurance->status->name == 'Active' ? 'success' : 'warning' }}">
                                                        {{ $insurance->status->name ?? 'N/A' }}
                                                    </span>
                                                    </td>
                                                    <td>
                                                        @if($insurance->insurance_document)
                                                            <a href="{{ asset('uploads/cars/insurance_documents/' . $insurance->insurance_document) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                <i class="fa fa-file"></i> View
                                                            </a>
                                                        @else
                                                            <span class="text-muted">No Document</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @else
                                <div class="col-12">
                                    <p class="text-muted">No Insurance records available</p>
                                </div>
                            @endif
                        </div>

                        <!-- Timestamps -->
                        <div class="row">
                            <div class="col-12">
                                <h4 class="border-bottom pb-2 mb-3">Record Information</h4>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Created At:</strong>
                                <p class="mb-0">{{ $car->created_at->format('d M, Y h:i A') }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Last Updated:</strong>
                                <p class="mb-0">{{ $car->updated_at->format('d M, Y h:i A') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
    </script>
@endsection
