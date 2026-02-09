<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sign Fleet Agreement</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto;">
<div style="background: #f8f9fa; padding: 20px; border-radius: 5px;">
    <h2 style="color: #2c3e50; text-align: center;">Fleet Agreement Signature Required</h2>

    <div style="background: white; padding: 20px; border-radius: 5px; margin: 20px 0;">
        <p>Dear {{ $driver->full_name }},</p>

        <p>You have a fleet management agreement that requires your signature.</p>

        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;">
            <p><strong>Agreement Details:</strong></p>
            <p><strong>Company:</strong> {{ $company->name ?? 'N/A' }}</p>
            <p><strong>Vehicle:</strong> {{ $agreement->car->registration }}</p>
            <p><strong>Agreement ID:</strong> #{{ $agreement->id }}</p>
            <p><strong>Duration:</strong> {{ $agreement->start_date->format('d M Y') }} to {{ $agreement->end_date->format('d M Y') }}</p>
        </div>

        <div style="text-align: center; margin: 25px 0;">
            <a href="{{ $signing_url }}"
               style="display: inline-block; padding: 12px 30px; background: #007bff;
                          color: white; text-decoration: none; border-radius: 5px;
                          font-weight: bold; font-size: 16px;">
                Review & Sign Agreement
            </a>
        </div>

        <p style="color: #6c757d; font-size: 14px;">
            <strong>Note:</strong> This link will expire in 30 days.
        </p>

        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">

        <p style="font-size: 12px; color: #6c757d;">
            If you have any questions, please contact your fleet manager.
        </p>
    </div>

    <div style="text-align: center; font-size: 12px; color: #6c757d; padding-top: 20px; border-top: 1px solid #ddd;">
        <p>This is an automated message from {{ config('app.name') }}.</p>
    </div>
</div>
</body>
</html>
