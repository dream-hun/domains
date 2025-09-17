<div class="domain-action-wrapper" style="display: flex; align-items: center; gap: 0.75rem; white-space: nowrap;">
    <span class="domain-price" style="font-size: 14px; color: #6b7280; font-weight: 500;">{{ $price }}/year</span>
    @if ($available)
        @if ($this->isInCart)
            <button wire:click="removeFromCart" class="btn btn-danger"
                style="font-size: 14px; padding: 6px 12px; border-radius: 6px; white-space: nowrap;"
                wire:loading.attr="disabled" wire:target="removeFromCart">
                <span wire:loading.remove wire:target="removeFromCart"><i class="bi bi-dash-circle-dotted"></i>
                    Remove</span>
                <span wire:loading wire:target="removeFromCart">Removing...</span>
            </button>
        @else
            <button wire:click="addToCart" class="btn btn-success"
                style="font-size: 14px; padding: 6px 12px; border-radius: 6px; white-space: nowrap;"
                wire:loading.attr="disabled" wire:target="addToCart">
                <span wire:loading.remove wire:target="addToCart"><i class="bi bi-cart4"></i> Add to cart</span>
                <span wire:loading wire:target="addToCart">Adding...</span>
            </button>
        @endif
    @else
        <button class="btn btn-secondary" disabled
            style="font-size: 14px; padding: 6px 12px; border-radius: 6px; white-space: nowrap;">Registered</button>
    @endif
</div>
