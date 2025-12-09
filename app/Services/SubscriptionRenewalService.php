<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Hosting\BillingCycle;
use App\Helpers\CurrencyHelper;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Subscription;
use Exception;
use Illuminate\Support\Facades\Log;

final readonly class SubscriptionRenewalService
{
    /**
     * Process subscription renewals for an order
     *
     * @param  Order  $order  The order containing subscription renewal items
     * @return array{successful: array, failed: array}
     */
    public function processSubscriptionRenewals(Order $order): array
    {
        $results = [
            'successful' => [],
            'failed' => [],
        ];

        /** @var OrderItem $orderItem */
        foreach ($order->orderItems as $orderItem) {
            if ($orderItem->domain_type !== 'subscription_renewal') {
                continue;
            }

            $subscriptionId = $orderItem->metadata['subscription_id'] ?? null;

            if (! $subscriptionId) {
                Log::error('Subscription ID not found in order item metadata', [
                    'order_item_id' => $orderItem->id,
                    'order_id' => $order->id,
                ]);
                $results['failed'][] = [
                    'subscription' => $orderItem->domain_name ?? 'Unknown',
                    'error' => 'Subscription ID not found in order item metadata',
                ];

                continue;
            }

            $subscription = Subscription::query()->find($subscriptionId);

            if (! $subscription) {
                Log::error('Subscription not found for renewal', [
                    'subscription_id' => $subscriptionId,
                    'order_id' => $order->id,
                ]);
                $results['failed'][] = [
                    'subscription' => $orderItem->domain_name ?? "Subscription ID: {$subscriptionId}",
                    'error' => 'Subscription not found',
                ];

                continue;
            }

            $billingCycleValue = $orderItem->metadata['billing_cycle'] ?? $subscription->billing_cycle;
            $billingCycle = $this->resolveBillingCycle($billingCycleValue);
            $paidAmount = (float) $orderItem->price;
            $orderItemCurrency = $orderItem->currency ?? 'USD';

            if ($orderItemCurrency !== 'USD') {
                $paidAmount = CurrencyHelper::convert(
                    $paidAmount,
                    $orderItemCurrency,
                    'USD'
                );
            }

            Log::info('Processing renewal for subscription', [
                'subscription_id' => $subscription->id,
                'subscription_uuid' => $subscription->uuid,
                'domain' => $subscription->domain,
                'stored_billing_cycle' => $subscription->billing_cycle,
                'order_billing_cycle' => $billingCycleValue,
                'paid_amount_usd' => $paidAmount,
                'order_item_currency' => $orderItemCurrency,
            ]);

            try {
                $planPrice = \App\Models\HostingPlanPrice::query()
                    ->where('hosting_plan_id', $subscription->hosting_plan_id)
                    ->where('billing_cycle', $billingCycle->value)
                    ->where('status', 'active')
                    ->first();

                if (! $planPrice) {
                    throw new Exception(
                        "No active pricing found for plan {$subscription->hosting_plan_id} with billing cycle {$billingCycle->value}"
                    );
                }

                $renewalSnapshot = [
                    'id' => $planPrice->id,
                    'regular_price' => $planPrice->regular_price,
                    'renewal_price' => $planPrice->renewal_price,
                    'billing_cycle' => $planPrice->billing_cycle,
                ];

                $subscription->extendSubscription(
                    $billingCycle,
                    $paidAmount,
                    validatePayment: true,
                    isComp: false,
                    renewalSnapshot: $renewalSnapshot
                );

                $results['successful'][] = [
                    'subscription' => $subscription->domain ?? "Subscription UUID: {$subscription->uuid}",
                    'subscription_id' => $subscription->id,
                    'subscription_uuid' => $subscription->uuid,
                    'billing_cycle' => $billingCycle->value,
                    'new_expiry' => $subscription->expires_at?->format('Y-m-d'),
                    'message' => 'Subscription renewed successfully',
                ];

                Log::info('Subscription renewed successfully', [
                    'subscription_id' => $subscription->id,
                    'subscription_uuid' => $subscription->uuid,
                    'new_expiry' => $subscription->expires_at->toDateString(),
                ]);

            } catch (Exception $exception) {
                $results['failed'][] = [
                    'subscription' => $subscription->domain ?? "Subscription UUID: {$subscription->uuid}",
                    'subscription_id' => $subscription->id,
                    'subscription_uuid' => $subscription->uuid,
                    'error' => $exception->getMessage(),
                ];

                Log::error('Subscription renewal failed', [
                    'subscription_id' => $subscription->id,
                    'subscription_uuid' => $subscription->uuid,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $results;
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
