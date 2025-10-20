<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Driver Portal' }} - Fleet Management</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.0/feather.min.css" rel="stylesheet">

    <style>
        body {
            background: #f8f9fa;
        }
        .sidebar {
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 4px 12px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        .main-content {
            padding: 2rem;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
    </style>

    @yield('css')
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 sidebar">
            <div class="position-sticky pt-3">
                <div class="text-center mb-4">
                    <h4><i class="fas fa-user-circle me-2"></i>Driver Portal</h4>
                </div>

                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('driver.dashboard') ? 'active' : '' }}"
                           href="{{ route('driver.dashboard') }}">
                            <i class="feather icon-home me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('driver.agreements*') ? 'active' : '' }}"
                           href="{{ route('driver.agreements') }}">
                            <i class="feather icon-file-text me-2"></i>My Agreements
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('driver.payments') ? 'active' : '' }}"
                           href="{{ route('driver.payments') }}">
                            <i class="feather icon-credit-card me-2"></i>Payments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('driver.profile') ? 'active' : '' }}"
                           href="{{ route('driver.profile') }}">
                            <i class="feather icon-user me-2"></i>Profile
                        </a>
                    </li>
                    <li class="nav-item mt-3">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="nav-link btn btn-link text-start w-100">
                                <i class="feather icon-log-out me-2"></i>Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="feather icon-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="feather icon-alert-circle me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@yield('js')
</body>
</html>
