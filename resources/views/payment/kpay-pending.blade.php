<x-admin-layout>


    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card shadow-lg border-0">
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <div class="position-relative d-inline-block">
                                <i class="bi bi-phone text-primary" style="font-size: 5rem;"></i>
                                <div class="spinner-border text-primary position-absolute"
                                     style="top: 15px; right: -15px; width: 2rem; height: 2rem;"
                                     role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                            </div>
                        </div>
                        <h3 class="mb-3 font-weight-bold">Awaiting Payment</h3>
                        <p class="text-muted mb-4">
                            We are waiting for confirmation of your KPay payment. Please complete the authorization on your mobile device.
                        </p>

                        <div class="alert alert-light border mb-4 text-left">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Order Number:</span>
                                <span class="font-weight-bold">{{ $order->order_number }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Amount:</span>
                                <span class="font-weight-bold">{{ $order->currency }} {{ number_format($order->total_amount, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Status:</span>
                                <span class="badge badge-warning">Pending Confirmation</span>
                            </div>
                        </div>

                        <div class="mt-4 d-grid gap-2">
                            <a href="{{ route('payment.kpay.status', $payment) }}"
                               class="btn btn-primary btn-lg w-100 mb-3"
                               id="check-status-btn">
                                <i class="bi bi-arrow-clockwise mr-2"></i>
                                Check Status Now
                            </a>
                            <a href="{{ route('payment.kpay.show') }}" class="btn btn-link text-muted">
                                <i class="bi bi-arrow-left mr-2"></i>
                                Back to Payment Method
                            </a>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mt-4 shadow-sm border-0">
                    <div class="d-flex">
                        <i class="bi bi-info-circle-fill mr-3" style="font-size: 1.5rem;"></i>
                        <div>
                            <strong>Automatic Update:</strong> This page will automatically update once your payment is confirmed. Please keep this window open.
                        </div>
                    </div>
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
                        window.location.href = '{{ route('payment.kpay.show') }}';
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
</x-admin-layout>
