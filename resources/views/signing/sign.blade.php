{{-- resources/views/signing/sign.blade.php --}}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign Agreement - {{ $signatureToken->agreement->company->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('app-assets/fonts/font-awesome/css/font-awesome.min.css') }}">
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
            overflow: hidden;
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
            overflow: hidden;
            max-width: 100%;
        }
        .signature-pad-frame {
            border: 2px solid #667eea;
            border-radius: 5px;
            overflow: hidden;
            background: white;
            height: 220px;
            max-width: 100%;
        }
        #signature-pad {
            display: block;
            width: 100%;
            height: 100%;
            border: none;
            cursor: crosshair;
            touch-action: none;
        }
        .typed-signature-frame {
            border: 2px solid #667eea;
            border-radius: 5px;
            overflow: hidden;
            background: #fff;
            height: 140px;
            max-width: 100%;
            margin-top: 12px;
        }
        #typed-signature-canvas {
            display: block;
            width: 100%;
            height: 100%;
            border: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        .expires-warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
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
            gap: 12px;
        }
        .pdf-preview-header a {
            color: white;
            font-size: 12px;
            text-decoration: none;
            opacity: 0.85;
            white-space: nowrap;
        }
        .pdf-preview-header a:hover { opacity: 1; }
        .pdf-preview-body {
            position: relative;
            background: #eee;
            min-height: 600px;
        }
        #agreement-iframe {
            display: block;
            width: 100%;
            height: 600px;
            border: none;
            background: #eee;
        }
        .pdf-loading {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 40px 20px;
            color: #888;
            font-size: 14px;
            background: rgba(238, 238, 238, 0.96);
            z-index: 2;
        }
        .pdf-loading.is-hidden {
            display: none;
        }
        .read-confirm {
            background: #e8f5e9;
            border: 1px solid #4caf50;
            border-left: 4px solid #4caf50;
            padding: 14px 18px;
            border-radius: 6px;
            margin: 16px 0;
        }
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

        <div class="card-header text-center">
            <h2 class="mb-0">
                <i class="fa fa-file-text me-2"></i>
                Vehicle Hire Agreement
            </h2>
            <p class="mb-0 mt-2">{{ $signatureToken->agreement->company->name }}</p>
        </div>

        <div class="card-body p-4">

            <div class="alert alert-info">
                <strong>Signing as:</strong>
                {{ $signatureToken->signer_name }} ({{ $signatureToken->signer_email }})
            </div>

            <div class="expires-warning">
                <i class="fa fa-clock-o me-2"></i>
                <strong>This signing link expires on:</strong>
                {{ $signatureToken->expires_at->format('M d, Y h:i A') }}
            </div>

            <div class="step-title">
                <span class="step-badge">1</span>
                Read Your Agreement
            </div>
            <p class="text-muted mb-2" style="font-size:13px;">
                Please read the full agreement carefully before signing. Scroll through all pages.
            </p>

            <div class="pdf-preview-wrapper">
                <div class="pdf-preview-header">
                    <span><i class="fa fa-file-pdf-o me-2"></i>Hire Agreement — Full Document</span>
                    <a href="{{ route('sign.preview', $signatureToken->token) }}" target="_blank" rel="noopener noreferrer">
                        <i class="fa fa-external-link me-1"></i>Open in new tab
                    </a>
                </div>
                <div class="pdf-preview-body">
                    <div class="pdf-loading" id="pdf-loading">
                        <i class="fa fa-spinner fa-spin fa-2x mb-3"></i>
                        <div>Loading agreement...</div>
                    </div>
                    <iframe
                        id="agreement-iframe"
                        src="{{ route('sign.preview', $signatureToken->token) }}"
                        title="Agreement Document">
                    </iframe>
                </div>
            </div>

            <div class="read-confirm">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" id="read-confirm" required>
                    <label class="form-check-label" for="read-confirm" style="font-size:13px; font-weight:500;">
                        I have read and understood all the terms and conditions in the agreement above, including the
                        Statement of Understanding and Statement of Liability.
                    </label>
                </div>
            </div>

            <div class="step-title">
                <span class="step-badge">2</span>
                Provide Your Signature
            </div>
            <p class="text-muted mb-2" style="font-size:13px;">
                Draw your signature or type your name. Your signature will appear on the hire agreement PDF.
            </p>

            <ul class="nav nav-pills mb-3" id="signature-method-tabs">
                <li class="nav-item">
                    <button type="button" class="nav-link active" id="tab-draw" data-method="draw">Draw</button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" id="tab-typed" data-method="typed">Type name</button>
                </li>
            </ul>

            <div id="draw-signature-panel">
                <div class="signature-pad-container" id="signature-pad-container">
                    <div class="signature-pad-frame">
                        <canvas id="signature-pad"></canvas>
                    </div>
                </div>
            </div>

            <div id="typed-signature-panel" style="display:none;">
                <label for="typed-name" class="form-label">Full name</label>
                <input type="text" class="form-control" id="typed-name"
                       value="{{ $signatureToken->signer_name }}"
                       maxlength="255"
                       placeholder="Type your full name">
                <div class="typed-signature-frame">
                    <canvas id="typed-signature-canvas"></canvas>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-between mt-3">
                <button type="button" class="btn btn-secondary" id="clear-signature">
                    <i class="fa fa-eraser me-2"></i>Clear Signature
                </button>
                <button type="button" class="btn btn-primary btn-lg" id="submit-signature">
                    <i class="fa fa-check-circle me-2"></i>Submit Signature
                </button>
            </div>

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
    const pdfLoading = document.getElementById('pdf-loading');
    const pdfIframe = document.getElementById('agreement-iframe');
    const previewUrl = @json(route('sign.preview', $signatureToken->token));

    function hidePdfLoading() {
        pdfLoading.classList.add('is-hidden');
    }

    function showPdfFallback() {
        pdfLoading.innerHTML = '<div>Could not preview the agreement here.</div>'
            + '<a class="mt-2 d-inline-block" href="' + previewUrl + '" target="_blank" rel="noopener noreferrer">'
            + '<i class="fa fa-external-link me-1"></i>Open in new tab</a>';
        pdfLoading.classList.remove('is-hidden');
    }

    pdfIframe.addEventListener('load', hidePdfLoading);
    pdfIframe.addEventListener('error', showPdfFallback);
    setTimeout(function () {
        if (! pdfLoading.classList.contains('is-hidden')) {
            showPdfFallback();
        }
    }, 8000);

    let signatureMethod = 'draw';
    const padContainer = document.getElementById('signature-pad-container');
    const canvas = document.getElementById('signature-pad');
    const signaturePad = new SignaturePad(canvas, {
        backgroundColor: 'rgb(255, 255, 255)',
        penColor: 'rgb(0, 0, 0)'
    });
    const typedCanvas = document.getElementById('typed-signature-canvas');
    const typedNameInput = document.getElementById('typed-name');
    const drawPanel = document.getElementById('draw-signature-panel');
    const typedPanel = document.getElementById('typed-signature-panel');
    const tabDraw = document.getElementById('tab-draw');
    const tabTyped = document.getElementById('tab-typed');

    function syncCanvasBuffer(target) {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const cssWidth = Math.max(1, Math.floor(target.offsetWidth));
        const cssHeight = Math.max(1, Math.floor(target.offsetHeight));
        target.width = Math.floor(cssWidth * ratio);
        target.height = Math.floor(cssHeight * ratio);
        const ctx = target.getContext('2d');
        ctx.setTransform(1, 0, 0, 1, 0, 0);
        return { ratio: ratio, cssWidth: cssWidth, cssHeight: cssHeight, ctx: ctx };
    }

    function resizeCanvas() {
        canvas.width = Math.max(1, Math.floor(canvas.offsetWidth));
        canvas.height = Math.max(1, Math.floor(canvas.offsetHeight));
        canvas.getContext('2d').setTransform(1, 0, 0, 1, 0, 0);
        signaturePad.clear();
    }

    function renderTypedName() {
        const size = syncCanvasBuffer(typedCanvas);
        const ctx = size.ctx;
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, typedCanvas.width, typedCanvas.height);
        const name = (typedNameInput.value || '').trim();
        if (! name) {
            return;
        }
        ctx.fillStyle = '#111111';
        ctx.font = (48 * size.ratio) + 'px "Times New Roman", Georgia, serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(name, typedCanvas.width / 2, typedCanvas.height / 2);
    }

    window.addEventListener('resize', function () {
        resizeCanvas();
        if (signatureMethod === 'typed') {
            renderTypedName();
        }
    });
    resizeCanvas();
    typedNameInput.addEventListener('input', renderTypedName);

    function setMethod(method) {
        signatureMethod = method;
        const isDraw = method === 'draw';
        drawPanel.style.display = isDraw ? 'block' : 'none';
        typedPanel.style.display = isDraw ? 'none' : 'block';
        tabDraw.classList.toggle('active', isDraw);
        tabTyped.classList.toggle('active', !isDraw);
        if (isDraw) {
            resizeCanvas();
        } else {
            renderTypedName();
        }
    }

    tabDraw.addEventListener('click', function () { setMethod('draw'); });
    tabTyped.addEventListener('click', function () { setMethod('typed'); });

    document.getElementById('clear-signature').addEventListener('click', () => {
        if (signatureMethod === 'draw') {
            signaturePad.clear();
        } else {
            typedNameInput.value = '';
            renderTypedName();
        }
    });

    document.getElementById('submit-signature').addEventListener('click', async () => {
        if (!document.getElementById('read-confirm').checked) {
            alert('Please confirm that you have read the agreement before signing.');
            return;
        }

        if (!document.getElementById('agree-terms').checked) {
            alert('Please agree that your electronic signature is legally binding.');
            return;
        }

        let signatureData = '';
        let typedName = null;

        if (signatureMethod === 'draw') {
            if (signaturePad.isEmpty()) {
                alert('Please provide your signature first.');
                return;
            }
            signatureData = signaturePad.toDataURL();
        } else {
            typedName = (typedNameInput.value || '').trim();
            if (!typedName) {
                alert('Please type your full name to sign.');
                return;
            }
            renderTypedName();
            signatureData = typedCanvas.toDataURL('image/png');
        }

        const btn = document.getElementById('submit-signature');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Processing...';

        try {
            const response = await fetch('{{ route('sign.submit', $signatureToken->token) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    signature: signatureData,
                    signature_method: signatureMethod,
                    typed_name: typedName
                })
            });

            const data = await response.json();

            if (data.success) {
                window.location.href = data.redirect;
            } else {
                alert(data.error || 'Failed to submit signature');
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-check-circle me-2"></i>Submit Signature';
            }
        } catch (error) {
            alert('An error occurred. Please try again.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-check-circle me-2"></i>Submit Signature';
        }
    });
</script>
</body>
</html>
