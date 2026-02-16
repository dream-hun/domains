<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Hosting\BillingCycle;
use App\Models\HostingPlan;
use App\Models\HostingPlanPrice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Subscription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class HostingSubscriptionService
{
    public function createSubscriptionsFromOrder(Order $order): void
    {
        $hostingItems = $order->orderItems()
            ->where('domain_type', 'hosting')
            ->get();

        // Batch-load hosting plans and prices
        $planIds = $hostingItems->map(fn (OrderItem $item) => (int) (($item->metadata ?? [])['hosting_plan_id'] ?? 0))->filter()->unique();
        $planPriceIds = $hostingItems->map(fn (OrderItem $item) => (int) (($item->metadata ?? [])['hosting_plan_pricing_id'] ?? ($item->metadata ?? [])['hosting_plan_price_id'] ?? 0))->filter()->unique();

        $plans = $planIds->isNotEmpty() ? HostingPlan::query()->findMany($planIds)->keyBy('id') : collect();
        $planPrices = $planPriceIds->isNotEmpty() ? HostingPlanPrice::query()->with('currency')->findMany($planPriceIds)->keyBy('id') : collect();

        foreach ($hostingItems as $orderItem) {
            $this->createSubscriptionFromItem($order, $orderItem, $plans, $planPrices);
        }
    }

    /**
     * @param  Collection<int, HostingPlan>  $plans
     * @param  Collection<int, HostingPlanPrice>  $planPrices
     */
    private function createSubscriptionFromItem(Order $order, OrderItem $orderItem, Collection $plans, Collection $planPrices): void
    {
        $metadata = $orderItem->metadata ?? [];

        $planId = (int) ($metadata['hosting_plan_id'] ?? 0);
        $planPriceId = (int) ($metadata['hosting_plan_pricing_id'] ?? $metadata['hosting_plan_price_id'] ?? 0);
        $billingCycle = (string) ($metadata['billing_cycle'] ?? '');
        $linkedDomain = array_key_exists('linked_domain', $metadata)
            ? $metadata['linked_domain']
            : ($orderItem->domain_name ?? null);

        if ($planId === 0 || $planPriceId === 0) {
            Log::warning('Skipping hosting subscription creation due to missing plan metadata', [
                'order_item_id' => $orderItem->id,
            ]);

            return;
        }

        /** @var HostingPlan|null $plan */
        $plan = $plans->get($planId);
        /** @var HostingPlanPrice|null $planPrice */
        $planPrice = $planPrices->get($planPriceId);

        if (! $plan || ! $planPrice) {
            Log::warning('Skipping hosting subscription creation due to missing plan records', [
                'order_item_id' => $orderItem->id,
                'plan_id' => $planId,
                'plan_price_id' => $planPriceId,
            ]);

            return;
        }

        if ($linkedDomain !== null) {
            $alreadyExists = Subscription::query()
                ->where('user_id', $order->user_id)
                ->where('domain', $linkedDomain)
                ->where('hosting_plan_pricing_id', $planPrice->id)
                ->exists();

            if ($alreadyExists) {
                return;
            }
        }

        $cycle = $this->resolveBillingCycle($billingCycle ?: $planPrice->billing_cycle);
        $now = Date::now();

        $durationMonths = (int) ($metadata['duration_months'] ?? $orderItem->quantity ?? 1);
        $durationMonths = min($durationMonths, 48);

        Log::info('Creating subscription from order item', [
            'order_item_id' => $orderItem->id,
            'order_id' => $order->id,
            'plan_id' => $planId,
            'quantity' => $orderItem->quantity,
            'duration_months_from_metadata' => $metadata['duration_months'] ?? null,
            'duration_months_used' => $durationMonths,
            'billing_cycle' => $billingCycle,
        ]);

        $expiresAt = $now->copy()->addMonths($durationMonths);

        Subscription::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $order->user_id,
            'hosting_plan_id' => $plan->id,
            'hosting_plan_pricing_id' => $planPrice->id,
            'product_snapshot' => [
                'plan' => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                ],
                'price' => [
                    'id' => $planPrice->id,
                    'regular_price' => $planPrice->regular_price,
                    'renewal_price' => $planPrice->renewal_price,
                    'billing_cycle' => $planPrice->billing_cycle,
                ],
                'order_item_id' => $orderItem->id,
                'duration_months' => $durationMonths,
            ],
            'billing_cycle' => $cycle->value,
            'domain' => $linkedDomain,
            'status' => 'active',
            'starts_at' => $now,
            'expires_at' => $expiresAt,
            'next_renewal_at' => $expiresAt,
        ]);
    }

    private function resolveBillingCycle(string $cycle): BillingCycle
    {
        foreach (BillingCycle::cases() as $case) {
            if ($case->value === $cycle) {
                return $case;
            }
        }

        return BillingCycle::Monthly;
    }
}
