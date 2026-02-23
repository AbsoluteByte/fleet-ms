{{-- resources/views/backend/dashboard/notifications.blade.php --}}

@extends('layouts.admin', ['title' => 'Fleet Notifications'])

@section('content')

    {{-- Page Header --}}
    <div class="content-header row">
        <div class="content-header-left col-md-9 col-12 mb-2">
            <div class="row breadcrumbs-top">
                <div class="col-12">
                    <h2 class="content-header-title float-left mb-0">
                        <i class="feather icon-bell mr-1"></i>
                        Fleet Notifications
                    </h2>
                    <div class="breadcrumb-wrapper col-12">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Fleet Notifications</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <div class="content-header-right text-md-right col-md-3 col-12 d-md-block d-none">
            <div class="form-group breadcrum-right">
                <button class="btn btn-primary btn-icon" onclick="refreshNotifications()">
                    <i class="feather icon-refresh-cw"></i>
                </button>
                <a href="{{ route('payments.notifications') }}" class="btn btn-outline-danger ml-50">
                    <i class="feather icon-credit-card"></i>
                    Payments
                </a>
            </div>
        </div>
    </div>

    {{-- Summary Cards - FLEET NOTIFICATIONS ONLY (NO PAYMENTS) --}}
    <div class="row">
        {{-- Expiring Insurance --}}
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card text-center cursor-pointer" onclick="filterNotifications('insurance_expiry')">
                <div class="card-content">
                    <div class="card-body py-1">
                        <div class="avatar bg-rgba-primary p-50 m-0 mb-1">
                            <div class="avatar-content">
                                <i class="feather icon-shield text-primary font-large-1"></i>
                            </div>
                        </div>
                        <h2 class="text-bold-700">{{ $summary['expiring_insurance'] }}</h2>
                        <p class="mb-0 font-small-3">Insurance</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Expiring PHV --}}
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card text-center cursor-pointer" onclick="filterNotifications('phv_expiry')">
                <div class="card-content">
                    <div class="card-body py-1">
                        <div class="avatar bg-rgba-secondary p-50 m-0 mb-1">
                            <div class="avatar-content">
                                <i class="feather icon-award text-secondary font-large-1"></i>
                            </div>
                        </div>
                        <h2 class="text-bold-700">{{ $summary['expiring_phv'] }}</h2>
                        <p class="mb-0 font-small-3">PHV License</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Expiring MOT --}}
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card text-center cursor-pointer" onclick="filterNotifications('mot_expiry')">
                <div class="card-content">
                    <div class="card-body py-1">
                        <div class="avatar bg-rgba-warning p-50 m-0 mb-1">
                            <div class="avatar-content">
                                <i class="feather icon-tool text-warning font-large-1"></i>
                            </div>
                        </div>
                        <h2 class="text-bold-700">{{ $summary['expiring_mot'] }}</h2>
                        <p class="mb-0 font-small-3">MOT</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Expiring Road Tax --}}
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card text-center cursor-pointer" onclick="filterNotifications('road_tax_expiry')">
                <div class="card-content">
                    <div class="card-body py-1">
                        <div class="avatar bg-rgba-success p-50 m-0 mb-1">
                            <div class="avatar-content">
                                <i class="feather icon-credit-card text-success font-large-1"></i>
                            </div>
                        </div>
                        <h2 class="text-bold-700">{{ $summary['expiring_road_tax'] }}</h2>
                        <p class="mb-0 font-small-3">Road Tax</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Driver Licenses --}}
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card text-center cursor-pointer" onclick="filterNotifications('driver_license_expiry')">
                <div class="card-content">
                    <div class="card-body py-1">
                        <div class="avatar bg-rgba-info p-50 m-0 mb-1">
                            <div class="avatar-content">
                                <i class="feather icon-user text-info font-large-1"></i>
                            </div>
                        </div>
                        <h2 class="text-bold-700">{{ $summary['expiring_driver_licenses'] }}</h2>
                        <p class="mb-0 font-small-3">Driver License</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- PHD Licenses --}}
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card text-center cursor-pointer" onclick="filterNotifications('phd_license_expiry')">
                <div class="card-content">
                    <div class="card-body py-1">
                        <div class="avatar bg-rgba-secondary p-50 m-0 mb-1">
                            <div class="avatar-content">
                                <i class="feather icon-user-check text-secondary font-large-1"></i>
                            </div>
                        </div>
                        <h2 class="text-bold-700">{{ $summary['expiring_phd_licenses'] }}</h2>
                        <p class="mb-0 font-small-3">PHD License</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Tabs - FLEET ONLY (NO PAYMENT TABS) --}}
    <div class="card">
        <div class="card-content">
            <div class="card-body p-1">
                <ul class="nav nav-pills nav-justified" id="notification-tabs">
                    <li class="nav-item">
                        <a class="nav-link active" href="javascript:void(0)" onclick="filterNotifications('')">
                            All Fleet
                            <span class="badge badge-pill badge-light ml-50">
                                {{ $summary['expiring_insurance'] + $summary['expiring_phv'] + $summary['expiring_mot'] + $summary['expiring_road_tax'] + $summary['expiring_driver_licenses'] + $summary['expiring_phd_licenses'] }}
                            </span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="javascript:void(0)" onclick="filterNotifications('insurance_expiry')">
                            Insurance
                            <span class="badge badge-pill badge-primary ml-50">{{ $summary['expiring_insurance'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="javascript:void(0)" onclick="filterNotifications('phv_expiry')">
                            PHV
                            <span class="badge badge-pill badge-secondary ml-50">{{ $summary['expiring_phv'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="javascript:void(0)" onclick="filterNotifications('mot_expiry')">
                            MOT
                            <span class="badge badge-pill badge-warning ml-50">{{ $summary['expiring_mot'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="javascript:void(0)" onclick="filterNotifications('road_tax_expiry')">
                            Road Tax
                            <span class="badge badge-pill badge-success ml-50">{{ $summary['expiring_road_tax'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="javascript:void(0)" onclick="filterNotifications('driver_license_expiry')">
                            Driver License
                            <span class="badge badge-pill badge-info ml-50">{{ $summary['expiring_driver_licenses'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="javascript:void(0)" onclick="filterNotifications('phd_license_expiry')">
                            PHD License
                            <span class="badge badge-pill badge-secondary ml-50">{{ $summary['expiring_phd_licenses'] }}</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Fleet Notifications DataTable --}}
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Fleet Notifications</h4>
        </div>
        <div class="card-content">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="notificationsTable" class="table table-hover-animation">
                        <thead>
                        <tr>
                            <th>TYPE</th>
                            <th>TITLE</th>
                            <th>MESSAGE</th>
                            <th>VEHICLE/DRIVER</th>
                            <th>EXPIRY STATUS</th>
                            <th>ACTIONS</th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/tables/datatable/datatables.min.css') }}">
    <style>
        .cursor-pointer {
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        .cursor-pointer:hover {
            transform: translateY(-5px);
        }
        .nav-pills .nav-link.active {
            background-color: #7367F0 !important;
        }
        .expired-row {
            background-color: rgba(234, 84, 85, 0.1) !important;
        }
        .expiring-soon-row {
            background-color: rgba(255, 159, 67, 0.1) !important;
        }
    </style>
@endsection

@section('js')
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.bootstrap4.min.js') }}"></script>
    <script>
        let notificationsTable;
        let currentFilter = '';

        $(document).ready(function() {
            initializeDataTable();
        });

        function initializeDataTable() {
            notificationsTable = $('#notificationsTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: '{{ route("notifications.index") }}',
                    data: function(d) {
                        d.type = currentFilter;
                    }
                },
                columns: [
                    {
                        data: 'type',
                        render: function(data, type, row) {
                            const iconMap = {
                                'insurance_expiry': 'icon-shield text-primary',
                                'phv_expiry': 'icon-award text-secondary',
                                'mot_expiry': 'icon-tool text-warning',
                                'road_tax_expiry': 'icon-credit-card text-success',
                                'driver_license_expiry': 'icon-user text-info',
                                'phd_license_expiry': 'icon-user-check text-secondary'
                            };

                            const icon = iconMap[data] || 'icon-bell text-secondary';
                            return `<i class="feather ${icon} font-medium-3"></i>`;
                        }
                    },
                    {
                        data: 'title',
                        render: function(data, type, row) {
                            let badge = '';
                            if (row.priority === 1) {
                                badge = '<span class="badge badge-danger ml-50">EXPIRED</span>';
                            } else if (row.priority === 2) {
                                badge = '<span class="badge badge-warning ml-50">TODAY</span>';
                            }
                            return `<span class="font-weight-bold text-${row.color}">${data}</span>${badge}`;
                        }
                    },
                    {
                        data: 'simple_message'
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            let html = '';
                            if (row.vehicle) {
                                html += `<span class="badge badge-light-secondary mr-50">
                            <i class="feather icon-truck"></i> ${row.vehicle}
                        </span>`;
                            }
                            if (row.driver) {
                                html += `<span class="badge badge-light-info">
                            <i class="feather icon-user"></i> ${row.driver}
                        </span>`;
                            }
                            return html || '-';
                        }
                    },
                    {
                        data: 'time_ago',
                        render: function(data, type, row) {
                            let colorClass = 'success';
                            if (row.priority === 1) colorClass = 'danger';
                            else if (row.priority === 2) colorClass = 'warning';

                            return `<span class="badge badge-light-${colorClass}">
                        <i class="feather icon-clock"></i> ${data}
                    </span>`;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        render: function(data, type, row) {
                            let html = '<div class="btn-group">';

                            if (row.action_url) {
                                html += `<a href="${row.action_url}" class="btn btn-sm btn-outline-primary" title="View Details">
                            <i class="feather icon-eye"></i>
                        </a>`;
                            }

                            html += '</div>';
                            return html;
                        }
                    }
                ],
                order: [[4, 'asc']], // Sort by time (expired first)
                pageLength: 25,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search fleet notifications...",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ fleet notifications"
                },
                dom: '<"row"<"col-sm-6"l><"col-sm-6"f>>' +
                    '<"row"<"col-sm-12"tr>>' +
                    '<"row"<"col-sm-5"i><"col-sm-7"p>>',
                rowCallback: function(row, data) {
                    // Add background color for expired items
                    if (data.priority === 1) {
                        $(row).addClass('expired-row');
                    } else if (data.priority === 2) {
                        $(row).addClass('expiring-soon-row');
                    }
                }
            });
        }

        function filterNotifications(type) {
            currentFilter = type;

            // Update active tab
            $('#notification-tabs .nav-link').removeClass('active');
            event.currentTarget.classList.add('active');

            // Reload table
            notificationsTable.ajax.reload();
        }

        function refreshNotifications() {
            notificationsTable.ajax.reload(null, false);
            toastr.success('Fleet notifications refreshed!', 'Success', {
                positionClass: 'toast-top-right'
            });
        }
    </script>
@endsection
