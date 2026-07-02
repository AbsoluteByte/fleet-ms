<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Documents</title>
    <style>
        body { margin: 0; padding: 0; background: #f4f6fb; font-family: Arial, sans-serif; color: #222; }
        .container { max-width: 640px; margin: 0 auto; padding: 24px; }
        .card { background: #fff; border-radius: 12px; overflow: hidden; border: 1px solid #e7ebf3; }
        .header { background: #1f3a8a; color: #fff; padding: 20px 24px; }
        .header-row { display: flex; align-items: center; gap: 14px; }
        .logo { width: 52px; height: 52px; border-radius: 8px; background: #fff; object-fit: contain; padding: 6px; }
        .body { padding: 24px; }
        .title { margin: 0 0 8px; font-size: 20px; }
        .muted { color: #5b6270; font-size: 14px; }
        .section-title { font-size: 15px; margin: 18px 0 8px; color: #111827; }
        .pill { display: inline-block; background: #eef2ff; color: #1f3a8a; border-radius: 999px; padding: 4px 10px; font-size: 12px; font-weight: bold; margin-right: 6px; }
        ul { margin: 8px 0 0 18px; padding: 0; }
        li { margin-bottom: 6px; font-size: 14px; }
        .missing { margin-top: 16px; border: 1px solid #f5d08a; background: #fff8ea; border-radius: 8px; padding: 12px 14px; }
        .footer { padding: 18px 24px; background: #f9fafc; border-top: 1px solid #e7ebf3; font-size: 12px; color: #6b7280; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="header">
            <div class="header-row">
                @if(!empty($company?->logo))
                    <img class="logo" src="{{ asset('uploads/companies/' . $company->logo) }}" alt="{{ $company->name }}">
                @endif
                <div>
                    <h1 class="title">Client Documents</h1>
                    <div>{{ $company?->name ?? $subjectCompany }}</div>
                </div>
            </div>
        </div>

        <div class="body">
            <p class="muted">
                Hello {{ $driver?->full_name ?: 'Driver' }},
                <br>
                Please find attached documents for agreement <strong>#{{ $agreement->id }}</strong>
                ({{ $agreement->car?->registration ?? 'Vehicle' }}).
            </p>

            <div>
                <span class="pill">Agreement #{{ $agreement->id }}</span>
                <span class="pill">{{ $agreement->car?->registration ?? 'Vehicle' }}</span>
            </div>

            <h3 class="section-title">Attached Documents</h3>
            @if($attachedLabels !== [])
                <ul>
                    @foreach($attachedLabels as $label)
                        <li>{{ $label }}</li>
                    @endforeach
                </ul>
            @else
                <p class="muted">No documents were available to attach.</p>
            @endif

            @if($missingDocuments !== [])
                <div class="missing">
                    <strong>Missing Documents</strong>
                    <ul>
                        @foreach($missingDocuments as $missing)
                            <li>{{ $missing }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="footer">
            This is an automated email from {{ $company?->name ?? $subjectCompany }}.
        </div>
    </div>
</div>
</body>
</html>
