@extends('layouts.admin', ['title' => 'Payment Setting Details'])

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">
                        <i class="fa fa-credit-card"></i> Payment Setting - {{ $paymentSetting->payment_type }}
                    </h4>
                    <div>
                        <a href="{{ route('payment-settings.edit', $paymentSetting) }}" class="btn btn-primary btn-sm">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('payment-settings.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <strong>Company</strong>
                            <p>{{ optional($paymentSetting->company)->name ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Payment Type</strong>
                            <p><span class="badge badge-primary">{{ $paymentSetting->payment_type }}</span></p>
                        </div>

                        @if($paymentSetting->payment_type === 'Bank Transfer')
                            <div class="col-12 mt-2">
                                <h5>Bank Transfer Details</h5>
                            </div>
                            <div class="col-md-6 mb-2">
                                <strong>Bank Name</strong>
                                <p>{{ $paymentSetting->bank_name }}</p>
                            </div>
                            <div class="col-md-6 mb-2">
                                <strong>Account Number</strong>
                                <p><code>{{ $paymentSetting->account_number }}</code></p>
                            </div>
                            <div class="col-md-6 mb-2">
                                <strong>Sort Code</strong>
                                <p><code>{{ $paymentSetting->sort_code }}</code></p>
                            </div>
                            @if($paymentSetting->iban_number)
                                <div class="col-md-6 mb-2">
                                    <strong>IBAN</strong>
                                    <p><code>{{ $paymentSetting->iban_number }}</code></p>
                                </div>
                            @endif
                        @endif

                        @if($paymentSetting->payment_type === 'Stripe')
                            <div class="col-12 mt-2">
                                <h5>Stripe Details</h5>
                            </div>
                            <div class="col-md-6 mb-2">
                                <strong>Public Key</strong>
                                <p><code>{{ Str::limit($paymentSetting->stripe_public_key, 60) }}</code></p>
                            </div>
                            <div class="col-md-6 mb-2">
                                <strong>Secret Key</strong>
                                <p><code>{{ Str::mask($paymentSetting->stripe_secret_key, '*', 4, -4) }}</code></p>
                            </div>
                        @endif

                        @if($paymentSetting->payment_type === 'PayPal')
                            <div class="col-12 mt-2">
                                <h5>PayPal Details</h5>
                            </div>
                            <div class="col-md-6 mb-2">
                                <strong>Client ID</strong>
                                <p><code>{{ Str::limit($paymentSetting->paypal_client_id, 60) }}</code></p>
                            </div>
                            <div class="col-md-6 mb-2">
                                <strong>Secret</strong>
                                <p><code>{{ Str::mask($paymentSetting->paypal_secret, '*', 4, -4) }}</code></p>
                            </div>
                        @endif

                        @if($paymentSetting->payment_type === 'Cash')
                            <div class="col-12 mt-2">
                                <div class="alert alert-info mb-0">Cash payment method has no additional details.</div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="card-footer text-right">
                    <form action="{{ route('payment-settings.destroy', $paymentSetting) }}" method="POST" style="display: inline-block;"
                          onsubmit="return confirm('Are you sure you want to delete this payment setting?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="fa fa-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
