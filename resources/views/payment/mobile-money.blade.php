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
                                <i class="bi bi-phone mr-2"></i>Mobile Money Payment
                            </h3>
                        </div>
                        <div class="card-body">
                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"
                                            aria-label="Close"></button>
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if (session('error'))
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"
                                            aria-label="Close"></button>
                                    {{ session('error') }}
                                </div>
                            @endif

                            <form method="POST" action="{{ route('payment.mobile-money') }}" id="payment-form"
                                  autocomplete="off">
                                @csrf

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
                                                   value="{{ old('billing_email', $user->address->email ?? '') }}"
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
                                                   value="{{ old('billing_address', $user->address->address_line_one ?? '') }}"
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
                                                   value="{{ old('billing_city', $user->address->city ?? '') }}"
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
                                                   value="{{ old('billing_postal_code', $user->address->postal_code ?? '') }}"
                                                   class="form-control @error('billing_postal_code') is-invalid @enderror">
                                            @error('billing_postal_code')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Phone Number -->
                                <div class="mb-4">
                                    <div class="mb-3">
                                        <label for="msisdn" class="form-label">
                                            Mobile Money Number <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <select name="phone_country_code"
                                                    class="form-select flex-grow-0"
                                                    style="max-width: 120px;"
                                                    aria-label="Country code">
                                                <option value="250" selected>+250 🇷🇼</option>
                                            </select>
                                            <input type="tel"
                                                   id="msisdn"
                                                   name="msisdn"
                                                   value="{{ old('msisdn', $user->address->phone_number ?? '') }}"
                                                   required
                                                   class="form-control @error('msisdn') is-invalid @enderror"
                                                   placeholder="788123456">
                                            @error('msisdn')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <small class="form-text text-muted">
                                            Enter your MTN or Airtel number without country code (e.g. 788123456)
                                        </small>
                                    </div>
                                </div>

                                <!-- Security Notice -->
                                <div class="alert alert-info mt-4 mb-4">
                                    <i class="bi bi-shield-check mr-2"></i>
                                    <strong>Secure Payment:</strong> Your payment is processed securely.
                                    You will receive a prompt on your mobile to confirm the payment.
                                </div>

                                <!-- Submit Button -->
                                <div class="mt-4">
                                    <button type="submit"
                                            id="submit-btn"
                                            class="btn btn-primary btn-lg w-100 d-flex align-items-center justify-content-center">
                                        <i class="bi bi-phone mr-2"></i>
                                        <span id="submit-text">
                                            Pay @price($totalAmount, $currency ?? 'RWF') with Mobile Money
                                        </span>
                                        <span id="submit-spinner" class="spinner-border spinner-border-sm ml-2 d-none"
                                              role="status">
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
                                        $itemCurrency = $item['currency'] ?? $currency ?? 'RWF';
                                        $displayCurrency = mb_strtoupper((string) $itemCurrency) === 'FRW' ? 'RWF' : $itemCurrency;
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
                                                @price($itemTotal, $displayCurrency)
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
                                @php
                                    $displayCurrency = $currency ?? 'RWF';
                                    $displayCurrency = mb_strtoupper((string) $displayCurrency) === 'FRW' ? 'RWF' : $displayCurrency;
                                @endphp
                                <div class="border-top pt-3 mt-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted">Subtotal</span>
                                        <span class="text-muted">
                                            @price($subtotal ?? $totalAmount, $displayCurrency)
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                        <h5 class="mb-0 font-weight-bold">Total</h5>
                                        <h4 class="mb-0 text-primary font-weight-bold">
                                            @price($totalAmount, $displayCurrency)
                                        </h4>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Processing Modal -->
            <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel"
                 aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content text-center">
                        <div class="modal-header border-0">
                            <h5 class="modal-title w-100" id="paymentModalLabel">Processing Payment</h5>
                        </div>
                        <div class="modal-body py-5">
                            <div id="modal-content-processing">
                                <div class="spinner-border text-primary mb-3" role="status"
                                     style="width: 3rem; height: 3rem;">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p class="mb-2" id="modal-message">Initializing payment...</p>
                                <small class="text-muted" id="modal-submessage"></small>
                            </div>
                            <div id="modal-content-error" class="d-none">
                                <i class="bi bi-exclamation-circle text-danger mb-3" style="font-size: 3rem;"></i>
                                <p class="text-danger mb-0" id="error-message">Payment failed</p>
                            </div>
                        </div>
                        <div class="modal-footer border-0 justify-content-center" id="modal-footer-actions"
                             style="display: none !important;">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('payment-form');
            const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
            const submitBtn = document.getElementById('submit-btn');

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                submitBtn.disabled = true;
                document.getElementById('submit-spinner').classList.remove('d-none');

                document.getElementById('modal-content-processing').classList.remove('d-none');
                document.getElementById('modal-content-error').classList.add('d-none');
                document.getElementById('modal-footer-actions').style.setProperty('display', 'none', 'important');
                document.getElementById('modal-message').textContent = 'Initializing payment...';
                document.getElementById('modal-submessage').textContent = 'Please wait while we set up your transaction.';

                paymentModal.show();

                const formData = new FormData(form);

                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': formData.get('_token')
                    },
                    body: new URLSearchParams(formData)
                })
                .then(async response => {
                    const contentType = response.headers.get('content-type');

                    if (!contentType || !contentType.includes('application/json')) {
                        if (response.redirected) {
                            window.location.href = response.url;
                            return null;
                        }
                        throw new Error('Unexpected response format');
                    }

                    const data = await response.json();

                    if (!response.ok) {
                        let errorMessage = data.error || data.message || 'Payment failed';

                        if (data.errors) {
                            const errorMessages = Object.values(data.errors).flat();
                            errorMessage = errorMessages.join(' ');
                        }

                        throw new Error(errorMessage);
                    }

                    return data;
                })
                .then(data => {
                    if (!data) return;

                    if (data.success) {
                        document.getElementById('modal-message').textContent = 'Payment Pending';
                        document.getElementById('modal-submessage').innerHTML = 'You will receive a prompt on your mobile.<br>Please approve to complete payment.';

                        if (data.check_status_url) {
                            checkPaymentStatus(data.check_status_url, data.success_url);
                        } else if (data.success_url) {
                            window.location.href = data.success_url;
                        } else {
                            showError('Payment response incomplete. Please check your order status.');
                        }
                    } else {
                        showError(data.error || 'Payment failed. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError(error.message || 'An error occurred. Please try again.');
                });
            });

            function checkPaymentStatus(statusUrl, successUrl) {
                let checkCount = 0;
                const maxChecks = 60;

                const statusInterval = setInterval(() => {
                    checkCount++;

                    fetch(statusUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'succeeded') {
                            clearInterval(statusInterval);
                            document.getElementById('modal-content-processing').classList.remove('d-none');
                            document.getElementById('modal-content-error').classList.add('d-none');
                            document.getElementById('modal-message').textContent = 'Payment Successful!';
                            document.getElementById('modal-submessage').textContent = 'Redirecting to your order...';

                            setTimeout(() => {
                                window.location.href = successUrl;
                            }, 1000);
                        } else if (data.status === 'failed') {
                            clearInterval(statusInterval);
                            showError(data.error || 'Payment failed. Please try again.');
                        } else if (checkCount >= maxChecks) {
                            clearInterval(statusInterval);
                            window.location.href = statusUrl;
                        }
                    })
                    .catch(error => {
                        console.error('Status check error:', error);
                        if (checkCount >= maxChecks) {
                            clearInterval(statusInterval);
                            showError('Unable to verify payment status. Please check your order.');
                        }
                    });
                }, 5000);
            }

            function showError(message) {
                document.getElementById('modal-content-processing').classList.add('d-none');
                document.getElementById('modal-content-error').classList.remove('d-none');
                document.getElementById('error-message').textContent = message;
                document.getElementById('modal-footer-actions').style.setProperty('display', 'flex', 'important');

                submitBtn.disabled = false;
                document.getElementById('submit-spinner').classList.add('d-none');

                setTimeout(() => {
                    paymentModal.hide();

                    setTimeout(() => {
                        document.getElementById('modal-content-processing').classList.remove('d-none');
                        document.getElementById('modal-content-error').classList.add('d-none');
                        document.getElementById('modal-footer-actions').style.setProperty('display', 'none', 'important');
                        document.getElementById('modal-message').textContent = 'Initializing payment...';
                        document.getElementById('modal-submessage').textContent = '';
                    }, 500);
                }, 5000);
            }
        });
    </script>
    @endpush
</x-admin-layout>
