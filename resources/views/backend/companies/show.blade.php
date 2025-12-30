@extends('layouts.admin', ['title' => 'Company Details'])

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">Company Details - {{ $company->name }}</h3>
                        <div>
                            <a href="{{ route($url . 'edit', $company->id) }}" class="btn btn-primary btn-sm">
                                <i class="fa fa-edit"></i> Edit
                            </a>
                            <a href="{{ route($url . 'index') }}" class="btn btn-secondary btn-sm">
                                <i class="fa fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Company Logo -->
                            @if($company->logo)
                                <div class="col-md-12 text-center mb-4">
                                    <div class="company-logo-container">
                                        <img src="{{ asset('uploads/companies/' . $company->logo) }}"
                                             alt="{{ $company->name }} Logo"
                                             class="img-thumbnail"
                                             style="max-width: 200px; max-height: 200px;">
                                    </div>
                                </div>
                            @endif

                            <!-- Company Information -->
                            <div class="col-12">
                                <h4 class="border-bottom pb-2 mb-3">
                                    <i class="fa fa-building"></i> Company Information
                                </h4>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="info-item">
                                    <strong><i class="fa fa-tag text-primary"></i> Company Name:</strong>
                                    <p class="mb-0 ml-4">{{ $company->name }}</p>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="info-item">
                                    <strong><i class="fa fa-user text-primary"></i> Director Name:</strong>
                                    <p class="mb-0 ml-4">{{ $company->director_name }}</p>
                                </div>
                            </div>

                            <!-- Contact Information -->
                            <div class="col-12 mt-3">
                                <h4 class="border-bottom pb-2 mb-3">
                                    <i class="fa fa-address-book"></i> Contact Information
                                </h4>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="info-item">
                                    <strong><i class="fa fa-envelope text-primary"></i> Email:</strong>
                                    <p class="mb-0 ml-4">
                                        <a href="mailto:{{ $company->email }}">{{ $company->email }}</a>
                                    </p>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="info-item">
                                    <strong><i class="fa fa-phone text-primary"></i> Phone:</strong>
                                    <p class="mb-0 ml-4">
                                        <a href="tel:{{ $company->phone }}">{{ $company->phone }}</a>
                                    </p>
                                </div>
                            </div>

                            <!-- Address Information -->
                            <div class="col-12 mt-3">
                                <h4 class="border-bottom pb-2 mb-3">
                                    <i class="fa fa-map-marker-alt"></i> Address Information
                                </h4>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="info-item">
                                    <strong><i class="fa fa-home text-primary"></i> Address Line 1:</strong>
                                    <p class="mb-0 ml-4">{{ $company->address_line_1 }}</p>
                                </div>
                            </div>

                            @if($company->address_line_2)
                                <div class="col-md-6 mb-3">
                                    <div class="info-item">
                                        <strong><i class="fa fa-home text-primary"></i> Address Line 2:</strong>
                                        <p class="mb-0 ml-4">{{ $company->address_line_2 }}</p>
                                    </div>
                                </div>
                            @endif

                            <div class="col-md-6 mb-3">
                                <div class="info-item">
                                    <strong><i class="fa fa-map-pin text-primary"></i> Postcode:</strong>
                                    <p class="mb-0 ml-4">{{ $company->postcode }}</p>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="info-item">
                                    <strong><i class="fa fa-city text-primary"></i> Town:</strong>
                                    <p class="mb-0 ml-4">{{ $company->town }}</p>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="info-item">
                                    <strong><i class="fa fa-map text-primary"></i> County:</strong>
                                    <p class="mb-0 ml-4">{{ $company->county }}</p>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="info-item">
                                    <strong><i class="fa fa-flag text-primary"></i> Country:</strong>
                                    <p class="mb-0 ml-4">{{ $company->country->name ?? 'N/A' }}</p>
                                </div>
                            </div>

                            <!-- Complete Address Card -->
                            <div class="col-12 mt-3">
                                <div class="card bg-light">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fa fa-location-arrow"></i> Complete Address</h5>
                                    </div>
                                    <div class="card-body">
                                        <address class="mb-0">
                                            <strong>{{ $company->name }}</strong><br>
                                            {{ $company->address_line_1 }}<br>
                                            @if($company->address_line_2)
                                                {{ $company->address_line_2 }}<br>
                                            @endif
                                            {{ $company->town }}, {{ $company->county }}<br>
                                            {{ $company->postcode }}<br>
                                            {{ $company->country->name ?? 'N/A' }}<br><br>
                                            <i class="fa fa-phone"></i> {{ $company->phone }}<br>
                                            <i class="fa fa-envelope"></i> {{ $company->email }}
                                        </address>
                                    </div>
                                </div>
                            </div>

                            <!-- Timestamps -->
                            <div class="col-12 mt-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <small class="text-muted">
                                            <i class="fa fa-calendar-plus"></i>
                                            <strong>Created:</strong> {{ $company->created_at->format('d M, Y h:i A') }}
                                        </small>
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <small class="text-muted">
                                            <i class="fa fa-calendar-check"></i>
                                            <strong>Last Updated:</strong> {{ $company->updated_at->format('d M, Y h:i A') }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Footer with Actions -->
                    <div class="card-footer">
                        <div class="row">
                            <div class="col-md-6">
                                <a href="{{ route($url . 'edit', $company->id) }}" class="btn btn-primary">
                                    <i class="fa fa-edit"></i> Edit Company
                                </a>
                            </div>
                            <div class="col-md-6 text-right">
                                <form action="{{ route($url . 'destroy', $company->id) }}"
                                      method="POST"
                                      style="display: inline-block;"
                                      onsubmit="return confirm('Are you sure you want to delete this company?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fa fa-trash"></i> Delete Company
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css')
    <style>
        .info-item {
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .company-logo-container {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            display: inline-block;
        }
        address {
            line-height: 1.8;
        }
    </style>
@endpush
