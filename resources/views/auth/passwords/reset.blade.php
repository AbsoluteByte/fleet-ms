@extends('layouts.auth', ['title' => 'Reset Password'])
@section('content')
    <div class="col-lg-6 d-lg-block d-none text-center align-self-center px-1 py-0">
        <img src="{{ asset('app-assets/images/logo/app-logo.png') }}" alt="branding logo">
    </div>
    <div class="col-lg-6 col-12 p-0">
        <div class="card rounded-0 mb-0 px-2">
            <div class="card-header pb-1">
                <div class="card-title">
                    <h4 class="mb-0">Reset Password</h4>
                </div>
            </div>
            <div class="card-content">
                <div class="card-body pt-1">
                    @include('alerts')
                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <fieldset class="form-label-group form-group position-relative has-icon-left">
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                   name="email"
                                   value="{{ old('email') }}" required autocomplete="email" autofocus>
                            <div class="form-control-position">
                                <i class="feather icon-user"></i>
                            </div>
                            <label for="user-name">Email Address</label>
                            @error('email')
                            <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                            @enderror
                        </fieldset>

                        <fieldset class="form-label-group position-relative has-icon-left">
                            <input id="password" type="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   name="password" required autocomplete="new-password">
                            <div class="form-control-position">
                                <i class="feather icon-lock"></i>
                            </div>
                            <label for="user-password">Password</label>
                            @error('password')
                            <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                            @enderror
                        </fieldset>

                        <fieldset class="form-label-group position-relative has-icon-left">
                            <input id="password-confirm" type="password"
                                   class="form-control" name="password_confirmation" required autocomplete="new-password">
                            <div class="form-control-position">
                                <i class="feather icon-lock"></i>
                            </div>
                            <label for="password-confirm">Confirm Password</label>
                            @error('password')
                            <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                            @enderror
                        </fieldset>

                        <button type="submit" class="btn btn-primary float-right btn-inline">Reset Password</button>
                    </form>
                </div>
            </div>
            <div class="login-footer">
                <div class="divider">
                </div>
            </div>
        </div>
    </div>

@endsection

