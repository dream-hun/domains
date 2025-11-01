<x-admin-layout>
    @section('page-title')
        Payment Cancelled
    @endsection

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-times-circle text-warning" style="font-size: 72px;"></i>
                        </div>

                        <h2 class="mb-3">Payment Cancelled</h2>

                        <p class="lead mb-4">
                            Your payment was cancelled or failed to process.
                        </p>

                        @if(session('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @endif

                        <div class="mt-4">
                            <p class="text-muted">
                                No charges have been made to your account.
                                You can try again or contact support if you need assistance.
                            </p>
                        </div>

                        <div class="mt-5">
                            <a href="{{ route('checkout.index') }}" class="btn btn-primary me-2">
                                <i class="fas fa-redo me-2"></i>
                                Try Again
                            </a>
                            <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                                <i class="fas fa-home me-2"></i>
                                Go to Dashboard
                            </a>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mt-4">
                    <h6 class="alert-heading">Need Help?</h6>
                    <p class="mb-0">
                        If you're experiencing issues with payment, please contact our support team.
                        We're here to help you complete your domain renewal.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>

