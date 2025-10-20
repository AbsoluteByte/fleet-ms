<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Driver Invitation - Fleet Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .invitation-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
        }

        .invitation-header {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .invitation-body {
            padding: 2rem;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #e5e7eb;
            padding: 12px 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
        }

        .btn-primary {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border: none;
            border-radius: 10px;
            padding: 12px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }

        .driver-info {
            background: rgba(99, 102, 241, 0.1);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="invitation-card">
                <div class="invitation-header">
                    <i class="fas fa-car fa-3x mb-3"></i>
                    <h2 class="mb-0">Driver Invitation</h2>
                    <p class="mb-0 opacity-75">Complete your registration</p>
                </div>

                <div class="invitation-body">
                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="driver-info">
                        <h5 class="text-primary mb-3">Welcome {{ $driver->full_name }}!</h5>
                        <p class="mb-2"><strong>Email:</strong> {{ $driver->email }}</p>
                        <p class="mb-2"><strong>Phone:</strong> {{ $driver->phone_number }}</p>
                        <p class="mb-0">You've been invited to join our fleet management system. Please set up your password to access your driver portal.</p>
                    </div>

                    <form method="POST" action="{{ route('driver.accept-invitation', $driver->invitation_token) }}">
                        @csrf

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                <input id="password" type="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       name="password" required>
                            </div>
                            @error('password')
                            <div class="invalid-feedback d-block">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirm Password</label>
                            <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                <input id="password_confirmation" type="password"
                                       class="form-control" name="password_confirmation" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input @error('terms') is-invalid @enderror"
                                       type="checkbox" name="terms" id="terms" value="1" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the terms and conditions of the fleet management system
                                </label>
                                @error('terms')
                                <div class="invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-key me-2"></i>
                            Complete Registration
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <p class="text-muted">
                            <small>This invitation will expire in {{ $driver->invited_at->addDays(7)->diffForHumans() }}</small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
