@php
    $paragraphs = preg_split("/\r\n|\r|\n/", trim($bodyText ?? '')) ?: [];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $subject ?? 'Fleet Insurance Request' }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #222; line-height: 1.6;">
@foreach($paragraphs as $paragraph)
    @if(trim($paragraph) !== '')
        <p style="margin: 0 0 1em;">{!! nl2br(e($paragraph)) !!}</p>
    @endif
@endforeach
</body>
</html>
