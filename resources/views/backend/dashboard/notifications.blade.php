{{-- resources/views/backend/dashboard/notifications.blade.php --}}
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
                    <button class="btn btn-outline-primary" onclick="refreshTable()">
                        <i class="feather icon-refresh-cw me-2"></i>
                        Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Summary Cards - ALL TYPES -->
    <div class="row g-3 mb-4">
        {{-- 1. Overdue Payments - RED --}}
        <div class="col-xl-3 col-lg-4 col-md-6 mb-1">
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

        {{-- 2. Due Today - ORANGE --}}
        <div class="col-xl-3 col-lg-4 col-md-6 mb-1">
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

        {{-- 3. Due This Week - BLUE --}}
        <div class="col-xl-3 col-lg-4 col-md-6 mb-1">
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

        {{-- 4. Expiring Insurance - TEAL --}}
        <div class="col-xl-3 col-lg-4 col-md-6 mb-1">
            <div class="summary-card teal" onclick="filterByType('insurance_expiry')">
                <div class="summary-icon">
                    <i class="feather icon-shield"></i>
                </div>
                <div class="summary-content">
                    <h3>{{ $summary['expiring_insurance'] }}</h3>
                    <p>Expiring Insurance</p>
                </div>
            </div>
        </div>

        {{-- 5. Expiring PHV - INDIGO --}}
        <div class="col-xl-3 col-lg-4 col-md-6 mb-1">
            <div class="summary-card indigo" onclick="filterByType('phv_expiry')">
                <div class="summary-icon">
                    <i class="feather icon-award"></i>
                </div>
                <div class="summary-content">
                    <h3>{{ $summary['expiring_phv'] }}</h3>
                    <p>Expiring PHV</p>
                </div>
            </div>
        </div>

        {{-- 6. Expiring MOT - YELLOW --}}
        <div class="col-xl-3 col-lg-4 col-md-6 mb-1">
            <div class="summary-card yellow" onclick="filterByType('mot_expiry')">
                <div class="summary-icon">
                    <i class="feather icon-tool"></i>
                </div>
                <div class="summary-content">
                    <h3>{{ $summary['expiring_mot'] }}</h3>
                    <p>Expiring MOT</p>
                </div>
            </div>
        </div>

        {{-- 7. Expiring Road Tax - GREEN --}}
        <div class="col-xl-3 col-lg-4 col-md-6 mb-1">
            <div class="summary-card success" onclick="filterByType('road_tax_expiry')">
                <div class="summary-icon">
                    <i class="feather icon-credit-card"></i>
                </div>
                <div class="summary-content">
                    <h3>{{ $summary['expiring_road_tax'] }}</h3>
                    <p>Expiring Road Tax</p>
                </div>
            </div>
        </div>

        {{-- 8. Driver Licenses - CYAN --}}
        <div class="col-xl-3 col-lg-4 col-md-6 mb-1">
            <div class="summary-card cyan" onclick="filterByType('driver_license_expiry')">
                <div class="summary-icon">
                    <i class="feather icon-user"></i>
                </div>
                <div class="summary-content">
                    <h3>{{ $summary['expiring_driver_licenses'] }}</h3>
                    <p>Driver Licenses</p>
                </div>
            </div>
        </div>

        {{-- 9. PHD Licenses - GRAY --}}
        <div class="col-xl-3 col-lg-4 col-md-6 mb-1">
            <div class="summary-card secondary" onclick="filterByType('phd_license_expiry')">
                <div class="summary-icon">
                    <i class="feather icon-user-check"></i>
                </div>
                <div class="summary-content">
                    <h3>{{ $summary['expiring_phd_licenses'] }}</h3>
                    <p>PHD Licenses</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Tabs - ALL TYPES -->
    <div class="filter-tabs-container mb-4">
        <ul class="nav nav-pills filter-tabs flex-wrap" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" href="javascript:void(0)" onclick="filterByType('')">
                    <i class="feather icon-list me-1"></i> All
                    <span class="badge bg-dark ms-1">{{ $summary['total_count'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="javascript:void(0)" onclick="filterByType('overdue_payment')">
                    <i class="feather icon-alert-triangle me-1"></i> Overdue
                    <span class="badge bg-danger ms-1">{{ $summary['overdue_payments'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="javascript:void(0)" onclick="filterByType('due_today')">
                    <i class="feather icon-clock me-1"></i> Due Today
                    <span class="badge bg-warning ms-1">{{ $summary['due_today'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="javascript:void(0)" onclick="filterByType('due_this_week')">
                    <i class="feather icon-calendar me-1"></i> This Week
                    <span class="badge bg-info ms-1">{{ $summary['due_this_week'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="javascript:void(0)" onclick="filterByType('insurance_expiry')">
                    <i class="feather icon-shield me-1"></i> Insurance
                    <span class="badge bg-primary ms-1">{{ $summary['expiring_insurance'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="javascript:void(0)" onclick="filterByType('phv_expiry')">
                    <i class="feather icon-award me-1"></i> PHV
                    <span class="badge bg-secondary ms-1">{{ $summary['expiring_phv'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="javascript:void(0)" onclick="filterByType('mot_expiry')">
                    <i class="feather icon-tool me-1"></i> MOT
                    <span class="badge bg-warning ms-1">{{ $summary['expiring_mot'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="javascript:void(0)" onclick="filterByType('road_tax_expiry')">
                    <i class="feather icon-credit-card me-1"></i> Road Tax
                    <span class="badge bg-success ms-1">{{ $summary['expiring_road_tax'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="javascript:void(0)" onclick="filterByType('driver_license_expiry')">
                    <i class="feather icon-user me-1"></i> Driver License
                    <span class="badge bg-info ms-1">{{ $summary['expiring_driver_licenses'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="javascript:void(0)" onclick="filterByType('phd_license_expiry')">
                    <i class="feather icon-user-check me-1"></i> PHD License
                    <span class="badge bg-secondary ms-1">{{ $summary['expiring_phd_licenses'] }}</span>
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
        /* ✅ Standard Bootstrap Colors - No Purple! */
        :root {
            --critical-color: #dc3545;    /* Bootstrap Red */
            --warning-color: #fd7e14;     /* Bootstrap Orange */
            --info-color: #0dcaf0;        /* Bootstrap Cyan */
            --primary-color: #0d6efd;     /* Bootstrap Blue */
            --secondary-color: #6c757d;   /* Bootstrap Gray */
            --success-color: #198754;     /* Bootstrap Green */
            --teal-color: #20c997;        /* Bootstrap Teal */
            --indigo-color: #6610f2;      /* Bootstrap Indigo */
            --yellow-color: #ffc107;      /* Bootstrap Yellow */
            --cyan-color: #0dcaf0;        /* Bootstrap Cyan */
        }

        body {
            background: #f8f9fa;
        }

        .notifications-header {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }

        .notifications-title {
            font-size: 2rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 0.5rem;
        }

        .notifications-subtitle {
            color: #6c757d;
            font-size: 1rem;
        }

        /* Summary Cards */
        .summary-card {
            background: white;
            border-radius: 10px;
            padding: 1.25rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
            border-left: 4px solid;
            cursor: pointer;
            height: 100%;
        }

        .summary-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }

        /* Standard Bootstrap Colors */
        .summary-card.critical { border-left-color: var(--critical-color); }
        .summary-card.warning { border-left-color: var(--warning-color); }
        .summary-card.info { border-left-color: var(--info-color); }
        .summary-card.primary { border-left-color: var(--primary-color); }
        .summary-card.secondary { border-left-color: var(--secondary-color); }
        .summary-card.success { border-left-color: var(--success-color); }
        .summary-card.teal { border-left-color: var(--teal-color); }
        .summary-card.indigo { border-left-color: var(--indigo-color); }
        .summary-card.yellow { border-left-color: var(--yellow-color); }
        .summary-card.cyan { border-left-color: var(--cyan-color); }

        .summary-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .critical .summary-icon {
            background: rgba(220, 53, 69, 0.1);
            color: var(--critical-color);
        }

        .warning .summary-icon {
            background: rgba(253, 126, 20, 0.1);
            color: var(--warning-color);
        }

        .info .summary-icon {
            background: rgba(13, 202, 240, 0.1);
            color: var(--info-color);
        }

        .primary .summary-icon {
            background: rgba(13, 110, 253, 0.1);
            color: var(--primary-color);
        }

        .secondary .summary-icon {
            background: rgba(108, 117, 125, 0.1);
            color: var(--secondary-color);
        }

        .success .summary-icon {
            background: rgba(25, 135, 84, 0.1);
            color: var(--success-color);
        }

        .teal .summary-icon {
            background: rgba(32, 201, 151, 0.1);
            color: var(--teal-color);
        }

        .indigo .summary-icon {
            background: rgba(102, 16, 242, 0.1);
            color: var(--indigo-color);
        }

        .yellow .summary-icon {
            background: rgba(255, 193, 7, 0.1);
            color: var(--yellow-color);
        }

        .cyan .summary-icon {
            background: rgba(13, 202, 240, 0.1);
            color: var(--cyan-color);
        }

        .summary-content h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: #212529;
        }

        .summary-content p {
            margin: 0;
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Filter Tabs */
        .filter-tabs-container {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .filter-tabs .nav-link {
            background: #f8f9fa;
            border: none;
            border-radius: 8px;
            color: #495057;
            font-weight: 500;
            padding: 0.6rem 1rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            margin: 0.25rem;
            white-space: nowrap;
        }

        .filter-tabs .nav-link:hover {
            background: #e9ecef;
            color: #212529;
        }

        .filter-tabs .nav-link.active {
            background: #0d6efd;
            color: white;
        }

        .filter-tabs .nav-link.active .badge {
            background: white !important;
            color: #0d6efd !important;
        }

        /* Notifications Card */
        .notifications-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        /* DataTables */
        #notificationsTable {
            font-size: 0.95rem;
        }

        #notificationsTable thead th {
            background: #212529;
            color: white;
            font-weight: 600;
            border: none;
            padding: 1rem;
        }

        #notificationsTable tbody tr {
            transition: all 0.2s ease;
        }

        #notificationsTable tbody tr:hover {
            background: #f8f9fa;
        }

        .notification-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-block;
        }

        .badge-critical { background: rgba(220, 53, 69, 0.1); color: #dc3545; }
        .badge-warning { background: rgba(253, 126, 20, 0.1); color: #fd7e14; }
        .badge-info { background: rgba(13, 202, 240, 0.1); color: #0dcaf0; }
        .badge-primary { background: rgba(13, 110, 253, 0.1); color: #0d6efd; }
        .badge-secondary { background: rgba(108, 117, 125, 0.1); color: #6c757d; }
        .badge-success { background: rgba(25, 135, 84, 0.1); color: #198754; }

        .action-btns {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            border-radius: 6px;
            font-size: 0.85rem;
        }

        .dataTables_wrapper .dataTables_filter input {
            border-radius: 6px;
            padding: 0.5rem 1rem;
            border: 1px solid #dee2e6;
        }

        .dataTables_wrapper .dataTables_length select {
            border-radius: 6px;
            padding: 0.5rem;
            border: 1px solid #dee2e6;
        }

        .page-item.active .page-link {
            background: #0d6efd;
            border-color: #0d6efd;
        }

        .page-link {
            border-radius: 6px;
            margin: 0 2px;
            color: #0d6efd;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .summary-card {
                margin-bottom: 0.5rem;
            }

            .filter-tabs {
                flex-direction: column;
            }

            .filter-tabs .nav-link {
                justify-content: space-between;
                margin: 0.25rem 0;
            }
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

                            // ✅ ALL notification types mapped
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
                                case 'phv_expiry':
                                    badgeClass = 'badge-secondary';
                                    icon = 'icon-award';
                                    break;
                                case 'mot_expiry':
                                    badgeClass = 'badge-warning';
                                    icon = 'icon-tool';
                                    break;
                                case 'road_tax_expiry':
                                    badgeClass = 'badge-success';
                                    icon = 'icon-credit-card';
                                    break;
                                case 'driver_license_expiry':
                                    badgeClass = 'badge-info';
                                    icon = 'icon-user';
                                    break;
                                case 'phd_license_expiry':
                                    badgeClass = 'badge-secondary';
                                    icon = 'icon-user-check';
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

                            // ✅ Show Pay button for ALL payment types
                            if (row.type.includes('payment')) {
                                const collectionId = row.id.split('_')[1];
                                const amount = row.amount ? row.amount.replace('£', '').replace(',', '') : '0';

                                html += `<button class="btn btn-sm btn-success" onclick="quickPayment('${collectionId}', '${amount}')">
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
                order: [[4, 'desc']], // Sort by time
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

            // Show success message
            toastr.success('Notifications refreshed!');
        }

        function dismissNotification(notificationId) {
            if (confirm('Dismiss this notification?')) {
                console.log('Dismissing:', notificationId);
                // TODO: Add AJAX call to mark as dismissed
                notificationsTable.ajax.reload();
                toastr.info('Notification dismissed');
            }
        }

        function quickPayment(collectionId, amount) {
            console.log('Quick payment for collection:', collectionId, 'Amount:', amount);
            // TODO: Open payment modal or redirect
            window.location.href = `/agreements/collections/${collectionId}/pay`;
        }
    </script>
@endsection
