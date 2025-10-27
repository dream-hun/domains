<?php

declare(strict_types=1);

namespace App\Services\Cart;

use App\Services\Coupon\CouponService;
use App\Services\CurrencyService;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

final class CartService
{
    public function __construct(
        private readonly CartItemManager $cartItemManager,
        private readonly CartPricingCalculator $pricingCalculator,
        private readonly CartMergeService $mergeService,
        private readonly CouponService $couponService,
        private readonly CurrencyService $currencyService
    ) {}

    /**
     * Add an item to the cart
     */
    public function addItem(string $domainName, array $pricing, array $attributes = []): mixed
    {
        // Validate years
        $years = $pricing['years'] ?? 1;
        if ($years < 1 || $years > 10) {
            throw new InvalidArgumentException('Years must be between 1 and 10');
        }

        $data = [
            'domain_name' => $domainName,
            'domain_type' => $pricing['domain_type'] ?? 'registration',
            'tld' => $pricing['tld'] ?? $this->extractTld($domainName),
            'base_price' => $pricing['base_price'],
            'base_currency' => $pricing['base_currency'] ?? 'USD',
            'eap_fee' => $pricing['eap_fee'] ?? 0,
            'premium_fee' => $pricing['premium_fee'] ?? 0,
            'privacy_fee' => $pricing['privacy_fee'] ?? 0,
            'years' => $years,
            'quantity' => 1,
            'attributes' => array_merge([
                'added_at' => now()->timestamp,
            ], $attributes),
        ];

        $item = $this->cartItemManager->create($data);
        $this->clearCache();

        return $item;
    }

    /**
     * Remove an item from the cart
     */
    public function removeItem(int|string $itemId): bool
    {
        $result = $this->cartItemManager->delete($itemId);

        if ($result) {
            $this->clearCache();
        }

        return $result;
    }

    /**
     * Update the quantity (years) for a cart item
     */
    public function updateQuantity(int|string $itemId, int $years): bool
    {
        // Validate years
        if ($years < 1 || $years > 10) {
            throw new InvalidArgumentException('Years must be between 1 and 10');
        }

        $result = $this->cartItemManager->update($itemId, ['years' => $years]);

        if ($result) {
            $this->clearCache();
        }

        return $result;
    }

    /**
     * Clear all items from the cart
     */
    public function clearCart(): bool
    {
        if ($this->getStorageStrategy() === 'database') {
            \App\Models\CartItem::forUser(Auth::id())->delete();
        } else {
            session()->forget('cart.items');
        }

        $this->clearCache();

        return true;
    }

    /**
     * Get all cart items
     */
    public function getItems(): \Illuminate\Support\Collection
    {
        $cacheKey = $this->getCacheKey('items');

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function () {
            return $this->cartItemManager->all();
        });
    }

    /**
     * Get a single cart item
     */
    public function getItem(int|string $itemId): mixed
    {
        return $this->cartItemManager->find($itemId);
    }

    /**
     * Get the count of items in the cart
     */
    public function getItemCount(): int
    {
        return $this->getItems()->count();
    }

    /**
     * Get the cart subtotal
     */
    public function getSubtotal(?string $currency = null): float
    {
        $currency = $currency ?? $this->currencyService->getUserCurrency()->code;
        $items = $this->getItems();

        return $this->pricingCalculator->calculateSubtotal($items, $currency);
    }

    /**
     * Get the cart total with discount applied
     */
    public function getTotal(?string $currency = null): float
    {
        $subtotal = $this->getSubtotal($currency);
        $discount = $this->getDiscount();

        return $this->pricingCalculator->calculateTotal($subtotal, $discount);
    }

    /**
     * Get the discount amount
     */
    public function getDiscount(): float
    {
        $coupon = $this->getAppliedCoupon();

        if (! $coupon) {
            return 0;
        }

        $subtotal = $this->getSubtotal();

        return $this->pricingCalculator->calculateDiscount($subtotal, $coupon);
    }

    /**
     * Apply a coupon to the cart
     */
    public function applyCoupon(string $code): \App\Models\Coupon
    {
        // Validate coupon
        $coupon = $this->couponService->validateCoupon($code);

        // Calculate discount
        $subtotal = $this->getSubtotal();
        $discountAmount = $this->pricingCalculator->calculateDiscount($subtotal, $coupon);

        // Store coupon in session
        session([
            'cart.coupon' => [
                'code' => $coupon->code,
                'type' => is_string($coupon->type) ? $coupon->type : $coupon->type->value,
                'value' => $coupon->value,
                'discount_amount' => $discountAmount,
                'currency' => $this->currencyService->getUserCurrency()->code,
            ],
        ]);

        $this->clearCache();

        return $coupon;
    }

    /**
     * Remove the applied coupon
     */
    public function removeCoupon(): bool
    {
        session()->forget('cart.coupon');
        $this->clearCache();

        return true;
    }

    /**
     * Get the currently applied coupon
     */
    public function getAppliedCoupon(): ?\App\Models\Coupon
    {
        if (! session()->has('cart.coupon')) {
            return null;
        }

        $couponData = session('cart.coupon');

        return \App\Models\Coupon::where('code', $couponData['code'])->first();
    }

    /**
     * Convert cart to a different currency
     */
    public function convertCurrency(string $targetCurrency): bool
    {
        // Update session currency
        session(['selected_currency' => $targetCurrency]);

        // Recalculate coupon discount if applied
        if (session()->has('cart.coupon')) {
            $couponData = session('cart.coupon');
            $coupon = $this->getAppliedCoupon();

            if ($coupon) {
                $subtotal = $this->getSubtotal($targetCurrency);
                $discountAmount = $this->pricingCalculator->calculateDiscount($subtotal, $coupon);

                session([
                    'cart.coupon' => [
                        'code' => $coupon->code,
                        'type' => is_string($coupon->type) ? $coupon->type : $coupon->type->value,
                        'value' => $coupon->value,
                        'discount_amount' => $discountAmount,
                        'currency' => $targetCurrency,
                    ],
                ]);
            }
        }

        $this->clearCache();

        return true;
    }

    /**
     * Validate the cart before checkout
     */
    public function validateCart(): array
    {
        $errors = [];
        $items = $this->getItems();

        // Check if cart has items
        if ($items->isEmpty()) {
            $errors[] = 'Cart is empty';

            return ['valid' => false, 'errors' => $errors];
        }

        // Additional validation can be added here
        // - Domain availability check
        // - Price validation
        // etc.

        return ['valid' => true, 'errors' => []];
    }

    /**
     * Prepare cart data for checkout
     */
    public function prepareForCheckout(): array
    {
        $items = $this->getItems();
        $currency = $this->currencyService->getUserCurrency()->code;
        $cartItems = [];

        foreach ($items as $item) {
            $itemTotal = $this->pricingCalculator->calculateItemTotal($item, $currency);

            $cartItems[] = [
                'domain_name' => $item->domain_name ?? $item->domain_name,
                'domain_type' => $item->domain_type ?? $item->domain_type,
                'tld' => $item->tld ?? $item->tld,
                'price' => $item->base_price ?? $item->base_price,
                'currency' => $currency,
                'quantity' => $item->quantity ?? $item->quantity,
                'years' => $item->years ?? $item->years,
                'eap_fee' => $item->eap_fee ?? $item->eap_fee,
                'premium_fee' => $item->premium_fee ?? $item->premium_fee,
                'privacy_fee' => $item->privacy_fee ?? $item->privacy_fee,
                'item_total' => $itemTotal,
                'domain_id' => $item->attributes['domain_id'] ?? null,
            ];
        }

        $subtotal = $this->getSubtotal($currency);
        $discount = $this->getDiscount();
        $total = $this->getTotal($currency);

        $paymentData = [
            'items' => $cartItems,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'currency' => $currency,
        ];

        // Include coupon details if applied
        if ($this->getAppliedCoupon()) {
            $couponData = session('cart.coupon');
            $paymentData['coupon'] = [
                'code' => $couponData['code'],
                'type' => $couponData['type'],
                'value' => $couponData['value'],
                'discount_amount' => $discount,
            ];
        }

        return $paymentData;
    }

    /**
     * Determine the storage strategy based on authentication status
     */
    private function getStorageStrategy(): string
    {
        return Auth::check() ? 'database' : 'session';
    }

    /**
     * Get the identifier for the current cart (user ID or session ID)
     */
    private function getIdentifier(): string|int
    {
        return Auth::check()
            ? Auth::id()
            : session()->getId();
    }

    /**
     * Check if the user is authenticated
     */
    private function isAuthenticated(): bool
    {
        return Auth::check();
    }

    /**
     * Extract TLD from domain name
     */
    private function extractTld(string $domainName): string
    {
        $parts = explode('.', $domainName);

        return end($parts);
    }

    /**
     * Get cache key for cart data
     */
    private function getCacheKey(string $type = 'items'): string
    {
        $identifier = $this->getIdentifier();
        $strategy = $this->getStorageStrategy();

        return "cart:{$strategy}:{$identifier}:{$type}";
    }

    /**
     * Clear all cart-related cache
     */
    private function clearCache(): void
    {
        $identifier = $this->getIdentifier();
        $strategy = $this->getStorageStrategy();

        \Illuminate\Support\Facades\Cache::forget("cart:{$strategy}:{$identifier}:items");
        \Illuminate\Support\Facades\Cache::forget("cart:{$strategy}:{$identifier}:totals");
        \Illuminate\Support\Facades\Cache::forget("cart:{$strategy}:{$identifier}:subtotal");
        \Illuminate\Support\Facades\Cache::forget("cart:{$strategy}:{$identifier}:total");
    }
}
