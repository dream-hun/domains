<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Hosting\BillingCycle;
use App\Models\HostingPlan;
use App\Models\HostingPlanPrice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Subscription;
use Illuminate\Support\Carbon;
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

        foreach ($hostingItems as $orderItem) {
            $this->createSubscriptionFromItem($order, $orderItem);
        }
    }

    private function createSubscriptionFromItem(Order $order, OrderItem $orderItem): void
    {
        $metadata = $orderItem->metadata ?? [];

        $planId = (int) ($metadata['hosting_plan_id'] ?? 0);
        $planPriceId = (int) ($metadata['hosting_plan_price_id'] ?? 0);
        $billingCycle = (string) ($metadata['billing_cycle'] ?? '');
        // Check if linked_domain key exists in metadata - if it does, use that value (even if null)
        // Otherwise fall back to domain_name from order item
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
        $plan = HostingPlan::query()->find($planId);
        /** @var HostingPlanPrice|null $planPrice */
        $planPrice = HostingPlanPrice::query()->find($planPriceId);

        if (! $plan || ! $planPrice) {
            Log::warning('Skipping hosting subscription creation due to missing plan records', [
                'order_item_id' => $orderItem->id,
                'plan_id' => $planId,
                'plan_price_id' => $planPriceId,
            ]);

            return;
        }

        // Prevent duplicates if we already created a subscription for this user/domain combo
        // For VPS with no domain, we skip duplicate checking since each VPS is independent
        if ($linkedDomain !== null) {
            $alreadyExists = Subscription::query()
                ->where('user_id', $order->user_id)
                ->where('domain', $linkedDomain)
                ->where('hosting_plan_price_id', $planPrice->id)
                ->exists();

            if ($alreadyExists) {
                return;
            }
        }

        $cycle = $this->resolveBillingCycle($billingCycle ?: $planPrice->billing_cycle);
        $now = Date::now();
        $expiresAt = $this->calculateExpiry($now, $cycle);

        Subscription::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $order->user_id,
            'hosting_plan_id' => $plan->id,
            'hosting_plan_price_id' => $planPrice->id,
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

    private function calculateExpiry(Carbon $start, BillingCycle $cycle): Carbon
    {
        return match ($cycle) {
            BillingCycle::Monthly => $start->copy()->addMonth(),
            BillingCycle::Annually => $start->copy()->addYear(),
        };
    }
}
