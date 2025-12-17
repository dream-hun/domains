<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Hosting\BillingCycle;
use App\Helpers\CurrencyHelper;
use App\Models\HostingPlanPrice;
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
                    'subscription' => $orderItem->domain_name ?? 'Subscription ID: '.$subscriptionId,
                    'error' => 'Subscription not found',
                ];

                continue;
            }

            $billingCycleValue = $orderItem->metadata['billing_cycle'] ?? $subscription->billing_cycle;
            $billingCycle = $this->resolveBillingCycle($billingCycleValue);
            // Get the total amount paid (price * quantity)
            $paidAmount = (float) $orderItem->total_amount;
            $orderItemCurrency = $orderItem->currency ?? 'USD';
            // Get the quantity (months) from the order item
            $quantityMonths = (int) $orderItem->quantity;

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
                'quantity_months' => $quantityMonths,
                'paid_amount_usd' => $paidAmount,
                'order_item_currency' => $orderItemCurrency,
            ]);

            try {
                // For annual renewals, use extendSubscription with billing cycle
                // For other cycles, use extendSubscriptionByMonths with calculated months
                if ($billingCycle === BillingCycle::Annually) {
                    // Get the annual plan price for validation
                    $annualPlanPrice = HostingPlanPrice::query()
                        ->where('hosting_plan_id', $subscription->hosting_plan_id)
                        ->where('billing_cycle', 'annually')
                        ->where('status', 'active')
                        ->first();

                    if (! $annualPlanPrice) {
                        throw new Exception(
                            sprintf('No active annual pricing found for plan %s', $subscription->hosting_plan_id)
                        );
                    }

                    $renewalSnapshot = [
                        'id' => $annualPlanPrice->id,
                        'regular_price' => $annualPlanPrice->regular_price,
                        'renewal_price' => $annualPlanPrice->renewal_price,
                        'billing_cycle' => $annualPlanPrice->billing_cycle,
                    ];

                    // Extend subscription by 1 year using extendSubscription
                    $subscription->extendSubscription(
                        $billingCycle,
                        $paidAmount,
                        validatePayment: true,
                        isComp: false,
                        renewalSnapshot: $renewalSnapshot
                    );
                } else {
                    // For non-annual cycles, get the plan price for the specific billing cycle
                    $cyclePlanPrice = HostingPlanPrice::query()
                        ->where('hosting_plan_id', $subscription->hosting_plan_id)
                        ->where('billing_cycle', $billingCycleValue)
                        ->where('status', 'active')
                        ->first();

                    if (! $cyclePlanPrice) {
                        throw new Exception(
                            sprintf('No active pricing found for plan %s with billing cycle %s', $subscription->hosting_plan_id, $billingCycleValue)
                        );
                    }

                    // Calculate number of months based on billing cycle
                    $monthsToAdd = $this->billingCycleToMonths($billingCycleValue, $quantityMonths);

                    // Validate payment amount against the cycle plan price
                    $expectedPrice = $cyclePlanPrice->getPriceInBaseCurrency('renewal_price');

                    // Use a more lenient tolerance (0.50) to account for currency conversion rounding
                    $tolerance = 0.50;
                    $difference = abs($paidAmount - $expectedPrice);

                    if ($difference > $tolerance) {
                        Log::error('Payment amount validation failed', [
                            'subscription_id' => $subscription->id,
                            'expected_price' => $expectedPrice,
                            'paid_amount' => $paidAmount,
                            'difference' => $difference,
                            'tolerance' => $tolerance,
                            'billing_cycle' => $billingCycleValue,
                            'order_item_currency' => $orderItemCurrency,
                        ]);

                        throw new Exception(
                            sprintf('Payment amount mismatch. Expected: %s for billing cycle %s, Paid: %s (difference: %s)', $expectedPrice, $billingCycleValue, $paidAmount, $difference)
                        );
                    }

                    // Log successful validation if there was a small difference within tolerance
                    if ($difference > 0.01) {
                        Log::info('Payment amount validation passed with small rounding difference', [
                            'subscription_id' => $subscription->id,
                            'expected_price' => $expectedPrice,
                            'paid_amount' => $paidAmount,
                            'difference' => $difference,
                        ]);
                    }

                    $renewalSnapshot = [
                        'id' => $cyclePlanPrice->id,
                        'regular_price' => $cyclePlanPrice->regular_price,
                        'renewal_price' => $cyclePlanPrice->renewal_price,
                        'billing_cycle' => $cyclePlanPrice->billing_cycle,
                    ];

                    $subscription->extendSubscriptionByMonths(
                        $monthsToAdd,
                        paidAmount: null,
                        renewalSnapshot: $renewalSnapshot
                    );

                    $subscription->refresh();
                    if ($subscription->billing_cycle !== $billingCycleValue) {
                        $subscription->update(['billing_cycle' => $billingCycleValue]);
                    }
                }

                $results['successful'][] = [
                    'subscription' => $subscription->domain ?? 'Subscription UUID: '.$subscription->uuid,
                    'subscription_id' => $subscription->id,
                    'subscription_uuid' => $subscription->uuid,
                    'billing_cycle' => $billingCycleValue,
                    'new_expiry' => $subscription->expires_at->format('Y-m-d'),
                    'message' => 'Subscription renewed successfully',
                ];

                Log::info('Subscription renewed successfully', [
                    'subscription_id' => $subscription->id,
                    'subscription_uuid' => $subscription->uuid,
                    'new_expiry' => $subscription->expires_at->toDateString(),
                ]);

            } catch (Exception $exception) {
                $results['failed'][] = [
                    'subscription' => $subscription->domain ?? 'Subscription UUID: '.$subscription->uuid,
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

    /**
     * Convert billing cycle string to number of months
     */
    private function billingCycleToMonths(string $billingCycle, int $defaultMonths): int
    {

        $billingCycle = mb_strtolower(mb_trim($billingCycle));

        return match ($billingCycle) {
            'monthly' => 1,
            'annually' => 12,

            default => $defaultMonths,
        };
    }
}
