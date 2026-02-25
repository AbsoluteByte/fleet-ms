@extends('layouts.auth', ['title' => 'Register'])

@section('content')
    <!--
    <div class="col-lg-6 d-lg-block d-none text-center align-self-center pl-0 pr-3 py-0">
        <img src="{{ asset('app-assets/images/logo/app-logo.png') }}" alt="branding logo">
    </div>
    -->
    <div class="col-lg-12 col-12 p-0">
        <div class="card rounded-0 mb-0 p-2">
            <div class="card-header pt-50 pb-1">
                <div class="card-title">
                    <h4 class="mb-0">Create Account</h4>
                </div>
            </div>
            <p class="px-2">Start your 30-day free trial. No credit card required.</p>

            <div class="card-content">
                <div class="card-body pt-0">
                    @include('alerts')

                    {{-- ✅ Trial Benefits --}}
                    <div class="alert alert-success mb-3">
                        <h6 class="mb-2"><i class="fa fa-check-circle"></i> What's Included in Your Free Trial:</h6>
                        <ul class="mb-0 pl-3">
                            <li>✅ Full access to all features</li>
                            <li>✅ Up to 5 vehicles & drivers</li>
                            <li>✅ 30 days completely free</li>
                            <li>✅ No credit card required</li>
                        </ul>
                    </div>

                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        {{-- Company Name --}}
                        <div class="form-label-group">
                            <input id="company_name" type="text"
                                   class="form-control @error('company_name') is-invalid @enderror"
                                   name="company_name" value="{{ old('company_name') }}"
                                   required autofocus>
                            <label for="company_name">Company Name *</label>
                            @error('company_name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        {{-- Your Name --}}
                        <div class="form-label-group">
                            <input id="name" type="text"
                                   class="form-control @error('name') is-invalid @enderror"
                                   name="name" value="{{ old('name') }}" required>
                            <label for="name">Your Name *</label>
                            @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div class="form-label-group">
                            <input id="email" type="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   name="email" value="{{ old('email') }}" required>
                            <label for="email">Email Address *</label>
                            @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        {{-- Password --}}
                        <div class="form-label-group">
                            <input id="password" type="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   name="password" required>
                            <label for="password">Password *</label>
                            @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        {{-- Confirm Password --}}
                        <div class="form-label-group">
                            <input id="password-confirm" type="password"
                                   class="form-control"
                                   name="password_confirmation" required>
                            <label for="password-confirm">Confirm Password *</label>
                        </div>

                        {{-- Terms --}}
                        <div class="form-group row">
                            <div class="col-12">
                                <fieldset class="checkbox">
                                    <div class="vs-checkbox-con vs-checkbox-primary">
                                        <input type="checkbox" name="terms" id="terms" required>
                                        <span class="vs-checkbox">
                                            <span class="vs-checkbox--check">
                                                <i class="vs-icon feather icon-check"></i>
                                            </span>
                                        </span>
                                        <span>I accept the <a href="#" target="_blank">terms & conditions</a> *</span>
                                    </div>
                                </fieldset>
                                @error('terms')
                                <span class="text-danger">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                        {{-- Submit Buttons --}}
                        <a href="{{ route('login') }}" class="btn btn-outline-primary float-left btn-inline mb-50">
                            Login
                        </a>
                        <button type="submit" class="btn btn-success float-right btn-inline mb-50">
                            <i class="fa fa-rocket"></i> Start Free Trial
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
