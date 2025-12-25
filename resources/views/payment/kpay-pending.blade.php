<x-user-layout>
    <div class="rts-hosting-banner rts-hosting-banner-bg">
        <div class="container">
            <div class="row">
                <div class="banner-area">
                    <div class="rts-hosting-banner rts-hosting-banner__content about__banner">
                        <h1 class="banner-title sal-animate" data-sal="slide-down" data-sal-delay="200"
                            data-sal-duration="800">
                            Payment Processing
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
                        <div class="mb-4">
                            <div class="spinner-border text-primary" role="status" style="width: 4rem; height: 4rem;">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <h3 class="mb-3">Processing Your Payment</h3>
                        <p class="text-muted mb-4">
                            Your KPay payment is being processed. Please wait while we verify your payment status.
                        </p>
                        <p class="text-muted small">
                            Order Number: <strong>{{ $order->order_number }}</strong>
                        </p>

                        <div class="mt-4">
                            <a href="{{ route('payment.kpay.status', $payment) }}"
                               class="btn btn-primary"
                               id="check-status-btn">
                                <i class="bi bi-arrow-clockwise me-2"></i>
                                Check Payment Status
                            </a>
                        </div>

                        <div class="mt-3">
                            <a href="{{ route('billing.show', $order) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-receipt me-2"></i>
                                View Order Details
                            </a>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mt-4">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Note:</strong> If your payment was successful, you will be redirected automatically.
                    If you don't see any updates, please check your payment status or contact support.
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkStatusBtn = document.getElementById('check-status-btn');
            const statusUrl = checkStatusBtn.href;

            // Auto-check status every 5 seconds
            let checkInterval = setInterval(function() {
                fetch(statusUrl, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.status === 'succeeded') {
                        clearInterval(checkInterval);
                        window.location.href = '{{ route('payment.kpay.success', $order) }}';
                    } else if (data.status === 'failed') {
                        clearInterval(checkInterval);
                        window.location.href = '{{ route('payment.failed', $order) }}';
                    }
                })
                .catch(error => {
                    console.error('Error checking status:', error);
                });
            }, 5000);

            // Stop checking after 2 minutes
            setTimeout(function() {
                clearInterval(checkInterval);
            }, 120000);
        });
    </script>
    @endpush
</x-user-layout>
