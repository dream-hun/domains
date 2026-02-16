<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\CurrencyHelper;
use App\Models\HostingPlanPrice;
use App\Models\Tld;
use Darryldecode\Cart\CartCollection;
use Exception;
use Throwable;

final readonly class CartPriceConverter
{
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

        $domainPriceResult = $this->getDomainPriceAndCurrency($item, $targetCurrency);

        if ($domainPriceResult !== null) {
            return $domainPriceResult['price'];
        }

        return CurrencyHelper::convert($itemPrice);
    }

    /**
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
     * @throws Exception|Throwable
     */
    public function convertCartItemsToCurrency(CartCollection $cartItems, string $targetCurrency): CartCollection
    {
        $convertedItems = collect();

        foreach ($cartItems as $item) {
            $originalCurrency = $item->attributes->currency ?? 'USD';
            $itemType = $item->attributes->get('type', 'registration');
            $domainPriceResult = $itemType === 'domain' ? $this->getDomainPriceAndCurrency($item, $targetCurrency) : null;

            if ($domainPriceResult !== null) {
                $convertedPrice = $domainPriceResult['price'];
                $resolvedCurrency = $domainPriceResult['currency'];
            } else {
                $convertedPrice = $this->convertItemPrice($item, $targetCurrency);
                $resolvedCurrency = $targetCurrency;
            }

            $convertedItem = clone $item;
            $convertedItem->price = $convertedPrice;
            $convertedItem->attributes->currency = $resolvedCurrency;

            if ($originalCurrency !== $resolvedCurrency) {
                $convertedItem->attributes->original_currency = $originalCurrency;
                $convertedItem->attributes->original_price = $item->price;
            }

            if ($item->attributes->get('type') === 'hosting') {
                $monthlyUnitPrice = $item->attributes->get('monthly_unit_price');
                if ($monthlyUnitPrice !== null) {
                    $convertedItem->attributes->monthly_unit_price = $this->convertHostingItemPrice($item, $targetCurrency);
                }
            }

            if ($item->attributes->get('type') === 'subscription_renewal') {
                $unitPrice = $item->attributes->get('unit_price');
                if ($unitPrice !== null) {
                    $convertedItem->attributes->unit_price = $this->convertSubscriptionRenewalItemPrice($item, $targetCurrency);
                }
            }

            $convertedItems->push($convertedItem);
        }

        return new CartCollection($convertedItems);
    }

    /**
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
            return (float) $monthlyPrice;
        }

        $planPriceId = $item->attributes->get('hosting_plan_pricing_id') ?? $item->attributes->get('hosting_plan_price_id');
        if (! $planPriceId) {
            return CurrencyHelper::convert((float) $monthlyPrice);
        }

        $planPrice = HostingPlanPrice::query()->with('currency')->find($planPriceId);
        if (! $planPrice instanceof HostingPlanPrice) {
            return CurrencyHelper::convert((float) $monthlyPrice);
        }

        return $planPrice->getPriceInCurrency('regular_price');
    }

    /**
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
        $displayUnitPrice = $item->attributes->get('display_unit_price')
            ?? $item->attributes->get('unit_price', $item->price);

        if ($itemCurrency === $targetCurrency) {
            return (float) $displayUnitPrice;
        }

        return CurrencyHelper::convert((float) $displayUnitPrice);
    }

    /**
     * @throws Exception|Throwable
     */
    private function calculateSubscriptionRenewalItemTotal(object $item, string $targetCurrency): float
    {
        $itemCurrency = $item->attributes->currency ?? 'USD';
        $monthlyUnitPrice = $item->attributes->get('unit_price', $item->price);

        if ($itemCurrency === $targetCurrency) {
            $convertedMonthlyPrice = (float) $monthlyUnitPrice;
        } else {
            $convertedMonthlyPrice = CurrencyHelper::convert((float) $monthlyUnitPrice);
        }

        return $convertedMonthlyPrice * $item->quantity;
    }

    /**
     * @return array{price: float, currency: string}|null
     */
    private function getDomainPriceAndCurrency(object $item, string $targetCurrency): ?array
    {
        $domainName = $item->attributes->get('domain_name') ?? $item->name ?? null;

        if (! is_string($domainName)) {
            return null;
        }

        $tldPart = mb_strrpos($domainName, '.') !== false
            ? mb_substr($domainName, mb_strrpos($domainName, '.') + 1)
            : null;

        if (! $tldPart) {
            return null;
        }

        $tld = Tld::query()
            ->where('name', $tldPart)
            ->orWhere('name', '.'.$tldPart)
            ->first();

        if (! $tld instanceof Tld) {
            return null;
        }

        $display = $tld->getDisplayPriceForCurrency($targetCurrency, 'register_price');

        if ($display['amount'] <= 0.0) {
            return null;
        }

        return [
            'price' => $display['amount'],
            'currency' => $display['currency_code'],
        ];
    }

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
