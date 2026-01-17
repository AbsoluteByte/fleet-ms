@extends('layouts.admin', ['title' => 'Choose Package'])

@section('content')
        <div class="content-header row">
            <div class="content-header-left col-12 mb-2 text-center">
                <h2 class="content-header-title">Choose Your Plan</h2>
                <p class="text-muted">Select the perfect plan for your business needs</p>
            </div>
        </div>

        <div class="content-body">
            @include('alerts')

            {{-- Billing Toggle --}}
            <div class="text-center mb-4">
                <div class="btn-group btn-group-lg" role="group">
                    <button type="button" class="btn btn-outline-primary active" onclick="showPricing('monthly')">
                        Monthly
                    </button>
                    <button type="button" class="btn btn-outline-primary" onclick="showPricing('quarterly')">
                        Quarterly <span class="badge badge-success">Save 10%</span>
                    </button>
                    <button type="button" class="btn btn-outline-primary" onclick="showPricing('yearly')">
                        Yearly <span class="badge badge-success">Save 20%</span>
                    </button>
                </div>
            </div>

            {{-- Monthly Packages --}}
            <div id="monthly-packages" class="pricing-section">
                <div class="row justify-content-center">
                    @foreach($monthlyPackages as $package)
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="pricing-card {{ $currentSubscription && $currentSubscription->package_id == $package->id ? 'current-plan' : '' }}">
                                @if($currentSubscription && $currentSubscription->package_id == $package->id)
                                    <div class="current-badge">Current Plan</div>
                                @endif

                                <div class="pricing-header">
                                    <h3>{{ $package->name }}</h3>
                                    <div class="price">
                                        <span class="currency">£</span>
                                        <span class="amount">{{ number_format($package->price, 0) }}</span>
                                        <span class="period">/month</span>
                                    </div>
                                    <p class="description">{{ $package->description }}</p>
                                </div>

                                <div class="pricing-body">
                                    <ul class="features-list">
                                        <li><i class="fa fa-check text-success"></i> {{ $package->getUsersLimit() }} Users</li>
                                        <li><i class="fa fa-check text-success"></i> {{ $package->getVehiclesLimit() }} Vehicles</li>
                                        <li><i class="fa fa-check text-success"></i> {{ $package->getDriversLimit() }} Drivers</li>
                                        @if($package->has_notifications)
                                            <li><i class="fa fa-check text-success"></i> Email & SMS Notifications</li>
                                        @endif
                                        @if($package->has_reports)
                                            <li><i class="fa fa-check text-success"></i> Advanced Reports</li>
                                        @endif
                                        @if($package->has_api_access)
                                            <li><i class="fa fa-check text-success"></i> API Access</li>
                                        @endif
                                        @foreach($package->features ?? [] as $feature)
                                            <li><i class="fa fa-check text-success"></i> {{ $feature }}</li>
                                        @endforeach
                                    </ul>
                                </div>

                                <div class="pricing-footer">
                                    @if($currentSubscription && $currentSubscription->package_id == $package->id)
                                        <button class="btn btn-outline-secondary btn-block" disabled>
                                            Current Plan
                                        </button>
                                    @else
                                        <button class="btn btn-primary btn-block" onclick="selectPackage({{ $package->id }}, '{{ $package->name }}')">
                                            <i class="fa fa-shopping-cart"></i> Select Plan
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Quarterly Packages --}}
            <div id="quarterly-packages" class="pricing-section" style="display: none;">
                <div class="row justify-content-center">
                    @foreach($quarterlyPackages as $package)
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="pricing-card">
                                <div class="pricing-header">
                                    <h3>{{ $package->name }}</h3>
                                    <div class="price">
                                        <span class="currency">£</span>
                                        <span class="amount">{{ number_format($package->price, 0) }}</span>
                                        <span class="period">/quarter</span>
                                    </div>
                                    <p class="description">{{ $package->description }}</p>
                                </div>

                                <div class="pricing-body">
                                    <ul class="features-list">
                                        <li><i class="fa fa-check text-success"></i> {{ $package->getUsersLimit() }} Users</li>
                                        <li><i class="fa fa-check text-success"></i> {{ $package->getVehiclesLimit() }} Vehicles</li>
                                        <li><i class="fa fa-check text-success"></i> {{ $package->getDriversLimit() }} Drivers</li>
                                        @foreach($package->features ?? [] as $feature)
                                            <li><i class="fa fa-check text-success"></i> {{ $feature }}</li>
                                        @endforeach
                                    </ul>
                                </div>

                                <div class="pricing-footer">
                                    <button class="btn btn-primary btn-block" onclick="selectPackage({{ $package->id }}, '{{ $package->name }}')">
                                        <i class="fa fa-shopping-cart"></i> Select Plan
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Yearly Packages --}}
            <div id="yearly-packages" class="pricing-section" style="display: none;">
                <div class="row justify-content-center">
                    @foreach($yearlyPackages as $package)
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="pricing-card">
                                <div class="pricing-header">
                                    <h3>{{ $package->name }}</h3>
                                    <div class="price">
                                        <span class="currency">£</span>
                                        <span class="amount">{{ number_format($package->price, 0) }}</span>
                                        <span class="period">/year</span>
                                    </div>
                                    <p class="description">{{ $package->description }}</p>
                                </div>

                                <div class="pricing-body">
                                    <ul class="features-list">
                                        <li><i class="fa fa-check text-success"></i> {{ $package->getUsersLimit() }} Users</li>
                                        <li><i class="fa fa-check text-success"></i> {{ $package->getVehiclesLimit() }} Vehicles</li>
                                        <li><i class="fa fa-check text-success"></i> {{ $package->getDriversLimit() }} Drivers</li>
                                        @foreach($package->features ?? [] as $feature)
                                            <li><i class="fa fa-check text-success"></i> {{ $feature }}</li>
                                        @endforeach
                                    </ul>
                                </div>

                                <div class="pricing-footer">
                                    <button class="btn btn-primary btn-block" onclick="selectPackage({{ $package->id }}, '{{ $package->name }}')">
                                        <i class="fa fa-shopping-cart"></i> Select Plan
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    {{-- Payment Modal --}}
    <div class="modal fade" id="paymentModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa fa-credit-card"></i> Complete Subscription
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="payment-form" action="" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="selected-plan-info mb-1 p-2 bg-light rounded">
                            <h6>Selected Plan: <strong id="selected-plan-name"></strong></h6>
                        </div>

                        <h6 class="mb-3">Enter Card Details</h6>
                        <div id="card-element" class="form-control" style="height: 40px; padding-top: 10px;"></div>
                        <div id="card-errors" class="text-danger mt-2" role="alert"></div>
                        <input type="hidden" name="stripe_payment_method_id" id="stripe_payment_method_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submit-payment">
                            <i class="fa fa-lock"></i> Subscribe Securely
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <style>
        .pricing-card {
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            padding: 30px;
            transition: all 0.3s ease;
            height: 100%;
            position: relative;
            background: white;
        }

        .pricing-card:hover {
            border-color: #667eea;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            transform: translateY(-10px);
        }

        .pricing-card.current-plan {
            border-color: #22c55e;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        }

        .current-badge {
            position: absolute;
            top: -15px;
            right: 20px;
            background: #22c55e;
            color: white;
            padding: 5px 20px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.85rem;
        }

        .pricing-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .pricing-header h3 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .price {
            margin: 20px 0;
        }

        .price .currency {
            font-size: 1.5rem;
            vertical-align: top;
        }

        .price .amount {
            font-size: 3.5rem;
            font-weight: 700;
            color: #667eea;
        }

        .price .period {
            font-size: 1rem;
            color: #6b7280;
        }

        .description {
            color: #6b7280;
            font-size: 0.95rem;
        }

        .features-list {
            list-style: none;
            padding: 0;
            margin-bottom: 30px;
        }

        .features-list li {
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .features-list li:last-child {
            border-bottom: none;
        }
    </style>
@endsection

@section('js')
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        const stripe = Stripe('{{ env('STRIPE_KEY') }}');
        const elements = stripe.elements();
        const cardElement = elements.create('card');
        cardElement.mount('#card-element');

        cardElement.on('change', function(event) {
            const displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });

        function showPricing(period) {
            document.querySelectorAll('.pricing-section').forEach(el => el.style.display = 'none');
            document.getElementById(period + '-packages').style.display = 'block';

            document.querySelectorAll('.btn-group .btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
        }

        function selectPackage(packageId, packageName) {
            document.getElementById('selected-plan-name').textContent = packageName;
            document.getElementById('payment-form').action = '{{ route("subscription.subscribe", ":id") }}'.replace(':id', packageId);
            $('#paymentModal').modal('show');
        }

        document.getElementById('payment-form').addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submit-payment');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';

            const {paymentMethod, error} = await stripe.createPaymentMethod({
                type: 'card',
                card: cardElement,
            });

            if (error) {
                document.getElementById('card-errors').textContent = error.message;
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa fa-lock"></i> Subscribe Securely';
                return;
            }

            document.getElementById('stripe_payment_method_id').value = paymentMethod.id;
            this.submit();
        });
    </script>
@endsection
