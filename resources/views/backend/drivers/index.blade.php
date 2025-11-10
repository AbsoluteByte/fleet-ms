@extends('layouts.admin', ['title' => 'Drivers'])
@section('content')
    <section id="basic-datatable">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ $plural }}</h4>
                        <a class="btn btn-primary float-right" href="{{ route($url . 'create') }}"><i
                                class="fa fa-plus"></i>
                            Add {{ $singular }}</a>
                    </div>
                    <hr>
                    <div class="card-content">
                        <div class="card-body card-dashboard">
                            @include('alerts')
                            <div class="table-responsive">
                                <table id="dataTable" class="table datatable table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>License Expiry</th>
                                        <th>Invitation Status</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($drivers as $driver)
                                        <tr>
                                            <td style="width: 500px !important;">
                                                <strong>{{ $driver->full_name }}</strong>
                                                <br>
                                                <span>Post Code: {{ $driver->post_code }}</span>
                                                <br>
                                                <small class="text-muted">DOB: {{ $driver->dob->format('M d, Y') }}</small>
                                            </td>
                                            <td>{{ $driver->email }}</td>
                                            <td>{{ $driver->phone_number }}</td>
                                            <td>
                                                @if($driver->driver_license_expiry_date->isPast())
                                                    <span class="badge bg-danger">
                                                    Expired {{ $driver->driver_license_expiry_date->format('M d, Y') }}
                                                    </span>
                                                @elseif($driver->driver_license_expiry_date->diffInDays(now()) <= 30)
                                                    <span class="badge bg-warning">
                                                     Expires {{ $driver->driver_license_expiry_date->format('M d, Y') }}
                                                     </span>
                                                @else
                                                    <span class="badge bg-success">
                                                        {{ $driver->driver_license_expiry_date->format('M d, Y') }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($driver->hasAcceptedInvitation())
                                                    <span class="badge bg-success">
                                        <i class="feather icon-check me-1"></i>
                                        Accepted
                                    </span>
                                                @elseif($driver->is_invited)
                                                    @if($driver->isInvitationExpired())
                                                        <span class="badge bg-danger">
                                            <i class="feather icon-x me-1"></i>
                                            Expired
                                        </span>
                                                    @else
                                                        <span class="badge bg-warning">
                                            <i class="feather icon-clock me-1"></i>
                                            Pending
                                        </span>
                                                    @endif
                                                @else
                                                    <span class="badge bg-secondary">
                                        <i class="feather icon-mail me-1"></i>
                                        Not Invited
                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('drivers.show', $driver) }}"
                                                       class="btn btn-sm btn-outline-info">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('drivers.edit', $driver) }}"
                                                       class="btn btn-sm btn-outline-warning">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    @if($driver->canBeInvited())
                                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                                onclick="inviteDriver({{ $driver->id }}, '{{ $driver->full_name }}')">
                                                            <i class="feather icon-send"></i>
                                                            {{ $driver->is_invited ? 'Resend' : 'Invite' }}
                                                        </button>
                                                    @elseif($driver->is_invited && !$driver->hasAcceptedInvitation())
                                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                                                onclick="resendInvitation({{ $driver->id }}, '{{ $driver->full_name }}')">
                                                            <i class="feather icon-refresh-cw"></i>
                                                            Resend
                                                        </button>
                                                    @endif
                                                    <form action="{{ route('drivers.destroy', $driver) }}" method="POST"
                                                          style="display: inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                                onclick="return confirm('Are you sure?')">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="fa fa-users fa-3x mb-3"></i>
                                                <br>
                                                No drivers found. <a href="{{ route('drivers.create') }}">Add your first
                                                    driver</a>
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
    <!-- Invitation Modal -->
    <div class="modal fade" id="invitationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Invite Driver</h5>
                    <button type="button" class="btn-close" data-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to send an invitation to <strong id="driverName"></strong>?</p>
                    <p class="text-muted">The driver will receive an email with instructions to set up their account and
                        access the driver portal.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <form id="invitationForm" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            <i class="feather icon-send me-1"></i>
                            Send Invitation
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('css')
    <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/tables/datatable/datatables.min.css') }}">
@endsection
@section('js')
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.bootstrap4.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            $('#dataTable').DataTable({
                processing: true,
                responsive: true,
            });
        });

        function inviteDriver(driverId, driverName) {
            document.getElementById('driverName').textContent = driverName;
            document.getElementById('invitationForm').action = `drivers/${driverId}/invite`;

            new bootstrap.Modal(document.getElementById('invitationModal')).show();
        }

        function resendInvitation(driverId, driverName) {
            document.getElementById('driverName').textContent = driverName;
            document.getElementById('invitationForm').action = `drivers/${driverId}/resend-invitation`;

            new bootstrap.Modal(document.getElementById('invitationModal')).show();
        }
    </script>
@endsection
