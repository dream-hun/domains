<x-user-layout>
    <div class="rts-hosting-banner rts-hosting-banner-bg">
        <div class="container">
            <div class="row">
                <div class="banner-area">
                    <div class="rts-hosting-banner rts-hosting-banner__content about__banner">
                        <h1 class="banner-title sal-animate" data-sal="slide-down" data-sal-delay="200"
                            data-sal-duration="800">
                            Payment
                        </h1>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5">
        <div class="row g-4">
            <!-- Order Summary -->
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">Order Summary</h3>
                    </div>
                    <div class="card-body">
                        @foreach ($cartItems as $item)
                            @php
                                // Handle both Cart facade array structure and direct array structure
                                $domainName = $item['name'] ?? $item['domain_name'] ?? 'Domain';
                                $domainType = $item['attributes']['type'] ?? $item['domain_type'] ?? 'registration';
                                $years = $item['quantity'] ?? $item['years'] ?? 1;
                                $price = $item['price'] ?? 0;
                            @endphp
                            <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
                                <div class="flex-grow-1">
                                    <h5 class="mb-1 text-dark">{{ $domainName }}</h5>
                                    <small class="text-muted">{{ ucfirst($domainType) }} - {{ $years }} year(s)</small>
                                </div>
                                <div class="text-end">
                                    <span class="fw-bold text-dark">@price($price * $years, 'USD')</span>
                                </div>
                            </div>
                        @endforeach

                        <div class="d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                            <h4 class="mb-0 text-dark">Total</h4>
                            <h4 class="mb-0 text-primary">@price($totalAmount, 'USD')</h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">Choose Payment Method</h3>
                    </div>
                    <div class="card-body">
                        <!-- Stripe Payment -->
                        <div class="border rounded p-4 mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center"
                                            style="width: 40px; height: 40px;">
                                            <i class="bi bi-credit-card text-white"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <h5 class="mb-1 text-dark">Credit/Debit Card</h5>
                                        <small class="text-muted">Secure payment via Stripe</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="https://js.stripe.com/v3/fingerprinted/img/visa-8c9b85c56a6b5c8c8b5b5b5b5b5b5b5b.svg"
                                        alt="Visa" class="img-fluid" style="height: 24px;">
                                    <img src="https://js.stripe.com/v3/fingerprinted/img/mastercard-8c9b85c56a6b5c8c8b5b5b5b5b5b5b5b.svg"
                                        alt="Mastercard" class="img-fluid" style="height: 24px;">
                                    <img src="https://js.stripe.com/v3/fingerprinted/img/amex-8c9b85c56a6b5c8c8b5b5b5b5b5b5b5b.svg"
                                        alt="American Express" class="img-fluid" style="height: 24px;">
                                </div>
                            </div>

                            <form method="POST" action="{{ route('payment.stripe') }}" id="stripe-payment-form">
                                @csrf

                                <!-- Billing Information -->
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="billing_name" class="form-label">Full Name</label>
                                        <input type="text" name="billing_name" id="billing_name"
                                            value="{{ $user->name }}" required class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="billing_email" class="form-label">Email</label>
                                        <input type="email" name="billing_email" id="billing_email"
                                            value="{{ $user->email }}" required class="form-control">
                                    </div>

                                    <div class="col-12">
                                        <label for="billing_address" class="form-label">Address</label>
                                        <input type="text" name="billing_address" id="billing_address"
                                            class="form-control">
                                    </div>

                                    <div class="col-md-4">
                                        <label for="billing_city" class="form-label">City</label>
                                        <input type="text" name="billing_city" id="billing_city"
                                            class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="billing_country" class="form-label">Country</label>
                                        <input type="text" name="billing_country" id="billing_country"
                                            class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="billing_postal_code" class="form-label">Postal Code</label>
                                        <input type="text" name="billing_postal_code" id="billing_postal_code"
                                            class="form-control">
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <button type="submit"
                                        class="btn btn-primary btn-lg w-100 d-flex align-items-center justify-content-center">
                                        <i class="bi bi-credit-card me-2"></i>
                                        Pay @price($totalAmount, 'USD') with Card
                                    </button>
                                </div>
                            </form>
                        </div>

                        @if (config('services.payment.kpay.base_url') &&
                             config('services.payment.kpay.username') &&
                             config('services.payment.kpay.password') &&
                             config('services.payment.kpay.retailer_id'))
                            <!-- KPay Payment -->
                            <div class="border rounded p-4">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <div class="bg-success rounded-circle d-flex align-items-center justify-content-center"
                                                style="width: 40px; height: 40px;">
                                                <i class="bi bi-phone text-white"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <h5 class="mb-1 text-dark">KPay Mobile Money</h5>
                                            <small class="text-muted">Pay with Mobile Money, Bank Card, or Bank Transfer</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <a href="{{ route('payment.kpay.show') }}"
                                       class="btn btn-success btn-lg w-100 d-flex align-items-center justify-content-center">
                                        <i class="bi bi-phone me-2"></i>
                                        Pay @price($totalAmount, 'USD') with KPay
                                    </a>
                                </div>
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>

        <!-- Back to Cart -->
        <div class="row mt-4">
            <div class="col-12">
                <a href="{{ route('cart.index') }}" class="btn btn-outline-secondary d-flex align-items-center">
                    <i class="bi bi-arrow-left me-2"></i>
                    Back to Cart
                </a>
            </div>
        </div>
    </div>
</x-user-layout>
