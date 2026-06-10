<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Permission Letter - {{ $driver->full_name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.45;
            color: #000;
            padding: 36px 42px 90px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
            table-layout: fixed;
        }

        .header-table td {
            width: 50%;
            vertical-align: top;
            padding: 0;
        }

        .header-date {
            font-size: 11px;
            line-height: 1.2;
        }

        .header-date-left {
            text-align: left;
            padding-top: 10px;
        }

        .header-date-right {
            text-align: right;
            padding-top: 10px;
        }

        .header-logo-left {
            text-align: left;
        }

        .header-logo-right {
            text-align: right;
        }

        .header-logo-left img,
        .header-logo-right img {
            max-height: 70px;
            max-width: 200px;
        }

        .header-logo-row td {
            padding-bottom: 0;
        }

        .header-date-row td {
            padding-top: 6px;
        }

        .doc-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin: 34px 0 20px;
        }

        p {
            margin-bottom: 10px;
            text-align: justify;
        }

        .section-title {
            color: #2f6fad;
            font-weight: bold;
            font-size: 11px;
            margin: 16px 0 8px;
            text-transform: uppercase;
        }

        .field {
            margin-bottom: 5px;
            line-height: 1.5;
        }

        .field .lbl {
            font-weight: bold;
            text-transform: uppercase;
        }

        .field .val {
            text-transform: uppercase;
        }

        .policy-field {
            font-weight: bold;
            text-transform: uppercase;
        }

        .driver-field {
            font-weight: bold;
            text-transform: uppercase;
        }

        .sig-block {
            margin-top: 26px;
        }

        .sig-image {
            margin-bottom: 4px;
        }

        .sig-image img {
            max-height: 58px;
            max-width: 180px;
        }

        .sig-line {
            width: 180px;
            border-top: 1px solid #000;
            margin: 2px 0 6px;
        }

        .sig-name {
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .sig-title {
            margin-bottom: 2px;
        }

        .sig-company {
            font-weight: bold;
            text-transform: uppercase;
        }

        .footer {
            position: fixed;
            bottom: 24px;
            left: 42px;
            right: 42px;
            font-size: 9px;
            line-height: 1.45;
            text-align: center;
            color: #333;
        }
    </style>
</head>
<body>

@php
    $logoUri = $letterMeta['logo_uri'] ?? null;
    $signatureUri = $letterMeta['signature_uri'] ?? null;
    $logoAlign = $letterMeta['logo_align'] ?? 'left';
    $directorName = trim($letterMeta['director_intro_name'] ?? $company->director_name ?? '');
    $directorSalutation = $letterMeta['director_salutation'] ?? 'Mr.';
    $directorIntro = $directorName !== '' ? $directorSalutation.' '.$directorName : 'the Company Director';
@endphp

<table class="header-table" cellpadding="0" cellspacing="0">
    @if($logoAlign === 'right')
        {{-- Samore: logo top-right, date below on the left --}}
        <tr class="header-logo-row">
            <td style="width: 50%;"></td>
            <td class="header-logo-right" style="width: 50%; text-align: right;">
                @if($logoUri)
                    <img src="{{ $logoUri }}" alt="{{ $company->name }}">
                @endif
            </td>
        </tr>
        <tr class="header-date-row">
            <td style="width: 50%; text-align: left;">
                <div class="header-date header-date-left">Date: {{ $letterDate }}</div>
            </td>
            <td style="width: 50%;"></td>
        </tr>
    @else
        {{-- Proactive: logo top-left, date below on the right --}}
        <tr class="header-logo-row">
            <td class="header-logo-left" style="width: 50%; text-align: left;">
                @if($logoUri)
                    <img src="{{ $logoUri }}" alt="{{ $company->name }}">
                @endif
            </td>
            <td style="width: 50%;"></td>
        </tr>
        <tr class="header-date-row">
            <td style="width: 50%;"></td>
            <td style="width: 50%; text-align: right;">
                <div class="header-date header-date-right">Date: {{ $letterDate }}</div>
            </td>
        </tr>
    @endif
</table>

<div class="doc-title">Permission Letter</div>

<p>Hello Sir/Madam</p>

<p>
    I, {{ $directorIntro }} (Company Director, {{ $letterMeta['intro_company_short'] }}) confirm that the below vehicle
    is owned by the company {{ $letterMeta['owned_by_name'] }}.
</p>

<div class="section-title">Vehicle Details</div>
<div class="field"><span class="lbl">Make and Model:</span> <span class="val">{{ strtoupper($car->carModel->name ?? '') }}</span></div>
<div class="field"><span class="lbl">Registration No:</span> <span class="val">{{ strtoupper($car->registration) }}</span></div>
<div class="field"><span class="lbl">Colour:</span> <span class="val">{{ strtoupper($car->color ?? '') }}</span></div>
<div class="policy-field">{{ $letterMeta['policy_label'] }} {{ $policyNumber ?: '—' }}</div>

<div class="section-title">Contract Commencing From</div>
<div class="field"><span class="lbl">Commencing Date:</span> <span class="val">{{ $agreement->start_date->format('d.m.Y') }}</span></div>
<div class="field"><span class="lbl">Contract Ending Date:</span> <span class="val">{{ $contractEndingDate }}</span></div>

<p style="margin-top: 14px;">
    I have authorized and given permission to the following individual to use this vehicle on a
    temporary basis.
</p>

<div class="driver-field">Driver Name: MR {{ strtoupper($driver->full_name) }}</div>
<div class="driver-field">Driver License No: {{ strtoupper($driver->driver_license_number ?? '') }}</div>
<div class="driver-field">Driver Address: @include('backend.agreements._driver_address_pdf', ['driver' => $driver])</div>

<p style="margin-top: 14px;">
    The above-mentioned person is fully insured to use this vehicle for carriage of passenger and hire
    and reward insurance purposes.
</p>

<p>Please contact me if you required any further information.</p>

<p>Yours faithfully.</p>

<div class="sig-block">
    @if($signatureUri)
        <div class="sig-image">
            <img src="{{ $signatureUri }}" alt="Signature">
        </div>
    @endif
    <div class="sig-line"></div>
    <div class="sig-name">{{ $letterMeta['signatory_name'] }}</div>
    <div class="sig-title">Company Director</div>
    <div class="sig-company">{{ $letterMeta['owned_by_name'] }}</div>
</div>

<div class="footer">
    {!! $letterMeta['footer_html'] !!}
</div>

</body>
</html>
