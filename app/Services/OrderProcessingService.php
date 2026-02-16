<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\ProcessDomainRenewalJob;
use App\Jobs\ProcessSubscriptionRenewalJob;
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

            $exchangeRate = 1.0;

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

                $pricingId = $this->resolveHostingPlanPricingId($attributes);
                if ($pricingId !== null) {
                    $itemMetadata['hosting_plan_pricing_id'] = $pricingId;
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

                $pricingId = $this->resolveHostingPlanPricingId($attributes);
                if ($pricingId !== null) {
                    $itemMetadata['hosting_plan_pricing_id'] = $pricingId;
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
            Log::info('Processing ProcessDomainRenewalJob synchronously', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
            dispatch_sync(new ProcessDomainRenewalJob($order));
        }

        if ($hasSubscriptionRenewals) {
            Log::info('Processing ProcessSubscriptionRenewalJob synchronously', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
            dispatch_sync(new ProcessSubscriptionRenewalJob($order));
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
        $order->loadMissing('orderItems');
        $orderItems = $order->orderItems;

        if ($orderItems->isEmpty()) {
            return route('billing.show', $order);
        }

        // Batch-load domains for registration/renewal items
        $domainIds = $orderItems
            ->whereIn('domain_type', ['registration', 'renewal'])
            ->pluck('domain_id')
            ->filter()
            ->unique()
            ->values();

        $domains = $domainIds->isNotEmpty()
            ? Domain::query()->findMany($domainIds)->keyBy('id')
            : collect();

        // Batch-load subscriptions for items that have subscription_id in metadata
        $subscriptionIds = $orderItems
            ->whereIn('domain_type', ['hosting', 'subscription_renewal'])
            ->map(fn (OrderItem $item) => ($item->metadata ?? [])['subscription_id'] ?? null)
            ->filter()
            ->unique()
            ->values();

        $subscriptions = $subscriptionIds->isNotEmpty()
            ? Subscription::query()->findMany($subscriptionIds)->keyBy('id')
            : collect();

        foreach ($orderItems as $item) {
            if (in_array($item->domain_type, ['registration', 'renewal'], true) && $item->domain_id) {
                $domain = $domains->get($item->domain_id);
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
                    $planPriceId = $this->resolveHostingPlanPricingId($metadata);

                    if ($planId && $planPriceId) {
                        $subscription = Subscription::query()
                            ->where('user_id', $order->user_id)
                            ->where('hosting_plan_id', $planId)
                            ->where('hosting_plan_pricing_id', $planPriceId)
                            ->where('created_at', '>=', $order->created_at->subMinutes(5))
                            ->latest()
                            ->first();
                    } else {
                        $subscription = null;
                    }
                } else {
                    $subscription = $subscriptionId ? $subscriptions->get($subscriptionId) : null;
                }

                if ($subscription) {
                    return route('admin.products.subscription.show', $subscription);
                }
            }
        }

        return route('billing.show', $order);
    }

    /**
     * Resolve hosting plan pricing ID from attributes, supporting legacy key for backwards compatibility.
     */
    private function resolveHostingPlanPricingId(array $attributes): ?int
    {
        $id = $attributes['hosting_plan_pricing_id'] ?? $attributes['hosting_plan_price_id'] ?? null;

        return $id !== null ? (int) $id : null;
    }
}
