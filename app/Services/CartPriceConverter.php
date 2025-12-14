<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\CurrencyHelper;
use Darryldecode\Cart\CartCollection;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Unified service for converting cart item prices to a target currency.
 * Handles all item types (domain, renewal, hosting, subscription_renewal) consistently.
 */
final readonly class CartPriceConverter
{
    public function __construct(
        private CurrencyService $currencyService
    ) {}

    /**
     * Convert a cart item's price to the target currency.
     * Handles all item types including hosting and subscription_renewal.
     *
     * @throws Exception
     */
    public function convertItemPrice(object $item, string $targetCurrency): float
    {
        $itemCurrency = $item->attributes->currency ?? 'USD';
        $itemType = $item->attributes->get('type', 'registration');
        $itemPrice = $item->price;

        // For hosting and subscription_renewal, we need special handling
        if ($itemType === 'hosting') {
            return $this->convertHostingItemPrice($item, $targetCurrency);
        }

        if ($itemType === 'subscription_renewal') {
            return $this->convertSubscriptionRenewalItemPrice($item, $targetCurrency);
        }

        // For all other item types (domain, renewal, registration, transfer)
        if ($itemCurrency === $targetCurrency) {
            return $itemPrice;
        }

        try {
            return CurrencyHelper::convert($itemPrice, $itemCurrency, $targetCurrency);
        } catch (Exception $exception) {
            Log::warning('Primary currency conversion failed in CartPriceConverter', [
                'from' => $itemCurrency,
                'to' => $targetCurrency,
                'amount' => $itemPrice,
                'error' => $exception->getMessage(),
            ]);

            try {
                return $this->currencyService->convert($itemPrice, $itemCurrency, $targetCurrency);
            } catch (Exception $fallbackException) {
                Log::error('Currency conversion failed after fallback in CartPriceConverter', [
                    'from' => $itemCurrency,
                    'to' => $targetCurrency,
                    'amount' => $itemPrice,
                    'error' => $fallbackException->getMessage(),
                ]);

                throw new Exception('Unable to convert currency.', $exception->getCode(), $exception);
            }
        }
    }

    /**
     * Calculate the total price for a cart item in the target currency.
     * This handles quantity and special pricing logic for different item types.
     *
     * @throws Exception
     */
    public function calculateItemTotal(object $item, string $targetCurrency): float
    {
        $itemType = $item->attributes->get('type', 'registration');
        $itemCurrency = $item->attributes->currency ?? 'USD';

        if ($itemType === 'hosting') {
            return $this->calculateHostingItemTotal($item, $targetCurrency);
        }

        if ($itemType === 'subscription_renewal') {
            return $this->calculateSubscriptionRenewalItemTotal($item, $targetCurrency);
        }

        // For all other item types
        $convertedPrice = $this->convertItemPrice($item, $targetCurrency);

        return $convertedPrice * $item->quantity;
    }

    /**
     * Convert all cart items to the target currency and return a collection.
     * This is useful for order creation where all items need to be in the same currency.
     *
     * @throws Exception
     */
    public function convertCartItemsToCurrency(CartCollection $cartItems, string $targetCurrency): CartCollection
    {
        $convertedItems = collect();

        foreach ($cartItems as $item) {
            $convertedPrice = $this->convertItemPrice($item, $targetCurrency);

            // Clone the item and update its price and currency
            $convertedItem = clone $item;
            $convertedItem->price = $convertedPrice;
            $convertedItem->attributes->currency = $targetCurrency;

            $convertedItems->push($convertedItem);
        }

        return new CartCollection($convertedItems);
    }

    /**
     * Calculate the total subtotal for all cart items in the target currency.
     *
     * @throws Exception
     */
    public function calculateCartSubtotal(CartCollection $cartItems, string $targetCurrency): float
    {
        $subtotal = 0;

        foreach ($cartItems as $item) {
            $subtotal += $this->calculateItemTotal($item, $targetCurrency);
        }

        return $subtotal;
    }

    /**
     * Convert hosting item price to target currency.
     * Hosting items use monthly_unit_price for calculations.
     *
     * @throws Exception
     */
    private function convertHostingItemPrice(object $item, string $targetCurrency): float
    {
        $itemCurrency = $item->attributes->currency ?? 'USD';
        $monthlyPrice = $item->attributes->get('monthly_unit_price');

        // If monthly_unit_price is not set, calculate it from the item price
        if (! $monthlyPrice) {
            $billingCycle = $item->attributes->get('billing_cycle', 'monthly');
            $billingCycleMonths = $this->getBillingCycleMonths($billingCycle);
            $monthlyPrice = $billingCycleMonths > 0 ? $item->price / $billingCycleMonths : $item->price;
        }

        if ($itemCurrency === $targetCurrency) {
            return $monthlyPrice;
        }

        try {
            return CurrencyHelper::convert($monthlyPrice, $itemCurrency, $targetCurrency);
        } catch (Exception $exception) {
            Log::warning('Hosting item currency conversion failed', [
                'from' => $itemCurrency,
                'to' => $targetCurrency,
                'amount' => $monthlyPrice,
                'error' => $exception->getMessage(),
            ]);

            try {
                return $this->currencyService->convert($monthlyPrice, $itemCurrency, $targetCurrency);
            } catch (Exception $fallbackException) {
                Log::error('Hosting item currency conversion failed after fallback', [
                    'from' => $itemCurrency,
                    'to' => $targetCurrency,
                    'amount' => $monthlyPrice,
                    'error' => $fallbackException->getMessage(),
                ]);

                throw new Exception('Unable to convert hosting item currency.', $exception->getCode(), $exception);
            }
        }
    }

    /**
     * Calculate hosting item total in target currency.
     * Uses monthly price × quantity (where quantity is in months).
     *
     * @throws Exception
     */
    private function calculateHostingItemTotal(object $item, string $targetCurrency): float
    {
        $monthlyPrice = $this->convertHostingItemPrice($item, $targetCurrency);

        return $monthlyPrice * $item->quantity;
    }

    /**
     * Convert subscription renewal item price to target currency.
     * Subscription renewals use display_unit_price or unit_price.
     *
     * @throws Exception
     */
    private function convertSubscriptionRenewalItemPrice(object $item, string $targetCurrency): float
    {
        $itemCurrency = $item->attributes->currency ?? 'USD';
        $displayUnitPrice = $item->attributes->get('display_unit_price');

        // If display_unit_price is not set, fall back to unit_price
        if (! $displayUnitPrice) {
            $displayUnitPrice = $item->attributes->get('unit_price', $item->price);
        }

        if ($itemCurrency === $targetCurrency) {
            return $displayUnitPrice;
        }

        try {
            return CurrencyHelper::convert($displayUnitPrice, $itemCurrency, $targetCurrency);
        } catch (Exception $exception) {
            Log::warning('Subscription renewal item currency conversion failed', [
                'from' => $itemCurrency,
                'to' => $targetCurrency,
                'amount' => $displayUnitPrice,
                'error' => $exception->getMessage(),
            ]);

            try {
                return $this->currencyService->convert($displayUnitPrice, $itemCurrency, $targetCurrency);
            } catch (Exception $fallbackException) {
                Log::error('Subscription renewal item currency conversion failed after fallback', [
                    'from' => $itemCurrency,
                    'to' => $targetCurrency,
                    'amount' => $displayUnitPrice,
                    'error' => $fallbackException->getMessage(),
                ]);

                throw new Exception('Unable to convert subscription renewal item currency.', $exception->getCode(), $exception);
            }
        }
    }

    /**
     * Calculate subscription renewal item total in target currency.
     * Handles annual billing cycles by converting months to years.
     *
     * @throws Exception
     */
    private function calculateSubscriptionRenewalItemTotal(object $item, string $targetCurrency): float
    {
        $billingCycle = $item->attributes->get('billing_cycle', 'monthly');
        $displayUnitPrice = $this->convertSubscriptionRenewalItemPrice($item, $targetCurrency);

        // If billing cycle is annually, convert months to years for calculation
        if ($billingCycle === 'annually') {
            $years = $item->quantity / 12;

            return $displayUnitPrice * $years;
        }

        // For monthly, use monthly price × quantity (in months)
        return $displayUnitPrice * $item->quantity;
    }

    /**
     * Get billing cycle duration in months.
     */
    private function getBillingCycleMonths(string $billingCycle): int
    {
        return match ($billingCycle) {
            'monthly' => 1,
            'quarterly' => 3,
            'semi-annually' => 6,
            'annually' => 12,
            'biennially' => 24,
            'triennially' => 36,
            default => 1,
        };
    }
}
