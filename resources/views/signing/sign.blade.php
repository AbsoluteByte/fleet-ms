{{-- resources/views/signing/sign.blade.php --}}

    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign Agreement - {{ $signatureToken->agreement->company->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .signing-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 25px;
        }
        .signature-pad-container {
            border: 2px dashed #ddd;
            border-radius: 10px;
            background: #f8f9fa;
            padding: 10px;
            margin: 20px 0;
        }
        #signature-pad {
            border: 2px solid #667eea;
            border-radius: 5px;
            background: white;
            cursor: crosshair;
            touch-action: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        .agreement-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #666;
        }
        .expires-warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
<div class="signing-container">
    <div class="card">
        <div class="card-header text-center">
            <h2 class="mb-0">
                <i class="fas fa-file-signature me-2"></i>
                Vehicle Hire Agreement
            </h2>
            <p class="mb-0 mt-2">{{ $signatureToken->agreement->company->name }}</p>
        </div>
        <div class="card-body p-4">

            {{-- Signer Info --}}
            <div class="alert alert-info">
                <strong>Signing as:</strong> {{ $signatureToken->signer_name }} ({{ $signatureToken->signer_email }})
            </div>

            {{-- Agreement Details --}}
            <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i>Agreement Details</h5>
            <div class="agreement-details">
                <div class="detail-row">
                    <span class="detail-label">Vehicle:</span>
                    <span>{{ $signatureToken->agreement->car->registration }} - {{ $signatureToken->agreement->car->carModel->name }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Start Date:</span>
                    <span>{{ $signatureToken->agreement->start_date->format('M d, Y') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">End Date:</span>
                    <span>{{ $signatureToken->agreement->end_date->format('M d, Y') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Weekly Rental:</span>
                    <span><strong>£{{ number_format($signatureToken->agreement->agreed_rent, 2) }}</strong></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Deposit:</span>
                    <span>£{{ number_format($signatureToken->agreement->deposit_amount, 2) }}</span>
                </div>
            </div>

            {{-- Expiry Warning --}}
            <div class="expires-warning">
                <i class="fas fa-clock me-2"></i>
                <strong>This signing link expires on:</strong> {{ $signatureToken->expires_at->format('M d, Y h:i A') }}
            </div>

            {{-- Signature Section --}}
            <h5 class="mt-4 mb-3"><i class="fas fa-pen me-2"></i>Your Signature</h5>
            <p class="text-muted">Please sign below using your mouse or finger (on touch devices)</p>

            <div class="signature-pad-container">
                <canvas id="signature-pad" width="860" height="300"></canvas>
            </div>

            <div class="d-flex gap-2 justify-content-between mt-3">
                <button type="button" class="btn btn-secondary" id="clear-signature">
                    <i class="fas fa-eraser me-2"></i>Clear Signature
                </button>
                <button type="button" class="btn btn-primary btn-lg" id="submit-signature">
                    <i class="fas fa-check-circle me-2"></i>Submit Signature
                </button>
            </div>

            {{-- Terms Checkbox --}}
            <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" id="agree-terms" required>
                <label class="form-check-label" for="agree-terms">
                    I agree that this electronic signature is legally binding and equivalent to my handwritten signature.
                </label>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
    const canvas = document.getElementById('signature-pad');
    const signaturePad = new SignaturePad(canvas, {
        backgroundColor: 'rgb(255, 255, 255)',
        penColor: 'rgb(0, 0, 0)'
    });

    // Clear signature
    document.getElementById('clear-signature').addEventListener('click', () => {
        signaturePad.clear();
    });

    // Submit signature
    document.getElementById('submit-signature').addEventListener('click', async () => {
        // Validate
        if (signaturePad.isEmpty()) {
            alert('Please provide your signature first');
            return;
        }

        /*if (!document.getElementById('agree-terms').checked) {
            alert('Please agree to the terms to continue');
            return;
        }*/

        // Get signature data
        const signatureData = signaturePad.toDataURL();

        // Submit
        const btn = document.getElementById('submit-signature');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';

        try {
            const response = await fetch('{{ route('sign.submit', $signatureToken->token) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ signature: signatureData })
            });

            const data = await response.json();

            if (data.success) {
                window.location.href = data.redirect;
            } else {
                alert(data.error || 'Failed to submit signature');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Submit Signature';
            }
        } catch (error) {
            alert('An error occurred. Please try again.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Submit Signature';
        }
    });
</script>
</body>
</html>
