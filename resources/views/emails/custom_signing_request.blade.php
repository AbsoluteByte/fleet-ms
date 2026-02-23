 <!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            margin: 0;
        }
        .greeting {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .button {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
        }
        .button:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        .details {
            background-color: white;
            padding: 20px;
            border-left: 4px solid #667eea;
            margin: 20px 0;
            border-radius: 5px;
        }
        .details-row {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .details-row:last-child {
            border-bottom: none;
        }
        .details-label {
            font-weight: bold;
            width: 140px;
            color: #666;
        }
        .details-value {
            color: #333;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            color: #856404;
        }
        .footer {
            text-align: center;
            color: #666;
            font-size: 12px;
            margin-top: 30px;
            padding: 20px;
            border-top: 1px solid #ddd;
        }
        .link-fallback {
            font-size: 12px;
            color: #666;
            word-break: break-all;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📝 Document Signature Required</h1>
    </div>

    <div class="content">
        <div class="greeting">
            Hello {{ $driver->full_name }},
        </div>

        <p>You have been requested to electronically sign a <strong>Vehicle Hire Agreement</strong> from <strong>{{ $company->name }}</strong>.</p>

        <div class="details">
            <strong style="font-size: 16px; color: #667eea;">Agreement Details:</strong>
            <div style="margin-top: 15px;">
                <div class="details-row">
                    <div class="details-label">Vehicle:</div>
                    <div class="details-value">{{ $agreement->car->registration }} - {{ $agreement->car->carModel->name }}</div>
                </div>
                <div class="details-row">
                    <div class="details-label">Start Date:</div>
                    <div class="details-value">{{ $agreement->start_date->format('M d, Y') }}</div>
                </div>
                <div class="details-row">
                    <div class="details-label">End Date:</div>
                    <div class="details-value">{{ $agreement->end_date->format('M d, Y') }}</div>
                </div>
                <div class="details-row">
                    <div class="details-label">Weekly Rental:</div>
                    <div class="details-value">£{{ number_format($agreement->agreed_rent, 2) }}</div>
                </div>
            </div>
        </div>

        <p style="margin-top: 25px;"><strong>To review and sign this agreement, please click the button below:</strong></p>

        <div style="text-align: center;">
            <a href="{{ $signing_url }}" class="button">
                ✍️ SIGN AGREEMENT NOW
            </a>
        </div>

        <div class="link-fallback">
            If the button doesn't work, copy and paste this link into your browser:<br>
            <a href="{{ $signing_url }}">{{ $signing_url }}</a>
        </div>

        <div class="warning">
            <strong>⚠️ Important:</strong> This signing link will expire on <strong>{{ $expires_at }}</strong>. Please sign before this date.
        </div>

        <p style="margin-top: 25px;">If you have any questions about this agreement, please contact {{ $company->name }} directly.</p>

        <p style="margin-top: 20px;">
            Best regards,<br>
            <strong>{{ $company->name }}</strong>
        </p>
    </div>

    <div class="footer">
        <p>This is an automated email. Please do not reply to this message.</p>
        <p>© {{ date('Y') }} {{ $company->name }}. All rights reserved.</p>
    </div>
</div>
</body>
</html>
