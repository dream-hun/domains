<x-admin-layout>
    @section('page-title')
        Payment Successful
    @endsection

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-check-circle text-success" style="font-size: 72px;"></i>
                        </div>

                        <h2 class="mb-3">Payment Successful!</h2>

                        <p class="lead mb-4">
                            Thank you for your payment. Your domain renewal is being processed.
                        </p>

                        @if(isset($order))
                            <div class="alert alert-info d-inline-block">
                                <strong>Order Number:</strong> {{ $order->order_number }}
                            </div>
                            <div class="mt-3">
                                <p><strong>Total Amount:</strong> {{ $order->currency }} {{ number_format($order->total_amount, 2) }}</p>
                            </div>
                        @elseif(session('order_number'))
                            <div class="alert alert-info d-inline-block">
                                <strong>Order Number:</strong> {{ session('order_number') }}
                            </div>
                        @endif

                        <div class="mt-4">
                            <p class="text-muted">
                                You will receive an email confirmation shortly with the details of your renewal.
                                The renewal process typically completes within a few minutes.
                            </p>
                        </div>

                        <div class="mt-5">
                            <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg">
                                <i class="fas fa-home me-2"></i>
                                Go to Dashboard
                            </a>
                        </div>
                    </div>
                </div>

                <div class="alert alert-success mt-4">
                    <h6 class="alert-heading">What happens next?</h6>
                    <ul class="mb-0">
                        <li>Your domain renewal has been queued for processing.</li>
                        <li>The renewal will be reflected in your domain dashboard shortly.</li>
                        <li>You will receive an email confirmation once the renewal is complete.</li>
                        <li>If you have any questions, please contact our support team.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>

