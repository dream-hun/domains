<x-admin-layout>


    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body text-center p-5">
                        <!-- Error Message -->
                        <div class="mb-5">
                            <div class="mx-auto d-flex align-items-center justify-content-center bg-danger rounded-circle mb-4"
                                style="width: 80px; height: 80px;">
                                <i class="bi bi-x text-white" style="font-size: 2rem;"></i>
                            </div>
                            <h2 class="text-dark mb-3">Payment Failed</h2>
                            <p class="text-muted">Unfortunately, your payment could not be processed.</p>
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
                                    <span class="badge bg-danger fs-6">Failed</span>
                                </div>
                            </div>
                        </div>

                        <!-- Possible Reasons -->
                        <div class="alert alert-warning mb-4">
                            <h5 class="alert-heading text-warning mb-3">Possible Reasons</h5>
                            <ul class="list-unstyled mb-0">
                                <li class="d-flex align-items-start mb-2">
                                    <i class="bi bi-exclamation-triangle-fill text-warning me-2 mt-1"></i>
                                    <span>Insufficient funds in your account</span>
                                </li>
                                <li class="d-flex align-items-start mb-2">
                                    <i class="bi bi-exclamation-triangle-fill text-warning me-2 mt-1"></i>
                                    <span>Incorrect card information</span>
                                </li>
                                <li class="d-flex align-items-start mb-2">
                                    <i class="bi bi-exclamation-triangle-fill text-warning me-2 mt-1"></i>
                                    <span>Card expired or blocked</span>
                                </li>
                                <li class="d-flex align-items-start">
                                    <i class="bi bi-exclamation-triangle-fill text-warning me-2 mt-1"></i>
                                    <span>Network or processing error</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Action Buttons -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                {{--<a href="{{ route('payment.index') }}"
                                    class="btn btn-primary btn-lg w-100 d-flex align-items-center justify-content-center">
                                    <i class="bi bi-arrow-clockwise me-2"></i>
                                    Try Again
                                </a>--}}
                            </div>

                            <div class="col-md-4">
                                <a href="{{ route('cart.index') }}"
                                    class="btn btn-outline-secondary btn-lg w-100 d-flex align-items-center justify-content-center">
                                    <i class="bi bi-cart me-2"></i>
                                    Back to Cart
                                </a>
                            </div>

                            <div class="col-md-4">
                                <a href="{{ route('dashboard') }}"
                                    class="btn btn-outline-primary btn-lg w-100 d-flex align-items-center justify-content-center">
                                    <i class="bi bi-speedometer2 me-2"></i>
                                    Dashboard
                                </a>
                            </div>
                        </div>

                        <!-- Support Information -->
                        <div class="border-top pt-4 text-center">
                            <p class="text-muted mb-0">
                                Need help? Contact our support team at
                                <a href="mailto:support@example.com" class="text-primary">support@example.com</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
