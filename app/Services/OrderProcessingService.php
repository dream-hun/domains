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

                if (isset($attributes['duration_months'])) {
                    $itemMetadata['duration_months'] = (int) $attributes['duration_months'];
                } else {
                    $itemMetadata['duration_months'] = $itemQuantity;
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

            if ($itemType === 'hosting') {
                if (isset($attributes['duration_months'])) {
                    $itemMetadata['duration_months'] = (int) $attributes['duration_months'];
                } else {
                    $itemMetadata['duration_months'] = $itemQuantity;
                }

                if (isset($attributes['billing_cycle'])) {
                    $itemMetadata['billing_cycle'] = $attributes['billing_cycle'];
                }

                if (isset($attributes['hosting_plan_id'])) {
                    $itemMetadata['hosting_plan_id'] = $attributes['hosting_plan_id'];
                }

                if (isset($attributes['hosting_plan_price_id'])) {
                    $itemMetadata['hosting_plan_price_id'] = $attributes['hosting_plan_price_id'];
                }

                if (isset($attributes['linked_domain'])) {
                    $itemMetadata['linked_domain'] = $attributes['linked_domain'];
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

    public function getServiceDetailsRedirectUrl(Order $order): string
    {
        $orderItems = $order->orderItems;

        if ($orderItems->isEmpty()) {
            return route('billing.show', $order);
        }

        foreach ($orderItems as $item) {
            if (in_array($item->domain_type, ['registration', 'renewal'], true) && $item->domain_id) {
                $domain = Domain::query()->find($item->domain_id);
                if ($domain && $domain->uuid) {
                    return route('admin.domain.info', $domain);
                }
            }
        }

        foreach ($orderItems as $item) {
            if (in_array($item->domain_type, ['hosting', 'subscription_renewal'], true)) {
                $metadata = $item->metadata ?? [];
                $subscriptionId = $metadata['subscription_id'] ?? null;

                if (! $subscriptionId && $item->domain_type === 'hosting') {
                    $planId = $metadata['hosting_plan_id'] ?? null;
                    $planPriceId = $metadata['hosting_plan_price_id'] ?? null;

                    if ($planId && $planPriceId) {
                        $subscription = Subscription::query()
                            ->where('user_id', $order->user_id)
                            ->where('hosting_plan_id', $planId)
                            ->where('hosting_plan_price_id', $planPriceId)
                            ->where('created_at', '>=', $order->created_at->subMinutes(5))
                            ->latest()
                            ->first();
                    } else {
                        $subscription = null;
                    }
                } else {
                    $subscription = $subscriptionId ? Subscription::query()->find($subscriptionId) : null;
                }

                if ($subscription) {
                    return route('admin.products.subscription.show', $subscription);
                }
            }
        }

        return route('billing.show', $order);
    }
}
