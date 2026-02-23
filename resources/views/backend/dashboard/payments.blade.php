@extends('layouts.admin', ['title' => 'Payment Notifications'])

@section('content')

    {{-- Page Header --}}
    <div class="content-header row">
        <div class="content-header-left col-md-9 col-12 mb-2">
            <div class="row breadcrumbs-top">
                <div class="col-12">
                    <h2 class="content-header-title float-left mb-0">
                        <i class="feather icon-credit-card mr-1"></i>
                        Payment Notifications
                    </h2>
                    <div class="breadcrumb-wrapper col-12">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Payments</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <div class="content-header-right text-md-right col-md-3 col-12 d-md-block d-none">
            <div class="form-group breadcrum-right">
                <a href="{{ route('notifications.index') }}" class="btn btn-outline-primary">
                    <i class="feather icon-bell mr-50"></i>
                    All Notifications
                </a>
            </div>
        </div>
    </div>

    {{-- Summary Cards - PAYMENT TYPES ONLY --}}
    <div class="row">
        {{-- Overdue Payments --}}
        <div class="col-lg-4 col-md-6 col-12">
            <div class="card text-center cursor-pointer" onclick="filterPayments('overdue_payment')">
                <div class="card-content">
                    <div class="card-body">
                        <div class="avatar bg-rgba-danger p-75 m-0 mb-1">
                            <div class="avatar-content">
                                <i class="feather icon-alert-triangle text-danger font-large-2"></i>
                            </div>
                        </div>
                        <h2 class="text-bold-700 text-danger">{{ $summary['overdue_payments'] }}</h2>
                        <p class="mb-0">Overdue Payments</p>
                        <p class="text-muted font-small-3 mb-0">Immediate action required</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Due Today --}}
        <div class="col-lg-4 col-md-6 col-12">
            <div class="card text-center cursor-pointer" onclick="filterPayments('due_today')">
                <div class="card-content">
                    <div class="card-body">
                        <div class="avatar bg-rgba-warning p-75 m-0 mb-1">
                            <div class="avatar-content">
                                <i class="feather icon-clock text-warning font-large-2"></i>
                            </div>
                        </div>
                        <h2 class="text-bold-700 text-warning">{{ $summary['due_today'] }}</h2>
                        <p class="mb-0">Due Today</p>
                        <p class="text-muted font-small-3 mb-0">Payment due today</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Due This Week --}}
        <div class="col-lg-4 col-md-6 col-12">
            <div class="card text-center cursor-pointer" onclick="filterPayments('due_this_week')">
                <div class="card-content">
                    <div class="card-body">
                        <div class="avatar bg-rgba-info p-75 m-0 mb-1">
                            <div class="avatar-content">
                                <i class="feather icon-calendar text-info font-large-2"></i>
                            </div>
                        </div>
                        <h2 class="text-bold-700 text-info">{{ $summary['due_this_week'] }}</h2>
                        <p class="mb-0">Due This Week</p>
                        <p class="text-muted font-small-3 mb-0">Upcoming payments</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Tabs --}}
    <div class="card">
        <div class="card-content">
            <div class="card-body p-1">
                <ul class="nav nav-pills nav-justified" id="payment-tabs">
                    <li class="nav-item">
                        <a class="nav-link active" href="javascript:void(0)" onclick="filterPayments('')">
                            All Payments
                            <span class="badge badge-pill badge-light ml-50">
                                {{ $summary['overdue_payments'] + $summary['due_today'] + $summary['due_this_week'] }}
                            </span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="javascript:void(0)" onclick="filterPayments('overdue_payment')">
                            Overdue
                            <span class="badge badge-pill badge-danger ml-50">{{ $summary['overdue_payments'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="javascript:void(0)" onclick="filterPayments('due_today')">
                            Due Today
                            <span class="badge badge-pill badge-warning ml-50">{{ $summary['due_today'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="javascript:void(0)" onclick="filterPayments('due_this_week')">
                            This Week
                            <span class="badge badge-pill badge-info ml-50">{{ $summary['due_this_week'] }}</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Payment Notifications Table --}}
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Payment Notifications</h4>
            <p class="text-muted mb-0">Priority sorted: Expired/Overdue first</p>
        </div>
        <div class="card-content">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="paymentsTable" class="table table-hover-animation">
                        <thead>
                        <tr>
                            <th>PRIORITY</th>
                            <th>DRIVER</th>
                            <th>VEHICLE</th>
                            <th>AMOUNT</th>
                            <th>DUE DATE</th>
                            <th>STATUS</th>
                            <th>ACTIONS</th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Payment Modal --}}
    <div class="modal fade" id="quickPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="quickPaymentForm" method="POST">
                    @csrf
                    <div class="modal-header bg-primary">
                        <h5 class="modal-title text-white">
                            <i class="feather icon-credit-card mr-50"></i>
                            Record Payment
                        </h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info" id="payment-summary"></div>

                        <div class="form-group">
                            <label>Payment Amount (£) <span class="text-danger">*</span></label>
                            <input type="number" name="amount_paid" id="quick_amount_paid"
                                   class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Payment Date <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" id="quick_payment_date"
                                   class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="form-group">
                            <label>Payment Method</label>
                            <select name="payment_method" class="form-control">
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cash">Cash</option>
                                <option value="card">Card Payment</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Notes (Optional)</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="feather icon-check mr-50"></i>Record Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('css')
    <link rel="stylesheet" type="text/css" href="{{ asset('app-assets/vendors/css/tables/datatable/datatables.min.css') }}">
    <style>
        .cursor-pointer {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .cursor-pointer:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 25px 0 rgba(0,0,0,.1);
        }
        .nav-pills .nav-link.active {
            background-color: #7367F0 !important;
        }
        .priority-badge-1,
        .priority-badge-2 {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
    </style>
@endsection

@section('js')
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.min.js') }}"></script>
    <script>
        let paymentsTable;
        let currentFilter = '';

        $(document).ready(function() {
            initializeDataTable();
        });

        function initializeDataTable() {
            paymentsTable = $('#paymentsTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: '{{ route("payments.notifications") }}',
                    data: function(d) {
                        d.type = currentFilter;
                    }
                },
                columns: [
                    {
                        data: 'priority',
                        render: function(data, type, row) {
                            const badges = {
                                1: '<span class="badge badge-danger priority-badge-1">CRITICAL</span>',
                                2: '<span class="badge badge-warning priority-badge-2">HIGH</span>',
                                3: '<span class="badge badge-info">MEDIUM</span>'
                            };
                            return badges[data] || badges[3];
                        }
                    },
                    {
                        data: 'driver_name'
                    },
                    {
                        data: 'vehicle',
                        render: function(data) {
                            return `<span class="badge badge-light-secondary">${data}</span>`;
                        }
                    },
                    {
                        data: 'amount',
                        render: function(data, type, row) {
                            return `<span class="font-weight-bold text-${row.color}">${data}</span>`;
                        }
                    },
                    {
                        data: 'due_date'
                    },
                    {
                        data: 'time_ago',
                        render: function(data, type, row) {
                            let color = 'success';
                            if (row.priority === 1) color = 'danger';
                            else if (row.priority === 2) color = 'warning';

                            return `<small class="text-${color}">${data}</small>`;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        render: function(data, type, row) {
                            return `
                        <div class="btn-group">
                            <a href="${row.action_url}" class="btn btn-sm btn-outline-primary">
                                <i class="feather icon-eye"></i>
                            </a>
                            <button class="btn btn-sm btn-${row.color}" onclick="openPaymentModal('${row.collection_id}', '${row.amount_raw}', '${row.driver_name}', '${row.vehicle}')">
                                <i class="feather icon-credit-card"></i> Pay
                            </button>
                        </div>
                    `;
                        }
                    }
                ],
                order: [[0, 'asc'], [4, 'asc']], // Priority first, then due date
                pageLength: 25,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search payments...",
                    lengthMenu: "Show _MENU_ payments",
                    info: "Showing _START_ to _END_ of _TOTAL_ payments"
                }
            });
        }

        function filterPayments(type) {
            currentFilter = type;

            // Update active tab
            $('#payment-tabs .nav-link').removeClass('active');
            event.currentTarget.classList.add('active');

            // Reload table
            paymentsTable.ajax.reload();
        }

        function openPaymentModal(collectionId, amount, driverName, vehicle) {
            const modal = $('#quickPaymentModal');
            const form = $('#quickPaymentForm');

            form.attr('action', `/admin/agreements/collections/${collectionId}/pay`);
            $('#quick_amount_paid').val(amount).attr('max', amount);

            $('#payment-summary').html(`
        <strong>Driver:</strong> ${driverName}<br>
        <strong>Vehicle:</strong> ${vehicle}<br>
        <strong>Amount Due:</strong> £${parseFloat(amount).toLocaleString()}
    `);

            modal.modal('show');
        }
    </script>
@endsection
