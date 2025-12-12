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
                <div class="card-body">
                    @if ($items && $items->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table cart-table align-middle">
                                <thead>
                                    <tr>
                                        <th style="font-size: 16px !important;">Item</th>
                                        <th style="font-size: 16px !important;" class="text-center">Quantity</th>
                                        <th style="font-size: 16px !important;" class="text-end">Price</th>
                                        <th style="font-size: 16px !important;" class="text-end">Subtotal</th>
                                        <th style="font-size: 16px !important;" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($items as $item)
                                        @php
                                            $itemType = $item->attributes->type ?? 'registration';
                                            $displayName = $item->attributes->domain_name ?? $item->name;

                                            if ($itemType === 'hosting' || $itemType === 'subscription_renewal') {
                                                $metadata = $item->attributes->metadata ?? [];
                                                $planData = $metadata['plan'] ?? null;
                                                $displayName = $planData['name'] ?? $item->name;

                                                if (str_contains($displayName, ' Hosting (')) {
                                                    $displayName = str_replace(' Hosting (', '', $displayName);
                                                    $displayName = preg_replace('/\s*\([^)]*\)\s*$/', '', $displayName);
                                                } elseif (str_contains($displayName, ' - ')) {
                                                    $parts = explode(' - ', $displayName);
                                                    $displayName = end($parts);
                                                    $displayName = preg_replace('/\s*\([^)]*\)\s*$/', '', $displayName);
                                                } elseif (preg_match('/^(.+?)\s*\(/', $displayName, $matches)) {
                                                    $displayName = $matches[1];
                                                }
                                            }
                                        @endphp
                                        <tr data-item-id="{{ $item->id }}">
                                            <td>
                                                <p class="nav-link h5 mb-0" style="font-size: 16px !important;">
                                                    {{ $displayName }}
                                                </p>
                                            </td>
                                            <td class="text-center">
                                                <div class="quantity-controls d-flex align-items-center justify-content-center">
                                                    @if($itemType === 'subscription_renewal' || $itemType === 'hosting')
                                                        @php
                                                            // For hosting and subscription_renewal, use duration_months if set, otherwise use quantity
                                                            // The quantity field should represent months for these item types
                                                            if (isset($item->attributes->duration_months)) {
                                                                $durationMonths = (int) $item->attributes->duration_months;
                                                            } elseif ($itemType === 'hosting') {
                                                                // For hosting, if duration_months not set, calculate from billing_cycle
                                                                // This handles legacy items that were added before duration_months was set
                                                                $billingCycle = $item->attributes->billing_cycle ?? 'monthly';
                                                                $durationMonths = $this->getBillingCycleMonths($billingCycle);
                                                            } else {
                                                                // For subscription_renewal, use quantity as fallback
                                                                $durationMonths = (int) $item->quantity;
                                                            }
                                                            $durationLabel = $this->formatDurationLabel($durationMonths);
                                                        @endphp
                                                        <button type="button" class="btn btn-outline-primary rounded-circle p-2"
                                                            style="width: 35px; height: 35px; font-size:16px !important;"
                                                            wire:click="updateQuantity('{{ $item->id }}', {{ $durationMonths - 1 }})"
                                                            wire:loading.class="opacity-75"
                                                            wire:target="updateQuantity('{{ $item->id }}', {{ $durationMonths - 1 }})"
                                                            {{ $durationMonths <= 1 ? 'disabled' : '' }}>
                                                            <i class="bi bi-dash"></i>
                                                        </button>
                                                        <span class="mx-4 fs-5" style="font-size: 16px !important;">
                                                            {{ $durationLabel }}
                                                        </span>
                                                        <button type="button" class="btn btn-outline-primary rounded-circle p-2"
                                                            style="width: 35px; height: 35px;"
                                                            wire:click="updateQuantity('{{ $item->id }}', {{ $durationMonths + 1 }})"
                                                            wire:loading.class="opacity-75"
                                                            wire:target="updateQuantity('{{ $item->id }}', {{ $durationMonths + 1 }})"
                                                            {{ $durationMonths >= 36 ? 'disabled' : '' }}>
                                                            <i class="bi bi-plus"></i>
                                                        </button>
                                                    @else
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
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-flex flex-column align-items-end">
                                                    <span class="text-muted" style="font-size: 14px !important; text-transform:uppercase !important;">
                                                        {{ $this->getFormattedItemPrice($item) }}
                                                        @if($itemType === 'subscription_renewal' || $itemType === 'hosting')
                                                            <span class="text-muted" style="font-size: 11px !important;">/month</span>
                                                        @else
                                                            <span class="text-muted" style="font-size: 11px !important;">/year</span>
                                                        @endif
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <span class="text-primary fw-bold"
                                                    style="font-size: 16px !important; text-transform:uppercase !important;">{{ $this->getFormattedItemTotal($item) }}</span>
                                            </td>
                                            <td class="text-center">
                                                <button wire:click="removeItem('{{ $item->id }}')" wire:loading.class="opacity-75"
                                                    wire:target="removeItem('{{ $item->id }}')" class="btn btn-danger btn-sm"
                                                    style="font-size: 14px !important;">
                                                    <i class="bi bi-trash3-fill me-1" wire:loading.remove
                                                        wire:target="removeItem('{{ $item->id }}')"></i>
                                                    <span wire:loading wire:target="removeItem('{{ $item->id }}')"
                                                        class="spinner-border spinner-border-sm me-1" role="status"
                                                        aria-hidden="true"></span>
                                                    Remove
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <p>Your cart is empty</p>
                            <a href="{{ route('domains') }}" class="btn btn-primary">Search Domains</a>
                        </div>
                    @endif
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

@push('styles')
<style>
    .cart-table {
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
        background: white;
    }

    .cart-table thead th {
        color: var(--color-secondary, #2d3748);
        font-weight: normal;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 1rem;
        border: none;
    }

    .cart-table tbody tr {
        transition: background-color 0.2s ease;
        border-bottom: 1px solid var(--border-color, #e9ecef);
    }

    .cart-table tbody tr:hover {
        background-color: transparent;
    }

    .cart-table tbody tr:last-child {
        border-bottom: none;
    }

    .cart-table tbody td {
        padding: 1.25rem 1rem;
        vertical-align: middle;
        border: none;
    }

    .cart-table tbody td:first-child {
        font-weight: 500;
        color: var(--color-secondary, #2d3748);
    }

    .cart-table .quantity-controls {
        gap: 0.5rem;
        padding: 0.5rem;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
    }

    .cart-table .quantity-controls button {
        transition: all 0.2s ease;
        border: 1px solid #dee2e6;
        background: white;
    }

    .cart-table .quantity-controls button:hover:not(:disabled) {
        background-color: var(--color-primary, #0774FF);
        color: white;
        border-color: var(--color-primary, #0774FF);
    }

    .cart-table .quantity-controls button:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }

    .cart-table .btn-danger {
        transition: all 0.2s ease;
    }

    .cart-table .btn-danger:hover {
        opacity: 0.9;
    }

    .table-responsive {
        border-radius: 8px;
        overflow: hidden;
    }

    @media (max-width: 768px) {
        .cart-table thead th,
        .cart-table tbody td {
            padding: 0.75rem 0.5rem;
            font-size: 14px;
        }

        .cart-table .quantity-controls {
            flex-direction: column;
            gap: 0.25rem;
        }

        .cart-table .quantity-controls span {
            margin: 0;
        }
    }
</style>
@endpush
