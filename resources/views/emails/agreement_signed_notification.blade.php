<!DOCTYPE html>
<html>
<body>
<h2>Agreement Signed - Notification</h2>

<p>Dear Admin,</p>

<p>The following agreement has been signed:</p>

<div style="background: #f8f9fa; padding: 15px; border-radius: 5px;">
    <p><strong>Agreement ID:</strong> #{{ $agreement->id }}</p>
    <p><strong>Driver:</strong> {{ $agreement->driver->full_name }}</p>
    <p><strong>Vehicle:</strong> {{ $agreement->car->registration }}</p>
    <p><strong>Signed On:</strong> {{ $agreement->esign_completed_at->format('d M Y h:i A') }}</p>
</div>

<p>You can view the signed document from the agreement details page.</p>

<p>Thank you,<br>
    {{ config('app.name') }}</p>
</body>
</html>
