<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Hire Agreement - {{ $driver->full_name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            font-size: 10.5px;
            line-height: 1.45;
            color: #000;
            padding: 25px 30px;
        }

        /* ── BIG TITLE ── */
        .doc-title {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            border: 1px solid #ccc;
            background-color: #f0f0f0;
            padding: 8px;
            margin-bottom: 14px;
        }

        /* ── COMPANY BLOCK ── */
        .company-block {
            margin-bottom: 12px;
        }
        .company-block .co-name {
            font-size: 12px;
            font-weight: bold;
        }
        .company-block .co-email {
            font-size: 11px;
        }

        /* ── TWO COLUMN DETAILS ── */
        .details-wrap {
            display: table;
            width: 100%;
            margin-bottom: 6px;
        }
        .col-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 15px;
        }
        .col-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-left: 10px;
        }

        /* section label - plain bold, no background */
        .col-title {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 5px;
        }

        /* each field row - label: value on same line */
        .field {
            margin-bottom: 3px;
            font-size: 10.5px;
        }
        .field .lbl { font-weight: bold; }

        /* PREVIOUS CAR */
        .prev-car {
            text-align: right;
            font-weight: bold;
            font-size: 10.5px;
            margin-bottom: 5px;
        }

        /* ── CONDITIONS HEADER ── gray bold box like sample */
        .cond-header {
            background-color: #b0b0b0;
            font-weight: bold;
            font-style: italic;
            font-size: 11px;
            padding: 5px 8px;
            margin: 8px 0;
            border: 1px solid #888;
        }

        /* ── CONDITIONS LIST ── */
        .cond-list {
            padding-left: 20px;
            margin: 6px 0;
        }
        .cond-list li {
            margin-bottom: 4px;
            text-align: justify;
            font-size: 10.5px;
        }

        /* DEPOSITS note */
        .deposit-note {
            text-align: center;
            font-size: 10.5px;
            margin: 8px 0;
        }

        /* ── VEHICLE CONDITION ── gray left-aligned header */
        .vc-header {
            background-color: #c8c8c8;
            font-weight: bold;
            font-style: italic;
            font-size: 11px;
            padding: 4px 8px;
            margin: 8px 0 6px 0;
            display: inline-block;
            border: 1px solid #999;
        }

        /* car diagram grid */
        .car-grid {
            width: 75%;
            margin: 6px auto;
            border-collapse: collapse;
        }
        .car-grid td {
            width: 50%;
            text-align: center;
            padding: 4px;
            vertical-align: middle;
        }
        .car-grid img {
            max-width: 100%;
            max-height: 90px;
        }
        .car-placeholder {
            border: 1px dashed #bbb;
            height: 80px;
            line-height: 80px;
            font-size: 9px;
            color: #aaa;
        }

        /* check + agree text */
        .check-text {
            font-size: 10.5px;
            font-weight: bold;
            margin: 8px 0 5px 0;
        }
        .agree-text {
            font-size: 10.5px;
            text-align: justify;
            margin-bottom: 14px;
        }

        /* ── SIGNATURE SECTION ── */
        .sig-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .sig-table td {
            width: 33.33%;
            text-align: center;
            padding: 0 8px;
            vertical-align: bottom;
        }
        .sig-line {
            border-bottom: 1px solid #000;
            height: 38px;
            margin-bottom: 3px;
        }
        .sig-label {
            font-size: 10.5px;
            font-weight: bold;
        }
    </style>
</head>
<body>

{{-- ── BIG TITLE ── --}}
<div class="doc-title">Hire Agreement</div>

{{-- ── COMPANY ── --}}
<div class="company-block">
    <div class="co-name">{{ strtoupper($company->name ?? 'SAMORE TRADERS LTD') }}</div>
    <div class="co-email">{{ $company->email ?? '' }}</div>
</div>

{{-- ── TWO COLUMNS ── --}}
<div class="details-wrap">
    {{-- LEFT: Customer Details --}}
    <div class="col-left">
        <div class="col-title">Customer Details</div>
        <div class="field"><span class="lbl">Name:</span> MR {{ strtoupper($driver->full_name) }}</div>
        <div class="field"><span class="lbl">Licence N.O:</span> {{ $driver->driver_license_number ?? '' }}</div>
        <div class="field"><span class="lbl">Expires:</span> {{ $driver->driver_license_expiry_date ? \Carbon\Carbon::parse($driver->driver_license_expiry_date)->format('d.m.Y') : '' }}</div>
        <div class="field"><span class="lbl">PHD Licence No:</span> {{ $driver->phd_license_number ?? '' }}</div>
        <div class="field"><span class="lbl">DOB:</span> {{ $driver->dob ? \Carbon\Carbon::parse($driver->dob)->format('d.m.Y') : '' }}</div>
        <div class="field"><span class="lbl">Phone N.O:</span> {{ $driver->phone_number ?? '' }}</div>
        <div class="field"><span class="lbl">ADDRESS:</span> {{ $driver->address1 ?? '' }}{{ isset($driver->address2) && $driver->address2 ? ', '.$driver->address2 : '' }}{{ isset($driver->post_code) && $driver->post_code ? ', '.$driver->post_code : '' }}</div>
    </div>

    {{-- RIGHT: Vehicle Details --}}
    <div class="col-right">
        <div class="col-title">Vehicle Details</div>
        <div class="field"><span class="lbl">Make &amp; Model:</span> {{ strtoupper($car->carModel->name ?? '') }}</div>
        <div class="field"><span class="lbl">Vehicle REG:</span> {{ strtoupper($car->registration) }}</div>
        <div class="field"><span class="lbl">Mileage Out:</span> {{ $agreement->mileage_out ? number_format($agreement->mileage_out) : '' }}</div>
        <div class="field"><span class="lbl">Date/Time Out:</span> {{ $agreement->start_date->format('d.m.Y') }} {{ $agreement->start_date->format('H:i') }} HRS</div>
        <div class="field"><span class="lbl">Date/Time Due:</span> {{ $agreement->end_date->format('d.m.Y') }} {{ $agreement->end_date->format('H:i') }} HRS</div>
        <div class="field"><span class="lbl">Hire Charge:</span> £{{ number_format($agreement->agreed_rent, 0) }} P/W</div>
        <div class="field"><span class="lbl">Deposit:</span> £{{ number_format($agreement->deposit_amount, 0) }}</div>
        <div class="field"><span class="lbl">Vehicle Return date:</span> {{ $agreement->end_date->format('d/m/Y') }}</div>
    </div>
</div>

{{-- ── CONDITIONS HEADER ── --}}
<div class="cond-header">
    Conditions of Hire Agreement: MIN 4 WEEKS CONTRACT &nbsp; £1000 EXCESS IN CASE OF ACCIDENT
</div>

{{-- ── CONDITIONS LIST ── --}}
<ol class="cond-list">
    <li>I am responsible for insuring the vehicle.</li>
    <li>I am over 21 and less than 86 years of age, suffer from the no physical or mental impairment affecting my ability to drive, and have held a valid UK licence applicable to the vehicle for at least 12 months. I have not accumulated more than 9 penalty points in the last 3 years, nor have I been disqualified from the driving in the last 5 years. I will pay any charges for the loss/damage as a result of not using the correct fuel (<strong>This is a {{ $car->fuel_type ?? 'Petrol' }} Vehicle</strong>).</li>
    <li>I will accept full responsibility for any uninsured loss or damage however such loss or damage is caused. In the event of an accident I will report the incident immediately to {{ $company->name ?? 'SAMORE TRADERS LTD' }} and will complete an accident report form with {{ $company->name ?? 'SAMORE TRADERS LTD' }}. Any necessary repair work will be carried out by {{ $company->name ?? 'SAMORE TRADERS LTD' }} and paid by me upon receipt of the invoice.</li>
    <li>I accept that any motoring or traffic offences, toll charges, congestion charges, penalties and fines arising in relation to the vehicle for the duration of the loan are my sole responsibility under the Road Traffic Regulations Act 1984, the Road Traffic Offenders Act 1988, and/or any subsequent relevant legislation. I will indemnify {{ $company->name ?? 'SAMORE TRADERS LTD' }} forthwith for any penalties, fines, legal fees, costs, interest or other charges paid by them in relation to any such motoring or traffic offences or toll charges. I hereby irrevocably consent to any such charges including an administration fee of £35 being charged to me.</li>
    <li>I agree the vehicle is only insured to be driven by the person(s) named above have signed this form and will only be used for social, domestic and pleasure including commuting by the insured to a permanent place of work. For the carriage of passengers or goods of hire and reward by prior appointment only provided (private hire) such use complies with the laws and regulations of the appropriate licensing authority. I will indemnify {{ $company->name ?? 'SAMORE TRADERS LTD' }} in full against all claims, costs and expenses arising out of the driving of the vehicle by any other persons.</li>
    <li>I will not enter into any agreement with any third party to further hire or loan of the vehicle.</li>
    <li>I understand that the vehicle must not be modified in any way.</li>
    <li>I understand it is my responsibility to check oil and water during the hire period. Failure to do so will result in charges.</li>
    <li>If any radio equipment is installed it must not be affixed to the vehicle i.e. screwed on brackets.</li>
    <li>If any insurance or third party pay out all payments must be made payable to "{{ $company->name ?? 'SAMORE TRADERS LTD' }}".</li>
    <li>The vehicle remains property of {{ $company->name ?? 'SAMORE TRADERS LTD' }} and can be taken back anytime without the permission of the hirer.</li>
    <li>The vehicle must be secured when parked and security features used correctly.</li>
    <li>If I fail to make rental, traffic offences or vehicle damage payments, I authorise {{ $company->name ?? 'SAMORE TRADERS LTD' }} or a sub-contractor to clamp and remove any vehicle, goods and chattels in my possession/custody at any time without notice to clear my debt. I understand I will incur further charges.</li>
    <li>I agree that if I am in breach of my contract then {{ $company->name ?? 'SAMORE TRADERS LTD' }} had a right of recovery against me.</li>
    <li>I also understand that this agreement can be terminated anytime by {{ $company->name ?? 'SAMORE TRADERS LTD' }}.</li>
    <li>If car will be closed before defined period than {{ $company->name ?? 'SAMORE TRADERS LTD' }} have right to not refund the deposit.</li>
</ol>

<div class="deposit-note">
    DEPOSITS WILL BE REFUNDED AFTER 5 WORKING DAYS WHEN THE HIRE VEHICLE IS RETURNED.
</div>

{{-- ── VEHICLE CONDITION ── --}}
<div class="vc-header">Vehicle Condition</div>

{{-- Car Diagram 2x2 --}}
@if(file_exists(public_path('images/car_diagram.png')))
    <div style="text-align:center; margin: 6px 0;">
        <img src="{{ public_path('images/car_diagram.png') }}" style="max-width:75%; height:auto;" alt="Vehicle Condition">
    </div>
@else
    <table class="car-grid">
        <tr>
            <td><div class="car-placeholder">Front View</div></td>
            <td><div class="car-placeholder">Rear View</div></td>
        </tr>
        <tr>
            <td><div class="car-placeholder">Left Side</div></td>
            <td><div class="car-placeholder">Right Side</div></td>
        </tr>
    </table>
@endif

@if($agreement->condition_report)
    <div style="font-size:10.5px; margin: 5px 0;"><strong>Condition Report:</strong> {{ $agreement->condition_report }}</div>
@endif

<div class="check-text">
    CHECK BEFORE TAKING VEHICLE IN ANY TYRES OR BULBS ARE REQUIRED, AS NONE WILL BE PROVIDED AT A LATER DATE.
</div>

<div class="agree-text">
    I have read and understand ALL the above conditions of hire and I agree to abide by them. I agree with the above vehicle condition report and I agree the vehicle is ONLY to be driven by the person(s) named above who have signed this form.
</div>

{{-- ── SIGNATURE SECTION ── --}}
<table class="sig-table">
    <tr>
        <td>
            <div class="sig-line"></div>
            <div class="sig-label">Client</div>
        </td>
        <td>
            <div class="sig-line"></div>
            <div class="sig-label">{{ strtoupper($company->name ?? 'SAMORE TRADERS LTD') }}</div>
        </td>
        <td>
            <div class="sig-line"></div>
            <div class="sig-label">Date</div>
        </td>
    </tr>
</table>

<div style="text-align:center; margin-top:12px; font-size:9px; color:#666;">
    Agreement ID: {{ $agreement->id }} | Generated: {{ \Carbon\Carbon::now()->format('d/m/Y') }}
</div>

{{-- ================================================================
     PAGE 2 — STATEMENT OF UNDERSTANDING
     ================================================================ --}}
<div style="page-break-before:always; padding:25px 30px; font-family:Arial,sans-serif; font-size:10.5px; line-height:1.45; color:#000;">

    <div style="background-color:#b0b0b0; font-weight:bold; font-style:italic; font-size:13px; text-align:center; padding:6px 8px; margin-bottom:16px; border:1px solid #888;">
        STATEMENT OF UNDERSTANDING
    </div>

    <div style="margin-bottom:12px;">
        <p style="font-weight:bold; margin-bottom:4px;">EXCESS FEE:</p>
        <p style="text-align:justify; margin-left:15px;">I HEREBY ACKNOWLEDGE AND AGREE THAT IN THE EVENT OF A MOTOR ACCIDENT, I AM RESPONSIBLE FOR PAYING THE APPLICABLE INSURANCE EXCESS FEE IN ORDER TO PROCEED WITH THE CLAIM PROCESS. IF, UPON CONCLUSION OF THE INVESTIGATION, THE ACCIDENT IS DETERMINED TO BE NON-FAULT, THE EXCESS FEE PAID BY ME SHALL BE REIMBURSED IN FULL. HOWEVER, IF THE OUTCOME OF THE CLAIM ESTABLISHES THAT I AM AT FAULT, I ACKNOWLEDGE THAT I SHALL HAVE NO RIGHT OR ENTITLEMENT TO RECOVER OR REQUEST REIMBURSEMENT OF THE EXCESS FEE, AS IT WILL BE DEEMED DULY PAYABLE. FURTHERMORE, I UNDERSTAND AND AGREE THAT IN CASES WHERE THE ACCIDENT INVOLVES AN UNINSURED THIRD PARTY, A STOLEN VEHICLE, OR IF THE VEHICLE I AM DRIVING IS STOLEN, I SHALL BE LIABLE TO PAY THE FULL EXCESS AMOUNT REGARDLESS OF FAULT, AS SUCH CIRCUMSTANCES MAY PREVENT RECOVERY OF COSTS FROM THE THIRD PARTY.</p>
    </div>

    <div style="margin-bottom:12px;">
        <p style="font-weight:bold; margin-bottom:4px;">TYRES &amp; HEADLIGHT BULB:</p>
        <p style="text-align:justify; margin-left:15px;">I ACKNOWLEDGE THAT UPON RENTING OUT CAR I WILL CHECK AND AGREE TO THE CONDITION OF TYRES AND BULBS. ONCE TAKEN THE CAR, IT WILL BECOME MY RESPONSIBILITY TO CHANGE THE TYRES AND BULBS.</p>
    </div>

    <div style="margin-bottom:12px;">
        <p style="font-weight:bold; margin-bottom:4px;">CAR WASH:</p>
        <p style="text-align:justify; margin-left:15px;">I ACKNOWLEDGE THAT UPON RETURNING THE CAR BACK, I WILL MAKE SURE TO RETURN THE CAR WASHED AND VACUUMED AND IN IMMACULATE CONDITION OTHERWISE THE COMPANY RESERVE THE RIGHT TO CHARGE ME £30 POUNDS.</p>
    </div>

    <div style="margin-bottom:12px;">
        <p style="font-weight:bold; margin-bottom:4px;">NOTICE:</p>
        <p style="text-align:justify; margin-left:15px;">I ACKNOWLEDGE THAT I AM BOUND TO GIVE ONE WEEK'S NOTICE AFTER MY MINIMUM CONTRACTUAL TERM HAS FINISHED IN ORDER TO RETURN THE VEHICLE. I FURTHER ACKNOWLEDGE THAT THIS NOTICE MUST BE GIVEN ON THE DUE DATE OF MY RENT PAYMENT; FAILURE TO DO SO WILL RESULT IN THE FORFEITURE OF MY DEPOSIT FOR CLOSING WITHOUT NOTICE. I ALSO UNDERSTAND AND ACCEPT THAT VEHICLE RENTALS ARE CHARGED ON A WEEKLY BASIS, NOT DAILY, AND ANY CLOSURE WILL BE CHARGED AS A FULL WEEK, REGARDLESS OF THE NUMBER OF DAYS REMAINING.</p>
    </div>

    <div style="margin-bottom:12px;">
        <p style="font-weight:bold; margin-bottom:4px;">MOT AND PLATES APPOINTMENT:</p>
        <p style="text-align:justify; margin-left:15px;">I ACKNOWLEDGE THAT WHENEVER THE VEHICLE REQUIRED MOT OR COUNCIL APPOINTMENT FOR RENEWAL OF PLATES, I AM BOUND TO BRING THE VEHICLE ON THE APPOINTMENT TIME TO THE OFFICE.</p>
    </div>

    <div style="margin-bottom:16px;">
        <p style="font-weight:bold; margin-bottom:4px;">DRIVING LICENCE CHANGES OR CONVICTIONS:</p>
        <p style="text-align:justify; margin-left:15px; margin-bottom:5px;">I ACKNOWLEDGE THAT I AM <strong>OBLIGED TO IMMEDIATELY INFORM THE COMPANY</strong> OF ANY <strong>CHANGES</strong> TO MY DRIVING LICENCE STATUS, INCLUDING BUT NOT LIMITED TO:</p>
        <ul style="margin-left:35px; text-align:justify;">
            <li style="margin-bottom:4px;"><strong>ENDORSEMENTS, PENALTY POINTS, DISQUALIFICATIONS, OR DRIVING CONVICTIONS</strong> OF ANY KIND.</li>
            <li style="margin-bottom:4px;"><strong>SUSPENSION, REVOCATION, OR RESTRICTION</strong> OF MY DRIVING LICENCE. FAILURE TO NOTIFY THE COMPANY OF SUCH CHANGES WILL RESULT IN <strong>IMMEDIATE TERMINATION OF THIS AGREEMENT AND THE RENTAL CONTRACT</strong>, AND I MAY BE REQUIRED TO <strong>RETURN THE VEHICLE WITHOUT NOTICE</strong>.</li>
        </ul>
    </div>

    <div style="border-top:1px solid #000; padding-top:12px;">
        <table style="width:100%; font-size:10.5px; border-collapse:collapse;">
            <tr>
                <td style="width:50%; padding:4px 10px 4px 0;"><strong>CAR REG:</strong> {{ $car->registration }}</td>
                <td style="width:50%; padding:4px 0 4px 10px;"><strong>NAME: MR</strong> {{ $driver->full_name }}</td>
            </tr>
            <tr>
                <td style="padding:4px 10px 4px 0;"><strong>DRIVING LICENCE:</strong> {{ $driver->driver_license_number ?? '' }}</td>
                <td style="padding:4px 0 4px 10px;"><strong>N.I NUMBER:</strong> {{ $driver->ni_number ?? '' }}</td>
            </tr>
            <tr>
                <td colspan="2" style="padding:4px 0;"><strong>EMAIL:</strong> {{ $driver->email ?? '' }}</td>
            </tr>
        </table>

        <table style="width:100%; margin-top:18px; border-collapse:collapse;">
            <tr>
                <td style="width:55%; padding-right:15px; vertical-align:bottom;">
                    <strong>SIGN:</strong>
                    <span style="border-bottom:1px solid #000; display:inline-block; width:72%; height:28px;"></span>
                </td>
                <td style="width:45%; vertical-align:bottom;">
                    <strong>DATE:</strong>
                    <span style="border-bottom:1px solid #000; display:inline-block; width:68%; height:28px;"></span>
                </td>
            </tr>
        </table>
    </div>

    <div style="text-align:center; margin-top:16px; font-size:9px; color:#666;">
        Agreement ID: {{ $agreement->id }} | Statement of Understanding
    </div>
</div>

{{-- ================================================================
     PAGE 3 — STATEMENT OF LIABILITY
     ================================================================ --}}
<div style="page-break-before:always; padding:25px 30px; font-family:Arial,sans-serif; font-size:10.5px; line-height:1.45; color:#000;">

    <div style="background-color:#b0b0b0; font-weight:bold; font-style:italic; font-size:13px; text-align:center; padding:6px 8px; margin-bottom:16px; border:1px solid #888;">
        STATEMENT OF LIABILITY
    </div>

    <p style="text-align:justify; margin-bottom:12px;">I ACCEPT THAT ANY MOTORING OR TRAFFIC OFFENCES, TOLL CHARGES, CONGESTION CHARGES, PENALTIES, ANY PARKING CHARGES, BUS LANE CONTRAVENTION AND FINE ARISING IN RELATION TO THE VEHICLE FOR THE DURATION OF THE LOAN ARE MY SOLE RESPONSIBILITY UNDER THE ROAD TRAFFIC REGULATION ACT 1984, THE ROAD TRAFFIC OFFENDER ACT 1988, AND/OR ANY SUBSEQUENT RELEVANT LEGISLATION.</p>

    <p style="text-align:justify; margin-bottom:20px;">ANY BREAKDOWN SERVICE IS REQUIRED ON VEHICLE IS DRIVER RESPONSIBILITY.</p>

    <p style="font-weight:bold; margin-bottom:10px;">DETAILS ARE AS FOLLOW:</p>

    <table style="width:100%; font-size:10.5px; border-collapse:collapse;">
        <tr>
            <td style="padding:5px 0; width:36%;"><strong>HIRER NAME: MR</strong></td>
            <td style="padding:5px 0; border-bottom:1px solid #000;">{{ $driver->full_name }}</td>
        </tr>
        <tr>
            <td style="padding:5px 0;"><strong>HIRER ADDRESS:</strong></td>
            <td style="padding:5px 0; border-bottom:1px solid #000;">{{ $driver->address1 ?? '' }}{{ isset($driver->address2) && $driver->address2 ? ', '.$driver->address2 : '' }}{{ isset($driver->post_code) && $driver->post_code ? ', '.$driver->post_code : '' }}</td>
        </tr>
        <tr>
            <td style="padding:5px 0;"><strong>MAKE AND MODEL:</strong></td>
            <td style="padding:5px 0; border-bottom:1px solid #000;">{{ strtoupper($car->carModel->name ?? '') }}</td>
        </tr>
        <tr>
            <td style="padding:5px 0;"><strong>VEHICLE REGISTRATION:</strong></td>
            <td style="padding:5px 0; border-bottom:1px solid #000;">{{ strtoupper($car->registration) }}</td>
        </tr>
        <tr>
            <td style="padding:5px 0;"><strong>DATE/TIME OUT:</strong></td>
            <td style="padding:5px 0; border-bottom:1px solid #000;">{{ $agreement->start_date->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td style="padding:5px 0;"><strong>DATE/TIME DUE:</strong></td>
            <td style="padding:5px 0; border-bottom:1px solid #000;">{{ $agreement->end_date->format('d/m/Y H:i') }}</td>
        </tr>
    </table>

    <p style="margin-top:18px; font-weight:bold; text-align:center;">I HAVE READ AND HAPPY TO SIGN FOR THIS STATEMENT OF LIABILITY.</p>

    <div style="text-align:center; margin-top:22px;">
        <div style="display:inline-block; width:42%; text-align:center;">
            <div style="border-bottom:1px solid #000; height:36px; margin-bottom:4px;"></div>
            <strong>CLIENT SIGNATURE</strong>
        </div>
    </div>

    <div style="text-align:center; margin-top:22px; font-size:9px; color:#666;">
        Agreement ID: {{ $agreement->id }} | Statement of Liability
    </div>
</div>

</body>
</html>
