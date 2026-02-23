{{-- resources/views/signing/expired.blade.php --}}

    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signing Link Expired</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .expired-container {
            max-width: 600px;
            width: 100%;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .expired-icon {
            font-size: 80px;
            color: #ffc107;
        }
    </style>
</head>
<body>
<div class="expired-container">
    <div class="card">
        <div class="card-body text-center p-5">
            <div class="expired-icon mb-4">
                <i class="fas fa-exclamation-triangle"></i>
            </div>

            <h2 class="mb-3">Signing Link Expired</h2>
            <p class="text-muted mb-4">
                This signing link expired on {{ $signatureToken->expires_at->format('M d, Y h:i A') }}.
            </p>

            <div class="alert alert-warning">
                <i class="fas fa-info-circle me-2"></i>
                Please contact {{ $signatureToken->agreement->company->name }} to request a new signing link.
            </div>

            <div class="bg-light p-4 rounded mt-4">
                <h6 class="mb-2">Company Contact</h6>
                <p class="mb-0"><strong>{{ $signatureToken->agreement->company->name }}</strong></p>
                @if($signatureToken->agreement->company->email)
                    <p class="mb-0"><i class="fas fa-envelope me-2"></i>{{ $signatureToken->agreement->company->email }}</p>
                @endif
                @if($signatureToken->agreement->company->phone)
                    <p class="mb-0"><i class="fas fa-phone me-2"></i>{{ $signatureToken->agreement->company->phone }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
</body>
</html>
