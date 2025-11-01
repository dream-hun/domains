<div>
    <div class="row col-md-12 g-5 justify-content-center">
        @if (session()->has('message'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('message') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="col-lg-9 justify-content-center">
            <div class="card border shadow-0">
                <div class="m-4">
                    @forelse ($items as $item)
                        <div class="row gy-3 mb-4 align-items-center" data-item-id="{{ $item->id }}">
                            <div class="col-lg-3">
                                <div class="me-lg-3">
                                    <div class="d-flex align-items-center flex-column align-items-start">
                                        <div class="d-flex align-items-center gap-2">
                                            <p class="nav-link h5 mb-0" style="font-size: 16px !important;">
                                                {{ $item->attributes->domain_name ?? $item->name }}</p>
                                            @if (($item->attributes->type ?? 'registration') === 'renewal')
                                                <span class="badge bg-success" style="font-size: 11px;">Renewal</span>
                                            @elseif (($item->attributes->type ?? 'registration') === 'transfer')
                                                <span class="badge bg-info" style="font-size: 11px;">Transfer</span>
                                            @else
                                                <span class="badge bg-primary" style="font-size: 11px;">Registration</span>
                                            @endif
                                        </div>
                                        @if (($item->attributes->type ?? 'registration') === 'renewal' && isset($item->attributes->current_expiry))
                                            <small class="text-muted" style="font-size: 12px;">Current expiry: {{ $item->attributes->current_expiry }}</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 d-flex flex-row align-items-center">
                                <div class="d-flex align-items-center gap-4">
                                    <div class="quantity-controls d-flex align-items-center">
                                        <button type="button" class="btn btn-outline-primary rounded-circle p-2"
                                            style="width: 35px; height: 35px; font-size:16px !important;"
                                            wire:click="updateQuantity('{{ $item->id }}', {{ $item->quantity - 1 }})"
                                            wire:loading.class="opacity-75"
                                            wire:target="updateQuantity('{{ $item->id }}', {{ $item->quantity - 1 }})"
                                            {{ $item->quantity <= 1 ? 'disabled' : '' }}>
                                            <i class="bi bi-dash"></i>
                                        </button>
                                        <span class="mx-4 fs-5"
                                            style="font-size: 16px !important;">{{ $item->quantity }}
                                            {{ Str::plural('Year', $item->quantity) }}</span>
                                        <button type="button" class="btn btn-outline-primary rounded-circle p-2"
                                            style="width: 35px; height: 35px;"
                                            wire:click="updateQuantity('{{ $item->id }}', {{ $item->quantity + 1 }})"
                                            wire:loading.class="opacity-75"
                                            wire:target="updateQuantity('{{ $item->id }}', {{ $item->quantity + 1 }})"
                                            {{ $item->quantity >= 10 ? 'disabled' : '' }}>
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                    <div class="price-display">
                                        <div class="h5 mb-0 d-flex align-items-center gap-2">
                                            <span class="text-primary"
                                                style="font-size: 16px !important; text-transform:uppercase !important;">{{ $this->getFormattedItemTotal($item) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 d-flex justify-content-end">
                                <button wire:click="removeItem('{{ $item->id }}')" wire:loading.class="opacity-75"
                                    wire:target="removeItem('{{ $item->id }}')" class="btn btn-danger btn-md w-50"
                                    style="font-size: 16px !important;">
                                    <i class="bi bi-trash3-fill me-2" wire:loading.remove
                                        wire:target="removeItem('{{ $item->id }}')"></i>
                                    <span wire:loading wire:target="removeItem('{{ $item->id }}')"
                                        class="spinner-border spinner-border-sm me-2" role="status"
                                        aria-hidden="true"></span>
                                    Remove
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <p>Your cart is empty</p>
                            <a href="{{ route('domains') }}" class="btn btn-primary">Search Domains</a>
                        </div>
                    @endforelse
                </div>
            </div>

            
        </div>

        <div class="col-lg-3">
            <div class="card shadow-0 border">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <p class="mb-2">Subtotal:</p>
                        <p class="mb-2" style="text-transform:uppercase !important;">{{ $this->formattedSubtotal }}</p>
                    </div>

                    @if ($isCouponApplied && $appliedCoupon)
                        <div class="d-flex justify-content-start text-success">
                            <p class="mb-2">
                                <i class="bi bi-tag-fill me-1"></i>
                                Discount ({{ $appliedCoupon->code }}):
                            </p>
                            <p class="mb-2" style="text-transform:uppercase !important;">-{{ $this->formattedDiscount }}</p>
                        </div>
                    @endif

                    <hr />

                    <div class="d-flex justify-content-between">
                        <p class="mb-2 fw-bold">Total:</p>
                        <p class="mb-2 fw-bold" style="text-transform:uppercase !important;">{{ $this->formattedTotal }}</p>
                    </div>

                    <div class="mt-3">
                        @if ($items && $items->isNotEmpty())
                            <a href="{{ route('checkout.index') }}" class="btn btn-success btn-lg w-100 mb-2 pb-3 pt-3"
                                style="font-size: 16px !important;">

                                    <i class="bi bi-credit-card me-2"></i>Proceed to Payment

                            </a>
                        @endif
                    </div>
                </div>
            </div>
            @if ($items && $items->isNotEmpty())
                <div class="card border shadow-0 mt-4">
                    <div class="card-body py-4">
                        <h6 class="mb-3">Have a coupon code?</h6>
                        
                        @if (!$isCouponApplied)
                            <form wire:submit.prevent="applyCoupon">
                                <div class="input-group">
                                    <input type="text" 
                                           wire:model="couponCode" 
                                           class="form-control" 
                                           placeholder="Enter coupon code"
                                           style="font-size: 16px !important; height: 40px;">
                                </div>
                                <div class="d-grid gap-2 mt-2">
                                    @error('couponCode')
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            {{ $message }}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </div>
                                    @enderror
                                    <button type="submit" 
                                            class="btn btn-primary btn-lg w-100 pb-3 pt-3 mt-4"
                                            wire:loading.attr="disabled"
                                            wire:target="applyCoupon"
                                            style="font-size: 16px !important;">
                                        <span wire:loading.remove wire:target="applyCoupon">Apply</span>
                                        <span wire:loading wire:target="applyCoupon" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                    </button>
                                </div>
                            </form>
                        @else
                            <div class="align-items-center justify-content-between">
                                <span class="badge bg-success align-items-center gap-2 badge-lg w-100 py-3" style="font-size: 16px;">
                                    <i class="bi bi-check-circle"></i>
                                    {{ $appliedCoupon->code }}
                                </span>
                                <button wire:click="removeCoupon" 
                                        class="btn btn-md w-100 py-3 btn-outline-danger mt-4 gap-2"
                                        wire:loading.attr="disabled"
                                        wire:target="removeCoupon"
                                        style="font-size: 14px;">
                                    <span wire:loading.remove wire:target="removeCoupon">Remove</span>
                                    <span wire:loading wire:target="removeCoupon" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
