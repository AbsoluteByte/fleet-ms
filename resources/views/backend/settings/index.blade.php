@extends('layouts.admin', ['title' => 'Application Settings'])
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">
                        <i class="fa fa-cog me-2"></i>
                        Application Settings
                    </h4>
                    <a href="{{ route('settings.edit', $setting) }}" class="btn btn-primary">
                        <i class="fa fa-edit me-1"></i>
                        Edit Settings
                    </a>
                </div>

                <div class="card-body">
                    @include('alerts')

                    {{-- E-Signature Provider Section --}}
                    <div class="settings-section mb-5">
                        <h5 class="border-bottom pb-2 mb-4">
                            <i class="fa fa-signature me-2 text-primary"></i>
                            E-Signature Provider
                        </h5>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-subtitle mb-3 text-muted">Current Provider</h6>
                                        <div class="d-flex align-items-center">
                                            @if($setting->esign_provider === 'hellosign')
                                                <div class="provider-icon me-3">
                                                    <i class="fa fa-file-signature fa-3x text-info"></i>
                                                </div>
                                                <div>
                                                    <h4 class="mb-1">HelloSign</h4>
                                                    <p class="text-muted mb-0">
                                                        <i class="fa fa-check-circle text-success me-1"></i>
                                                        Using HelloSign API for signatures
                                                    </p>
                                                    <small class="text-muted">
                                                        Powered by Dropbox Sign
                                                    </small>
                                                </div>
                                            @else
                                                <div class="provider-icon me-3">
                                                    <i class="fa fa-pen-fancy fa-3x text-success"></i>
                                                </div>
                                                <div>
                                                    <h4 class="mb-1">Custom Signing</h4>
                                                    <p class="text-muted mb-0">
                                                        <i class="fa fa-check-circle text-success me-1"></i>
                                                        Using built-in signature system
                                                    </p>
                                                    <small class="text-muted">
                                                        Token-based electronic signatures
                                                    </small>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body">
                                        <h6 class="card-subtitle mb-3 text-muted">Provider Features</h6>

                                        @if($setting->esign_provider === 'hellosign')
                                            <ul class="list-unstyled mb-0">
                                                <li class="mb-2">
                                                    <i class="fa fa-check text-success me-2"></i>
                                                    Professional email templates
                                                </li>
                                                <li class="mb-2">
                                                    <i class="fa fa-check text-success me-2"></i>
                                                    Legally binding signatures
                                                </li>
                                                <li class="mb-2">
                                                    <i class="fa fa-check text-success me-2"></i>
                                                    Audit trail included
                                                </li>
                                                <li class="mb-2">
                                                    <i class="fa fa-check text-success me-2"></i>
                                                    Mobile-friendly signing
                                                </li>
                                                <li class="mb-0">
                                                    <i class="fa fa-check text-success me-2"></i>
                                                    Automatic reminders
                                                </li>
                                            </ul>
                                        @else
                                            <ul class="list-unstyled mb-0">
                                                <li class="mb-2">
                                                    <i class="fa fa-check text-success me-2"></i>
                                                    Custom branded emails
                                                </li>
                                                <li class="mb-2">
                                                    <i class="fa fa-check text-success me-2"></i>
                                                    Legally binding signatures
                                                </li>
                                                <li class="mb-2">
                                                    <i class="fa fa-check text-success me-2"></i>
                                                    No external dependencies
                                                </li>
                                                <li class="mb-2">
                                                    <i class="fa fa-check text-success me-2"></i>
                                                    Touch & mouse support
                                                </li>
                                                <li class="mb-0">
                                                    <i class="fa fa-check text-success me-2"></i>
                                                    Free - No API costs
                                                </li>
                                            </ul>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info mt-4">
                            <i class="fa fa-info-circle me-2"></i>
                            <strong>Note:</strong>
                            @if($setting->esign_provider === 'hellosign')
                                Make sure HelloSign API credentials are configured in your environment file (.env).
                            @else
                                Custom signing uses secure token-based links sent via email. No external API required.
                            @endif
                        </div>
                    </div>

                    {{-- Future Settings Sections (Commented for now) --}}
                    {{--
                    <div class="settings-section mb-5">
                        <h5 class="border-bottom pb-2 mb-4">
                            <i class="fa fa-bell me-2 text-warning"></i>
                            Notification Settings
                        </h5>
                        <p class="text-muted">Coming soon...</p>
                    </div>

                    <div class="settings-section">
                        <h5 class="border-bottom pb-2 mb-4">
                            <i class="fa fa-clock me-2 text-danger"></i>
                            Reminder Settings
                        </h5>
                        <p class="text-muted">Coming soon...</p>
                    </div>
                    --}}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <style>
        .settings-section {
            padding: 20px 0;
        }
        .provider-icon {
            width: 80px;
            text-align: center;
        }
    </style>
@endsection
