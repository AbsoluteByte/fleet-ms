@extends('layouts.admin', ['title' => 'Driver Details'])

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">Driver Details - {{ $driver->first_name }} {{ $driver->last_name }}</h3>
                        <div>
                            @if(!$driver->hasAcceptedInvitation())
                                @if($driver->is_invited)
                                    <form action="{{ route($url . 'resendInvitation', $driver->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-warning btn-sm">
                                            <i class="fa fa-paper-plane"></i> Resend Invitation
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route($url . 'invite', $driver->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-info btn-sm">
                                            <i class="fa fa-envelope"></i> Send Invitation
                                        </button>
                                    </form>
                                @endif
                            @endif
                            <a href="{{ route($url . 'edit', $driver->id) }}" class="btn btn-primary btn-sm">
                                <i class="fa fa-edit"></i> Edit
                            </a>
                            <a href="{{ route($url . 'index') }}" class="btn btn-secondary btn-sm">
                                <i class="fa fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>

                    <div class="card-body">
                        <!-- Invitation Status -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h4 class="border-bottom pb-2 mb-3">Invitation Status</h4>
                            </div>
                            <div class="col-md-4 mb-3">
                                <strong>Invitation Status:</strong>
                                <p class="mb-0">
                                    @if($driver->hasAcceptedInvitation())
                                        <span class="badge badge-success">Accepted</span>
                                    @elseif($driver->is_invited)
                                        <span class="badge badge-warning">Pending</span>
                                    @else
                                        <span class="badge badge-secondary">Not Invited</span>
                                    @endif
                                </p>
                            </div>
                            @if($driver->invited_at)
                                <div class="col-md-4 mb-3">
                                    <strong>Invited At:</strong>
                                    <p class="mb-0">{{ \Carbon\Carbon::parse($driver->invited_at)->format('d M, Y h:i A') }}</p>
                                </div>
                            @endif
                            @if($driver->invitation_accepted_at)
                                <div class="col-md-4 mb-3">
                                    <strong>Accepted At:</strong>
                                    <p class="mb-0">{{ \Carbon\Carbon::parse($driver->invitation_accepted_at)->format('d M, Y h:i A') }}</p>
                                </div>
                            @endif
                        </div>

                        <!-- Personal Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h4 class="border-bottom pb-2 mb-3">Personal Information</h4>
                            </div>
                            <div class="col-md-4 mb-3">
                                <strong>First Name:</strong>
                                <p class="mb-0">{{ $driver->first_name }}</p>
                            </div>
                            @if($driver->middle_name)
                                <div class="col-md-4 mb-3">
                                    <strong>Middle Name:</strong>
                                    <p class="mb-0">{{ $driver->middle_name }}</p>
                                </div>
                            @endif
                            <div class="col-md-4 mb-3">
                                <strong>Last Name:</strong>
                                <p class="mb-0">{{ $driver->last_name }}</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <strong>Date of Birth:</strong>
                                <p class="mb-0">{{ \Carbon\Carbon::parse($driver->dob)->format('d M, Y') }}</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <strong>Age:</strong>
                                <p class="mb-0">{{ \Carbon\Carbon::parse($driver->dob)->age }} years</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <strong>Email:</strong>
                                <p class="mb-0">
                                    <a href="mailto:{{ $driver->email }}">{{ $driver->email }}</a>
                                </p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <strong>Phone Number:</strong>
                                <p class="mb-0">
                                    <a href="tel:{{ $driver->phone_number }}">{{ $driver->phone_number }}</a>
                                </p>
                            </div>
                        </div>

                        <!-- Address Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h4 class="border-bottom pb-2 mb-3">Address Information</h4>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Address Line 1:</strong>
                                <p class="mb-0">{{ $driver->address1 }}</p>
                            </div>
                            @if($driver->address2)
                                <div class="col-md-6 mb-3">
                                    <strong>Address Line 2:</strong>
                                    <p class="mb-0">{{ $driver->address2 }}</p>
                                </div>
                            @endif
                            <div class="col-md-3 mb-3">
                                <strong>Post Code:</strong>
                                <p class="mb-0">{{ $driver->post_code }}</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <strong>Town:</strong>
                                <p class="mb-0">{{ $driver->town }}</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <strong>County:</strong>
                                <p class="mb-0">{{ $driver->county }}</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <strong>Country:</strong>
                                <p class="mb-0">{{ $driver->country->name ?? 'N/A' }}</p>
                            </div>
                        </div>

                        <!-- License Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h4 class="border-bottom pb-2 mb-3">License Information</h4>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Driver License Number:</strong>
                                <p class="mb-0">{{ $driver->driver_license_number }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Driver License Expiry Date:</strong>
                                <p class="mb-0">
                                    {{ \Carbon\Carbon::parse($driver->driver_license_expiry_date)->format('d M, Y') }}
                                    @if(\Carbon\Carbon::parse($driver->driver_license_expiry_date)->isPast())
                                        <span class="badge badge-danger ml-2">Expired</span>
                                    @elseif(\Carbon\Carbon::parse($driver->driver_license_expiry_date)->diffInDays(now()) <= 30)
                                        <span class="badge badge-warning ml-2">Expiring Soon</span>
                                    @else
                                        <span class="badge badge-success ml-2">Valid</span>
                                    @endif
                                </p>
                            </div>
                            @if($driver->phd_license_number)
                                <div class="col-md-6 mb-3">
                                    <strong>PHD License Number:</strong>
                                    <p class="mb-0">{{ $driver->phd_license_number }}</p>
                                </div>
                            @endif
                            @if($driver->phd_license_expiry_date)
                                <div class="col-md-6 mb-3">
                                    <strong>PHD License Expiry Date:</strong>
                                    <p class="mb-0">
                                        {{ \Carbon\Carbon::parse($driver->phd_license_expiry_date)->format('d M, Y') }}
                                        @if(\Carbon\Carbon::parse($driver->phd_license_expiry_date)->isPast())
                                            <span class="badge badge-danger ml-2">Expired</span>
                                        @elseif(\Carbon\Carbon::parse($driver->phd_license_expiry_date)->diffInDays(now()) <= 30)
                                            <span class="badge badge-warning ml-2">Expiring Soon</span>
                                        @else
                                            <span class="badge badge-success ml-2">Valid</span>
                                        @endif
                                    </p>
                                </div>
                            @endif
                        </div>

                        <!-- Emergency Contact -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h4 class="border-bottom pb-2 mb-3">Emergency Contact</h4>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Next of Kin:</strong>
                                <p class="mb-0">{{ $driver->next_of_kin }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Next of Kin Phone:</strong>
                                <p class="mb-0">
                                    <a href="tel:{{ $driver->next_of_kin_phone }}">{{ $driver->next_of_kin_phone }}</a>
                                </p>
                            </div>
                        </div>

                        <!-- Documents -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h4 class="border-bottom pb-2 mb-3">Documents</h4>
                            </div>
                            @if($driver->driver_license_document)
                                <div class="col-md-4 mb-3">
                                    <strong>Driver License Document:</strong>
                                    <p class="mb-0">
                                        <a href="{{ asset('uploads/driver_licenses/' . $driver->driver_license_document) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fa fa-file-pdf"></i> View Document
                                        </a>
                                    </p>
                                </div>
                            @endif
                            @if($driver->driver_phd_license_document)
                                <div class="col-md-4 mb-3">
                                    <strong>PHD License Document:</strong>
                                    <p class="mb-0">
                                        <a href="{{ asset('uploads/driver_licenses/' . $driver->driver_phd_license_document) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fa fa-file-pdf"></i> View Document
                                        </a>
                                    </p>
                                </div>
                            @endif
                            @if($driver->proof_of_address_document)
                                <div class="col-md-4 mb-3">
                                    <strong>Proof of Address:</strong>
                                    <p class="mb-0">
                                        <a href="{{ asset('uploads/driver_licenses/' . $driver->proof_of_address_document) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fa fa-file-pdf"></i> View Document
                                        </a>
                                    </p>
                                </div>
                            @endif
                            @if(!$driver->driver_license_document && !$driver->driver_phd_license_document && !$driver->proof_of_address_document)
                                <div class="col-12">
                                    <p class="text-muted">No documents uploaded</p>
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
                                <p class="mb-0">{{ $driver->created_at->format('d M, Y h:i A') }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Last Updated:</strong>
                                <p class="mb-0">{{ $driver->updated_at->format('d M, Y h:i A') }}</p>
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
