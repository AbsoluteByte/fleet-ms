<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Agreement Financial Summary — #{{ $agreement->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 16px 0 6px; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
        .meta { margin-bottom: 12px; color: #555; }
        table.details { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.details td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            vertical-align: top;
        }
        table.details td.label {
            width: 32%;
            background: #f8f8f8;
            font-weight: bold;
        }
        table.summary { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.summary td {
            border: 1px solid #ddd;
            padding: 8px;
            width: 50%;
            vertical-align: top;
        }
        table.summary small { color: #777; display: block; margin-bottom: 2px; }
        table.summary strong { font-size: 13px; }
        table.entries { width: 100%; border-collapse: collapse; }
        table.entries th, table.entries td {
            border: 1px solid #ccc;
            padding: 5px 6px;
            text-align: left;
            vertical-align: top;
        }
        table.entries th { background: #f3f3f3; font-size: 10px; }
        .text-success { color: #28c76f; }
        .text-danger { color: #ea5455; }
        .text-muted { color: #888; }
        .row-line { margin-bottom: 4px; }
        .row-line strong { float: right; }
        .footer { margin-top: 16px; font-size: 9px; color: #888; }
    </style>
</head>
<body>
    <h1>Agreement Financial Summary</h1>
    <div class="meta">
        <strong>{{ $company?->name ?? 'Company' }}</strong><br>
        Agreement #{{ $agreement->id }}
        @if($agreement->driver)
            — {{ $agreement->driver->full_name }}
        @endif
        <br>
        Generated {{ $generatedAt }}
    </div>

    <h2>Agreement Details</h2>
    <table class="details">
        <tr>
            <td class="label">Driver</td>
            <td>{{ $agreement->driver?->full_name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Vehicle</td>
            <td>
                {{ $agreement->car?->registration ?? '—' }}
                @if($agreement->car?->carModel)
                    — {{ $agreement->car->carModel->name }}
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Status</td>
            <td>{{ $agreement->status?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Start Date</td>
            <td>{{ $agreement->start_date?->format('d M Y H:i') ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">End Date</td>
            <td>{{ $agreement->end_date?->format('d M Y') ?? '—' }}</td>
        </tr>
        @if($agreement->closing_date)
            <tr>
                <td class="label">Closing Date</td>
                <td>{{ $agreement->closing_date->format('d M Y H:i') }}</td>
            </tr>
        @endif
        @if($agreement->termination_notice_date)
            <tr>
                <td class="label">Termination Notice</td>
                <td>{{ $agreement->termination_notice_date->format('d M Y') }}</td>
            </tr>
        @endif
        @if($agreement->termination_available_from_date)
            <tr>
                <td class="label">Car Available From</td>
                <td>{{ $agreement->termination_available_from_date->format('d M Y') }}</td>
            </tr>
        @endif
        <tr>
            <td class="label">Agreed Rent</td>
            <td>£{{ number_format((float) $agreement->agreed_rent, 2) }} ({{ ucfirst($agreement->collection_type ?? 'weekly') }})</td>
        </tr>
        <tr>
            <td class="label">Deposit Amount</td>
            <td>£{{ number_format((float) $agreement->deposit_amount, 2) }}</td>
        </tr>
    </table>

    <h2>Financial Summary</h2>
    <table class="summary">
        <tr>
            <td>
                <small>Total Paid</small>
                <strong class="text-success">£{{ number_format($agreement->total_paid, 2) }}</strong>
            </td>
            <td>
                <small>Outstanding</small>
                <strong class="text-danger">£{{ number_format($agreement->total_outstanding, 2) }}</strong>
            </td>
        </tr>
    </table>

    @if($agreement->hasConfiguredDiscount())
        <div style="margin-bottom: 12px;">
            <strong>Discount:</strong>
            {{ $agreement->discount_type === 'percentage'
                ? rtrim(rtrim(number_format((float) $agreement->discount_value, 2), '0'), '.').'%'
                : '£'.number_format((float) $agreement->discount_value, 2) }}
            ({{ $agreement->discount_is_one_time ? 'One-time' : 'Recurring' }})
            — Rent after discount: £{{ number_format($agreement->discounted_rent, 2) }}
        </div>
    @endif

    <div style="margin-bottom: 12px;">
        <div class="row-line">
            <span>Deposit Amount</span>
            <strong>£{{ number_format((float) $agreement->deposit_amount, 2) }}</strong>
        </div>

        <strong>Deductions</strong>
        @if($agreement->deductions->isEmpty())
            <div class="text-muted">No deductions.</div>
        @else
            @foreach($agreement->deductions as $deduction)
                <div class="row-line">
                    <span>{{ $deduction->notes ?: 'Deduction' }}</span>
                    <strong class="text-danger">−£{{ number_format((float) $deduction->amount, 2) }}</strong>
                </div>
            @endforeach
        @endif
        <div class="row-line">
            <span>Deduction total</span>
            <strong>£{{ number_format($agreement->deductions_total, 2) }}</strong>
        </div>
        <div class="row-line">
            <span>Remaining Deposit</span>
            <strong class="text-success">£{{ number_format(max((float) $agreement->deposit_amount - $agreement->deductions_total, 0), 2) }}</strong>
        </div>

        <strong style="display: block; margin-top: 8px;">Damages</strong>
        @if($agreement->additionalCharges->isEmpty())
            <div class="text-muted">No damages recorded.</div>
        @else
            @foreach($agreement->additionalCharges as $charge)
                <div class="row-line">
                    <span>
                        {{ $charge->typeLabel() }}
                        @if($charge->notes)
                            — {{ $charge->notes }}
                        @endif
                    </span>
                    <strong class="text-danger">£{{ number_format((float) $charge->amount, 2) }}</strong>
                </div>
            @endforeach
            <div class="row-line">
                <span>Damages total</span>
                <strong>£{{ number_format((float) $agreement->additionalCharges->sum('amount'), 2) }}</strong>
            </div>
        @endif

        @if($agreement->hasBeenUpgraded())
            <div style="margin-top: 8px;">
                Deposit transferred to agreement #{{ $agreement->upgradedToAgreement?->id }}.
            </div>
        @elseif($agreement->depositRefund)
            @php
                $refund = $agreement->depositRefund;
            @endphp
            <div style="margin-top: 8px; border-top: 1px solid #ddd; padding-top: 6px;">
                <strong>{{ $refund->isPosted() ? 'Refund completed' : 'Refund pending' }}</strong>
                — £{{ number_format((float) $refund->amount, 2) }}
                @if($refund->isPosted() && $refund->refund_date)
                    on {{ $refund->refund_date->format('d M Y') }}
                @endif
                @if((float) $refund->debt_offset_amount > 0)
                    <div class="row-line">
                        <span>{{ $refund->isPosted() ? 'Debt cleared from deposit' : 'Debt offset pending' }}</span>
                        <strong>£{{ number_format((float) $refund->debt_offset_amount, 2) }}</strong>
                    </div>
                @endif
                @if((float) $settlementRemainingDebt > 0)
                    <div class="row-line">
                        <span>Remaining driver debt</span>
                        <strong class="text-danger">£{{ number_format((float) $settlementRemainingDebt, 2) }}</strong>
                    </div>
                @endif
            </div>
        @elseif($settlementPreview)
            <div style="margin-top: 8px; border-top: 1px solid #ddd; padding-top: 6px;">
                <div class="row-line">
                    <span>Applied to driver debt</span>
                    <strong>£{{ number_format((float) $settlementPreview['debt_offset_amount'], 2) }}</strong>
                </div>
                <div class="row-line">
                    <span>Refund Amount</span>
                    <strong class="text-success">£{{ number_format((float) $settlementPreview['refund_amount'], 2) }}</strong>
                </div>
                @if((float) ($settlementPreview['remaining_debt_amount'] ?? 0) > 0)
                    <div class="row-line">
                        <span>Remaining driver debt</span>
                        <strong class="text-danger">£{{ number_format((float) $settlementPreview['remaining_debt_amount'], 2) }}</strong>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <h2>Refund Banking Details</h2>
    <table class="details">
        <tr>
            <td class="label">Refund Person Name</td>
            <td>{{ $agreement->refund_person_name ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">Account Number</td>
            <td>{{ $agreement->refund_account_number ?: '—' }}</td>
        </tr>
    </table>

    <h2>Invoices</h2>
    <table class="entries">
        <thead>
            <tr>
                <th>Invoice No</th>
                <th>Type</th>
                <th>Invoice Date</th>
                <th>Due Date</th>
                <th>Total</th>
                <th>Paid</th>
                <th>Balance</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @if($agreement->invoices->isEmpty())
                <tr>
                    <td colspan="8" class="text-muted">No invoices found for this agreement.</td>
                </tr>
            @else
                @foreach($agreement->invoices as $invoice)
                    @php
                        $invoiceTypeLabel = match($invoice->invoice_type) {
                            'agreement_deposit' => 'Deposit',
                            'agreement_additional_charge' => 'Damage',
                            default => 'Rent',
                        };
                    @endphp
                    <tr>
                        <td>{{ $invoice->invoice_no ?: '#'.$invoice->id }}</td>
                        <td>{{ $invoiceTypeLabel }}</td>
                        <td>{{ optional($invoice->invoice_date)->format('d M Y') ?: '—' }}</td>
                        <td>{{ optional($invoice->due_date)->format('d M Y') ?: '—' }}</td>
                        <td>£{{ number_format((float) $invoice->total_amount, 2) }}</td>
                        <td class="text-success">£{{ number_format((float) $invoice->paid_amount, 2) }}</td>
                        <td class="{{ (float) $invoice->balance_amount > 0 ? 'text-danger' : 'text-success' }}">
                            £{{ number_format((float) $invoice->balance_amount, 2) }}
                        </td>
                        <td>{{ ucfirst($invoice->status) }}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>

    <div class="footer">
        This document was generated from the agreement financial summary on {{ $generatedAt }}.
    </div>
</body>
</html>
