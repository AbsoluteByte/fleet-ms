<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Documents Email Preview — Agreement #{{ $agreement->id }}</title>
    <style>
        body { margin: 0; padding: 24px; background: #eef1f6; font-family: Arial, sans-serif; color: #1f2937; }
        .dev-banner { background: #7c3aed; color: #fff; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
        .meta { background: #fff; border: 1px solid #d1d5db; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
        .meta-row { margin-bottom: 8px; font-size: 14px; }
        .meta-row:last-child { margin-bottom: 0; }
        .meta-label { font-weight: bold; color: #4b5563; display: inline-block; width: 110px; }
        .attachments { margin-top: 12px; font-size: 13px; }
        .attachments ul { margin: 6px 0 0 18px; padding: 0; }
        .email-frame { background: #fff; border: 1px solid #d1d5db; border-radius: 8px; overflow: hidden; }
        .email-frame-header { background: #f9fafb; border-bottom: 1px solid #e5e7eb; padding: 10px 16px; font-size: 13px; color: #6b7280; }
        .email-frame-body { padding: 0; }
    </style>
</head>
<body>
    <div class="dev-banner">
        <strong>DEV MODE</strong> — Email preview only. Nothing has been sent.
    </div>

    <div class="meta">
        <div class="meta-row">
            <span class="meta-label">To:</span>
            {{ $recipient ?: '— (no driver email)' }}
        </div>
        <div class="meta-row">
            <span class="meta-label">CC:</span>
            {{ $ccRecipient ?? '—' }}
        </div>
        <div class="meta-row">
            <span class="meta-label">Subject:</span>
            {{ $subject }}
        </div>
        <div class="attachments">
            <span class="meta-label">Attachments:</span>
            @if($attachments !== [])
                <ul>
                    @foreach($attachments as $attachment)
                        <li>{{ $attachment['label'] }} — {{ $attachment['as'] }}</li>
                    @endforeach
                </ul>
            @else
                <span class="text-muted">None</span>
            @endif
        </div>
    </div>

    <div class="email-frame">
        <div class="email-frame-header">Email body preview</div>
        <div class="email-frame-body">
            {!! $emailHtml !!}
        </div>
    </div>
</body>
</html>
