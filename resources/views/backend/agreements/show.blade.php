@extends('layouts.admin', ['title' => 'Agreement Details'])

@push('css')
    <style>
        .header-navbar-shadow {
            height: 80px !important;
        }
        .btn-group > form .btn {
            border-radius: 0 !important;
        }
        .agreement-discount-summary {
            padding: 14px;
            border: 1px solid rgba(115, 103, 240, .22);
            border-radius: 8px;
            background: linear-gradient(135deg, #fbfaff 0%, #f5f3ff 100%);
            color: #5e5873;
        }
        .agreement-discount-summary__notes {
            padding: 8px 10px;
            border-left: 3px solid #7367f0;
            border-radius: 0 5px 5px 0;
            background: rgba(255, 255, 255, .72);
            color: #6e6b7b;
            font-size: 12px;
        }
    </style>
@endpush

@section('content')
    <div class="justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-2">
            <i class="fa fa-handshake me-2"></i>
            Agreement Details
        </h1>
        <div class="btn-group">
            <a href="{{ route('agreements.pdf.preview', $agreement) }}" class="btn btn-outline-danger" target="_blank" rel="noopener noreferrer">
                <i class="fa fa-eye me-2"></i>
                Preview
            </a>
            <a href="{{ route('agreements.pdf', $agreement) }}" class="btn btn-danger">
                <i class="fa fa-file-pdf-o me-2"></i>
                Generate PDF
            </a>
            <a href="{{ route('agreements.permission-letter', $agreement) }}" class="btn btn-outline-primary" target="_blank" rel="noopener noreferrer">
                <i class="fa fa-file-text-o me-2"></i>
                Permission Letter
            </a>
            <form action="{{ route('agreements.send-client-documents', $agreement) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-success" onclick="return confirm('Send client documents to jawad@samoretraders.com?')">
                    <i class="fa fa-envelope me-2"></i>
                    Send Client Documents
                </button>
            </form>
            @if(config('app.dev_mode'))
                <a href="{{ route('agreements.preview-client-documents-email', $agreement) }}" class="btn btn-outline-secondary" target="_blank" rel="noopener noreferrer">
                    <i class="fa fa-eye me-2"></i>
                    Preview Client Documents Email
                </a>
            @endif
            @php
                $refundStatus = $agreement->depositRefundStatus();
                $showRefundBtn = $refundStatus !== null || $agreement->canRequestDepositRefund();
            @endphp
            @if($showRefundBtn)
                @if($refundStatus === 'pending')
                    <button type="button" class="btn btn-outline-secondary" disabled style="opacity: .45;"
                            title="Deposit refund pending daily financial sheet approval">
                        <i class="fa fa-undo me-2"></i>
                        Refund Deposit
                    </button>
                @elseif($refundStatus === 'posted')
                    <button type="button" class="btn btn-outline-secondary" disabled style="opacity: .45;"
                            title="Deposit already refunded">
                        <i class="fa fa-undo me-2"></i>
                        Refund Deposit
                    </button>
                @else
                    <button type="button" class="btn btn-outline-success"
                            data-toggle="modal"
                            data-target="#refundDepositModal"
                            data-refund-deposit-btn
                            data-action="{{ route('agreements.refund-deposit', $agreement) }}"
                            data-amount="{{ number_format((float) ($settlementPreview['refund_amount'] ?? 0), 2, '.', '') }}"
                            data-gross-deposit="{{ number_format((float) ($settlementPreview['gross_deposit_amount'] ?? 0), 2, '.', '') }}"
                            data-deductions="{{ number_format((float) ($settlementPreview['deductions_amount'] ?? 0), 2, '.', '') }}"
                            data-driver-outstanding="{{ number_format((float) ($settlementPreview['driver_outstanding_amount'] ?? 0), 2, '.', '') }}"
                            data-debt-offset="{{ number_format((float) ($settlementPreview['debt_offset_amount'] ?? 0), 2, '.', '') }}"
                            data-remaining-debt="{{ number_format((float) ($settlementPreview['remaining_debt_amount'] ?? 0), 2, '.', '') }}">
                        <i class="fa fa-undo me-2"></i>
                        Refund Deposit
                    </button>
                @endif
            @endif
            <a href="{{ route('agreements.edit', $agreement) }}" class="btn btn-warning">
                <i class="fa fa-edit me-2"></i>
                Edit
            </a>
            <a href="{{ route('agreements.index') }}" class="btn btn-secondary">
                <i class="fa fa-arrow-left me-2"></i>
                Back
            </a>
        </div>
    </div>

    @include('alerts')
    <!-- Agreement Overview -->
    <div class="row mb-4">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header" style="position: static; width: 100%; z-index: unset;">
                    <h5 class="card-title mb-0">Agreement Information</h5>
                </div>
                <div class="card-body" style="margin-top: 0px;">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Company:</strong></td>
                                    <td>{{ $agreement->company->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Driver:</strong></td>
                                    <td>{{ $agreement->driver->selectOptionLabel() }}</td>
                                </tr>
                                @if($agreement->paying_company_name)
                                    <tr>
                                        <td><strong>Paying company:</strong></td>
                                        <td>{{ $agreement->paying_company_name }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td><strong>Vehicle:</strong></td>
                                    <td>{{ $agreement->car->registration }} - {{ $agreement->car->carModel->name }}</td>
                                </tr>
                                @if($agreement->isReplacementVehicle() && $agreement->parentAgreement)
                                    <tr>
                                        <td><strong>Original agreement:</strong></td>
                                        <td>
                                            <a href="{{ route('agreements.show', $agreement->parentAgreement) }}">
                                                #{{ $agreement->parentAgreement->id }}
                                                — {{ $agreement->parentAgreement->car->registration ?? '—' }}
                                                ({{ $agreement->parentAgreement->driver?->selectOptionLabel() ?? '—' }})
                                            </a>
                                        </td>
                                    </tr>
                                @endif
                                @if($agreement->isUpgradedAgreement() && $agreement->upgradedFromAgreement)
                                    <tr>
                                        <td><strong>Changed from:</strong></td>
                                        <td>
                                            <a href="{{ route('agreements.show', $agreement->upgradedFromAgreement) }}">
                                                #{{ $agreement->upgradedFromAgreement->id }}
                                                — {{ $agreement->upgradedFromAgreement->car->registration ?? '—' }}
                                            </a>
                                        </td>
                                    </tr>
                                @endif
                                @if($agreement->upgradedToAgreement)
                                    <tr>
                                        <td><strong>Changed to:</strong></td>
                                        <td>
                                            <a href="{{ route('agreements.show', $agreement->upgradedToAgreement) }}">
                                                #{{ $agreement->upgradedToAgreement->id }}
                                                — {{ $agreement->upgradedToAgreement->car->registration ?? '—' }}
                                            </a>
                                        </td>
                                    </tr>
                                @endif
                                <tr>
                                    <td><strong>Start Date:</strong></td>
                                    <td>
                                        {{ $agreement->start_date->format('M d, Y g:i A') }}
                                        @if($agreement->hasDeferredBillingAnchor())
                                            <span class="text-muted">(pickup)</span>
                                        @endif
                                    </td>
                                </tr>
                                @if($agreement->hasDeferredBillingAnchor())
                                    <tr>
                                        <td><strong>Regular rent from:</strong></td>
                                        <td>{{ $agreement->billing_anchor_date->format('M d, Y') }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td><strong>End Date:</strong></td>
                                    <td>{{ $agreement->end_date->format('M d, Y') }}</td>
                                </tr>
                                @if($agreement->closing_date)
                                    <tr>
                                        <td><strong>Closing date &amp; time:</strong></td>
                                        <td>{{ $agreement->closing_date->format('M d, Y h:i A') }}</td>
                                    </tr>
                                @endif
                                @if($agreement->termination_notice_date)
                                    <tr>
                                        <td><strong>Termination Notice:</strong></td>
                                        <td>{{ $agreement->termination_notice_date->format('M d, Y') }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Car Available From:</strong></td>
                                        <td>{{ $agreement->termination_available_from_date ? $agreement->termination_available_from_date->format('M d, Y') : '—' }}</td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                        <div class="col-md-6">
                            @if($agreement->isReplacementVehicle())
                                <div class="alert alert-info mb-3">
                                    This is a replacement vehicle agreement. Billing is handled on the
                                    @if($agreement->parentAgreement)
                                        <a href="{{ route('agreements.show', $agreement->parentAgreement) }}">original agreement #{{ $agreement->parentAgreement->id }}</a>.
                                    @else
                                        original agreement.
                                    @endif
                                </div>
                            @endif
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Agreed Rent:</strong></td>
                                    <td>
                                        @if($agreement->isReplacementVehicle())
                                            <span class="text-muted">Billing on original agreement</span>
                                        @else
                                            £{{ number_format($agreement->agreed_rent, 2) }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Deposit:</strong></td>
                                    <td>
                                        @if($agreement->isReplacementVehicle())
                                            <span class="text-muted">Billing on original agreement</span>
                                        @else
                                            £{{ number_format($agreement->deposit_amount, 2) }}
                                        @endif
                                    </td>
                                </tr>
                                @if($agreement->security_deposit)
                                    <tr>
                                        <td><strong>Security Deposit:</strong></td>
                                        <td>£{{ number_format($agreement->security_deposit, 2) }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td><strong>Collection Type:</strong></td>
                                    <td>
                                        <span class="badge bg-info">{{ ucfirst($agreement->collection_type) }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                    <span class="badge" style="background-color: {{ $agreement->status->color }}">
                                        {{ $agreement->status->name }}
                                    </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($agreement->mileage_out || $agreement->mileage_in)
                        <div class="mt-3 pt-3 border-top">
                            <h6>Mileage Information</h6>
                            <div class="row">
                                @if($agreement->mileage_out)
                                    <div class="col-md-6">
                                        <strong>Mileage Out:</strong> {{ number_format($agreement->mileage_out) }} miles
                                    </div>
                                @endif
                                @if($agreement->mileage_in)
                                    <div class="col-md-6">
                                        <strong>Mileage In:</strong> {{ number_format($agreement->mileage_in) }} miles
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    @php
                        $activeCarInsurance = $agreement->car?->currentActiveInsurance();
                        $ownInsuranceProofFiles = $agreement->ownInsuranceProofFileNames();
                        $mutualDetailSlipFiles = $agreement->mutualDetailSlipFileNames();
                    @endphp
                    <div class="mt-3 pt-3 border-top">
                        <h6>Insurance Details</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless table-sm mb-0">
                                    <tr>
                                        <td class="ps-0" style="width: 42%;"><strong>Insurance provided by:</strong></td>
                                        <td>
                                            @if($agreement->using_own_insurance)
                                                <span class="badge bg-info">Client's</span>
                                            @else
                                                <span class="badge bg-primary">Company's</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @if($agreement->using_own_insurance)
                                        <tr>
                                            <td class="ps-0"><strong>Provider:</strong></td>
                                            <td>{{ $agreement->own_insurance_provider_name ?: '—' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="ps-0"><strong>Insurance type:</strong></td>
                                            <td>{{ $agreement->own_insurance_type ?: '—' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="ps-0"><strong>Policy number:</strong></td>
                                            <td>{{ $agreement->own_insurance_policy_number ?: '—' }}</td>
                                        </tr>
                                    @else
                                        <tr>
                                            <td class="ps-0"><strong>Provider:</strong></td>
                                            <td>{{ optional($activeCarInsurance?->insuranceProvider)->provider_name ?: '—' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="ps-0"><strong>Policy number:</strong></td>
                                            <td>{{ optional($activeCarInsurance?->insuranceProvider)->policy_number ?: '—' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="ps-0"><strong>Status:</strong></td>
                                            <td>
                                                @if($activeCarInsurance)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-warning">No active insurance on vehicle</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless table-sm mb-0">
                                    @if($agreement->using_own_insurance)
                                        <tr>
                                            <td class="ps-0" style="width: 42%;"><strong>Start date:</strong></td>
                                            <td>{{ $agreement->own_insurance_start_date?->format('M d, Y') ?: '—' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="ps-0"><strong>End date:</strong></td>
                                            <td>{{ $agreement->own_insurance_end_date?->format('M d, Y') ?: '—' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="ps-0 align-top"><strong>Proof documents:</strong></td>
                                            <td>
                                                @if($ownInsuranceProofFiles !== [])
                                                    <ul class="mb-0 ps-3">
                                                        @foreach($ownInsuranceProofFiles as $proofName)
                                                            <li>
                                                                <x-document-actions
                                                                    :view-url="asset('uploads/insurance_documents/' . $proofName)"
                                                                    style="list-item"
                                                                    :view-text="'View file ' . $loop->iteration"
                                                                />
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                        </tr>
                                    @else
                                        <tr>
                                            <td class="ps-0" style="width: 42%;"><strong>Expiry date:</strong></td>
                                            <td>{{ $activeCarInsurance?->expiry_date?->format('M d, Y') ?: '—' }}</td>
                                        </tr>
                                    @endif
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 pt-3 border-top">
                        <h6>Mutual Detail Slip</h6>
                        @if($mutualDetailSlipFiles !== [])
                            <ul class="mb-0 ps-3">
                                @foreach($mutualDetailSlipFiles as $slipName)
                                    <li>
                                        <x-document-actions
                                            :view-url="asset('uploads/agreement_documents/' . $slipName)"
                                            style="list-item"
                                            :view-text="'View file ' . $loop->iteration"
                                        />
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="mb-0 text-muted">No mutual detail slip uploaded.</p>
                        @endif
                    </div>

                    @if($agreement->termination_notice_date && $agreement->termination_notes)
                        <div class="mt-3 pt-3 border-top">
                            <h6>Termination Notes</h6>
                            <p class="mb-0" style="white-space: pre-wrap;">{{ $agreement->termination_notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header" style="position: static; width: 100%; z-index: unset;">
                    <h5 class="card-title mb-0">Financial Summary</h5>
                </div>
                <div class="card-body" style="margin-top: 0px;">
                    <div class="mb-3">
                        <h6 class="text-success">Total Paid</h6>
                        <h4 class="text-success">£{{ number_format($agreement->total_paid, 2) }}</h4>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-danger">Outstanding</h6>
                        <h4 class="text-danger">£{{ number_format($agreement->total_outstanding, 2) }}</h4>
                    </div>
                    @if($agreement->hasConfiguredDiscount())
                        <div class="agreement-discount-summary mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="mb-0"><i class="fa fa-tag mr-1"></i> Discount</h6>
                                <span class="badge {{ $agreement->discount_is_one_time ? 'badge-primary' : 'badge-light-primary' }}">
                                    {{ $agreement->discount_is_one_time ? 'One-time' : 'Recurring' }}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Discount</span>
                                <strong>
                                    {{ $agreement->discount_type === 'percentage'
                                        ? rtrim(rtrim(number_format((float) $agreement->discount_value, 2), '0'), '.').'%' 
                                        : '£'.number_format((float) $agreement->discount_value, 2) }}
                                </strong>
                            </div>
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Discount given from</span>
                                <strong>
                                    {{ ($agreement->discount_started_at ?? $agreement->updated_at ?? $agreement->created_at)?->format('M d, Y') }}
                                </strong>
                            </div>
                            <div class="d-flex justify-content-between small">
                                <span>Rent after discount</span>
                                <strong class="text-success">£{{ number_format($agreement->discounted_rent, 2) }}</strong>
                            </div>
                            @if($agreement->discount_notes)
                                <div class="agreement-discount-summary__notes mt-2">{{ $agreement->discount_notes }}</div>
                            @endif
                            @if($agreement->discount_is_one_time)
                                <div class="mt-2 pt-2 border-top">
                                    @if($agreement->hasPendingOneTimeDiscount())
                                        <span class="badge badge-warning">Pending next rent invoice</span>
                                    @elseif($agreement->discountConsumedInvoice)
                                        <span class="badge badge-success">Applied</span>
                                        <div class="small mt-1">
                                            Invoice
                                            <strong>{{ $agreement->discountConsumedInvoice->invoice_no ?: '#'.$agreement->discountConsumedInvoice->id }}</strong>
                                            on {{ $agreement->discount_consumed_at?->format('M d, Y') }}
                                        </div>
                                    @else
                                        <span class="badge badge-secondary">Consumed</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                    <div class="border-top pt-3 mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <strong>Deposit Amount</strong>
                            <strong>£{{ number_format((float) $agreement->deposit_amount, 2) }}</strong>
                        </div>

                        <h6 class="mb-2">Deductions</h6>
                        @forelse($agreement->deductions as $deduction)
                            <div class="d-flex justify-content-between small mb-1">
                                <span>{{ $deduction->notes ?: 'Deduction' }}</span>
                                <span class="text-danger">−£{{ number_format((float) $deduction->amount, 2) }}</span>
                            </div>
                        @empty
                            <p class="small text-muted mb-1">No deductions.</p>
                        @endforelse
                        <div class="d-flex justify-content-between border-top pt-1">
                            <span>Deduction total</span>
                            <strong>£{{ number_format($agreement->deductions_total, 2) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <strong>Remaining Deposit</strong>
                            <strong class="text-success">
                                £{{ number_format(max((float) $agreement->deposit_amount - $agreement->deductions_total, 0), 2) }}
                            </strong>
                        </div>

                        <h6 class="mb-2 mt-3">Damages</h6>
                        @forelse($agreement->additionalCharges as $charge)
                            <div class="d-flex justify-content-between small mb-1">
                                <span>
                                    <strong>{{ $charge->typeLabel() }}</strong>
                                    @if($charge->notes)
                                        — {{ $charge->notes }}
                                    @endif
                                    @if($charge->invoice)
                                        <span class="text-muted">({{ $charge->invoice->invoice_no ?: '#'.$charge->invoice->id }})</span>
                                    @endif
                                </span>
                                <span class="text-danger">£{{ number_format((float) $charge->amount, 2) }}</span>
                            </div>
                        @empty
                            <p class="small text-muted mb-1">No damages recorded.</p>
                        @endforelse
                        @if($agreement->additionalCharges->isNotEmpty())
                            <div class="d-flex justify-content-between border-top pt-1">
                                <span>Damages total</span>
                                <strong>£{{ number_format((float) $agreement->additionalCharges->sum('amount'), 2) }}</strong>
                            </div>
                        @endif

                        @if($agreement->hasBeenUpgraded())
                            <div class="alert alert-info py-2 mt-3 mb-0">
                                Deposit transferred to
                                <a href="{{ route('agreements.show', $agreement->upgradedToAgreement) }}">
                                    agreement #{{ $agreement->upgradedToAgreement->id }}
                                </a>.
                            </div>
                        @elseif($agreement->depositRefund)
                            @php($refund = $agreement->depositRefund)
                            <div class="mt-3 pt-2 border-top">
                                @if($refund->isPending())
                                    <span class="badge badge-warning mb-2">Refund pending</span>
                                @else
                                    <span class="badge badge-success mb-2">Refund completed — £{{ number_format((float) $refund->amount, 2) }}</span>
                                @endif
                                <div class="d-flex justify-content-between small">
                                    <span>Refund amount</span>
                                    <strong>£{{ number_format((float) $refund->amount, 2) }}</strong>
                                </div>
                                @if($refund->isPosted() && $refund->refund_date)
                                    <div class="d-flex justify-content-between small mt-1">
                                        <span>Refund date</span>
                                        <strong>{{ $refund->refund_date->format('M d, Y') }}</strong>
                                    </div>
                                @endif
                                @if((float) $refund->debt_offset_amount > 0)
                                    <div class="d-flex justify-content-between small">
                                        <span>{{ $refund->isPosted() ? 'Debt cleared from deposit' : 'Debt offset pending' }}</span>
                                        <strong>£{{ number_format((float) $refund->debt_offset_amount, 2) }}</strong>
                                    </div>
                                @endif
                                @if((float) $settlementRemainingDebt > 0)
                                    <div class="d-flex justify-content-between small text-danger">
                                        <span>Remaining driver debt</span>
                                        <strong>£{{ number_format((float) $settlementRemainingDebt, 2) }}</strong>
                                    </div>
                                @endif
                            </div>
                        @elseif($settlementPreview)
                            <div class="mt-3 pt-2 border-top">
                                <div class="d-flex justify-content-between small">
                                    <span>Applied to driver debt</span>
                                    <strong>£{{ number_format((float) $settlementPreview['debt_offset_amount'], 2) }}</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <strong>Refund Amount</strong>
                                    <strong class="text-success">£{{ number_format((float) $settlementPreview['refund_amount'], 2) }}</strong>
                                </div>
                                @if((float) $settlementPreview['remaining_debt_amount'] > 0)
                                    <div class="d-flex justify-content-between small text-danger">
                                        <span>Remaining driver debt</span>
                                        <strong>£{{ number_format((float) $settlementPreview['remaining_debt_amount'], 2) }}</strong>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                    @if($agreement->next_collection_date)
                        <div class="mb-3">
                            <h6 class="text-warning">Next Collection</h6>
                            <p class="mb-0">{{ $agreement->next_collection_date->format('M d, Y') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @include('backend.agreements.partials.invoices', ['agreement' => $agreement])
        <!-- E-Signature Status Card -->
        <div class="col-xl-4 mt-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fa fa-signature me-2"></i>
                        E-Signature Status
                    </h5>
                </div>
                <div class="card-body">
                    @if($agreement->hellosign_status)
                        <div class="mb-3">
                            <h6>Status</h6>
                            <span class="{{ $agreement->esign_status_badge }}">
                        {{ $agreement->esign_status_text }}
                    </span>
                        </div>

                        @if($agreement->esign_sent_at)
                            <div class="mb-3">
                                <h6>Sent On</h6>
                                <p class="mb-0">{{ $agreement->esign_sent_at->format('M d, Y h:i A') }}</p>
                            </div>
                        @endif

                        @if($agreement->esign_completed_at)
                            <div class="mb-3">
                                <h6>Signed On</h6>
                                <p class="mb-0">{{ $agreement->esign_completed_at->format('M d, Y h:i A') }}</p>
                            </div>
                        @endif

                        {{-- ✅ PENDING STATUS - Show Check Status + Resend --}}
                        @if($agreement->hellosign_status == 'pending')
                            <div class="d-grid gap-2">
                                {{-- ✅ Check Status Button --}}
                                <form action="{{ route('agreements.esign-status', $agreement) }}" method="GET">
                                    <button type="submit" class="btn btn-info btn-sm w-100">
                                        <i class="fa fa-sync me-1"></i>
                                        Check Status & Download
                                    </button>
                                </form>

                                {{-- ✅ Resend Reminder --}}
                                <form action="{{ route('agreements.resend-esign', $agreement) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-warning btn-sm w-100">
                                        <i class="fa fa-paper-plane me-1"></i>
                                        Resend Reminder
                                    </button>
                                </form>
                            </div>
                        @endif

                        {{-- ✅ SIGNED STATUS - Show Download Button --}}
                        @if($agreement->hellosign_status == 'signed' && $agreement->esign_document_path)
                            <div class="d-grid gap-2">
                                <a href="{{ document_view_url(route('agreements.view-signed', $agreement)) }}"
                                   class="btn btn-success btn-sm w-100 document-view-link" target="_blank">
                                    <i class="fa fa-file-pdf me-1"></i>
                                    View Signed Document
                                </a>

                                <a href="{{ asset($agreement->esign_document_path) }}"
                                   class="btn btn-outline-success btn-sm w-100" download>
                                    <i class="fa fa-download me-1"></i>
                                    Download Signed PDF
                                </a>
                            </div>
                        @elseif($agreement->hellosign_status == 'signed' && !$agreement->esign_document_path)
                            {{-- ✅ If signed but no document, fetch it --}}
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle me-1"></i>
                                Document is signed. Click below to download:
                            </div>
                            <form action="{{ route('agreements.esign-status', $agreement) }}" method="GET">
                                <button type="submit" class="btn btn-success btn-sm w-100">
                                    <i class="fa fa-download me-1"></i>
                                    Download Signed Document
                                </button>
                            </form>
                        @endif

                    @else
                        {{-- ✅ NOT SENT YET --}}
                        <div class="text-center py-4">
                            <i class="fa fa-signature fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Not sent for e-signature</p>

                            @if($agreement->canSendForESignature())
                                <form action="{{ route('agreements.send-esign', $agreement) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm"
                                            onclick="return confirm('Send this agreement for e-signature to {{ $agreement->driver->email }}?')">
                                        <i class="fa fa-paper-plane me-1"></i>
                                        Send for E-Signature
                                    </button>
                                </form>
                            @else
                                <p class="small text-danger mt-2">
                                    <i class="fa fa-exclamation-triangle me-1"></i>
                                    Driver email is required for e-signature
                                </p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Collections Schedule -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fa fa-calendar-alt me-2"></i>
                Payment Collections
            </h5>
            @if($agreement->auto_schedule_collections)
                <button class="btn btn-sm btn-outline-primary" onclick="regenerateCollections()">
                    <i class="fa fa-sync me-1"></i>
                    Regenerate Schedule
                </button>
            @endif
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>Collection Date</th>
                        <th>Due Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Amount Paid</th>
                        <th>Payment Date</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($agreement->collections as $collection)
                        <tr class="{{ $collection->payment_status === 'overdue' ? 'table-danger' : '' }}">
                            <td>{{ $collection->date->format('M d, Y') }}</td>
                            <td>
                                {{ $collection->due_date->format('M d, Y') }}
                                @if($collection->payment_status === 'overdue')
                                    <br><small class="text-danger">{{ $collection->days_overdue }} days overdue</small>
                                @endif
                            </td>
                            <td>£{{ number_format($collection->amount, 2) }}</td>
                            <td>
                                <span class="badge {{ $collection->status_badge_class }}">
                                    {{ ucfirst($collection->payment_status) }}
                                </span>
                            </td>
                            <td>£{{ number_format($collection->amount_paid, 2) }}</td>
                            <td>
                                {{ $collection->payment_date ? $collection->payment_date->format('M d, Y') : '-' }}
                            </td>
                            <td>
                                @if($collection->payment_status !== 'paid')
                                    <button class="btn btn-sm btn-success"
                                            onclick="showPaymentModal({{ $collection->id }}, {{ $collection->remaining_amount }})">
                                        <i class="fa fa-pound-sign"></i>
                                        Pay
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">
                                No collections scheduled
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="paymentForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Record Payment</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="amount_paid">Amount Paid *</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">£</span>
                                </div>
                                <input type="number" name="amount_paid" id="amount_paid"
                                       class="form-control" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="payment_date">Payment Date *</label>
                            <input type="date" name="payment_date" id="payment_date"
                                   class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="payment_notes">Notes</label>
                            <textarea name="notes" id="payment_notes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Record Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('backend.agreements.partials.refund-deposit-modal', ['bankAccounts' => $bankAccounts ?? collect()])
@endsection

@section('js')
    <script>
        function showPaymentModal(collectionId, remainingAmount) {
            const form = document.getElementById('paymentForm');
            const amountInput = document.getElementById('amount_paid');

            form.action = `{{ route('agreements.show', $agreement) }}/collections/${collectionId}/pay`;
            amountInput.value = remainingAmount;
            amountInput.max = remainingAmount;

            jQuery('#paymentModal').modal('show');
        }

        function regenerateCollections() {
            if (confirm('This will regenerate all auto-scheduled collections. Continue?')) {
                fetch(`{{ route('agreements.show', $agreement) }}/regenerate-collections`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error regenerating collections');
                        }
                    });
            }
        }
    </script>
@endsection
