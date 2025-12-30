<nav class="header-navbar navbar-expand-lg navbar navbar-with-menu floating-nav navbar-light navbar-shadow">
    <div class="navbar-wrapper">
        <div class="navbar-container content">
            <div class="navbar-collapse" id="navbar-mobile">
                <div class="mr-auto float-left bookmark-wrapper d-flex align-items-center">
                    <ul class="nav navbar-nav">
                        <li class="nav-item mobile-menu d-xl-none mr-auto"><a
                                class="nav-link nav-menu-main menu-toggle hidden-xs" href="#"><i
                                    class="ficon feather icon-menu"></i></a></li>
                    </ul>
                    <ul class="nav navbar-nav bookmark-icons">
                    </ul>
                    <ul class="nav navbar-nav">
                    </ul>
                </div>
                <ul class="nav navbar-nav float-right">
                    <!-- Enhanced Fleet Notification System -->
                    @if(auth()->user()->isAdmin() || auth()->user()->isUser())

                    <li class="dropdown dropdown-notification nav-item">
                        <a class="nav-link nav-link-label" href="#" data-toggle="dropdown" id="notificationBell">
                            <i class="ficon feather icon-bell"></i>
                            <span class="badge badge-pill badge-primary badge-up" id="notificationBadge" style="display: none;">0</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-media dropdown-menu-right" style="min-width: 350px;">
                            <li class="dropdown-menu-header">
                                <div class="dropdown-header m-0 p-2">
                                    <h3 class="white" id="notificationCount">0 New</h3>
                                    <span class="notification-title">Fleet Notifications</span>
                                </div>
                            </li>
                            <li class="scrollable-container media-list" id="notificationsList">
                                <!-- Dynamic notifications will be loaded here -->
                                <div class="text-center p-3" id="loadingNotifications">
                                    <i class="feather icon-loader spinning"></i>
                                    <p class="mt-2 mb-0 text-muted">Loading notifications...</p>
                                </div>
                            </li>
                            <li class="dropdown-menu-footer">
                                <a class="dropdown-item p-1 text-center" href="{{ route('dashboard') }}">
                                    View Dashboard
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endif
                    <!-- User Dropdown (unchanged) -->
                    <li class="dropdown dropdown-user nav-item">
                        <a class="dropdown-toggle nav-link dropdown-user-link"
                           href="#" data-toggle="dropdown">
                            <div class="user-nav d-sm-flex">
                                <span class="user-name text-bold-600 header-text-align">{{ ucwords(auth()->user()->name) }}</span>
                            </div>
                            <span>
                                @if (auth()->user()->profile_image)
                                    <img class="round" src="{{ asset('uploads/users/' . auth()->user()->profile_image) }}"
                                         alt="{{ auth()->user()->profile_image }}" height="40" width="40">
                                @else
                                    <img class="round" src="{{ asset('uploads/users/default.png') }}"
                                         alt="avatar" height="40" width="40">
                                @endif
                            </span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="{{ route('profile') }}">
                                <i class="feather icon-user"></i> Setting
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="{{ url('logout') }}">
                                <i class="feather icon-power"></i> Logout
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<!-- Fleet Notification Styles -->

<script>
    // Fleet Management Notification System
    class FleetNotificationManager {
        constructor() {
            this.notificationBadge = document.getElementById('notificationBadge');
            this.notificationCount = document.getElementById('notificationCount');
            this.notificationsList = document.getElementById('notificationsList');
            this.lastUpdateTime = null;

            // Initialize
            this.init();
        }

        init() {
            // Load notifications on page load
            this.updateHeaderNotifications();

            // Set up periodic updates (every 2 minutes)
            setInterval(() => {
                this.updateHeaderNotifications();
            }, 120000);

            // Add click handlers
            this.setupEventListeners();
        }

        async updateHeaderNotifications() {
            try {
                const response = await fetch('dashboard/fleet-notifications');

                if (!response.ok) {
                    throw new Error('Failed to fetch notifications');
                }

                const data = await response.json();
                this.renderNotifications(data);

            } catch (error) {
                console.error('Error updating notifications:', error);
                this.showErrorState();
            }
        }

        renderNotifications(data) {
            // Calculate total count properly from the summary object
            let totalCount = 0;

            if (data.summary) {
                // Sum all individual notification type counts
                totalCount = (data.summary.overdue_payments || 0) +
                    (data.summary.due_today || 0) +
                    (data.summary.due_this_week || 0) +
                    (data.summary.expiring_insurance || 0) +
                    (data.summary.expiring_phv || 0) +
                    (data.summary.expiring_mot || 0) +
                    (data.summary.expiring_road_tax || 0) +
                    (data.summary.expiring_driver_licenses || 0) +
                    (data.summary.expiring_phd_licenses || 0);
            }

            // Alternative: If notifications array is available, use its length
            if (!totalCount && data.notifications && Array.isArray(data.notifications)) {
                totalCount = data.notifications.length;
            }

            // Update badge
            if (totalCount > 0) {
                this.notificationBadge.textContent = totalCount;
                this.notificationBadge.style.display = 'block';
                this.notificationCount.textContent = `${totalCount} New`;
            } else {
                this.notificationBadge.style.display = 'none';
                this.notificationCount.textContent = '0 New';
            }

            // Render notification list
            this.renderNotificationList(data.notifications || []);
        }

        renderNotificationList(notifications) {
            if (!notifications.length) {
                this.notificationsList.innerHTML = `
                <div class="empty-notifications">
                    <i class="feather icon-check-circle"></i>
                    <p class="mb-0">All caught up!</p>
                    <small>No pending notifications</small>
                </div>
            `;
                return;
            }

            let notificationHtml = '';

            // Show top 15 notifications in header dropdown
            const headerNotifications = notifications.slice(0, 15);

            headerNotifications.forEach(notification => {
                const iconClass = notification.icon || 'icon-bell';
                const colorClass = notification.color || 'primary';
                const itemClass = this.getNotificationItemClass(notification.type);

                notificationHtml += `
                <a class="d-flex justify-content-between notification-item-fleet ${itemClass}"
                   href="${notification.action_url || 'javascript:void(0)'}"
                   onclick="this.style.opacity='0.6'"
                   style="border-left-color: ${notification.border_color}; background-color: ${notification.bg_color}">
                    <div class="media d-flex align-items-start w-100">
                        <div class="media-left mr-3">
                            <i class="feather ${iconClass} font-medium-5 text-${colorClass}"></i>
                        </div>
                        <div class="media-body flex-grow-1">
                            <h6 class="text-${colorClass} media-heading mb-1">${notification.title}</h6>
                            <small class="notification-text">${notification.simple_message}</small>
                            ${notification.vehicle ? `<div class="notification-vehicle mt-1">${notification.vehicle}</div>` : ''}
                            ${notification.amount ? `<div class="notification-amount mt-1">${notification.amount}</div>` : ''}
                            <small class="d-block mt-1">
                                <time class="media-meta text-muted">${notification.time_ago}</time>
                            </small>
                        </div>
                        ${this.getNotificationActions(notification)}
                    </div>
                </a>
            `;
            });

            // Add "View All" link if there are more notifications
            if (notifications.length > 15) {
                notificationHtml += `
                <div class="text-center p-3 border-top">
                    <a href="/notifications" class="btn btn-sm btn-outline-primary">
                        <i class="feather icon-bell me-1"></i>
                        View All ${notifications.length} Notifications
                    </a>
                </div>
                `;
            }

            this.notificationsList.innerHTML = notificationHtml;
        }

        getNotificationActions(notification) {
            let actions = '';

            // Add quick pay button for payment notifications
            if (['overdue_payment', 'due_today', 'due_this_week'].includes(notification.type)) {
                const collectionId = notification.id.split('_')[1];
                const amount = notification.amount ? notification.amount.replace('£', '').replace(',', '') : '0';

                actions = `
                    <div class="notification-actions mt-2">
                        <button class="btn btn-sm btn-${notification.color} notification-action-btn"
                                onclick="event.preventDefault(); event.stopPropagation(); quickPayFromNotification('${collectionId}', '${amount}')">
                            <i class="feather icon-credit-card"></i>
                            Pay Now
                        </button>
                    </div>
                `;
            }

            return actions;
        }

        getNotificationItemClass(type) {
            const classes = {
                'overdue_payment': 'overdue',
                'due_today': 'due-soon',
                'due_this_week': 'due-soon',
                'insurance_expiry': 'insurance-expiry',
                'payment_received': 'payment-received',
                'phv_expiry': 'insurance-expiry',
                'mot_expiry': 'due-soon',
                'road_tax_expiry': 'payment-received',
                'driver_license_expiry': 'insurance-expiry',
                'phd_license_expiry': 'insurance-expiry',
                'default': ''
            };
            return classes[type] || classes.default;
        }

        setupEventListeners() {
            // Bell click handler for manual refresh
            const bellElement = document.getElementById('notificationBell');
            if (bellElement) {
                bellElement.addEventListener('click', (e) => {
                    // Small delay to allow dropdown to show
                    setTimeout(() => {
                        this.updateHeaderNotifications();
                    }, 100);
                });
            }
        }

        showErrorState() {
            this.notificationsList.innerHTML = `
            <div class="text-center p-3">
                <i class="feather icon-wifi-off text-muted"></i>
                <p class="mt-2 mb-0 text-muted">Connection error</p>
                <small>Unable to load notifications</small>
            </div>
        `;
        }

        // Method to manually trigger notification refresh (can be called from anywhere)
        refresh() {
            this.updateHeaderNotifications();
        }

        // Method to add a real-time notification (for future websocket integration)
        addRealTimeNotification(notification) {
            console.log('New real-time notification:', notification);
            this.updateHeaderNotifications();
        }

        // Public method to update count from external sources (like dashboard)
        updateCountFromExternal(count) {
            if (this.notificationBadge && this.notificationCount) {
                if (count > 0) {
                    this.notificationBadge.textContent = count;
                    this.notificationBadge.style.display = 'block';
                    this.notificationCount.textContent = `${count} New`;
                } else {
                    this.notificationBadge.style.display = 'none';
                    this.notificationCount.textContent = '0 New';
                }
            }
        }
    }

    // Global notification manager instance
    let fleetNotificationManager;

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        fleetNotificationManager = new FleetNotificationManager();
    });

    // Global function for backward compatibility
    function updateHeaderNotifications() {
        if (fleetNotificationManager) {
            fleetNotificationManager.refresh();
        }
    }

    // Function to update header count from dashboard
    function updateHeaderNotificationCount(count) {
        if (fleetNotificationManager) {
            fleetNotificationManager.updateCountFromExternal(count);
        }
    }

    // Quick payment function for notification action buttons
    function quickPayFromNotification(collectionId, amount) {
        // Open payment modal (assuming it exists on the page)
        if (typeof quickPay === 'function') {
            quickPay(collectionId, amount);
        } else {
            // Redirect to payment page
            window.location.href = `/agreements/collections/${collectionId}/pay`;
        }
    }

    // Mark notification as read
    function markNotificationAsRead(notificationId) {
        fetch(`/notifications/${notificationId}/mark-read`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        }).then(() => {
            updateHeaderNotifications();
        });
    }
</script>
