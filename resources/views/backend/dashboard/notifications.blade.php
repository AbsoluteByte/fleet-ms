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
                    <button class="btn btn-primary" onclick="refreshNotifications()">
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
            <div class="summary-card critical">
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
            <div class="summary-card warning">
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
            <div class="summary-card info">
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
            <div class="summary-card primary">
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
            <div class="summary-card secondary">
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
            <div class="summary-card success">
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
                <a class="nav-link active" href="{{ route('notifications.index') }}">
                    All Notifications
                    <span class="badge">{{ $summary['total_count'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('notifications.index') }}?type=overdue_payment">
                    Overdue Payments
                    <span class="badge badge-danger">{{ $summary['overdue_payments'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('notifications.index') }}?type=due_today">
                    Due Today
                    <span class="badge badge-warning">{{ $summary['due_today'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('notifications.index') }}?type=insurance_expiry">
                    Insurance
                    <span class="badge badge-primary">{{ $summary['expiring_insurance'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('notifications.index') }}?type=mot_expiry">
                    MOT
                    <span class="badge badge-secondary">{{ $summary['expiring_mot'] }}</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Notifications List -->
    <div class="notifications-container">
        <div class="card notifications-card">
            <div class="card-body p-0">
                @if($notifications->count() > 0)
                    <div class="notifications-list">
                        @foreach($notifications as $notification)
                            <div class="notification-item {{ $notification['type'] }}"
                                 style="border-left-color: {{ $notification['border_color'] }};
                                        background-color: {{ $notification['bg_color'] }}">

                                <div class="notification-main">
                                    <div class="notification-icon-wrapper">
                                        <div class="notification-icon text-{{ $notification['color'] }}">
                                            <i class="feather {{ $notification['icon'] }}"></i>
                                        </div>
                                    </div>

                                    <div class="notification-content">
                                        <div class="notification-header">
                                            <h5 class="notification-title text-{{ $notification['color'] }}">
                                                {{ $notification['title'] }}
                                            </h5>
                                            <span class="notification-time">
                                                {{ $notification['time_ago'] }}
                                            </span>
                                        </div>

                                        <div class="notification-message">
                                            {{ $notification['message'] }}
                                        </div>

                                        <div class="notification-meta">
                                            @if(isset($notification['vehicle']))
                                                <span class="meta-item vehicle">
                                                    <i class="feather icon-truck"></i>
                                                    {{ $notification['vehicle'] }}
                                                </span>
                                            @endif

                                            @if(isset($notification['amount']))
                                                <span class="meta-item amount text-{{ $notification['color'] }}">
                                                    <i class="feather icon-pound-sign"></i>
                                                    {{ $notification['amount'] }}
                                                </span>
                                            @endif

                                            @if(isset($notification['driver']))
                                                <span class="meta-item driver">
                                                    <i class="feather icon-user"></i>
                                                    {{ $notification['driver'] }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="notification-actions">
                                        @if($notification['action_url'])
                                            <a href="{{ $notification['action_url'] }}"
                                               class="btn btn-sm btn-outline-{{ $notification['color'] }} action-btn">
                                                <i class="feather icon-external-link"></i>
                                                View Details
                                            </a>
                                        @endif

                                        @if(in_array($notification['type'], ['overdue_payment', 'due_today', 'due_this_week']))
                                            <button class="btn btn-sm btn-{{ $notification['color'] }} payment-btn"
                                                    onclick="quickPayment('{{ $notification['id'] }}')">
                                                <i class="feather icon-credit-card"></i>
                                                Pay Now
                                            </button>
                                        @endif

                                        <button class="btn btn-sm btn-outline-secondary dismiss-btn"
                                                onclick="dismissNotification('{{ $notification['id'] }}')">
                                            <i class="feather icon-x"></i>
                                            Dismiss
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="notifications-pagination">
                        <div class="d-flex justify-content-between align-items-center p-3">
                            <div class="pagination-info">
                                Showing {{ $notifications->count() }} of {{ $summary['total_count'] }} notifications
                            </div>
                            <div class="pagination-controls">
                                <button class="btn btn-outline-secondary btn-sm" disabled>
                                    <i class="feather icon-chevron-left"></i> Previous
                                </button>
                                <button class="btn btn-outline-secondary btn-sm">
                                    Next <i class="feather icon-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="empty-notifications">
                        <div class="empty-state">
                            <i class="feather icon-check-circle"></i>
                            <h3>All Caught Up!</h3>
                            <p>No notifications to display. Your fleet is running smoothly.</p>
                            <button class="btn btn-primary" onclick="refreshNotifications()">
                                <i class="feather icon-refresh-cw me-2"></i>
                                Check for Updates
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('css')
    <style>
        /* Notifications Page Styles */
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

        /* Header Styles */
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

        .notifications-subtitle {
            color: #6b7280;
            font-size: 1.1rem;
            margin-bottom: 0;
        }

        .notification-actions {
            display: flex;
            gap: 1rem;
        }

        /* Summary Cards */
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
        }

        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .summary-card.critical {
            border-left-color: var(--critical-color);
        }

        .summary-card.warning {
            border-left-color: var(--warning-color);
        }

        .summary-card.info {
            border-left-color: var(--info-color);
        }

        .summary-card.primary {
            border-left-color: var(--primary-color);
        }

        .summary-card.secondary {
            border-left-color: var(--secondary-color);
        }

        .summary-card.success {
            border-left-color: var(--success-color);
        }

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
            background: rgba(239, 68, 68, 0.1);
            color: var(--critical-color);
        }

        .warning .summary-icon {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .info .summary-icon {
            background: rgba(59, 130, 246, 0.1);
            color: var(--info-color);
        }

        .primary .summary-icon {
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary-color);
        }

        .secondary .summary-icon {
            background: rgba(107, 114, 128, 0.1);
            color: var(--secondary-color);
        }

        .success .summary-icon {
            background: rgba(34, 197, 94, 0.1);
            color: var(--success-color);
        }

        .summary-content h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .summary-content p {
            color: #6b7280;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 0;
        }

        /* Filter Tabs */
        .filter-tabs-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 15px;
            padding: 1rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .filter-tabs {
            border: none;
            gap: 0.5rem;
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
            transform: translateY(-2px);
        }

        .filter-tabs .badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.2);
        }

        /* Notifications List */
        .notifications-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            overflow: hidden;
        }

        .notification-item {
            padding: 2rem;
            border-left: 4px solid;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            position: relative;
        }

        .notification-item:hover {
            transform: translateX(10px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-main {
            display: flex;
            align-items: flex-start;
            gap: 1.5rem;
        }

        .notification-icon-wrapper {
            flex-shrink: 0;
        }

        .notification-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .notification-content {
            flex: 1;
        }

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .notification-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0;
        }

        .notification-time {
            color: #6b7280;
            font-size: 0.85rem;
            font-weight: 500;
            background: rgba(0,0,0,0.05);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
        }

        .notification-message {
            color: #4b5563;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .notification-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(0,0,0,0.05);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            color: #6b7280;
        }

        .meta-item.amount {
            font-weight: 600;
        }

        .notification-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .action-btn,
        .payment-btn,
        .dismiss-btn {
            border-radius: 10px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .action-btn:hover,
        .payment-btn:hover,
        .dismiss-btn:hover {
            transform: translateY(-2px);
        }

        /* Empty State */
        .empty-notifications {
            padding: 4rem 2rem;
            text-align: center;
        }

        .empty-state i {
            font-size: 4rem;
            color: #22c55e;
            margin-bottom: 2rem;
        }

        .empty-state h3 {
            font-size: 2rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: #6b7280;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        /* Pagination */
        .notifications-pagination {
            background: rgba(0,0,0,0.02);
            border-top: 1px solid rgba(0,0,0,0.05);
        }

        .pagination-info {
            color: #6b7280;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .pagination-controls {
            display: flex;
            gap: 0.5rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .notifications-title {
                font-size: 2rem;
            }

            .notification-actions {
                flex-direction: column;
            }

            .notification-header {
                flex-direction: column;
                gap: 1rem;
            }

            .notification-meta {
                flex-direction: column;
                gap: 0.5rem;
            }

            .summary-card {
                text-align: center;
                flex-direction: column;
            }

            .filter-tabs {
                flex-direction: column;
            }
        }

        /* Animation Classes */
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .slide-in-right {
            animation: slideInRight 0.6s ease-out;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
    </style>
@endsection

@section('js')
    <script>
        // Notifications Management Functions
        function refreshNotifications() {
            location.reload();
        }

        function markAllAsRead() {
            if (confirm('Mark all notifications as read?')) {
                // Implementation for marking all notifications as read
                console.log('Marking all notifications as read...');
                // You can add AJAX call here
            }
        }

        function dismissNotification(notificationId) {
            if (confirm('Dismiss this notification?')) {
                const notificationElement = document.querySelector(`[onclick*="${notificationId}"]`).closest('.notification-item');
                notificationElement.style.transition = 'all 0.3s ease';
                notificationElement.style.transform = 'translateX(100%)';
                notificationElement.style.opacity = '0';

                setTimeout(() => {
                    notificationElement.remove();
                }, 300);

                // You can add AJAX call here to update backend
                console.log('Dismissing notification:', notificationId);
            }
        }

        function quickPayment(notificationId) {
            // Implementation for quick payment
            console.log('Quick payment for:', notificationId);
            // You can add payment modal or redirect logic here
        }

        // Add animation classes on page load
        document.addEventListener('DOMContentLoaded', function() {
            const notifications = document.querySelectorAll('.notification-item');
            notifications.forEach((notification, index) => {
                notification.classList.add('fade-in');
                notification.style.animationDelay = `${index * 0.1}s`;
            });

            const summaryCards = document.querySelectorAll('.summary-card');
            summaryCards.forEach((card, index) => {
                card.classList.add('slide-in-right');
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });

        // Auto-refresh notifications every 5 minutes
        setInterval(() => {
            console.log('Auto-refreshing notifications...');
            // You can add AJAX call here to update notifications without page reload
        }, 300000);
    </script>
@endsection
