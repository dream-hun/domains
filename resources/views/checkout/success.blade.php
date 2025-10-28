<x-admin-layout>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <div class="mb-4">
                            <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                        </div>
                        <h2 class="mb-3">Payment Successful!</h2>
                        <p class="lead mb-4">Thank you for your order. Your payment has been processed successfully.</p>
                        
                        <div class="alert alert-info">
                            <strong>Order Number:</strong> {{ $order->order_number }}
                        </div>

                        <div class="mb-4">
                            <h5>Domains Purchased:</h5>
                            <ul class="list-unstyled">
                                @foreach($order->orderItems as $item)
                                    <li class="mb-2">
                                        <i class="bi bi-globe text-primary"></i>
                                        <strong>{{ $item->domain_name }}</strong> - {{ $item->years }} year(s)
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <p class="text-muted mb-4">
                            Your domains are being registered. You will receive a confirmation email shortly.
                        </p>

                        <div class="d-flex justify-content-center gap-3">
                            <a href="{{ route('admin.domains.index') }}" class="btn btn-primary">
                                <i class="bi bi-list mr-2"></i>
                                View My Domains
                            </a>
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
