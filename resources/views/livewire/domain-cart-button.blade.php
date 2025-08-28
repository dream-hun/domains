<div class="domain-action-wrapper">
    <span class="domain-price">{{ $price }}</span>

    @if($available)
        @if($this->isInCart)
            <button
                wire:click="removeFromCart"
                class="domain-btn"
                style="background-color: #dc3545 !important;"
                wire:loading.attr="disabled"
                wire:target="removeFromCart"
            >
                <span wire:loading.remove wire:target="removeFromCart">Remove</span>
                <span wire:loading wire:target="removeFromCart">
                    <i class="fas fa-spinner fa-spin"></i> Removing...
                </span>
            </button>
        @else
            <button
                wire:click="addToCart"
                class="domain-btn"
                wire:loading.attr="disabled"
                wire:target="addToCart"
            >
                <span wire:loading.remove wire:target="addToCart">Add to Cart</span>
                <span wire:loading wire:target="addToCart">
                    <i class="fas fa-spinner fa-spin"></i> Adding...
                </span>
            </button>
        @endif
    @else
        <button class="domain-btn" disabled>Unavailable</button>
    @endif
</div>
