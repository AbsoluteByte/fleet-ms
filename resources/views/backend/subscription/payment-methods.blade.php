@extends('layouts.admin', ['title' => 'Payment Methods'])

@section('content')
        <div class="content-header row">
            <div class="content-header-left col-md-9 col-12 mb-2">
                <h2 class="content-header-title">
                    <i class="fa fa-credit-card"></i> Payment Methods
                </h2>
                <p class="text-muted">Manage your payment cards</p>
            </div>
            <div class="content-header-right text-md-right col-md-3 col-12">
                <button class="btn btn-primary" data-toggle="modal" data-target="#addCardModal">
                    <i class="fa fa-plus"></i> Add Card
                </button>
            </div>
        </div>

        <div class="content-body">
            @include('alerts')

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            @if($paymentMethods->count() > 0)
                                <div class="row">
                                    @foreach($paymentMethods as $method)
                                        <div class="col-lg-4 col-md-6 mb-4">
                                            <div class="card-payment-method {{ $method->is_default ? 'default-card' : '' }}">
                                                @if($method->is_default)
                                                    <div class="default-badge">
                                                        <i class="fa fa-star"></i> Default
                                                    </div>
                                                @endif

                                                <div class="card-brand-icon">
                                                    @if(strtolower($method->card_brand) == 'visa')
                                                        <i class="fab fa-cc-visa fa-3x text-primary"></i>
                                                    @elseif(strtolower($method->card_brand) == 'mastercard')
                                                        <i class="fab fa-cc-mastercard fa-3x text-warning"></i>
                                                    @elseif(strtolower($method->card_brand) == 'amex')
                                                        <i class="fab fa-cc-amex fa-3x text-info"></i>
                                                    @else
                                                        <i class="fa fa-credit-card fa-3x text-secondary"></i>
                                                    @endif
                                                </div>

                                                <div class="card-details">
                                                    <h5 class="card-number">
                                                        •••• •••• •••• {{ $method->card_last_four }}
                                                    </h5>
                                                    <p class="card-brand">{{ ucfirst($method->card_brand) }}</p>
                                                    <p class="card-expiry">
                                                        <i class="fa fa-calendar"></i>
                                                        Expires {{ $method->getExpiryDisplay() }}
                                                    </p>

                                                    @if($method->isExpired())
                                                        <span class="badge badge-danger">Expired</span>
                                                    @elseif($method->isExpiringSoon())
                                                        <span class="badge badge-warning">Expiring Soon</span>
                                                    @else
                                                        <span class="badge badge-success">Active</span>
                                                    @endif
                                                </div>

                                                <div class="card-actions mt-3">
                                                    @if(!$method->is_default)
                                                        <button class="btn btn-sm btn-outline-primary"
                                                                onclick="makeDefault({{ $method->id }})">
                                                            <i class="fa fa-star"></i> Make Default
                                                        </button>
                                                    @endif

                                                    <form action="{{ route($url . 'payment-methods.remove', $method->id) }}"
                                                          method="POST" style="display: inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                                onclick="return confirm('Remove this card?')">
                                                            <i class="fa fa-trash"></i> Remove
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="fa fa-credit-card fa-4x text-muted mb-3"></i>
                                    <h4>No Payment Methods Added</h4>
                                    <p class="text-muted mb-4">Add a card to manage your subscription</p>
                                    <button class="btn btn-primary btn-lg" data-toggle="modal" data-target="#addCardModal">
                                        <i class="fa fa-plus"></i> Add Your First Card
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Security Info --}}
            <div class="row">
                <div class="col-12">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5><i class="fa fa-shield-alt text-success"></i> Your Payment is Secure</h5>
                            <p class="mb-0">
                                <i class="fa fa-check text-success"></i> All transactions are encrypted with SSL<br>
                                <i class="fa fa-check text-success"></i> We never store your full card details<br>
                                <i class="fa fa-check text-success"></i> Powered by Stripe - PCI DSS compliant
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    {{-- Add Card Modal --}}
    <div class="modal fade" id="addCardModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa fa-credit-card"></i> Add Payment Method
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="add-card-form" action="{{ route($url . 'payment-methods.add') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            Your card details are securely processed by Stripe. We never store your full card number.
                        </div>

                        <div class="form-group">
                            <label>Card Information</label>
                            <div id="card-element-add" class="form-control" style="height: 40px; padding-top: 10px;"></div>
                            <div id="card-errors-add" class="text-danger mt-2" role="alert"></div>
                            <input type="hidden" name="stripe_payment_method_id" id="stripe_payment_method_id_add">
                        </div>

                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="make_default" name="make_default">
                            <label class="custom-control-label" for="make_default">
                                Make this my default payment method
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submit-card">
                            <i class="fa fa-plus"></i> Add Card
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <style>
        .card-payment-method {
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            padding: 25px;
            transition: all 0.3s ease;
            position: relative;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            height: 100%;
        }

        .card-payment-method:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .card-payment-method.default-card {
            border-color: #fbbf24;
            box-shadow: 0 5px 20px rgba(251, 191, 36, 0.3);
        }

        .default-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #fbbf24;
            color: #000;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
        }

        .card-brand-icon {
            text-align: center;
            margin-bottom: 20px;
            background: rgba(255,255,255,0.2);
            padding: 20px;
            border-radius: 10px;
        }

        .card-details {
            text-align: center;
        }

        .card-number {
            font-size: 1.5rem;
            font-weight: 600;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }

        .card-brand {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .card-expiry {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .card-actions {
            display: flex;
            justify-content: space-around;
            gap: 10px;
        }

        .card-actions .btn {
            flex: 1;
        }
    </style>
@endsection

@section('js')
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        const stripe = Stripe('{{ env('STRIPE_KEY') }}');
        const elements = stripe.elements();
        const cardElementAdd = elements.create('card');
        cardElementAdd.mount('#card-element-add');

        cardElementAdd.on('change', function(event) {
            const displayError = document.getElementById('card-errors-add');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });

        document.getElementById('add-card-form').addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submit-card');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';

            const {paymentMethod, error} = await stripe.createPaymentMethod({
                type: 'card',
                card: cardElementAdd,
            });

            if (error) {
                document.getElementById('card-errors-add').textContent = error.message;
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa fa-plus"></i> Add Card';
                return;
            }

            document.getElementById('stripe_payment_method_id_add').value = paymentMethod.id;
            this.submit();
        });

        function makeDefault(methodId) {
            // Implement make default functionality
            console.log('Make default:', methodId);
            alert('This feature will be implemented with AJAX');
        }
    </script>
@endsection
