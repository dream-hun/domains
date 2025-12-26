<x-admin-layout>
    @section('page-title')
        Order Payment
    @endsection
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Complete Payment</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item active">Payment</li>
                    </ol>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="row">
                <!-- Payment Form -->
                <div class="col-lg-7">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white border-bottom">
                            <h3 class="card-title mb-0">
                                <i class="bi bi-credit-card mr-2"></i>Payment Information
                            </h3>
                        </div>
                        <div class="card-body">
                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if (session('error'))
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                    {{ session('error') }}
                                </div>
                            @endif

                            <form method="POST" action="{{ route('payment.kpay') }}" id="kpay-payment-form">
                                @csrf
                                <input type="hidden" name="payment_method" id="payment_method" value="{{ old('payment_method', 'momo') }}">

                                <!-- Billing Information Section -->
                                <div class="mb-4">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="billing_name" class="form-label">
                                                Full Name <span class="text-danger">*</span>
                                            </label>
                                            <input type="text"
                                                   name="billing_name"
                                                   id="billing_name"
                                                   value="{{ old('billing_name', $user->name) }}"
                                                   required
                                                   class="form-control @error('billing_name') is-invalid @enderror">
                                            @error('billing_name')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label for="billing_email" class="form-label">
                                                Email <span class="text-danger">*</span>
                                            </label>
                                            <input type="email"
                                                   name="billing_email"
                                                   id="billing_email"
                                                   value="{{ old('billing_email', $user->email) }}"
                                                   required
                                                   class="form-control @error('billing_email') is-invalid @enderror">
                                            @error('billing_email')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-12">
                                            <label for="billing_address" class="form-label">Address</label>
                                            <input type="text"
                                                   name="billing_address"
                                                   id="billing_address"
                                                   value="{{ old('billing_address') }}"
                                                   class="form-control @error('billing_address') is-invalid @enderror">
                                            @error('billing_address')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label for="billing_city" class="form-label">City</label>
                                            <input type="text"
                                                   name="billing_city"
                                                   id="billing_city"
                                                   value="{{ old('billing_city') }}"
                                                   class="form-control @error('billing_city') is-invalid @enderror">
                                            @error('billing_city')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label for="billing_country" class="form-label">Country</label>
                                            <input type="text"
                                                   name="billing_country"
                                                   id="billing_country"
                                                   value="{{ old('billing_country', 'RW') }}"
                                                   class="form-control @error('billing_country') is-invalid @enderror">
                                            @error('billing_country')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label for="billing_postal_code" class="form-label">Postal Code</label>
                                            <input type="text"
                                                   name="billing_postal_code"
                                                   id="billing_postal_code"
                                                   value="{{ old('billing_postal_code') }}"
                                                   class="form-control @error('billing_postal_code') is-invalid @enderror">
                                            @error('billing_postal_code')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Method Selection -->
                                <div class="mb-4">
                                    <h5 class="mb-3 text-dark">
                                        <i class="bi bi-wallet2 mr-2"></i>Payment Method
                                    </h5>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="payment-method-option" data-method="momo">
                                                <input type="radio"
                                                       name="pmethod"
                                                       id="payment_momo"
                                                       value="momo"
                                                       {{ old('pmethod', 'momo') === 'momo' ? 'checked' : '' }}
                                                       class="d-none">
                                                <label for="payment_momo" class="payment-method-card">
                                                    <div class="payment-method-content">
                                                        <i class="bi bi-phone mb-2"></i>
                                                        <h6 class="mb-1">Mobile Money</h6>
                                                        <small class="text-muted">Pay with Momo</small>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="payment-method-option" data-method="card">
                                                <input type="radio"
                                                       name="pmethod"
                                                       id="payment_card"
                                                       value="cc"
                                                       {{ old('pmethod') === 'cc' ? 'checked' : '' }}
                                                       class="d-none">
                                                <label for="payment_card" class="payment-method-card">
                                                    <div class="payment-method-content">
                                                        <i class="bi bi-credit-card mb-2"></i>
                                                        <h6 class="mb-1">Credit/Debit Card</h6>
                                                        <small class="text-muted">Pay with Card</small>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Phone Number (Required for all KPay payment methods) -->
                                <div class="mb-4">
                                    <div class="mb-3">
                                        <label for="msisdn" class="form-label">
                                            Phone Number <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">
                                                    <i class="bi bi-phone"></i>
                                                </span>
                                            </div>
                                            <input type="tel"
                                                   id="msisdn"
                                                   name="msisdn"
                                                   value="{{ old('msisdn', $user->phone ?? '') }}"
                                                   required
                                                   class="form-control @error('msisdn') is-invalid @enderror"
                                                   placeholder="250788123456">
                                            @error('msisdn')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <small class="form-text text-muted">Enter your phone number (required for KPay verification)</small>
                                    </div>
                                </div>

                                <!-- Card Payment Fields -->
                                <div id="card-fields" class="payment-fields" style="display: none;">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="card_number" class="form-label">
                                                Card Number <span class="text-danger">*</span>
                                            </label>
                                            <input type="text"
                                                   id="card_number"
                                                   name="card_number"
                                                   value="{{ old('card_number') }}"
                                                   class="form-control @error('card_number') is-invalid @enderror"
                                                   placeholder="1234 5678 9012 3456"
                                                   maxlength="19">
                                            @error('card_number')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label for="card_expiry" class="form-label">
                                                Expiry Date <span class="text-danger">*</span>
                                            </label>
                                            <input type="text"
                                                   id="card_expiry"
                                                   name="card_expiry"
                                                   value="{{ old('card_expiry') }}"
                                                   class="form-control @error('card_expiry') is-invalid @enderror"
                                                   placeholder="MM/YY"
                                                   maxlength="5">
                                            @error('card_expiry')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label for="card_cvv" class="form-label">
                                                CVV <span class="text-danger">*</span>
                                            </label>
                                            <input type="text"
                                                   id="card_cvv"
                                                   name="card_cvv"
                                                   value="{{ old('card_cvv') }}"
                                                   class="form-control @error('card_cvv') is-invalid @enderror"
                                                   placeholder="123"
                                                   maxlength="4">
                                            @error('card_cvv')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-12">
                                            <label for="cardholder_name" class="form-label">
                                                Cardholder Name <span class="text-danger">*</span>
                                            </label>
                                            <input type="text"
                                                   id="cardholder_name"
                                                   name="cardholder_name"
                                                   value="{{ old('cardholder_name', $user->name) }}"
                                                   class="form-control @error('cardholder_name') is-invalid @enderror"
                                                   placeholder="Name on card">
                                            @error('cardholder_name')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Security Notice -->
                                <div class="alert alert-info mt-4 mb-4">
                                    <i class="bi bi-shield-check mr-2"></i>
                                    <strong>Secure Payment:</strong> Your payment information is encrypted and secure.
                                    We do not store your payment credentials.
                                </div>

                                <!-- Submit Button -->
                                <div class="mt-4">
                                    <button type="submit"
                                            id="submit-btn"
                                            class="btn btn-primary btn-lg w-100 d-flex align-items-center justify-content-center">
                                        <i class="bi bi-lock mr-2"></i>
                                        <span id="submit-text">
                                            Pay {{ $currency ?? 'USD' }} {{ number_format($totalAmount, 2) }}
                                        </span>
                                        <span id="submit-spinner" class="spinner-border spinner-border-sm ml-2 d-none" role="status">
                                            <span class="sr-only">Loading...</span>
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- Order Summary -->
                <div class="col-lg-5">
                    <div class="card shadow-sm sticky-top" style="top: 20px;">
                        <div class="card-header bg-primary text-white">
                            <h3 class="card-title mb-0">
                                <i class="bi bi-cart mr-2"></i>Order Summary
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="order-items">
                                @forelse ($cartItems as $item)
                                    @php
                                        $domainName = $item['domain_name'] ?? $item['name'] ?? 'Domain';
                                        $domainType = $item['domain_type'] ?? $item['attributes']['type'] ?? 'registration';
                                        $quantity = $item['quantity'] ?? 1;
                                        $years = $item['years'] ?? ($domainType === 'hosting' || $domainType === 'subscription_renewal' ? (int)($quantity / 12) : $quantity);
                                        $itemPrice = $item['price'] ?? 0;
                                        $itemTotal = $itemPrice * $quantity;
                                        $itemCurrency = $item['currency'] ?? $currency ?? 'USD';
                                    @endphp
                                    <div class="d-flex justify-content-between align-items-start py-3 border-bottom">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 text-dark font-weight-bold">{{ $domainName }}</h6>
                                            <small class="text-muted d-block">
                                                {{ ucfirst(str_replace('_', ' ', $domainType)) }}
                                                @if($domainType === 'hosting' || $domainType === 'subscription_renewal')
                                                    - {{ $quantity }} month(s)
                                                @else
                                                    - {{ $years }} year(s)
                                                @endif
                                            </small>
                                        </div>
                                        <div class="text-end ml-3">
                                            <span class="font-weight-bold text-dark">
                                                {{ $itemCurrency }} {{ number_format($itemTotal, 2) }}
                                            </span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-4">
                                        <i class="bi bi-cart text-muted mb-3" style="font-size: 3rem;"></i>
                                        <p class="text-muted mb-0">No items in cart</p>
                                    </div>
                                @endforelse
                            </div>

                            @if(!empty($cartItems))
                                <div class="border-top pt-3 mt-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted">Subtotal</span>
                                        <span class="text-muted">
                                            {{ $currency ?? 'USD' }} {{ number_format($subtotal ?? $totalAmount, 2) }}
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                        <h5 class="mb-0 font-weight-bold">Total</h5>
                                        <h4 class="mb-0 text-primary font-weight-bold">
                                            {{ $currency ?? 'USD' }} {{ number_format($totalAmount, 2) }}
                                        </h4>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    @section('styles')
        <style>
            .payment-method-option {
                position: relative;
            }

            .payment-method-card {
                display: block;
                padding: 1.5rem;
                border: 2px solid #dee2e6;
                border-radius: 0.5rem;
                cursor: pointer;
                transition: all 0.3s ease;
                background: #fff;
                text-align: center;
                height: 100%;
            }

            .payment-method-card:hover {
                border-color: #007bff;
                box-shadow: 0 4px 8px rgba(0, 123, 255, 0.1);
                transform: translateY(-2px);
            }

            .payment-method-option input:checked + .payment-method-card {
                border-color: #007bff;
                background: #f0f8ff;
                box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
            }

            .payment-method-content {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }

            .payment-fields {
                animation: fadeIn 0.3s ease-in;
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(-10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .order-items {
                max-height: 400px;
                overflow-y: auto;
            }

            .order-items::-webkit-scrollbar {
                width: 6px;
            }

            .order-items::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 10px;
            }

            .order-items::-webkit-scrollbar-thumb {
                background: #888;
                border-radius: 10px;
            }

            .order-items::-webkit-scrollbar-thumb:hover {
                background: #555;
            }
        </style>
    @endsection

    @section('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const form = document.getElementById('kpay-payment-form');
                const submitBtn = document.getElementById('submit-btn');
                const submitText = document.getElementById('submit-text');
                const submitSpinner = document.getElementById('submit-spinner');
                const paymentMethodInput = document.getElementById('payment_method');
                const cardFields = document.getElementById('card-fields');
                const paymentMethodRadios = document.querySelectorAll('input[name="pmethod"]');

                // Function to toggle payment method fields
                function togglePaymentFields(selectedMethod) {
                    // MSISDN is always required for KPay, so we don't hide it
                    const msisdnField = document.getElementById('msisdn');
                    if (msisdnField) {
                        msisdnField.required = true;
                    }

                    if (selectedMethod === 'momo') {
                        cardFields.style.display = 'none';
                        // Make card fields not required
                        const cardNumber = document.getElementById('card_number');
                        const cardExpiry = document.getElementById('card_expiry');
                        const cardCvv = document.getElementById('card_cvv');
                        const cardholderName = document.getElementById('cardholder_name');
                        if (cardNumber) cardNumber.required = false;
                        if (cardExpiry) cardExpiry.required = false;
                        if (cardCvv) cardCvv.required = false;
                        if (cardholderName) cardholderName.required = false;
                    } else if (selectedMethod === 'cc') {
                        cardFields.style.display = 'block';
                        // Make card fields required
                        const cardNumber = document.getElementById('card_number');
                        const cardExpiry = document.getElementById('card_expiry');
                        const cardCvv = document.getElementById('card_cvv');
                        const cardholderName = document.getElementById('cardholder_name');
                        if (cardNumber) cardNumber.required = true;
                        if (cardExpiry) cardExpiry.required = true;
                        if (cardCvv) cardCvv.required = true;
                        if (cardholderName) cardholderName.required = true;
                    }
                }

                // Payment method toggle
                paymentMethodRadios.forEach(radio => {
                    radio.addEventListener('change', function() {
                        const selectedMethod = this.value;
                        // Update hidden payment_method field for consistency
                        if (paymentMethodInput) {
                            paymentMethodInput.value = selectedMethod === 'cc' ? 'card' : selectedMethod;
                        }
                        togglePaymentFields(selectedMethod);
                    });
                });

                // Initialize on page load
                const initialMethod = document.querySelector('input[name="pmethod"]:checked')?.value || 'momo';
                if (paymentMethodInput) {
                    paymentMethodInput.value = initialMethod === 'cc' ? 'card' : initialMethod;
                }
                togglePaymentFields(initialMethod);

                // Form submission with validation
                form.addEventListener('submit', function (e) {
                    const selectedMethod = document.querySelector('input[name="pmethod"]:checked')?.value;

                    if (!selectedMethod) {
                        e.preventDefault();
                        alert('Please select a payment method.');
                        return false;
                    }

                    // Validate required fields based on selected method
                    // MSISDN is always required for KPay
                    const msisdn = document.getElementById('msisdn').value.trim();
                    if (!msisdn) {
                        e.preventDefault();
                        alert('Phone number is required for KPay payment.');
                        document.getElementById('msisdn').focus();
                        return false;
                    }

                    if (selectedMethod === 'cc') {
                        const cardNumber = document.getElementById('card_number').value.trim();
                        const cardExpiry = document.getElementById('card_expiry').value.trim();
                        const cardCvv = document.getElementById('card_cvv').value.trim();
                        const cardholderName = document.getElementById('cardholder_name').value.trim();

                        if (!cardNumber || !cardExpiry || !cardCvv || !cardholderName) {
                            e.preventDefault();
                            alert('Please fill in all card payment fields.');
                            if (!cardNumber) document.getElementById('card_number').focus();
                            else if (!cardExpiry) document.getElementById('card_expiry').focus();
                            else if (!cardCvv) document.getElementById('card_cvv').focus();
                            else if (!cardholderName) document.getElementById('cardholder_name').focus();
                            return false;
                        }
                    }

                    // Disable submit button and show loading state
                    submitBtn.disabled = true;
                    submitText.textContent = 'Processing Payment...';
                    submitSpinner.classList.remove('d-none');
                });

                // Format phone number input
                const msisdnInput = document.getElementById('msisdn');
                if (msisdnInput) {
                    msisdnInput.addEventListener('input', function (e) {
                        let value = e.target.value;
                        if (value.startsWith('+')) {
                            value = '+' + value.slice(1).replace(/\D/g, '');
                        } else {
                            value = value.replace(/\D/g, '');
                        }
                        e.target.value = value;
                    });
                }

                // Format card number input
                const cardNumberInput = document.getElementById('card_number');
                if (cardNumberInput) {
                    cardNumberInput.addEventListener('input', function (e) {
                        let value = e.target.value.replace(/\s/g, '').replace(/\D/g, '');
                        let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
                        if (formattedValue.length > 19) {
                            formattedValue = formattedValue.substring(0, 19);
                        }
                        e.target.value = formattedValue;
                    });
                }

                // Format card expiry input
                const cardExpiryInput = document.getElementById('card_expiry');
                if (cardExpiryInput) {
                    cardExpiryInput.addEventListener('input', function (e) {
                        let value = e.target.value.replace(/\D/g, '');
                        if (value.length >= 2) {
                            value = value.substring(0, 2) + '/' + value.substring(2, 4);
                        }
                        e.target.value = value;
                    });
                }

                // Format CVV input (numbers only)
                const cardCvvInput = document.getElementById('card_cvv');
                if (cardCvvInput) {
                    cardCvvInput.addEventListener('input', function (e) {
                        e.target.value = e.target.value.replace(/\D/g, '');
                    });
                }
            });
        </script>
    @endsection
</x-admin-layout>
