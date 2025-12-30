@extends('layouts.admin', ['title' => 'Payment Details'])

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">
                            <i class="fa fa-credit-card"></i> Payment Details - {{ $payment->payment_type }}
                        </h3>
                        <div>
                            <a href="{{ route($url . 'edit', $payment->id) }}" class="btn btn-primary btn-sm">
                                <i class="fa fa-edit"></i> Edit
                            </a>
                            <a href="{{ route($url . 'index') }}" class="btn btn-secondary btn-sm">
                                <i class="fa fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Basic Information -->
                            <div class="col-12">
                                <h4 class="border-bottom pb-2 mb-3">
                                    <i class="fa fa-info-circle"></i> Basic Information
                                </h4>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="info-item">
                                    <strong><i class="fa fa-money-check text-primary"></i> Payment Type:</strong>
                                    <p class="mb-0 ml-4">
                                        <span class="badge badge-primary badge-lg">{{ $payment->payment_type }}</span>
                                    </p>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="info-item">
                                    <strong><i class="fa fa-building text-primary"></i> Company:</strong>
                                    <p class="mb-0 ml-4">{{ $payment->company->name ?? 'N/A' }}</p>
                                </div>
                            </div>

                            <!-- Bank Transfer Details -->
                            @if($payment->payment_type === 'Bank Transfer')
                                <div class="col-12 mt-3">
                                    <h4 class="border-bottom pb-2 mb-3">
                                        <i class="fa fa-university"></i> Bank Transfer Details
                                    </h4>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <div class="info-item">
                                        <strong><i class="fa fa-bank text-success"></i> Bank Name:</strong>
                                        <p class="mb-0 ml-4">{{ $payment->bank_name }}</p>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <div class="info-item">
                                        <strong><i class="fa fa-hashtag text-success"></i> Account Number:</strong>
                                        <p class="mb-0 ml-4">
                                            <code>{{ $payment->account_number }}</code>
                                        </p>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <div class="info-item">
                                        <strong><i class="fa fa-sort-numeric-down text-success"></i> Sort Code:</strong>
                                        <p class="mb-0 ml-4">
                                            <code>{{ $payment->sort_code }}</code>
                                        </p>
                                    </div>
                                </div>

                                @if($payment->iban_number)
                                    <div class="col-md-6 mb-3">
                                        <div class="info-item">
                                            <strong><i class="fa fa-globe text-success"></i> IBAN Number:</strong>
                                            <p class="mb-0 ml-4">
                                                <code>{{ $payment->iban_number }}</code>
                                            </p>
                                        </div>
                                    </div>
                                @endif

                                <!-- Bank Transfer Summary Card -->
                                <div class="col-12 mt-3">
                                    <div class="card bg-light border-success">
                                        <div class="card-header bg-success text-white">
                                            <h5 class="mb-0"><i class="fa fa-university"></i> Bank Account Summary</h5>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-borderless mb-0">
                                                <tr>
                                                    <th width="200">Bank Name:</th>
                                                    <td>{{ $payment->bank_name }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Account Number:</th>
                                                    <td><code class="text-dark">{{ $payment->account_number }}</code></td>
                                                </tr>
                                                <tr>
                                                    <th>Sort Code:</th>
                                                    <td><code class="text-dark">{{ $payment->sort_code }}</code></td>
                                                </tr>
                                                @if($payment->iban_number)
                                                    <tr>
                                                        <th>IBAN:</th>
                                                        <td><code class="text-dark">{{ $payment->iban_number }}</code></td>
                                                    </tr>
                                                @endif
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Stripe Details -->
                            @if($payment->payment_type === 'Stripe')
                                <div class="col-12 mt-3">
                                    <h4 class="border-bottom pb-2 mb-3">
                                        <i class="fab fa-stripe"></i> Stripe Configuration
                                    </h4>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <div class="info-item">
                                        <strong><i class="fa fa-key text-info"></i> Public Key:</strong>
                                        <p class="mb-0 ml-4">
                                            <code class="text-break">{{ Str::limit($payment->stripe_public_key, 50) }}</code>
                                        </p>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <div class="info-item">
                                        <strong><i class="fa fa-lock text-info"></i> Secret Key:</strong>
                                        <p class="mb-0 ml-4">
                                            <code>{{ Str::mask($payment->stripe_secret_key, '*', 4, -4) }}</code>
                                            <small class="text-muted d-block">(Masked for security)</small>
                                        </p>
                                    </div>
                                </div>

                                <!-- Stripe Summary Card -->
                                <div class="col-12 mt-3">
                                    <div class="card bg-light border-info">
                                        <div class="card-header bg-info text-white">
                                            <h5 class="mb-0"><i class="fab fa-stripe"></i> Stripe API Configuration</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="alert alert-info mb-3">
                                                <i class="fa fa-info-circle"></i>
                                                <strong>Note:</strong> Secret key is masked for security purposes.
                                            </div>
                                            <table class="table table-borderless mb-0">
                                                <tr>
                                                    <th width="200">Publishable Key:</th>
                                                    <td><code class="text-dark text-break">{{ $payment->stripe_public_key }}</code></td>
                                                </tr>
                                                <tr>
                                                    <th>Secret Key:</th>
                                                    <td><code class="text-dark">{{ Str::mask($payment->stripe_secret_key, '*', 4, -4) }}</code></td>
                                                </tr>
                                                <tr>
                                                    <th>Status:</th>
                                                    <td><span class="badge badge-success">Active</span></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- PayPal Details -->
                            @if($payment->payment_type === 'PayPal')
                                <div class="col-12 mt-3">
                                    <h4 class="border-bottom pb-2 mb-3">
                                        <i class="fab fa-paypal"></i> PayPal Configuration
                                    </h4>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <div class="info-item">
                                        <strong><i class="fa fa-id-card text-primary"></i> Client ID:</strong>
                                        <p class="mb-0 ml-4">
                                            <code class="text-break">{{ Str::limit($payment->paypal_client_id, 50) }}</code>
                                        </p>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <div class="info-item">
                                        <strong><i class="fa fa-lock text-primary"></i> Client Secret:</strong>
                                        <p class="mb-0 ml-4">
                                            <code>{{ Str::mask($payment->paypal_secret, '*', 4, -4) }}</code>
                                            <small class="text-muted d-block">(Masked for security)</small>
                                        </p>
                                    </div>
                                </div>

                                <!-- PayPal Summary Card -->
                                <div class="col-12 mt-3">
                                    <div class="card bg-light border-primary">
                                        <div class="card-header bg-primary text-white">
                                            <h5 class="mb-0"><i class="fab fa-paypal"></i> PayPal API Configuration</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="alert alert-info mb-3">
                                                <i class="fa fa-info-circle"></i>
                                                <strong>Note:</strong> Client secret is masked for security purposes.
                                            </div>
                                            <table class="table table-borderless mb-0">
                                                <tr>
                                                    <th width="200">Client ID:</th>
                                                    <td><code class="text-dark text-break">{{ $payment->paypal_client_id }}</code></td>
                                                </tr>
                                                <tr>
                                                    <th>Client Secret:</th>
                                                    <td><code class="text-dark">{{ Str::mask($payment->paypal_secret, '*', 4, -4) }}</code></td>
                                                </tr>
                                                <tr>
                                                    <th>Status:</th>
                                                    <td><span class="badge badge-success">Active</span></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Timestamps -->
                            <div class="col-12 mt-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <small class="text-muted">
                                            <i class="fa fa-calendar-plus"></i>
                                            <strong>Created:</strong> {{ $payment->created_at->format('d M, Y h:i A') }}
                                        </small>
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <small class="text-muted">
                                            <i class="fa fa-calendar-check"></i>
                                            <strong>Last Updated:</strong> {{ $payment->updated_at->format('d M, Y h:i A') }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Footer with Actions -->
                    <div class="card-footer">
                        <div class="row">
                            <div class="col-md-6">
                                <a href="{{ route($url . 'edit', $payment->id) }}" class="btn btn-primary">
                                    <i class="fa fa-edit"></i> Edit Payment Method
                                </a>
                            </div>
                            <div class="col-md-6 text-right">
                                <form action="{{ route($url . 'destroy', $payment->id) }}"
                                      method="POST"
                                      style="display: inline-block;"
                                      onsubmit="return confirm('Are you sure you want to delete this payment method?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fa fa-trash"></i> Delete Payment Method
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css')
    <style>
        .info-item {
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .badge-lg {
            font-size: 1rem;
            padding: 8px 15px;
        }
        code {
            background: #f8f9fa;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .text-break {
            word-break: break-all;
        }
    </style>
@endpush
