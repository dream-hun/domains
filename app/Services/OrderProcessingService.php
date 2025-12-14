<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\ProcessDomainRenewalJob;
use App\Jobs\ProcessSubscriptionRenewalJob;
use App\Models\Currency;
use App\Models\Domain;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;

final readonly class OrderProcessingService
{
    /**
     * Create OrderItem records from order's items JSON field
     * This ensures OrderItem records exist for SubscriptionRenewalService to process
     */
    public function createOrderItemsFromJson(Order $order): void
    {
        // Check if OrderItem records already exist to avoid duplicates
        if ($order->orderItems()->exists()) {
            Log::info('OrderItem records already exist for order', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);

            return;
        }

        $items = $order->items ?? [];

        if (empty($items)) {
            Log::warning('No items found in order JSON to create OrderItem records', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);

            return;
        }

        foreach ($items as $item) {
            $attributes = $item['attributes'] ?? [];
            $itemType = $attributes['type'] ?? 'registration';
            $itemPrice = (float) ($item['price'] ?? 0);
            $itemQuantity = (int) ($item['quantity'] ?? 1);
            $itemTotal = $itemPrice * $itemQuantity;
            $itemCurrency = $attributes['currency'] ?? $order->currency ?? 'USD';
            $domainId = $attributes['domain_id'] ?? null;
            $domainName = $attributes['domain_name'] ?? $item['name'] ?? 'Unknown';

            // Get the exchange rate for the item's currency
            $itemCurrencyModel = Currency::query()->where('code', $itemCurrency)->first();
            $exchangeRate = $itemCurrencyModel ? $itemCurrencyModel->exchange_rate : 1.0;

            // Build metadata from attributes
            $itemMetadata = $attributes['metadata'] ?? [];

            // For subscription renewals, ensure subscription_id and billing_cycle are in metadata
            if ($itemType === 'subscription_renewal') {
                $subscriptionId = $attributes['subscription_id'] ?? null;
                if ($subscriptionId) {
                    $itemMetadata['subscription_id'] = $subscriptionId;
                }

                // CRITICAL: Ensure billing_cycle is stored in metadata
                $billingCycle = $attributes['billing_cycle'] ?? null;
                if ($billingCycle) {
                    $itemMetadata['billing_cycle'] = $billingCycle;
                } else {
                    Log::warning('Billing cycle not found in subscription renewal attributes', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'item_name' => $domainName,
                        'attributes' => $attributes,
                    ]);
                }

                // Also include other subscription-related attributes
                if (isset($attributes['subscription_uuid'])) {
                    $itemMetadata['subscription_uuid'] = $attributes['subscription_uuid'];
                }

                if (isset($attributes['hosting_plan_id'])) {
                    $itemMetadata['hosting_plan_id'] = $attributes['hosting_plan_id'];
                }

                if (isset($attributes['hosting_plan_price_id'])) {
                    $itemMetadata['hosting_plan_price_id'] = $attributes['hosting_plan_price_id'];
                }
            }

            // For domain renewals, include years in metadata if present
            if ($itemType === 'renewal' && isset($attributes['years'])) {
                $itemMetadata['years'] = $attributes['years'];
            }

            // For renewal items, ensure years matches quantity to prevent renewal for wrong duration
            $years = $attributes['years'] ?? $itemQuantity;
            if ($itemType === 'renewal') {
                // For renewals, quantity represents years, so use quantity to ensure accuracy
                $years = $itemQuantity;
            }

            OrderItem::query()->create([
                'order_id' => $order->id,
                'domain_name' => $domainName,
                'domain_type' => $itemType,
                'domain_id' => $domainId,
                'price' => $itemPrice,
                'currency' => $itemCurrency,
                'exchange_rate' => $exchangeRate,
                'quantity' => $itemQuantity,
                'years' => $years,
                'total_amount' => $itemTotal,
                'metadata' => $itemMetadata,
            ]);
        }

        Log::info('Created OrderItem records from order JSON', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'items_count' => count($items),
        ]);
    }

    /**
     * Dispatch appropriate renewal jobs based on order items
     */
    public function dispatchRenewalJobs(Order $order): void
    {
        $items = $order->items ?? [];
        $hasDomainRenewals = false;
        $hasSubscriptionRenewals = false;

        foreach ($items as $item) {
            $itemType = $item['attributes']['type'] ?? null;

            if ($itemType === 'renewal') {
                $hasDomainRenewals = true;
            } elseif ($itemType === 'subscription_renewal') {
                $hasSubscriptionRenewals = true;
            }
        }

        if ($hasDomainRenewals) {
            Log::info('Dispatching ProcessDomainRenewalJob', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
            dispatch(new ProcessDomainRenewalJob($order));
        }

        if ($hasSubscriptionRenewals) {
            Log::info('Dispatching ProcessSubscriptionRenewalJob', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
            dispatch(new ProcessSubscriptionRenewalJob($order));
        }

        if (! $hasDomainRenewals && ! $hasSubscriptionRenewals) {
            Log::warning('No renewal items found in order, no jobs dispatched', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
        }
    }

    /**
     * Get redirect URL to service details based on order items
     * Prioritizes domain renewals, then subscription renewals, then falls back to billing
     */
    public function getServiceDetailsRedirectUrl(Order $order): string
    {
        // Load order items if not already loaded
        $orderItems = $order->orderItems;

        if ($orderItems->isEmpty()) {
            // Fallback to billing page if no order items
            return route('billing.show', $order);
        }

        // First, check for domain renewals
        foreach ($orderItems as $item) {
            if ($item->domain_type === 'renewal' && $item->domain_id) {
                $domain = Domain::query()->find($item->domain_id);
                if ($domain && $domain->uuid) {
                    return route('admin.domain.info', $domain);
                }
            }
        }

        // Then, check for subscription renewals
        foreach ($orderItems as $item) {
            if ($item->domain_type === 'subscription_renewal') {
                $metadata = $item->metadata ?? [];
                $subscriptionId = $metadata['subscription_id'] ?? null;

                if ($subscriptionId) {
                    $subscription = Subscription::query()->find($subscriptionId);
                    if ($subscription) {
                        return route('admin.products.subscription.show', $subscription);
                    }
                }
            }
        }

        // Fallback to billing page
        return route('billing.show', $order);
    }
}
