<aside class="card shadow-sm order-summary-card" aria-label="Order summary">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Order Summary</h5>
    </div>
    <div class="card-body">
        {{-- Cart Items --}}
        @foreach($this->cartItems as $item)
            <div class="order-item mb-3 pb-3 border-bottom">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <strong class="d-block">{{ $this->getItemDisplayName($item) }}</strong>
                        <small class="text-muted">
                            {{ $this->getRegistrationPeriod($item) }}
                        </small>
                        @if(isset($item->attributes['whois_privacy']) && $item->attributes['whois_privacy'])
                            <br>
                            <small class="text-primary">
                                <i class="bi bi-shield-fill-check"></i> + WHOIS Privacy
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

            @if($isCouponApplied && $appliedCoupon)
                <div class="d-flex justify-content-between mb-2 text-success">
                    <span>
                        <i class="fas fa-tag mr-1"></i>
                        Discount ({{ $appliedCoupon->code }}):
                    </span>
                    <strong>-{{ $this->formatCurrency($discountAmount) }}</strong>
                </div>
            @endif

            <hr class="my-3">

            <div class="d-flex justify-content-between mb-3">
                <span class="h5 mb-0">Total:</span>
                <strong class="h5 mb-0 text-primary">{{ $this->formatCurrency($this->orderTotal) }}</strong>
            </div>

            @if($isCouponApplied && $appliedCoupon)
                <div class="alert alert-success py-2 px-3 mb-0 small">
                    <i class="fas fa-check-circle mr-1"></i>
                    You saved {{ $this->formatCurrency($discountAmount) }} with coupon <strong>{{ $appliedCoupon->code }}</strong>!
                </div>
            @endif
        </div>

        <div class="text-center mt-4 pt-3 border-top">
            <small class="text-muted">
                <i class="bi bi-lock"></i> Secure checkout with SSL encryption
            </small>
        </div>
    </div>
</aside>
