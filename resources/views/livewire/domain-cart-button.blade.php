<div class="domain-action-wrapper"
     style="display: flex; align-items: center; gap: 1rem; padding: 0.5rem; border-radius: 0.375rem;">
    <span class="domain-price" style="font-size: 16px;">{{ $price }}</span>

    @if($available)
        @if($this->isInCart)
            <button
                wire:click="removeFromCart"
                class="btn btn-danger"
                style="font-size: 16px; border-radius: 8px;"
                wire:loading.attr="disabled"
                wire:target="removeFromCart"
            >
                <i class="bi bi-bag-x"></i>
                <span wire:loading.remove wire:target="removeFromCart">Remove</span>
                <span wire:loading wire:target="removeFromCart">
                    <i class="bi bi-arrow-repeat"></i> Removing...
                </span>
            </button>
        @else
            <button wire:click="addToCart" class="btn btn-success" style="font-size: 16px; border-radius: 8px;"
                    wire:loading.attr="disabled" wire:target="addToCart">
                <i class="bi bi-cart"></i>
                <span wire:loading.remove wire:target="addToCart">Add to Cart</span>
                <span wire:loading wire:target="addToCart">
                    <i class="bi bi-arrow-repeat"></i> Adding...
                </span>
            </button>
        @endif
    @else
        <button class="btn btn-secondary" disabled style="font-size: 16px; border-radius: 8px;">
            Unavailable
        </button>
    @endif
</div>
