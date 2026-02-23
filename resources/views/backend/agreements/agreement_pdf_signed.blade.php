{{-- resources/views/backend/agreements/agreement_pdf_signed.blade.php --}}

{{-- Replace the signature section styling and HTML --}}

    <!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Signed Hire Agreement - {{ $driver->full_name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.3;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .top-line {
            border-top: 2px solid #000;
            text-align: center;
            font-weight: bold;
            padding: 5px 0;
            margin-bottom: 10px;
        }

        .company-name {
            font-size: 14px;
            font-weight: bold;
            background-color: yellow;
            padding: 3px;
            margin: 5px 0;
            display: inline-block;
        }

        .company-email {
            font-size: 12px;
            font-weight: bold;
            background-color: yellow;
            padding: 3px;
            margin: 5px 0;
            display: inline-block;
        }

        .section-header {
            background-color: yellow;
            font-weight: bold;
            padding: 3px;
            margin: 15px 0 10px 0;
            display: inline-block;
        }

        .details-row {
            margin: 8px 0;
            display: table;
            width: 100%;
        }

        .details-item {
            display: table-cell;
            padding-right: 20px;
            vertical-align: top;
        }

        .field-label {
            font-weight: bold;
        }

        .field-value {
            border-bottom: 1px solid #000;
            min-height: 16px;
            padding-bottom: 2px;
            display: inline-block;
            min-width: 150px;
        }

        .conditions-header {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            text-align: center;
            font-weight: bold;
            padding: 10px;
            margin: 20px 0;
        }

        .conditions {
            margin: 15px 0;
        }

        .conditions ol {
            padding-left: 20px;
        }

        .conditions li {
            margin-bottom: 8px;
            text-align: justify;
        }

        .highlight {
            font-weight: bold;
        }

        .vehicle-condition {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            text-align: center;
            font-weight: bold;
            padding: 10px;
            margin: 20px 0;
        }

        .final-note {
            text-align: center;
            font-weight: bold;
            margin: 15px 0;
        }

        .signature-section {
            margin-top: 30px;
            text-align: center;
        }

        .signature-line {
            display: inline-block;
            width: 30%;
            text-align: center;
            margin: 0 1.5%;
            vertical-align: top;
        }

        /* ✅ FIXED: Signature container - signature line ke oper */
        .signature-container {
            position: relative;
            height: 50px;
            margin-bottom: 5px;
        }

        /* ✅ The signature image - positioned at bottom of container, above the line */
        .signature-image {
            max-width: 90%;
            max-height: 45px;
            display: block;
            margin: 0 auto;
            margin-bottom: 0;
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
        }

        /* ✅ The underline - appears right below signature */
        .signature-underline {
            border-bottom: 1px solid #000;
            width: 100%;
            position: absolute;
            bottom: 0;
        }

        .signed-badge {
            background-color: #28a745;
            color: white;
            padding: 2px 6px;
            font-size: 8px;
            border-radius: 3px;
            display: inline-block;
            margin-top: 3px;
        }

        .signature-info {
            font-size: 8px;
            color: #666;
            margin-top: 3px;
            line-height: 1.2;
        }
    </style>
</head>
<body>
<!-- Header Section -->
<div class="top-line">
    Hire Agreement - SIGNED COPY
</div>
<div class="top-line"></div>

<div class="header">
    <div class="company-name">{{ $company->name ?? 'SAMORE TRADERS LTD' }}</div>
    <br>
    <div class="company-email">{{ $company->email ?? 'samoretradersltd@gmail.com' }}</div>
</div>

<!-- Details Section - Two Columns -->
<div style="display: table; width: 100%; margin-bottom: 20px;">
    <!-- Left Column - Customer Details -->
    <div style="display: table-cell; width: 50%; vertical-align: top; padding-right: 20px;">
        <div class="section-header">Customer Details</div>

        <div class="details-row">
            <div class="details-item">
                <span class="field-label">Name:</span><br>
                <span class="field-value">{{ $driver->full_name }}</span>
            </div>
        </div>

        <div class="details-row">
            <div class="details-item">
                <span class="field-label">Licence N.O:</span><br>
                <span class="field-value">{{ $driver->driver_license_number ?? '' }}</span>
            </div>
        </div>

        <div class="details-row">
            <div class="details-item">
                <span class="field-label">Expires:</span><br>
                <span class="field-value">{{ $driver->driver_license_expiry_date ? \Carbon\Carbon::parse($driver->driver_license_expiry_date)->format('d/m/Y') : '' }}</span>
            </div>
        </div>

        <div class="details-row">
            <div class="details-item">
                <span class="field-label">PHD Licence No:</span><br>
                <span class="field-value">{{ $driver->phd_license_number ?? '' }}</span>
            </div>
        </div>

        <div class="details-row">
            <div class="details-item">
                <span class="field-label">DOB:</span><br>
                <span class="field-value">{{ $driver->dob ? \Carbon\Carbon::parse($driver->dob)->format('d/m/Y') : '' }}</span>
            </div>
        </div>

        <div class="details-row">
            <div class="details-item">
                <span class="field-label">Phone N.O:</span><br>
                <span class="field-value">{{ $driver->phone_number ?? '' }}</span>
            </div>
        </div>

        <div class="details-row">
            <div class="details-item">
                <span class="field-label">Address:</span><br>
                <span class="field-value">{{ $driver->address1 ?? '' }}</span>
            </div>
        </div>
    </div>

    <!-- Right Column - Vehicle Details -->
    <div style="display: table-cell; width: 50%; vertical-align: top; padding-left: 20px;">
        <div class="section-header">Vehicle Details</div>

        <div class="details-row">
            <div class="details-item">
                <span class="field-label">Make & Model:</span><br>
                <span class="field-value">{{ $car->carModel->name ?? '' }}</span>
            </div>
        </div>

        <div class="details-row">
            <div class="details-item">
                <span class="field-label">Vehicle REG:</span><br>
                <span class="field-value">{{ $car->registration }}</span>
            </div>
        </div>

        <div class="details-row">
            <div class="details-item">
                <span class="field-label">Mileage Out:</span><br>
                <span class="field-value">{{ number_format($agreement->mileage_out ?? 0) }}</span>
            </div>
        </div>

        <div class="details-row">
            <div class="details-item">
                <span class="field-label">Date/Time Out:</span><br>
                <span class="field-value">{{ $agreement->start_date->format('d/m/Y H:i') }}</span>
            </div>
        </div>

        <div class="details-row">
            <div class="details-item">
                <span class="field-label">Date/Time Due:</span><br>
                <span class="field-value">{{ $agreement->end_date->format('d/m/Y H:i') }}</span>
            </div>
        </div>

        <div class="details-row">
            <div class="details-item">
                <span class="field-label">Hire Charge:</span><br>
                <span class="field-value">£{{ number_format($agreement->agreed_rent, 2) }}</span>
            </div>
        </div>

        <div class="details-row">
            <div class="details-item">
                <span class="field-label">Deposit:</span><br>
                <span class="field-value">£{{ number_format($agreement->deposit_amount, 2) }}</span>
            </div>
        </div>

        <div class="details-row">
            <div class="details-item">
                <span class="field-label">Vehicle return date:</span><br>
                <span class="field-value">{{ $agreement->end_date->format('d/m/Y') }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Conditions Header -->
<div class="conditions-header">
    Conditions of Hire Agreement: MIN 4 WEEKS CONTRACT £1000 ACCESS IN CASE OF ACCIDENT
</div>

<!-- Conditions List -->
<div class="conditions">
    <ol>
        <li>I am responsible for insuring the vehicle.</li>

        <li>I am over 21 and less than 86 years of age, suffer from no physical or mental impairment affecting my ability to drive, and have held a valid UK licence applicable to the vehicle for at least 12 months. I have not accumulated more than 9 penalty points in the last 3 years, nor have I been disqualified from driving in the last 5 years. I will pay any charges for the loss/damage as a result of not using the correct fuel <span class="highlight">(This is a {{ $car->fuel_type ?? 'Petrol' }} Vehicle)</span>.</li>

        <li>I will accept full responsibility for any uninsured loss or damage however such loss or damage is caused. In the event of an accident I will report the incident immediately to {{ $company->name ?? 'SAMORE TRADERS LTD' }} and will complete an accident report form with {{ $company->name ?? 'SAMORE TRADERS LTD' }}. Any necessary repair work will be carried out by {{ $company->name ?? 'SAMORE TRADERS LTD' }} and paid by me upon receipt of the invoice.</li>

        <li>I accept that any motoring or traffic offences, toll charges, congestion charges, penalties and fines arising in relation to the vehicle for the duration of the loan are my sole responsibility under the Road Traffic Regulations Act 1984, the Road Traffic Offenders Act 1988, and/or any subsequent relevant legislation. I will indemnify {{ $company->name ?? 'SAMORE TRADERS LTD' }} forthwith for any penalties, fines, legal fees, costs, interest or other charges paid by them in relation to any such motoring or traffic offences or toll charges. I hereby irrevocably consent to any such charges including an administration fee of £35 being charged to me.</li>

        <li>I agree the vehicle is only insured to be driven by the person(s) named above who have signed this form and will only be used for social, domestic and pleasure including commuting by the insured to a permanent place of work. For the carriage of passengers or goods for hire and reward by prior appointment only provided (private hire) such use complies with the laws and regulations of the appropriate licensing authority. I will indemnify {{ $company->name ?? 'SAMORE TRADERS LTD' }} in full against any and all claims, costs and expenses arising out of the driving of the vehicle by any other persons.</li>

        <li>I will not enter into any agreement with any third party for further hire or loan of the vehicle.</li>

        <li>I understand that the vehicle must not be modified in any way.</li>

        <li>I understand it is my responsibility to check oil and water during the hire period. Failure to do so will result in charges.</li>

        <li>If any radio equipment is installed it must not be affixed to the vehicle i.e. screwed on brackets.</li>

        <li>If any insurance or third party pay out all payments must be made payable to "{{ $company->name ?? 'SAMORE TRADERS LTD' }}".</li>

        <li>The vehicle remains property of {{ $company->name ?? 'SAMORE TRADERS LTD' }} and can be taken back anytime without the permission of the hirer.</li>

        <li>The vehicle must be secured when parked and security features used correctly.</li>

        <li>If I fail to make rental, traffic offences or vehicle damage payments, I authorise {{ $company->name ?? 'SAMORE TRADERS LTD' }} or a sub-contractor to clamp and remove any vehicle, goods and chattels in my possession/custody at any time without notice to clear my debt. I understand I will incur further charges.</li>

        <li>I agree that if I am in breach of my contract then {{ $company->name ?? 'SAMORE TRADERS LTD' }} has a right of recovery against me.</li>

        <li>I also understand that this agreement can be terminated anytime by either me or {{ $company->name ?? 'SAMORE TRADERS LTD' }}.</li>

        <li>If car is returned before defined period then {{ $company->name ?? 'SAMORE TRADERS LTD' }} has the right to not refund the deposit.</li>
    </ol>
</div>

<div class="final-note">
    DEPOSITS WILL BE RETURNED WITHIN 4 WORKING DAYS WHEN THE HIRE VEHICLE IS RETURNED.
</div>

<!-- Vehicle Condition Section -->
<div class="vehicle-condition">
    Vehicle Condition
</div>

<div style="text-align: center; margin: 20px 0;">
    <p><strong>CHECK BEFORE TAKING VEHICLE IF ANY TYRES OR BULBS ARE REQUIRED, AS NONE WILL BE PROVIDED AT A LATER DATE.</strong></p>

    @if($agreement->condition_report)
        <p><strong>Condition Report:</strong> {{ $agreement->condition_report }}</p>
    @endif

    <p style="margin-top: 20px;">I have read and understand ALL of the above conditions of hire and I agree to abide by them. I agree with the above vehicle condition report and I agree the vehicle is ONLY insured to be driven by the person(s) named above who have signed this form.</p>
</div>

<!-- ✅ SIGNATURE SECTION - FIXED POSITIONING -->
<div class="signature-section">
    {{-- ✅ CLIENT SIGNATURE - Signature line ke OPER --}}
    <div class="signature-line">
        <div class="signature-container">
            @if(isset($signature_image) && $signature_image)
                {{-- ✅ Signature image positioned at bottom, touching the line --}}
                <img src="{{ $signature_image }}" class="signature-image" alt="Client Signature">
            @endif
            {{-- ✅ Underline appears right below signature --}}
            <div class="signature-underline"></div>
        </div>
        <strong>Client</strong>
        @if(isset($signature_image) && $signature_image)
{{--
            <div class="signed-badge">✓ SIGNED</div>
--}}
            <div class="signature-info">
                {{ $driver->full_name }}<br>
                {{ now()->format('d/m/Y H:i') }}
            </div>
        @endif
    </div>

    {{-- Company Signature --}}
    <div class="signature-line">
        <div class="signature-container">
            <div class="signature-underline"></div>
        </div>
        <strong>{{ $company->name ?? 'SAMORE TRADERS LTD' }}</strong>
    </div>

    {{-- Date --}}
    <div class="signature-line">
        <div class="signature-container">
            <div class="signature-underline"></div>
        </div>
        <strong>Date</strong>
    </div>
</div>

<div style="text-align: center; margin-top: 30px; font-size: 10px; color: #666; border-top: 1px solid #ddd; padding-top: 10px;">
    <strong>Electronically Signed Document</strong><br>
    Agreement ID: {{ $agreement->id }} | Signed on: {{ now()->format('d/m/Y H:i:s') }}<br>
    This is a legally binding electronic signature
</div>
</body>
</html>
