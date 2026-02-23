{{-- resources/views/signing/already-signed.blade.php --}}

    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Already Signed</title>
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
        .signed-container {
            max-width: 600px;
            width: 100%;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .signed-icon {
            font-size: 80px;
            color: #28a745;
        }
    </style>
</head>
<body>
<div class="signed-container">
    <div class="card">
        <div class="card-body text-center p-5">
            <div class="signed-icon mb-4">
                <i class="fas fa-check-circle"></i>
            </div>

            <h2 class="mb-3">Already Signed</h2>
            <p class="text-muted mb-4">
                This agreement was already signed on {{ $signatureToken->signed_at->format('M d, Y h:i A') }}.
            </p>

            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                No further action is required from you.
            </div>

            <div class="bg-light p-4 rounded mt-4">
                <h6 class="mb-3">Signature Details</h6>
                <p class="mb-2"><strong>Signed By:</strong> {{ $signatureToken->signer_name }}</p>
                <p class="mb-2"><strong>Email:</strong> {{ $signatureToken->signer_email }}</p>
                <p class="mb-0"><strong>Date:</strong> {{ $signatureToken->signed_at->format('M d, Y h:i A') }}</p>
            </div>

            <p class="small text-muted mt-4">
                If you need a copy of the signed agreement, please contact {{ $signatureToken->agreement->company->name }}.
            </p>
        </div>
    </div>
</div>
</body>
</html>
