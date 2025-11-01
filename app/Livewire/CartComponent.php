<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Helpers\CurrencyHelper;
use App\Models\Coupon;
use App\Services\Coupon\CouponService;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Log;

final class CartComponent extends Component
{
    public $items;

    public $subtotalAmount = 0;

    public $totalAmount = 0;

    public $currency;

    public ?string $couponCode = null;

    public ?Coupon $appliedCoupon = null;

    public float $discountAmount = 0;

    public bool $isCouponApplied = false;

    protected $listeners = ['refreshCart' => '$refresh', 'currencyChanged' => 'updateCurrency'];

    public function mount(): void
    {
        $this->currency = CurrencyHelper::getUserCurrency();

        // Restore coupon from session if exists
        if (session()->has('coupon')) {
            $couponData = session('coupon');
            $this->appliedCoupon = Coupon::where('code', $couponData['code'])->first();
            if ($this->appliedCoupon) {
                $this->isCouponApplied = true;
                $this->couponCode = $this->appliedCoupon->code;
            }
        }

        $this->updateCartTotal();
    }

    public function updateCurrency(string $currency): void
    {
        Log::info('CartComponent received currency change', [
            'new_currency' => $currency,
            'old_currency' => $this->currency,
        ]);

        $this->currency = $currency;
        $this->updateCartTotal();

        // Recalculate discount if coupon is applied
        if ($this->isCouponApplied && $this->appliedCoupon) {
            $this->calculateDiscount();

            // Update coupon session with new currency
            session([
                'coupon' => [
                    'code' => $this->appliedCoupon->code,
                    'type' => $this->appliedCoupon->type->value,
                    'value' => $this->appliedCoupon->value,
                    'discount_amount' => $this->discountAmount,
                    'currency' => $this->currency,
                ],
            ]);
        }

        // Update session currency
        session(['selected_currency' => $this->currency]);
    }

    public function updateCartTotal(): void
    {
        // Get cart content and maintain original order
        $cartContent = Cart::getContent();

        // Sort by creation timestamp to maintain consistent order
        // This ensures items stay in the same order regardless of updates
        $this->items = $cartContent->sortBy(function ($item) {
            return $item->attributes->get('added_at', 0);
        });

        // Calculate totals with currency conversion
        $subtotal = 0;

        foreach ($this->items as $item) {
            $itemCurrency = $item->attributes->currency ?? 'USD';
            $itemPrice = $item->price;

            // Convert item price to display currency if different
            if ($itemCurrency !== $this->currency) {
                try {
                    $itemPrice = CurrencyHelper::convert(
                        $item->price,
                        $itemCurrency,
                        $this->currency
                    );
                } catch (Exception) {
                    // Fallback to original price if conversion fails
                    $itemPrice = $item->price;
                }
            }

            $itemTotal = $itemPrice * $item->quantity;
            $subtotal += $itemTotal;
        }

        $this->subtotalAmount = $subtotal;

        // Apply discount to total
        $total = $subtotal - $this->discountAmount;

        // Ensure total never goes below zero
        $this->totalAmount = max(0, $total);
    }

    /**
     * @throws Exception
     */
    public function getFormattedSubtotalProperty(): string
    {
        return CurrencyHelper::formatMoney($this->subtotalAmount, $this->currency);
    }

    /**
     * @throws Exception
     */
    public function getFormattedTotalProperty(): string
    {
        return CurrencyHelper::formatMoney($this->totalAmount, $this->currency);
    }

    /**
     * @throws Exception
     */
    public function getFormattedDiscountProperty(): string
    {
        try {
            return CurrencyHelper::formatMoney($this->discountAmount, $this->currency);
        } catch (Exception $e) {
            return CurrencyHelper::formatMoney(0, $this->currency);
        }
    }

    /**
     * Get formatted price for individual cart item
     *
     * @throws Exception
     */
    public function getFormattedItemPrice($item): string
    {
        $itemCurrency = $item->attributes->currency ?? 'USD';
        $itemPrice = $item->price;

        // Convert item price to display currency if different
        if ($itemCurrency !== $this->currency) {
            try {
                $itemPrice = CurrencyHelper::convert(
                    $item->price,
                    $itemCurrency,
                    $this->currency
                );
            } catch (Exception) {
                // Fallback to original price if conversion fails
                $itemPrice = $item->price;
            }
        }

        return CurrencyHelper::formatMoney($itemPrice, $this->currency);
    }

    /**
     * Get formatted total price for individual cart item (price * quantity)
     */
    public function getFormattedItemTotal($item): string
    {
        $itemCurrency = $item->attributes->currency ?? 'USD';
        $itemPrice = $item->price;

        // Convert item price to display currency if different
        if ($itemCurrency !== $this->currency) {
            try {
                $itemPrice = CurrencyHelper::convert(
                    $item->price,
                    $itemCurrency,
                    $this->currency
                );
            } catch (Exception) {
                // Fallback to original price if conversion fails
                $itemPrice = $item->price;
            }
        }

        $total = $itemPrice * $item->quantity;

        return CurrencyHelper::formatMoney($total, $this->currency);
    }

    public function updateQuantity($id, $quantity): void
    {
        try {
            if ($quantity > 0 && $quantity <= 10) {
                // Get current item to preserve its attributes
                $currentItem = Cart::get($id);

                // Update quantity while preserving attributes
                Cart::update($id, [
                    'quantity' => [
                        'relative' => false,
                        'value' => (int) $quantity,
                    ],
                ]);

                // Make sure we preserve the original added_at timestamp
                // This ensures the item maintains its position in the list
                if (! $currentItem->attributes->has('added_at')) {
                    Cart::update($id, [
                        'attributes' => [
                            'added_at' => now()->timestamp,
                        ],
                    ]);
                }

                $this->updateCartTotal();

                // Recalculate discount if coupon is applied
                if ($this->isCouponApplied && $this->appliedCoupon) {
                    $this->calculateDiscount();

                    // Update coupon session with new discount amount
                    session([
                        'coupon' => [
                            'code' => $this->appliedCoupon->code,
                            'type' => $this->appliedCoupon->type->value,
                            'value' => $this->appliedCoupon->value,
                            'discount_amount' => $this->discountAmount,
                            'currency' => $this->currency,
                        ],
                    ]);
                }

                $this->dispatch('refreshCart');

                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => 'Quantity updated successfully',
                ]);
            }
        } catch (Exception) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to update quantity',
            ]);
        }
    }

    public function addToCart($domain, $price, $currency = null): void
    {
        try {
            // Convert price string to numeric value (remove currency symbols)
            $numericPrice = (float) preg_replace('/[^\d.]/', '', $price);
            $itemCurrency = $currency ?? $this->currency;

            Cart::add([
                'id' => $domain,
                'name' => $domain,
                'price' => $numericPrice,
                'quantity' => 1,
                'attributes' => [
                    'type' => 'domain',
                    'currency' => $itemCurrency,
                    'added_at' => now()->timestamp,
                ],
            ]);

            $this->updateCartTotal();

            // Recalculate discount if coupon is applied
            if ($this->isCouponApplied && $this->appliedCoupon) {
                $this->calculateDiscount();

                // Update coupon session with new discount amount
                session([
                    'coupon' => [
                        'code' => $this->appliedCoupon->code,
                        'type' => $this->appliedCoupon->type->value,
                        'value' => $this->appliedCoupon->value,
                        'discount_amount' => $this->discountAmount,
                        'currency' => $this->currency,
                    ],
                ]);
            }

            $this->dispatch('refreshCart');

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Domain added to cart successfully',
            ]);
        } catch (Exception) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to add domain to cart',
            ]);
        }
    }

    /**
     * Add domain renewal to cart
     */
    public function addRenewalToCart(int $domainId, int $years = 1): void
    {
        try {
            $domain = \App\Models\Domain::with('domainPrice')->findOrFail($domainId);

            // Validate ownership
            if ($domain->owner_id !== auth()->id()) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'You do not own this domain',
                ]);

                return;
            }

            // Validate domain can be renewed
            $renewalService = app(\App\Services\RenewalService::class);
            $canRenew = $renewalService->canRenewDomain($domain, auth()->id());

            if (! $canRenew['can_renew']) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => $canRenew['reason'] ?? 'Cannot renew this domain',
                ]);

                return;
            }

            // Get renewal price
            $priceData = $renewalService->getRenewalPrice($domain, $years);
            $price = $priceData['price'];
            $currency = $priceData['currency'];

            // Create unique cart ID for renewal
            $cartId = 'renewal-'.$domainId;

            // Check if already in cart
            if (Cart::get($cartId)) {
                $this->dispatch('notify', [
                    'type' => 'warning',
                    'message' => 'This domain renewal is already in your cart',
                ]);

                return;
            }

            // Add to cart
            Cart::add([
                'id' => $cartId,
                'name' => $domain->name.' (Renewal)',
                'price' => $price,
                'quantity' => $years,
                'attributes' => [
                    'type' => 'renewal',
                    'domain_id' => $domain->id,
                    'domain_name' => $domain->name,
                    'current_expiry' => $domain->expires_at?->format('Y-m-d'),
                    'tld' => $domain->domainPrice->tld,
                    'currency' => $currency,
                    'added_at' => now()->timestamp,
                ],
            ]);

            $this->updateCartTotal();

            // Recalculate discount if coupon is applied
            if ($this->isCouponApplied && $this->appliedCoupon) {
                $this->calculateDiscount();

                // Update coupon session with new discount amount
                session([
                    'coupon' => [
                        'code' => $this->appliedCoupon->code,
                        'type' => $this->appliedCoupon->type->value,
                        'value' => $this->appliedCoupon->value,
                        'discount_amount' => $this->discountAmount,
                        'currency' => $this->currency,
                    ],
                ]);
            }

            $this->dispatch('refreshCart');

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Domain renewal for {$domain->name} added to cart for {$years} year(s)",
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to add renewal to cart', [
                'domain_id' => $domainId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to add renewal to cart: '.$e->getMessage(),
            ]);
        }
    }

    public function removeItem($id): void
    {
        try {
            Cart::remove($id);
            $this->updateCartTotal();

            // Recalculate discount if coupon is applied
            if ($this->isCouponApplied && $this->appliedCoupon) {
                $this->calculateDiscount();

                // Update coupon session with new discount amount
                session([
                    'coupon' => [
                        'code' => $this->appliedCoupon->code,
                        'type' => $this->appliedCoupon->type->value,
                        'value' => $this->appliedCoupon->value,
                        'discount_amount' => $this->discountAmount,
                        'currency' => $this->currency,
                    ],
                ]);
            }

            $this->dispatch('refreshCart');

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Item removed from cart successfully',
            ]);
        } catch (Exception) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to remove item from cart',
            ]);
        }
    }

    public function applyCoupon(): void
    {
        try {
            // Validate coupon code is not empty
            if (empty($this->couponCode)) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Please enter a coupon code',
                ]);

                return;
            }

            // Check if cart has items
            if ($this->items->isEmpty()) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Add items to cart before applying coupon',
                ]);

                return;
            }

            // Validate coupon using CouponService
            $couponService = app(CouponService::class);
            $this->appliedCoupon = $couponService->validateCoupon($this->couponCode);
            $this->isCouponApplied = true;

            // Calculate discount (this will set discountAmount and update totalAmount)
            $this->calculateDiscount();

            // Store coupon data in session
            session([
                'coupon' => [
                    'code' => $this->appliedCoupon->code,
                    'type' => $this->appliedCoupon->type->value,
                    'value' => $this->appliedCoupon->value,
                    'discount_amount' => $this->discountAmount,
                    'currency' => $this->currency,
                ],
            ]);

            // Dispatch event to update other components (like CartTotal)
            $this->dispatch('couponApplied');
            $this->dispatch('refreshCart');

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Coupon '{$this->appliedCoupon->code}' applied! You saved ".CurrencyHelper::formatMoney($this->discountAmount, $this->currency),
            ]);
        } catch (Exception $e) {
            // Clear any partial coupon state
            $this->appliedCoupon = null;
            $this->isCouponApplied = false;
            $this->discountAmount = 0;

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function removeCoupon(): void
    {
        // Reset coupon properties
        $this->appliedCoupon = null;
        $this->discountAmount = 0;
        $this->isCouponApplied = false;
        $this->couponCode = null;

        session()->forget('coupon');

        $this->updateCartTotal();

        // Dispatch event to update other components (like CartTotal)
        $this->dispatch('couponRemoved');
        $this->dispatch('refreshCart');

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Coupon removed successfully',
        ]);
    }

    /**
     * Prepare cart data for payment processing
     */
    public function prepareCartForPayment(): array
    {
        $cartItems = [];

        foreach ($this->items as $item) {
            $itemCurrency = $item->attributes->currency ?? 'USD';
            $itemPrice = $item->price;

            if ($itemCurrency !== $this->currency) {
                try {
                    $itemPrice = CurrencyHelper::convert(
                        $item->price,
                        $itemCurrency,
                        $this->currency
                    );
                } catch (Exception) {
                    $itemPrice = $item->price;
                }
            }

            $cartItems[] = [
                'domain_name' => $item->name,
                'domain_type' => $item->attributes->get('type', 'registration'),
                'price' => $itemPrice,
                'currency' => $this->currency,
                'quantity' => $item->quantity,
                'years' => $item->quantity,
                'domain_id' => $item->attributes->get('domain_id'),
            ];
        }

        $paymentData = [
            'items' => $cartItems,
            'subtotal' => $this->subtotalAmount,
            'total' => $this->totalAmount,
            'currency' => $this->currency,
        ];

        if ($this->isCouponApplied && $this->appliedCoupon) {
            $paymentData['coupon'] = [
                'code' => $this->appliedCoupon->code,
                'type' => $this->appliedCoupon->type->value,
                'value' => $this->appliedCoupon->value,
                'discount_amount' => $this->discountAmount,
            ];
        }

        return $paymentData;
    }

    public function proceedToPayment(): void
    {
        if ($this->items->isEmpty()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Your cart is empty',
            ]);

            return;
        }

        // Prepare cart data with currency conversion
        $paymentData = $this->prepareCartForPayment();

        // Store prepared cart data in session
        session([
            'cart' => $paymentData['items'],
            'cart_subtotal' => $paymentData['subtotal'],
            'cart_total' => $paymentData['total'],
            'selected_currency' => $paymentData['currency'],
        ]);

        // Store coupon data if present
        if (isset($paymentData['coupon'])) {
            session(['coupon' => $paymentData['coupon']]);
        }

        $this->redirect(route('payment.index'));
    }

    public function render(): View
    {
        return view('livewire.cart-component');
    }

    private function calculateDiscount(): void
    {
        if (! $this->appliedCoupon || ! $this->isCouponApplied) {
            $this->discountAmount = 0;

            return;
        }

        $subtotal = $this->subtotalAmount;

        $couponService = app(CouponService::class);
        $discountedTotal = $couponService->applyCoupon($this->appliedCoupon, $subtotal);

        $discount = $subtotal - $discountedTotal;

        $this->discountAmount = max(0, min($discount, $subtotal));

        $this->totalAmount = max(0, $subtotal - $this->discountAmount);
    }
}
