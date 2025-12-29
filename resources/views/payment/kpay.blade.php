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

                            <form method="POST" action="{{ route('payment.kpay') }}" autocomplete="off">
                                @csrf
                                <input type="hidden" name="pmethod" id="pmethod" value="{{ old('pmethod', 'momo') }}">

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
                                        <div class="col-12 mt-4">
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
                                        <div class="col-md-4 mt-4">
                                            <label for="billing_country" class="form-label">Country</label>
                                            <input type="text"
                                                   name="billing_country"
                                                   id="billing_country"
                                                   value="{{ old('billing_country', 'Rwanda') }}"
                                                   class="form-control @error('billing_country') is-invalid @enderror">
                                            @error('billing_country')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4 mt-4">
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

                                        <div class="col-md-4 mt-4">
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
                                        <small class="form-text text-muted">Enter your phone number (required for payment)</small>
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
</x-admin-layout>
