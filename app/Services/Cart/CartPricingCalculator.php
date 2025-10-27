<?php

declare(strict_types=1);

namespace App\Services\Cart;

use App\Models\CartItem;
use App\Models\Coupon;
use App\Services\Coupon\CouponService;
use App\Services\CurrencyService;
use Exception;
use Illuminate\Support\Collection;

final class CartPricingCalculator
{
    public function __construct(
        private readonly CouponService $couponService,
        private readonly CurrencyService $currencyService
    ) {}

    /**
     * Calculate the total for a single cart item
     */
    public function calculateItemTotal(CartItem|array $item, ?string $currency = null): float
    {
        $currency = $currency ?? $this->getItemCurrency($item);

        // Convert base price if needed
        $basePrice = $this->convertIfNeeded(
            $this->getItemBasePrice($item),
            $this->getItemCurrency($item),
            $currency
        );

        // Base price is multiplied by years
        $baseTotal = $basePrice * $this->getItemYears($item);

        // Additional fees are one-time charges (not multiplied by years)
        $fees = $this->convertIfNeeded(
            $this->getItemEapFee($item) + $this->getItemPremiumFee($item) + $this->getItemPrivacyFee($item),
            $this->getItemCurrency($item),
            $currency
        );

        return round($baseTotal + $fees, 2);
    }

    /**
     * Calculate subtotal for all cart items
     */
    public function calculateSubtotal(Collection $items, ?string $currency = null): float
    {
        return $items->sum(fn ($item) => $this->calculateItemTotal($item, $currency));
    }

    /**
     * Calculate discount amount based on coupon
     */
    public function calculateDiscount(float $subtotal, ?Coupon $coupon): float
    {
        if (! $coupon) {
            return 0;
        }

        $discountedTotal = $this->couponService->applyCoupon($coupon, $subtotal);
        $discount = $subtotal - $discountedTotal;

        // Ensure discount doesn't exceed subtotal
        return min(max(0, $discount), $subtotal);
    }

    /**
     * Calculate final total with discount applied
     */
    public function calculateTotal(float $subtotal, float $discount): float
    {
        return max(0, round($subtotal - $discount, 2));
    }

    /**
     * Convert price from one currency to another
     */
    public function convertPrice(float $amount, string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        try {
            return $this->currencyService->convert($amount, $fromCurrency, $toCurrency);
        } catch (Exception) {
            // Fallback to original amount if conversion fails
            return $amount;
        }
    }

    /**
     * Convert price if currencies differ
     */
    private function convertIfNeeded(float $amount, string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        return $this->convertPrice($amount, $fromCurrency, $toCurrency);
    }

    /**
     * Get item base price (works with both CartItem model and array/object)
     */
    private function getItemBasePrice(CartItem|array $item): float
    {
        if ($item instanceof CartItem) {
            return (float) $item->base_price;
        }

        return (float) ($item->base_price ?? 0);
    }

    /**
     * Get item currency
     */
    private function getItemCurrency(CartItem|array $item): string
    {
        if ($item instanceof CartItem) {
            return $item->base_currency;
        }

        return $item->base_currency ?? 'USD';
    }

    /**
     * Get item years
     */
    private function getItemYears(CartItem|array $item): int
    {
        if ($item instanceof CartItem) {
            return $item->years;
        }

        return (int) ($item->years ?? 1);
    }

    /**
     * Get item EAP fee
     */
    private function getItemEapFee(CartItem|array $item): float
    {
        if ($item instanceof CartItem) {
            return (float) $item->eap_fee;
        }

        return (float) ($item->eap_fee ?? 0);
    }

    /**
     * Get item premium fee
     */
    private function getItemPremiumFee(CartItem|array $item): float
    {
        if ($item instanceof CartItem) {
            return (float) $item->premium_fee;
        }

        return (float) ($item->premium_fee ?? 0);
    }

    /**
     * Get item privacy fee
     */
    private function getItemPrivacyFee(CartItem|array $item): float
    {
        if ($item instanceof CartItem) {
            return (float) $item->privacy_fee;
        }

        return (float) ($item->privacy_fee ?? 0);
    }
}
