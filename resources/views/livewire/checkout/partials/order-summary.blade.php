<div class="card shadow-sm sticky-top" style="top: 20px;">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Order Summary</h5>
    </div>
    <div class="card-body">
        {{-- Cart Items --}}
        @foreach($this->cartItems as $item)
            <div class="order-item mb-3 pb-3 border-bottom">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <strong class="d-block">{{ $item->name }}</strong>
                        <small class="text-muted">
                            {{ $item->quantity }} {{ Str::plural('year', $item->quantity) }}
                        </small>
                        @if(isset($item->attributes['whois_privacy']) && $item->attributes['whois_privacy'])
                            <br>
                            <small class="text-primary">
                                <i class="fas fa-shield-alt"></i> + WHOIS Privacy
                            </small>
                        @endif
                    </div>
                    <div class="text-right ml-3">
                        <strong>{{ $this->getItemPrice($item) }}</strong>
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Pricing Summary --}}
        <div class="pricing-summary">
            <div class="d-flex justify-content-between mb-2">
                <span>Subtotal:</span>
                <strong>{{ $this->formatCurrency($this->orderSubtotal) }}</strong>
            </div>
            
            <hr class="my-3">
            
            <div class="d-flex justify-content-between mb-3">
                <span class="h5 mb-0">Total:</span>
                <strong class="h5 mb-0 text-primary">{{ $this->formatCurrency($this->orderTotal) }}</strong>
            </div>
        </div>

        {{-- Contact Information (Step 2+) --}}
        @if($currentStep >= 2 && $this->selectedContact)
            <hr class="my-3">
            <div class="contact-info">
                <h6 class="mb-2">
                    <i class="fas fa-user mr-2"></i>Contact Information
                </h6>
                <p class="small mb-0">
                    <strong>{{ $this->selectedContact->full_name }}</strong><br>
                    {{ $this->selectedContact->email }}<br>
                    <span class="text-muted">
                        {{ $this->selectedContact->city }}, {{ $this->selectedContact->country_code }}
                    </span>
                </p>
            </div>
        @endif

        {{-- Payment Method (Step 3+) --}}
        @if($currentStep >= 3 && $selectedPaymentMethod)
            <hr class="my-3">
            <div class="payment-info">
                <h6 class="mb-2">
                    <i class="fas fa-credit-card mr-2"></i>Payment Method
                </h6>
                <p class="small mb-0">
                    @foreach($paymentMethods as $method)
                        @if($method['id'] === $selectedPaymentMethod)
                            <strong>{{ $method['name'] }}</strong>
                        @endif
                    @endforeach
                </p>
            </div>
        @endif

        {{-- Security Badge --}}
        <div class="text-center mt-4 pt-3 border-top">
            <small class="text-muted">
                <i class="fas fa-lock"></i> Secure checkout with SSL encryption
            </small>
        </div>
    </div>
</div>
