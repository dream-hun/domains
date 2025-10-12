<x-user-layout>
    <div class="rts-hosting-banner rts-hosting-banner-bg">
        <div class="container">
            <div class="row">
                <div class="banner-area">
                    <div class="rts-hosting-banner rts-hosting-banner__content about__banner">
                        <h1 class="banner-title sal-animate" data-sal="slide-down" data-sal-delay="200"
                            data-sal-duration="800">
                            Payment Successful
                        </h1>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body text-center p-5">
                        <!-- Success Message -->
                        <div class="mb-5">
                            <div class="mx-auto d-flex align-items-center justify-content-center bg-success rounded-circle mb-4"
                                style="width: 80px; height: 80px;">
                                <i class="bi bi-check text-white" style="font-size: 2rem;"></i>
                            </div>
                            <h2 class="text-dark mb-3">Payment Successful!</h2>
                            <p class="text-muted">Your order has been processed successfully.</p>
                        </div>

                        <!-- Order Details -->
                        <div class="card bg-light mb-4">
                            <div class="card-header bg-primary text-white">
                                <h4 class="card-title mb-0">Order Details</h4>
                            </div>
                            <div class="card-body">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <p class="mb-1 text-muted">Order Number</p>
                                        <p class="mb-0 fw-bold text-dark">{{ $order->order_number }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1 text-muted">Order Date</p>
                                        <p class="mb-0 fw-bold text-dark">{{ $order->created_at->format('M d, Y H:i') }}
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1 text-muted">Payment Method</p>
                                        <p class="mb-0 fw-bold text-dark text-capitalize">{{ $order->payment_method }}
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1 text-muted">Total Amount</p>
                                        <p class="mb-0 fw-bold text-dark">${{ number_format($order->total_amount, 2) }}
                                        </p>
                                    </div>
                                </div>

                                <div class="border-top pt-3">
                                    <p class="mb-1 text-muted">Payment Status</p>
                                    <span class="badge bg-success fs-6">Paid</span>
                                </div>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <div class="card bg-white border mb-4">
                            <div class="card-header">
                                <h4 class="card-title mb-0">Order Items</h4>
                            </div>
                            <div class="card-body p-0">
                                @foreach ($order->orderItems as $item)
                                    <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 text-dark">{{ $item->domain_name }}</h6>
                                            <small class="text-muted">{{ ucfirst($item->domain_type) }} -
                                                {{ $item->years }} year(s)</small>
                                        </div>
                                        <div class="text-end">
                                            <span
                                                class="fw-bold text-dark">${{ number_format($item->total_amount, 2) }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Domain Registration Results -->
                        @if (session('registration_results'))
                            @php
                                $results = session('registration_results');
                                $successful = $results['successful'] ?? [];
                                $failed = $results['failed'] ?? [];
                            @endphp

                            @if (!empty($successful))
                                <div class="alert alert-success mb-4">
                                    <h5 class="alert-heading text-success mb-3">
                                        <i class="bi bi-check-circle-fill me-2"></i>Domains Registered Successfully
                                    </h5>
                                    <ul class="list-unstyled mb-0">
                                        @foreach ($successful as $domain)
                                            <li class="d-flex align-items-start mb-2">
                                                <i class="bi bi-check-circle text-success me-2 mt-1"></i>
                                                <span><strong>{{ $domain['domain'] }}</strong> - {{ $domain['message'] }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if (!empty($failed))
                                <div class="alert alert-warning mb-4">
                                    <h5 class="alert-heading text-warning mb-3">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Registration Issues
                                    </h5>
                                    <ul class="list-unstyled mb-0">
                                        @foreach ($failed as $domain)
                                            <li class="d-flex align-items-start mb-2">
                                                <i class="bi bi-exclamation-triangle text-warning me-2 mt-1"></i>
                                                <span><strong>{{ $domain['domain'] }}</strong> - {{ $domain['message'] }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                    <div class="mt-3">
                                        <small class="text-muted">Our support team will contact you regarding any failed registrations.</small>
                                    </div>
                                </div>
                            @endif
                        @endif

                        <!-- Next Steps -->
                        <div class="alert alert-info mb-4">
                            <h5 class="alert-heading text-info mb-3">What's Next?</h5>
                            <ul class="list-unstyled mb-0">
                                <li class="d-flex align-items-start mb-2">
                                    <i class="bi bi-check-circle-fill text-info me-2 mt-1"></i>
                                    <span>You will receive a confirmation email shortly</span>
                                </li>
                                <li class="d-flex align-items-start mb-2">
                                    <i class="bi bi-check-circle-fill text-info me-2 mt-1"></i>
                                    <span>Successfully registered domains are now active in your account</span>
                                </li>
                                <li class="d-flex align-items-start">
                                    <i class="bi bi-check-circle-fill text-info me-2 mt-1"></i>
                                    <span>You can manage your domains from your dashboard</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Action Buttons -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <a href="{{ route('dashboard') }}"
                                    class="btn btn-primary btn-lg w-100 d-flex align-items-center justify-content-center">
                                    <i class="bi bi-speedometer2 me-2"></i>
                                    Go to Dashboard
                                </a>
                            </div>

                            <div class="col-md-6">
                                <a href="{{ route('domains.search') }}"
                                    class="btn btn-outline-primary btn-lg w-100 d-flex align-items-center justify-content-center">
                                    <i class="bi bi-search me-2"></i>
                                    Search More Domains
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-user-layout>
