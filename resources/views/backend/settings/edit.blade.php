@extends('layouts.admin', ['title' => 'Edit Settings'])
@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <i class="fa fa-edit me-2"></i>
                        Edit Application Settings
                    </h4>
                </div>

                <form action="{{ route('settings.update', $model) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        @include('alerts')

                        {{-- E-Signature Provider Selection --}}
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fa fa-signature me-1"></i>
                                E-Signature Provider <span class="text-danger">*</span>
                            </label>

                            <div class="row g-3">
                                {{-- Custom Signing Option --}}
                                <div class="col-md-6">
                                    <div class="form-check provider-card {{ $model->esign_provider === 'custom' ? 'active' : '' }}">
                                        <input
                                            class="form-check-input"
                                            type="radio"
                                            name="esign_provider"
                                            id="provider_custom"
                                            value="custom"
                                            {{ $model->esign_provider === 'custom' ? 'checked' : '' }}
                                            required
                                        >
                                        <label class="form-check-label w-100" for="provider_custom">
                                            <div class="provider-option">
                                                <div class="provider-header">
                                                    <i class="fa fa-pen-fancy fa-2x text-success mb-3"></i>
                                                    <h5 class="mb-1">Custom Signing</h5>
                                                    <small class="text-muted">Built-in signature system</small>
                                                </div>
                                                <div class="provider-features mt-3">
                                                    <div class="feature-item">
                                                        <i class="fa fa-check text-success me-2"></i>
                                                        <small>Free - No API costs</small>
                                                    </div>
                                                    <div class="feature-item">
                                                        <i class="fa fa-check text-success me-2"></i>
                                                        <small>Custom branded emails</small>
                                                    </div>
                                                    <div class="feature-item">
                                                        <i class="fa fa-check text-success me-2"></i>
                                                        <small>Full control</small>
                                                    </div>
                                                    <div class="feature-item">
                                                        <i class="fa fa-check text-success me-2"></i>
                                                        <small>No external dependencies</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                {{-- HelloSign Option --}}
                                <div class="col-md-6">
                                    <div class="form-check provider-card {{ $model->esign_provider === 'hellosign' ? 'active' : '' }}">
                                        <input
                                            class="form-check-input"
                                            type="radio"
                                            name="esign_provider"
                                            id="provider_hellosign"
                                            value="hellosign"
                                            {{ $model->esign_provider === 'hellosign' ? 'checked' : '' }}
                                            required
                                        >
                                        <label class="form-check-label w-100" for="provider_hellosign">
                                            <div class="provider-option">
                                                <div class="provider-header">
                                                    <i class="fa fa-file-signature fa-2x text-info mb-3"></i>
                                                    <h5 class="mb-1">HelloSign</h5>
                                                    <small class="text-muted">Powered by Dropbox Sign</small>
                                                </div>
                                                <div class="provider-features mt-3">
                                                    <div class="feature-item">
                                                        <i class="fa fa-check text-success me-2"></i>
                                                        <small>Professional templates</small>
                                                    </div>
                                                    <div class="feature-item">
                                                        <i class="fa fa-check text-success me-2"></i>
                                                        <small>Automatic reminders</small>
                                                    </div>
                                                    <div class="feature-item">
                                                        <i class="fa fa-check text-success me-2"></i>
                                                        <small>Audit trail included</small>
                                                    </div>
                                                    <div class="feature-item">
                                                        <i class="fa fa-check text-success me-2"></i>
                                                        <small>Industry standard</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            @error('esign_provider')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror

                            <div class="alert alert-warning mt-3">
                                <i class="fa fa-exclamation-triangle me-2"></i>
                                <strong>Important:</strong> Changing the provider will affect all future agreement signatures.
                                Existing pending signatures will continue with their original provider.
                            </div>
                        </div>

                        {{-- Future Settings (Commented) --}}
                        {{--
                        <hr class="my-4">

                        <div class="mb-4">
                            <h6 class="mb-3">Notification Settings</h6>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="email_notifications" checked>
                                <label class="form-check-label" for="email_notifications">
                                    Enable email notifications
                                </label>
                            </div>
                        </div>
                        --}}
                    </div>

                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('settings.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left me-1"></i>
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save me-1"></i>
                                Save Settings
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <style>
        .provider-card {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            height: 100%;
        }

        .provider-card:hover {
            border-color: #007bff;
            box-shadow: 0 4px 12px rgba(0,123,255,0.15);
        }

        .provider-card.active {
            border-color: #007bff;
            background-color: #f0f7ff;
            box-shadow: 0 4px 12px rgba(0,123,255,0.25);
        }

        .provider-card .form-check-input {
            float: right;
            margin-top: 0;
        }

        .provider-option {
            text-align: center;
        }

        .provider-header {
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .provider-features {
            text-align: left;
        }

        .feature-item {
            padding: 5px 0;
        }

        .feature-item i {
            width: 20px;
        }
    </style>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            // Click anywhere on card to select
            $('.provider-card').click(function() {
                $(this).find('input[type="radio"]').prop('checked', true);
                $('.provider-card').removeClass('active');
                $(this).addClass('active');
            });
        });
    </script>
@endsection
