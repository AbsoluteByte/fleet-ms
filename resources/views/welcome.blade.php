<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fleet Management System</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Figtree', sans-serif;
        }

        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            color: white;
            text-align: center;
        }

        .hero-content {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 3rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-custom {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            margin: 0 10px;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
            color: white;
        }

        .btn-outline-custom {
            border: 2px solid white;
            color: white;
            background: transparent;
            border-radius: 10px;
            padding: 10px 28px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            margin: 0 10px;
        }

        .btn-outline-custom:hover {
            background: white;
            color: #667eea;
            transform: translateY(-2px);
        }

        .floating-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
            z-index: -1;
        }

        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 20s infinite linear;
        }

        @keyframes float {
            0% { transform: translateY(100vh) rotate(0deg); }
            100% { transform: translateY(-100px) rotate(360deg); }
        }
    </style>
</head>
<body>
<div class="floating-shapes">
    <div class="shape" style="left: 10%; width: 80px; height: 80px; animation-delay: -2s;"></div>
    <div class="shape" style="left: 20%; width: 120px; height: 120px; animation-delay: -8s;"></div>
    <div class="shape" style="left: 30%; width: 60px; height: 60px; animation-delay: -15s;"></div>
    <div class="shape" style="left: 40%; width: 100px; height: 100px; animation-delay: -5s;"></div>
    <div class="shape" style="left: 50%; width: 40px; height: 40px; animation-delay: -12s;"></div>
    <div class="shape" style="left: 60%; width: 90px; height: 90px; animation-delay: -18s;"></div>
    <div class="shape" style="left: 70%; width: 70px; height: 70px; animation-delay: -3s;"></div>
    <div class="shape" style="left: 80%; width: 110px; height: 110px; animation-delay: -10s;"></div>
    <div class="shape" style="left: 90%; width: 50px; height: 50px; animation-delay: -7s;"></div>
</div>

<div class="hero-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="hero-content">
                    <i class="fas fa-car-side fa-5x mb-4"></i>
                    <h1 class="display-3 fw-bold mb-4">Fleet Management System</h1>
                    <p class="lead mb-5">
                        Complete solution for managing your fleet operations, drivers, agreements, and expenses.
                        Built with modern technology and beautiful design.
                    </p>

                    <div class="d-flex justify-content-center flex-wrap gap-3">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn-custom">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Go to Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn-custom">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Login
                            </a>
                            {{--<a href="{{ route('register') }}" class="btn-outline-custom">
                                <i class="fas fa-user-plus me-2"></i>
                                Register
                            </a>--}}
                        @endauth
                    </div>

                    <div class="mt-5">
                        <h4 class="mb-3">Key Features</h4>
                        <div class="row text-start">
                            <div class="col-md-6">
                                <p><i class="fas fa-check me-2"></i> Fleet & Driver Management</p>
                                <p><i class="fas fa-check me-2"></i> Agreement Tracking</p>
                                <p><i class="fas fa-check me-2"></i> Expense Management</p>
                            </div>
                            <div class="col-md-6">
                                <p><i class="fas fa-check me-2"></i> Document Expiry Alerts</p>
                                <p><i class="fas fa-check me-2"></i> Claims & Penalties</p>
                                <p><i class="fas fa-check me-2"></i> Comprehensive Reports</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
