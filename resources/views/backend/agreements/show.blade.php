@extends('layouts.admin', ['title' => 'Agreement Details'])

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">
            <i class="fa fa-handshake me-2"></i>
            Agreement Details
        </h1>
        <div class="btn-group">
            <a href="{{ route('agreements.pdf', $agreement) }}" class="btn btn-danger" target="_blank">
                <i class="fa fa-file-pdf-o me-2"></i>
                Generate PDF
            </a>
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
                <div class="card-header">
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
                                    <td>{{ $agreement->driver->full_name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Vehicle:</strong></td>
                                    <td>{{ $agreement->car->registration }} - {{ $agreement->car->carModel->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Start Date:</strong></td>
                                    <td>{{ $agreement->start_date->format('M d, Y') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>End Date:</strong></td>
                                    <td>{{ $agreement->end_date->format('M d, Y') }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Agreed Rent:</strong></td>
                                    <td>£{{ number_format($agreement->agreed_rent, 2) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Deposit:</strong></td>
                                    <td>£{{ number_format($agreement->deposit_amount, 2) }}</td>
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
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
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
                                <a href="{{ route('agreements.view-signed', $agreement) }}"
                                   class="btn btn-success btn-sm w-100" target="_blank">
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
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="paymentForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Record Payment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="amount_paid" class="form-label">Amount Paid *</label>
                            <div class="input-group">
                                <span class="input-group-text">£</span>
                                <input type="number" name="amount_paid" id="amount_paid"
                                       class="form-control" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="payment_date" class="form-label">Payment Date *</label>
                            <input type="date" name="payment_date" id="payment_date"
                                   class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="payment_notes" class="form-label">Notes</label>
                            <textarea name="notes" id="payment_notes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Record Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        function showPaymentModal(collectionId, remainingAmount) {
            const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
            const form = document.getElementById('paymentForm');
            const amountInput = document.getElementById('amount_paid');

            form.action = `{{ route('agreements.show', $agreement) }}/collections/${collectionId}/pay`;
            amountInput.value = remainingAmount;
            amountInput.max = remainingAmount;

            modal.show();
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
