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
            max-width: 950px;
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
        .detail-row:last-child { border-bottom: none; }
        .detail-label { font-weight: 600; color: #666; }

        .expires-warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }

        /* ✅ PDF Preview section */
        .pdf-preview-wrapper {
            border: 2px solid #667eea;
            border-radius: 10px;
            overflow: hidden;
            margin: 20px 0;
        }
        .pdf-preview-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 16px;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .pdf-preview-header a {
            color: white;
            font-size: 12px;
            text-decoration: none;
            opacity: 0.85;
        }
        .pdf-preview-header a:hover { opacity: 1; }
        #agreement-iframe {
            width: 100%;
            height: 600px;
            border: none;
            display: block;
            background: #eee;
        }
        .pdf-loading {
            text-align: center;
            padding: 60px 20px;
            color: #888;
            font-size: 14px;
        }

        /* ✅ Read confirmation */
        .read-confirm {
            background: #e8f5e9;
            border: 1px solid #4caf50;
            border-left: 4px solid #4caf50;
            padding: 14px 18px;
            border-radius: 6px;
            margin: 16px 0;
        }

        /* Step badges */
        .step-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: bold;
            font-size: 13px;
            margin-right: 8px;
            flex-shrink: 0;
        }
        .step-title {
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            margin: 24px 0 8px 0;
        }
    </style>
</head>
<body>
<div class="signing-container px-3">
    <div class="card">

        {{-- Header --}}
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
                <strong>Signing as:</strong>
                {{ $signatureToken->signer_name }} ({{ $signatureToken->signer_email }})
            </div>

            {{-- Expiry Warning --}}
            <div class="expires-warning">
                <i class="fas fa-clock me-2"></i>
                <strong>This signing link expires on:</strong>
                {{ $signatureToken->expires_at->format('M d, Y h:i A') }}
            </div>

            {{-- ✅ STEP 1: Read Agreement --}}
            <div class="step-title">
                <span class="step-badge">1</span>
                Read Your Agreement
            </div>
            <p class="text-muted mb-2" style="font-size:13px;">
                Please read the full agreement carefully before signing. Scroll through all pages.
            </p>

            <div class="pdf-preview-wrapper">
                <div class="pdf-preview-header">
                    <span><i class="fas fa-file-pdf me-2"></i>Hire Agreement — Full Document</span>
                    <a href="{{ route('sign.preview', $signatureToken->token) }}" target="_blank">
                        <i class="fas fa-external-link-alt me-1"></i>Open in new tab
                    </a>
                </div>
                <div class="pdf-loading" id="pdf-loading">
                    <i class="fas fa-spinner fa-spin fa-2x mb-3"></i><br>
                    Loading agreement...
                </div>
                <iframe
                    id="agreement-iframe"
                    src="{{ route('sign.preview', $signatureToken->token) }}"
                    style="display:none;"
                    onload="iframeLoaded()"
                    title="Agreement Document">
                </iframe>
            </div>

            {{-- ✅ Read Confirmation --}}
            <div class="read-confirm">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" id="read-confirm" required>
                    <label class="form-check-label" for="read-confirm" style="font-size:13px; font-weight:500;">
                        I have read and understood all the terms and conditions in the agreement above, including the
                        Statement of Understanding and Statement of Liability.
                    </label>
                </div>
            </div>

            {{-- ✅ STEP 2: Sign --}}
            <div class="step-title">
                <span class="step-badge">2</span>
                Provide Your Signature
            </div>
            <p class="text-muted mb-2" style="font-size:13px;">
                Sign below using your mouse or finger (on touch devices). Your signature will appear on all 3 documents.
            </p>

            <div class="signature-pad-container">
                <canvas id="signature-pad" width="560" height="220"></canvas>
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
                <label class="form-check-label" for="agree-terms" style="font-size:13px;">
                    I agree that this electronic signature is legally binding and equivalent to my handwritten signature.
                </label>
            </div>

        </div>
    </div>

    <div class="text-center text-white mt-3 pb-3" style="font-size:12px; opacity:0.8;">
        Secure Electronic Signing &mdash; {{ $signatureToken->agreement->company->name }}
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
    // ── PDF iframe load ──
    function iframeLoaded() {
        document.getElementById('pdf-loading').style.display = 'none';
        document.getElementById('agreement-iframe').style.display = 'block';
    }

    // ── Signature Pad ──
    const canvas = document.getElementById('signature-pad');
    const signaturePad = new SignaturePad(canvas, {
        backgroundColor: 'rgb(255, 255, 255)',
        penColor: 'rgb(0, 0, 0)'
    });

    // Resize canvas properly on mobile
    function resizeCanvas() {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const rect  = canvas.getBoundingClientRect();
        canvas.width  = rect.width  * ratio;
        canvas.height = rect.height * ratio;
        canvas.getContext('2d').scale(ratio, ratio);
        signaturePad.clear();
    }
    window.addEventListener('resize', resizeCanvas);
    resizeCanvas();

    // Clear
    document.getElementById('clear-signature').addEventListener('click', () => {
        signaturePad.clear();
    });

    // Submit
    document.getElementById('submit-signature').addEventListener('click', async () => {

        if (!document.getElementById('read-confirm').checked) {
            alert('Please confirm that you have read the agreement before signing.');
            return;
        }

        if (signaturePad.isEmpty()) {
            alert('Please provide your signature first.');
            return;
        }

        if (!document.getElementById('agree-terms').checked) {
            alert('Please agree that your electronic signature is legally binding.');
            return;
        }

        const signatureData = signaturePad.toDataURL();
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
