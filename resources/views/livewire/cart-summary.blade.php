<div>
    @if($this->cartItemsCount > 0)
        <div class="cart-summary-container fixed-bottom bg-white shadow-lg p-3 border-top" style="z-index: 999; font-size: 16px; line-height: 26px;">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-cart-fill text-primary me-2"></i>
                            <span class="fw-bold">{{ $this->cartItemsCount }} item(s) in cart</span>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <span class="fw-bold text-primary">Total: {{ $this->formattedTotal }}</span>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="{{ route('cart.index') }}"
                           class="btn btn-primary me-2"
                           style="font-size: 16px; line-height: 26px; padding: 5px 10px; border-radius: 8px;">
                            <bi class="bi bi-cart-fill"></bi> View Cart
                        </a>
                        <a href="{{ route('domains.register') }}"
                           class="btn btn-success"
                           style="font-size: 16px; line-height: 26px; padding: 5px 10px; border-radius: 8px;">
                            Checkout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
