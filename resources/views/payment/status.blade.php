<x-user-layout>
    <div class="rts-hosting-banner rts-hosting-banner-bg">
        <div class="container">
            <div class="row">
                <div class="banner-area">
                    <div class="rts-hosting-banner rts-hosting-banner__content about__banner">
                        <h1 class="banner-title sal-animate" data-sal="slide-down" data-sal-delay="200"
                            data-sal-duration="800">
                            Payment Status
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
                    <div class="card-body text-center py-5">
                        @if($order->payment_status === 'paid')
                            <div class="mb-4">
                                <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                            </div>
                            <h3 class="mb-3 text-success">Payment Successful</h3>
                            <p class="text-muted mb-4">
                                Your payment has been processed successfully. Your order is being processed.
                            </p>
                        @elseif($order->payment_status === 'failed')
                            <div class="mb-4">
                                <i class="bi bi-x-circle-fill text-danger" style="font-size: 4rem;"></i>
                            </div>
                            <h3 class="mb-3 text-danger">Payment Failed</h3>
                            <p class="text-muted mb-4">
                                Your payment could not be processed. Please try again or contact support.
                            </p>
                        @else
                            <div class="mb-4">
                                <div class="spinner-border text-primary" role="status" style="width: 4rem; height: 4rem;">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                            <h3 class="mb-3">Processing Your Payment</h3>
                            <p class="text-muted mb-4">
                                Your payment is being processed. Please wait while we verify your payment status.
                            </p>
                        @endif

                        <p class="text-muted small">
                            Order Number: <strong>{{ $order->order_number }}</strong>
                        </p>
                        <p class="text-muted small">
                            Status: <strong>{{ ucfirst($order->payment_status) }}</strong>
                        </p>

                        <div class="mt-4">
                            <a href="{{ route('billing.show', $order) }}" class="btn btn-primary">
                                <i class="bi bi-receipt me-2"></i>
                                View Order Details
                            </a>
                        </div>

                        @if($order->payment_status === 'pending')
                            <div class="mt-3">
                                <a href="{{ route('payment.status', $order) }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-clockwise me-2"></i>
                                    Refresh Status
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                @if($order->payment_status === 'pending')
                    <div class="alert alert-info mt-4">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Note:</strong> If your payment was successful, the status will update automatically.
                        If you don't see any updates, please refresh or contact support.
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-user-layout>
