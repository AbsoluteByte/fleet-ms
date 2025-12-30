@extends('layouts.admin', ['title' => 'Fleet Notifications'])

@section('content')
    <!-- Notifications Header -->
    <div class="notifications-header mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="notifications-title">
                    <i class="feather icon-bell me-3"></i>
                    Fleet Notifications
                </h1>
                <p class="notifications-subtitle">Manage all your fleet notifications in one place</p>
            </div>
            <div class="col-auto">
                <div class="notification-actions">
                    <button class="btn btn-outline-primary" onclick="markAllAsRead()">
                        <i class="feather icon-check-circle me-2"></i>
                        Mark All Read
                    </button>
                    <button class="btn btn-primary" onclick="refreshTable()">
                        <i class="feather icon-refresh-cw me-2"></i>
                        Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Summary Cards -->
    <div class="row g-4 mb-4">
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="summary-card critical" onclick="filterByType('overdue_payment')">
                <div class="summary-icon">
                    <i class="feather icon-alert-triangle"></i>
                </div>
                <div class="summary-content">
                    <h3>{{ $summary['overdue_payments'] }}</h3>
                    <p>Overdue Payments</p>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="summary-card warning" onclick="filterByType('due_today')">
                <div class="summary-icon">
                    <i class="feather icon-clock"></i>
                </div>
                <div class="summary-content">
                    <h3>{{ $summary['due_today'] }}</h3>
                    <p>Due Today</p>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="summary-card info" onclick="filterByType('due_this_week')">
                <div class="summary-icon">
                    <i class="feather icon-calendar"></i>
                </div>
                <div class="summary-content">
                    <h3>{{ $summary['due_this_week'] }}</h3>
                    <p>Due This Week</p>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="summary-card primary" onclick="filterByType('insurance_expiry')">
                <div class="summary-icon">
                    <i class="feather icon-shield"></i>
                </div>
                <div class="summary-content">
                    <h3>{{ $summary['expiring_insurance'] }}</h3>
                    <p>Expiring Insurance</p>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="summary-card secondary" onclick="filterByType('mot_expiry')">
                <div class="summary-icon">
                    <i class="feather icon-tool"></i>
                </div>
                <div class="summary-content">
                    <h3>{{ $summary['expiring_mot'] }}</h3>
                    <p>Expiring MOT</p>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="summary-card success" onclick="filterByType('driver_license_expiry')">
                <div class="summary-icon">
                    <i class="feather icon-users"></i>
                </div>
                <div class="summary-content">
                    <h3>{{ $summary['expiring_driver_licenses'] }}</h3>
                    <p>Expiring Licenses</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs-container mb-4">
        <ul class="nav nav-pills filter-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" href="javascript:void(0)" onclick="filterByType('')">
                    All Notifications
                    <span class="badge">{{ $summary['total_count'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="javascript:void(0)" onclick="filterByType('overdue_payment')">
                    Overdue Payments
                    <span class="badge badge-danger">{{ $summary['overdue_payments'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="javascript:void(0)" onclick="filterByType('due_today')">
                    Due Today
                    <span class="badge badge-warning">{{ $summary['due_today'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="javascript:void(0)" onclick="filterByType('insurance_expiry')">
                    Insurance
                    <span class="badge badge-primary">{{ $summary['expiring_insurance'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="javascript:void(0)" onclick="filterByType('mot_expiry')">
                    MOT
                    <span class="badge badge-secondary">{{ $summary['expiring_mot'] }}</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Notifications DataTable -->
    <div class="notifications-container">
        <div class="card notifications-card">
            <div class="card-body">
                <table id="notificationsTable" class="table table-hover" style="width:100%">
                    <thead>
                    <tr>
                        <th>Type</th>
                        <th>Title</th>
                        <th>Message</th>
                        <th>Details</th>
                        <th>Time</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

    <style>
        /* Previous CSS remains same... */
        :root {
            --critical-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --primary-color: #6366f1;
            --secondary-color: #6b7280;
            --success-color: #22c55e;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }

        .notifications-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .notifications-title {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .summary-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
            border-left: 4px solid;
            cursor: pointer;
        }

        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .summary-card.critical { border-left-color: var(--critical-color); }
        .summary-card.warning { border-left-color: var(--warning-color); }
        .summary-card.info { border-left-color: var(--info-color); }
        .summary-card.primary { border-left-color: var(--primary-color); }
        .summary-card.secondary { border-left-color: var(--secondary-color); }
        .summary-card.success { border-left-color: var(--success-color); }

        .summary-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .critical .summary-icon {
            background: rgba(239, 68, 68, 0.1);
            color: var(--critical-color);
        }

        .warning .summary-icon {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .filter-tabs-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 1rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .filter-tabs .nav-link {
            background: rgba(0,0,0,0.05);
            border: none;
            border-radius: 10px;
            color: #6b7280;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-tabs .nav-link:hover,
        .filter-tabs .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .notifications-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        /* DataTables Custom Styling */
        #notificationsTable {
            font-size: 0.95rem;
        }

        #notificationsTable thead th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            border: none;
            padding: 1rem;
        }

        #notificationsTable tbody tr {
            transition: all 0.3s ease;
        }

        #notificationsTable tbody tr:hover {
            background: rgba(102, 126, 234, 0.05);
            transform: translateX(5px);
        }

        .notification-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-block;
        }

        .badge-critical { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .badge-warning { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .badge-info { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .badge-primary { background: rgba(99, 102, 241, 0.1); color: #6366f1; }

        .action-btns {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            border-radius: 8px;
            font-size: 0.85rem;
        }

        .dataTables_wrapper .dataTables_filter input {
            border-radius: 20px;
            padding: 0.5rem 1rem;
            border: 2px solid #e5e7eb;
        }

        .dataTables_wrapper .dataTables_length select {
            border-radius: 10px;
            padding: 0.5rem;
            border: 2px solid #e5e7eb;
        }

        .page-item.active .page-link {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }

        .page-link {
            border-radius: 8px;
            margin: 0 2px;
            border: 2px solid #e5e7eb;
            color: #6366f1;
        }
    </style>
@endsection

@section('js')
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

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
                            let badgeClass = '';
                            let icon = '';

                            switch(data) {
                                case 'overdue_payment':
                                    badgeClass = 'badge-critical';
                                    icon = 'icon-alert-triangle';
                                    break;
                                case 'due_today':
                                    badgeClass = 'badge-warning';
                                    icon = 'icon-clock';
                                    break;
                                case 'due_this_week':
                                    badgeClass = 'badge-info';
                                    icon = 'icon-calendar';
                                    break;
                                case 'insurance_expiry':
                                    badgeClass = 'badge-primary';
                                    icon = 'icon-shield';
                                    break;
                                default:
                                    badgeClass = 'badge-secondary';
                                    icon = 'icon-bell';
                            }

                            return `<span class="notification-badge ${badgeClass}">
                                <i class="feather ${icon}"></i>
                            </span>`;
                        }
                    },
                    {
                        data: 'title',
                        render: function(data, type, row) {
                            return `<strong style="color: ${row.border_color}">${data}</strong>`;
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
                                html += `<span class="badge bg-secondary me-1"><i class="feather icon-truck"></i> ${row.vehicle}</span>`;
                            }
                            if (row.amount) {
                                html += `<span class="badge bg-success"><i class="feather icon-pound-sign"></i> ${row.amount}</span>`;
                            }
                            if (row.driver) {
                                html += `<span class="badge bg-info"><i class="feather icon-user"></i> ${row.driver}</span>`;
                            }
                            return html || '-';
                        }
                    },
                    {
                        data: 'time_ago',
                        render: function(data) {
                            return `<small class="text-muted">${data}</small>`;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        render: function(data, type, row) {
                            let html = '<div class="action-btns">';

                            if (row.action_url) {
                                html += `<a href="${row.action_url}" class="btn btn-sm btn-outline-primary">
                                    <i class="feather icon-eye"></i>
                                </a>`;
                            }

                            if (row.type.includes('payment')) {
                                html += `<button class="btn btn-sm btn-success" onclick="quickPayment('${row.id}')">
                                    <i class="feather icon-credit-card"></i>
                                </button>`;
                            }

                            html += `<button class="btn btn-sm btn-outline-danger" onclick="dismissNotification('${row.id}')">
                                <i class="feather icon-x"></i>
                            </button>`;

                            html += '</div>';
                            return html;
                        }
                    }
                ],
                order: [[0, 'asc']],
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                    '<"row"<"col-sm-12"tr>>' +
                    '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search notifications...",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ notifications",
                    emptyTable: "No notifications found",
                    zeroRecords: "No matching notifications found"
                },
                responsive: true
            });
        }

        function filterByType(type) {
            currentFilter = type;

            // Update active tab
            $('.filter-tabs .nav-link').removeClass('active');
            event.currentTarget.classList.add('active');

            // Reload table
            notificationsTable.ajax.reload();
        }

        function refreshTable() {
            notificationsTable.ajax.reload(null, false);
        }

        function markAllAsRead() {
            if (confirm('Mark all notifications as read?')) {
                console.log('Marking all as read...');
                // Add AJAX call here
            }
        }

        function dismissNotification(notificationId) {
            if (confirm('Dismiss this notification?')) {
                console.log('Dismissing:', notificationId);
                // Add AJAX call here
                notificationsTable.ajax.reload();
            }
        }

        function quickPayment(notificationId) {
            console.log('Quick payment for:', notificationId);
            // Add payment logic here
        }
    </script>
@endsection
