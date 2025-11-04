<x-admin-layout>
    @section('page-title')
        Checkout
    @endsection

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <div class="mb-4">
                            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>

                        <h3 class="mb-3">Redirecting to Checkout...</h3>

                        <p class="text-muted">
                            Please wait while we prepare your secure checkout session.
                            You will be redirected to Stripe to complete your payment.
                        </p>

                        <div class="mt-4">
                            <small class="text-muted">
                                <i class="fas fa-lock me-2"></i>
                                Your payment is secure and encrypted
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
