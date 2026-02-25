<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $title ?? 'Fleet Management' }}</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }

        .footer {
            background: #343a40;
            color: white;
            padding: 2rem 0;
            margin-top: 3rem;
        }
    </style>

    @stack('css')
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="/">
            <i class="fas fa-car me-2"></i>
            Fleet Management
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('frontend.agreements.create') }}">Create Agreement</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Alert Messages -->
@if(session('success'))
    <div class="container mt-3">
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
@endif

@if(session('error'))
    <div class="container mt-3">
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
@endif

<!-- Main Content -->
<main>
    @yield('content')
</main>

<!-- Footer -->
<footer class="footer mt-auto">
    <div class="container text-center">
        <p class="mb-0">&copy; {{ date('Y') }} FleetIQ. All rights reserved.</p>
    </div>
</footer>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery (optional, if needed) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

@stack('js')
</body>
</html>
