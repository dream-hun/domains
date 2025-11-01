<x-admin-layout>
    @section('page-title')
        Checkout
    @endsection

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Checkout</h3>
                    </div>
                    <div class="card-body">
                        <!-- Order Summary -->
                        <div class="mb-4">
                            <h5>Order Summary</h5>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Domain</th>
                                            <th>Type</th>
                                            <th>Years</th>
                                            <th class="text-end">Price</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($cartItems as $item)
                                            <tr>
                                                <td>{{ $item->name }}</td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        {{ ucfirst($item->attributes['type'] ?? 'unknown') }}
                                                    </span>
                                                </td>
                                                <td>{{ $item->quantity }} {{ Str::plural('year', $item->quantity) }}</td>
                                                <td class="text-end">
                                                    {{ $currency }} {{ number_format($item->getPriceSum(), 2) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                            <td class="text-end">
                                                <strong>{{ $currency }} {{ number_format($cartTotal, 2) }}</strong>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Payment Form -->
                        <div class="mb-4">
                            <h5>Payment Information</h5>
                            <form id="payment-form">
                                @csrf
                                <!-- Stripe Elements will be inserted here -->
                                <div id="payment-element" class="mb-3"></div>

                                <div id="payment-message" class="alert d-none"></div>

                                <button id="submit" class="btn btn-primary btn-lg w-100">
                                    <span id="button-text">Pay {{ $currency }} {{ number_format($cartTotal, 2) }}</span>
                                    <div class="spinner-border spinner-border-sm d-none" id="spinner" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </button>
                            </form>
                        </div>

                        <div class="text-center text-muted">
                            <small>
                                <i class="fas fa-lock"></i>
                                Your payment is secure and encrypted
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://js.stripe.com/v3/"></script>
        <script>
            // Initialize Stripe
            const stripe = Stripe('{{ $stripeKey }}');
            
            let elements;
            let paymentElement;

            initialize();

            async function initialize() {
                // Create Payment Intent
                const response = await fetch('{{ route('checkout.payment-intent') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const { clientSecret, paymentIntentId } = await response.json();

                // Store payment intent ID for later use
                window.paymentIntentId = paymentIntentId;

                const appearance = {
                    theme: 'stripe',
                };
                elements = stripe.elements({ clientSecret, appearance });

                paymentElement = elements.create('payment');
                paymentElement.mount('#payment-element');
            }

            const form = document.getElementById('payment-form');
            const submitButton = document.getElementById('submit');

            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                setLoading(true);

                const { error } = await stripe.confirmPayment({
                    elements,
                    confirmParams: {
                        return_url: '{{ route('checkout.success') }}?payment_intent_id=' + window.paymentIntentId,
                    },
                });

                if (error) {
                    showMessage(error.message, 'danger');
                    setLoading(false);
                } else {
                    // Payment succeeded, user will be redirected to success page
                }
            });

            function setLoading(isLoading) {
                if (isLoading) {
                    submitButton.disabled = true;
                    document.getElementById('spinner').classList.remove('d-none');
                    document.getElementById('button-text').classList.add('d-none');
                } else {
                    submitButton.disabled = false;
                    document.getElementById('spinner').classList.add('d-none');
                    document.getElementById('button-text').classList.remove('d-none');
                }
            }

            function showMessage(message, type = 'danger') {
                const messageDiv = document.getElementById('payment-message');
                messageDiv.textContent = message;
                messageDiv.className = `alert alert-${type}`;
                messageDiv.classList.remove('d-none');

                setTimeout(() => {
                    messageDiv.classList.add('d-none');
                }, 5000);
            }
        </script>
    @endpush
</x-admin-layout>

