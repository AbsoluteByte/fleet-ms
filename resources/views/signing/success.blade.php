{{-- resources/views/signing/success.blade.php --}}

    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agreement Signed Successfully</title>
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
        .success-container {
            max-width: 600px;
            width: 100%;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .success-icon {
            font-size: 80px;
            color: #28a745;
            animation: scaleIn 0.5s ease-in-out;
        }
        @keyframes scaleIn {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
<div class="success-container">
    <div class="card">
        <div class="card-body text-center p-5">
            <div class="success-icon mb-4">
                <i class="fas fa-check-circle"></i>
            </div>

            <h2 class="mb-3">Agreement Signed Successfully!</h2>
            <p class="text-muted mb-4">Thank you for signing the vehicle hire agreement.</p>

            <div class="alert alert-success">
                <i class="fas fa-info-circle me-2"></i>
                Your signature has been recorded and the company has been notified.
            </div>

            <div class="bg-light p-4 rounded mb-4">
                <h6 class="mb-3">Agreement Details</h6>
                <div class="detail-row">
                    <span class="text-muted">Signed By:</span>
                    <strong>{{ $signatureToken->signer_name }}</strong>
                </div>
                <div class="detail-row">
                    <span class="text-muted">Signed At:</span>
                    <strong>{{ $signatureToken->signed_at->format('M d, Y h:i A') }}</strong>
                </div>
                <div class="detail-row">
                    <span class="text-muted">Vehicle:</span>
                    <strong>{{ $signatureToken->agreement->car->registration }}</strong>
                </div>
            </div>

            <p class="small text-muted">
                You will receive a copy of the signed agreement via email shortly.
            </p>

            <div class="mt-4">
                <i class="fas fa-lock me-1"></i>
                <small class="text-muted">Legally binding electronic signature</small>
            </div>
        </div>
    </div>
</div>
</body>
</html>
