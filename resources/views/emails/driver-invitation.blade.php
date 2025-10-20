<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Driver Invitation</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #fff; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
    </style>
</head>
<body>
<div class="container">
    <div class="content">
        <h3>Hello {{ $driver->full_name }},</h3>

        <p>You have been invited to join our fleet management system as a driver. This platform will allow you to:</p>

        <ul>
            <li>View your rental agreements</li>
            <li>Track payment schedules</li>
            <li>Update your profile information</li>
            <li>Access important documents</li>
        </ul>

        <p>To complete your registration and set up your account, please click the button below:</p>

        <p style="text-align: center;">
            <a href="{{ $invitationUrl }}" class="button">Complete Registration</a>
        </p>

        <p><strong>Important:</strong> This invitation will expire on {{ $expiresAt->format('F j, Y \a\t g:i A') }}.</p>

        <hr>

        <h4>Your Details:</h4>
        <p>
            <strong>Name:</strong> {{ $driver->full_name }}<br>
            <strong>Email:</strong> {{ $driver->email }}<br>
            <strong>Phone:</strong> {{ $driver->phone_number }}
        </p>

        <p>If you have any questions or need assistance, please contact our support team.</p>

        <p>Best regards</p>
    </div>

    <div class="footer">
        <p>If you cannot click the button above, copy and paste this link into your browser:<br>
            <small>{{ $invitationUrl }}</small></p>
    </div>
</div>
</body>
</html>
