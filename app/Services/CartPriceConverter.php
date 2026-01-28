<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Currency\CurrencyConverterContract;
use Darryldecode\Cart\CartCollection;
use Exception;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class CartPriceConverter
{
    public function __construct(
        private CurrencyConverterContract $currencyConverter
    ) {}

    /**
     * @throws Exception|Throwable
     */
    public function convertItemPrice(object $item, string $targetCurrency): float
    {
        $itemCurrency = $item->attributes->currency ?? 'USD';
        $itemType = $item->attributes->get('type', 'registration');
        $itemPrice = $item->price;

        if ($itemType === 'hosting') {
            return $this->convertHostingItemPrice($item, $targetCurrency);
        }

        if ($itemType === 'subscription_renewal') {
            return $this->convertSubscriptionRenewalItemPrice($item, $targetCurrency);
        }

        if ($itemCurrency === $targetCurrency) {
            return $itemPrice;
        }

        try {
            return $this->currencyConverter->convert($itemPrice, $itemCurrency, $targetCurrency);
        } catch (Exception $exception) {
            Log::error('Currency conversion failed in CartPriceConverter', [
                'from' => $itemCurrency,
                'to' => $targetCurrency,
                'amount' => $itemPrice,
                'error' => $exception->getMessage(),
            ]);

            throw new Exception('Unable to convert currency.', $exception->getCode(), $exception);
        }
    }

    /**
     * Calculate the total price for a cart item in the target currency.
     * This handles quantity and special pricing logic for different item types.
     *
     * @throws Exception|Throwable
     */
    public function calculateItemTotal(object $item, string $targetCurrency): float
    {
        $itemType = $item->attributes->get('type', 'registration');

        if ($itemType === 'hosting') {
            return $this->calculateHostingItemTotal($item, $targetCurrency);
        }

        if ($itemType === 'subscription_renewal') {
            return $this->calculateSubscriptionRenewalItemTotal($item, $targetCurrency);
        }

        $convertedPrice = $this->convertItemPrice($item, $targetCurrency);

        return $convertedPrice * $item->quantity;
    }

    /**
     * Convert all cart items to the target currency and return a collection.
     * This is useful for order creation where all items need to be in the same currency.
     *
     * @throws Exception|Throwable
     */
    public function convertCartItemsToCurrency(CartCollection $cartItems, string $targetCurrency): CartCollection
    {
        $convertedItems = collect();

        foreach ($cartItems as $item) {
            $originalCurrency = $item->attributes->currency ?? 'USD';
            $itemType = $item->attributes->get('type', 'registration');

            // Convert monthly_unit_price for hosting items before converting price
            if ($itemType === 'hosting') {
                $monthlyUnitPrice = $item->attributes->get('monthly_unit_price');
                if ($monthlyUnitPrice !== null && $originalCurrency !== $targetCurrency) {
                    try {
                        $convertedMonthlyPrice = $this->currencyConverter->convert($monthlyUnitPrice, $originalCurrency, $targetCurrency);
                        // Temporarily set converted monthly price to get correct converted price
                        $tempItem = clone $item;
                        $tempItem->attributes->monthly_unit_price = $convertedMonthlyPrice;
                        $tempItem->attributes->currency = $targetCurrency;
                        $convertedPrice = $this->convertItemPrice($tempItem, $targetCurrency);
                    } catch (Exception) {
                        $convertedPrice = $this->convertItemPrice($item, $targetCurrency);
                    }
                } else {
                    $convertedPrice = $this->convertItemPrice($item, $targetCurrency);
                }
            } elseif ($itemType === 'subscription_renewal') {
                $unitPrice = $item->attributes->get('unit_price');
                if ($unitPrice !== null && $originalCurrency !== $targetCurrency) {
                    try {
                        $convertedUnitPrice = $this->currencyConverter->convert($unitPrice, $originalCurrency, $targetCurrency);
                        // Temporarily set converted unit price to get correct converted price
                        $tempItem = clone $item;
                        $tempItem->attributes->unit_price = $convertedUnitPrice;
                        $tempItem->attributes->currency = $targetCurrency;
                        $convertedPrice = $this->convertItemPrice($tempItem, $targetCurrency);
                    } catch (Exception) {
                        $convertedPrice = $this->convertItemPrice($item, $targetCurrency);
                    }
                } else {
                    $convertedPrice = $this->convertItemPrice($item, $targetCurrency);
                }
            } else {
                $convertedPrice = $this->convertItemPrice($item, $targetCurrency);
            }

            // Clone the item and update its price and currency
            $convertedItem = clone $item;
            $convertedItem->price = $convertedPrice;
            $convertedItem->attributes->currency = $targetCurrency;

            // Set converted monthly_unit_price for hosting items
            if ($itemType === 'hosting') {
                $monthlyUnitPrice = $item->attributes->get('monthly_unit_price');
                if ($monthlyUnitPrice !== null && $originalCurrency !== $targetCurrency) {
                    try {
                        $convertedMonthlyPrice = $this->currencyConverter->convert($monthlyUnitPrice, $originalCurrency, $targetCurrency);
                        $convertedItem->attributes->monthly_unit_price = $convertedMonthlyPrice;
                    } catch (Exception) {
                        // Keep original if conversion fails
                    }
                }
            }

            // Set converted unit_price for subscription renewal items
            if ($itemType === 'subscription_renewal') {
                $unitPrice = $item->attributes->get('unit_price');
                if ($unitPrice !== null && $originalCurrency !== $targetCurrency) {
                    try {
                        $convertedUnitPrice = $this->currencyConverter->convert($unitPrice, $originalCurrency, $targetCurrency);
                        $convertedItem->attributes->unit_price = $convertedUnitPrice;
                    } catch (Exception) {
                        // Keep original if conversion fails
                    }
                }
            }

            // Preserve original currency for audit purposes
            if ($originalCurrency !== $targetCurrency) {
                $convertedItem->attributes->original_currency = $originalCurrency;
                $convertedItem->attributes->original_price = $item->price;
            }

            $convertedItems->push($convertedItem);
        }

        return new CartCollection($convertedItems);
    }

    /**
     * Calculate the total subtotal for all cart items in the target currency.
     *
     * @throws Exception|Throwable
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
     * @throws Exception|Throwable
     */
    private function convertHostingItemPrice(object $item, string $targetCurrency): float
    {
        $itemCurrency = $item->attributes->currency ?? 'USD';
        $monthlyPrice = $item->attributes->get('monthly_unit_price');

        if (! $monthlyPrice) {
            $billingCycle = $item->attributes->get('billing_cycle', 'monthly');
            $billingCycleMonths = $this->getBillingCycleMonths($billingCycle);
            $monthlyPrice = $billingCycleMonths > 0 ? $item->price / $billingCycleMonths : $item->price;
        }

        if ($itemCurrency === $targetCurrency) {
            return $monthlyPrice;
        }

        try {
            return $this->currencyConverter->convert($monthlyPrice, $itemCurrency, $targetCurrency);
        } catch (Exception $exception) {
            Log::error('Hosting item currency conversion failed', [
                'from' => $itemCurrency,
                'to' => $targetCurrency,
                'amount' => $monthlyPrice,
                'error' => $exception->getMessage(),
            ]);

            throw new Exception('Unable to convert hosting item currency.', $exception->getCode(), $exception);
        }
    }

    /**
     * Calculate hosting item total in target currency.
     * Uses monthly price × quantity (where quantity is in months).
     *
     * @throws Exception|Throwable
     */
    private function calculateHostingItemTotal(object $item, string $targetCurrency): float
    {
        $monthlyPrice = $this->convertHostingItemPrice($item, $targetCurrency);

        return $monthlyPrice * $item->quantity;
    }

    /**
     * @throws Exception|Throwable
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
            return $this->currencyConverter->convert($displayUnitPrice, $itemCurrency, $targetCurrency);
        } catch (Exception $exception) {
            Log::error('Subscription renewal item currency conversion failed', [
                'from' => $itemCurrency,
                'to' => $targetCurrency,
                'amount' => $displayUnitPrice,
                'error' => $exception->getMessage(),
            ]);

            throw new Exception('Unable to convert subscription renewal item currency.', $exception->getCode(), $exception);
        }
    }

    /**
     * @throws Exception|Throwable
     */
    private function calculateSubscriptionRenewalItemTotal(object $item, string $targetCurrency): float
    {
        // Always use monthly unit price for calculations, regardless of billing cycle
        // This ensures consistency with validation logic that expects monthly price × quantity
        $itemCurrency = $item->attributes->currency ?? 'USD';
        $monthlyUnitPrice = $item->attributes->get('unit_price', $item->price);

        if ($itemCurrency === $targetCurrency) {
            $convertedMonthlyPrice = $monthlyUnitPrice;
        } else {
            try {
                $convertedMonthlyPrice = $this->currencyConverter->convert($monthlyUnitPrice, $itemCurrency, $targetCurrency);
            } catch (Exception $exception) {
                Log::error('Subscription renewal monthly unit price currency conversion failed', [
                    'from' => $itemCurrency,
                    'to' => $targetCurrency,
                    'amount' => $monthlyUnitPrice,
                    'error' => $exception->getMessage(),
                ]);

                throw new Exception('Unable to convert subscription renewal monthly unit price currency.', $exception->getCode(), $exception);
            }
        }

        // Always calculate as monthly price × quantity (in months)
        // This matches the validation logic in SubscriptionRenewalService
        return $convertedMonthlyPrice * $item->quantity;
    }

    /**
     * Get billing cycle duration in months.
     */
    private function getBillingCycleMonths(string $billingCycle): int
    {
        return match ($billingCycle) {
            'quarterly' => 3,
            'semi-annually' => 6,
            'annually' => 12,
            'biennially' => 24,
            'triennially' => 36,
            default => 1,
        };
    }
}
