@extends('layouts.admin', ['title' => 'Agreement Details'])

@push('css')
    <style>
        .header-navbar-shadow {
            height: 80px !important;
        }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">
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
            @if($canUpgradeCar)
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#changeCarModal">
                    <i class="fa fa-exchange me-2"></i>
                    Change Car
                </button>
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
                <div class="card-body">
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
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-success">Total Paid</h6>
                        <h4 class="text-success">£{{ number_format($agreement->total_paid, 2) }}</h4>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-danger">Outstanding</h6>
                        <h4 class="text-danger">£{{ number_format($agreement->total_outstanding, 2) }}</h4>
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

    @if($canUpgradeCar)
        <div class="modal fade" id="changeCarModal" tabindex="-1" role="dialog" aria-labelledby="changeCarModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <form method="POST" action="{{ route('agreements.upgrade-car', $agreement) }}" id="changeCarForm">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="changeCarModalLabel">Change Car</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info">
                                The current agreement will be closed and a new agreement will be created.
                                Deposit carries over from the current agreement (no new deposit invoice).
                                Billing stays aligned to the original cycle (next anchor:
                                <strong>{{ \Carbon\Carbon::parse($upgradePreview['next_anchor'])->format('M d, Y') }}</strong>).
                            </div>

                            <div class="form-group">
                                <label for="change_car_id">Select Vehicle *</label>
                                <select name="car_id" id="change_car_id" class="form-control select-search" required>
                                    <option value="">Loading available vehicles...</option>
                                </select>
                                <div id="changeCarLoadError" class="text-danger small mt-1 d-none"></div>
                            </div>

                            <div class="form-group">
                                <label for="change_agreed_rent">Agreed Rent *</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">£</span>
                                    </div>
                                    <input type="number" name="agreed_rent" id="change_agreed_rent"
                                           class="form-control @error('agreed_rent') is-invalid @enderror"
                                           step="0.01" min="0" required
                                           value="{{ old('agreed_rent', $agreement->agreed_rent) }}">
                                </div>
                                <small class="form-text text-muted">Current rent: £{{ number_format($agreement->agreed_rent, 2) }}. You may enter a higher or lower amount.</small>
                                @error('agreed_rent')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="card bg-light">
                                <div class="card-body py-2">
                                    <h6 class="mb-1">Estimated first-period adjustment</h6>
                                    <p class="mb-0" id="changeCarAdjustmentPreview">
                                        Enter a new agreed rent to see the estimated invoice or credit.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="changeCarSubmitBtn">
                                <i class="fa fa-check mr-50"></i>
                                Change &amp; Create Agreement
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

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
@endsection

@section('js')
    <script>
        const upgradePreview = @json($upgradePreview);
        const currentAgreedRent = {{ (float) $agreement->agreed_rent }};

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

        function formatMoney(amount) {
            return '£' + Number(amount).toFixed(2);
        }

        function calculateChangeCarAdjustment(newRent) {
            if (!upgradePreview || isNaN(newRent)) {
                return null;
            }

            const rentDiff = newRent - currentAgreedRent;
            const amount = (rentDiff / upgradePreview.period_days) * upgradePreview.remaining_days;

            return {
                amount: amount,
                rentDiff: rentDiff,
                type: amount > 0 ? 'invoice' : (amount < 0 ? 'credit' : 'none'),
            };
        }

        function updateChangeCarAdjustmentPreview() {
            const previewEl = document.getElementById('changeCarAdjustmentPreview');
            const rentInput = document.getElementById('change_agreed_rent');

            if (!previewEl || !rentInput || !upgradePreview) {
                return;
            }

            const newRent = parseFloat(rentInput.value || '0');
            const adjustment = calculateChangeCarAdjustment(newRent);

            if (!adjustment || isNaN(newRent)) {
                previewEl.textContent = 'Enter a new agreed rent to see the estimated invoice or credit.';
                return;
            }

            if (upgradePreview.remaining_days === 0) {
                previewEl.innerHTML = 'Change is on a billing anchor day, so no immediate adjustment will be made. The first full rent invoice will be on the next billing date.';
                return;
            }

            if (adjustment.type === 'none') {
                previewEl.innerHTML = 'Rent is unchanged. No immediate invoice or credit. The next full rent invoice will be on the billing anchor date.';
                return;
            }

            if (adjustment.type === 'credit') {
                previewEl.innerHTML = `
                    Rent difference: <strong>${formatMoney(adjustment.rentDiff)}</strong> per period<br>
                    Remaining days until next billing anchor: <strong>${upgradePreview.remaining_days}</strong><br>
                    Estimated driver credit: <strong>${formatMoney(Math.abs(adjustment.amount))}</strong> (visible on driver payments)
                `;
                return;
            }

            previewEl.innerHTML = `
                Rent difference: <strong>${formatMoney(adjustment.rentDiff)}</strong> per period<br>
                Remaining days until next billing anchor: <strong>${upgradePreview.remaining_days}</strong><br>
                Estimated proration invoice: <strong>${formatMoney(adjustment.amount)}</strong>
            `;
        }

        function loadChangeCars() {
            const carSelect = document.getElementById('change_car_id');
            const errorEl = document.getElementById('changeCarLoadError');

            if (!carSelect) {
                return;
            }

            carSelect.innerHTML = '<option value="">Loading available vehicles...</option>';
            errorEl.classList.add('d-none');
            errorEl.textContent = '';

            fetch(`{{ route('agreements.upgrade-cars', $agreement) }}`, {
                headers: {
                    'Accept': 'application/json',
                },
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Unable to load available vehicles.');
                    }

                    return response.json();
                })
                .then(data => {
                    const cars = data.cars || [];

                    if (cars.length === 0) {
                        carSelect.innerHTML = '<option value="">No eligible vehicles available</option>';
                        return;
                    }

                    carSelect.innerHTML = '<option value="">Select a vehicle</option>';
                    cars.forEach(car => {
                        const option = document.createElement('option');
                        option.value = car.id;
                        option.textContent = `${car.registration} - ${car.model || 'Unknown model'}${car.company ? ` (${car.company})` : ''}`;
                        carSelect.appendChild(option);
                    });

                    if (window.jQuery && window.jQuery.fn.select2) {
                        const $carSelect = window.jQuery(carSelect);
                        if ($carSelect.hasClass('select2-hidden-accessible')) {
                            $carSelect.select2('destroy');
                        }
                        if (window.initBackendSelect2) {
                            window.initBackendSelect2(carSelect.parentElement);
                        }
                    }
                })
                .catch(error => {
                    carSelect.innerHTML = '<option value="">Unable to load vehicles</option>';
                    errorEl.textContent = error.message;
                    errorEl.classList.remove('d-none');
                });
        }

        jQuery(document).ready(function () {
            const changeCarModal = jQuery('#changeCarModal');
            const rentInput = document.getElementById('change_agreed_rent');

            if (rentInput) {
                rentInput.addEventListener('input', updateChangeCarAdjustmentPreview);
                updateChangeCarAdjustmentPreview();
            }

            if (changeCarModal.length) {
                changeCarModal.on('show.bs.modal', loadChangeCars);
            }

            @if($errors->has('car_id') || $errors->has('agreed_rent'))
                changeCarModal.modal('show');
                loadChangeCars();
            @endif
        });
    </script>
@endsection
