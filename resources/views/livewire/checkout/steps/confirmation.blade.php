<div class="card shadow-sm">
    <div class="card-body text-center py-5">
        <div class="mb-4">
            <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
        </div>
        
        <h2 class="mb-3">Order Confirmed!</h2>
        
        <p class="lead mb-4">
            Thank you for your purchase. Your order has been successfully processed.
        </p>
        
        <div class="alert alert-success mb-4">
            <strong>Order Number:</strong> {{ $orderNumber }}
        </div>
        
        <p class="text-muted mb-4">
            A confirmation email has been sent to your email address with all the details of your order.
        </p>
        
        <hr class="my-4">
        
        <h5 class="mb-3">What's Next?</h5>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <i class="fas fa-globe fa-2x text-primary mb-3"></i>
                        <h6>Manage Your Domains</h6>
                        <p class="small text-muted mb-3">
                            View and manage your newly registered domains
                        </p>
                        <a href="{{ route('admin.domains.index') }}" class="btn btn-primary btn-sm">
                            Go to Domains
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <i class="fas fa-file-invoice fa-2x text-info mb-3"></i>
                        <h6>View Your Orders</h6>
                        <p class="small text-muted mb-3">
                            See all your orders and billing history
                        </p>
                        <a href="{{ route('billing.index') }}" class="btn btn-info btn-sm">
                            View Orders
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                <i class="fas fa-home mr-2"></i>
                Return to Dashboard
            </a>
        </div>
    </div>
</div>
