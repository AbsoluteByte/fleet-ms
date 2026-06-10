<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Permission Letter - {{ $driver->full_name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.35;
            color: #000;
            padding: 30px 40px;
        }

        .letter-date {
            margin-bottom: 18px;
        }

        .doc-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 18px;
        }

        .logo-wrap {
            text-align: center;
            margin-bottom: 12px;
        }

        .logo-wrap img {
            max-height: 60px;
            max-width: 180px;
        }

        p {
            margin-bottom: 10px;
            text-align: justify;
        }

        .section-title {
            font-weight: bold;
            margin: 14px 0 6px 0;
            text-decoration: underline;
        }

        .field {
            margin-bottom: 4px;
        }

        .field .lbl {
            font-weight: bold;
        }

        .sig-block {
            margin-top: 28px;
        }

        .sig-name {
            font-weight: bold;
            margin-bottom: 2px;
        }

        .sig-title {
            margin-bottom: 2px;
        }

        .sig-company {
            font-weight: bold;
        }

        .footer {
            position: fixed;
            bottom: 25px;
            left: 40px;
            right: 40px;
            font-size: 9px;
            line-height: 1.4;
            text-align: center;
            color: #333;
        }
    </style>
</head>
<body>

@if($company->logo)
    <div class="logo-wrap">
        <img src="{{ public_path('uploads/companies/' . $company->logo) }}" alt="{{ $company->name }}">
    </div>
@endif

<div class="letter-date">Date: {{ $letterDate }}</div>

<div class="doc-title">Permission Letter</div>

<p>Hello Sir/Madam</p>

<p>
    I, Mr. {{ $company->director_name }} (Company Director, {{ $company->name }}) confirm that the below vehicle
    is owned by the company {{ strtoupper($company->name) }}.
</p>

<div class="section-title">VEHICLE DETAILS</div>
<div class="field"><span class="lbl">MAKE AND MODEL:</span> {{ strtoupper($car->carModel->name ?? '') }}</div>
<div class="field"><span class="lbl">REGISTRATION NO:</span> {{ strtoupper($car->registration) }}</div>
<div class="field"><span class="lbl">COLOUR:</span> {{ strtoupper($car->color ?? '') }}</div>
<div class="field"><span class="lbl">INSURANCE POLICY NO:</span> {{ $policyNumber ?: '—' }}</div>

<div class="section-title">CONTRACT COMMENCING FROM</div>
<div class="field"><span class="lbl">COMMENCING DATE:</span> {{ $agreement->start_date->format('d.m.Y') }}</div>
<div class="field"><span class="lbl">CONTRACT ENDING DATE:</span> {{ $agreement->end_date->format('d.m.Y') }}</div>

<p style="margin-top: 12px;">
    I have authorized and given permission to the following individual to use this vehicle on a
    temporary basis.
</p>

<div class="field"><span class="lbl">DRIVER NAME:</span> MR {{ strtoupper($driver->full_name) }}</div>
<div class="field"><span class="lbl">DRIVER LICENSE NO:</span> {{ strtoupper($driver->driver_license_number ?? '') }}</div>
<div class="field"><span class="lbl">DRIVER ADDRESS:</span> @include('backend.agreements._driver_address_pdf', ['driver' => $driver])</div>

<p style="margin-top: 12px;">
    The above-mentioned person is fully insured to use this vehicle for carriage of passenger and hire
    and reward insurance purposes.
</p>

<p>Please contact me if you required any further information.</p>

<p>Yours faithfully.</p>

<div class="sig-block">
    <div class="sig-name">{{ strtoupper($company->director_name) }}</div>
    <div class="sig-title">Company Director</div>
    <div class="sig-company">{{ strtoupper($company->name) }}</div>
</div>

<div class="footer">
    {{ $company->name }}, {{ $company->address_line_1 }}, {{ $company->town }} {{ $company->postcode }} {{ $company->phone }} | {{ $company->email }}.
    @if($company->company_registration_number)
        <br>{{ $company->name }} is Registered in England and Wales with Company No. {{ $company->company_registration_number }}
    @endif
</div>

</body>
</html>
