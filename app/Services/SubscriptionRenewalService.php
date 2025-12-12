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
                // Get the monthly plan price for validation
                $monthlyPlanPrice = HostingPlanPrice::query()
                    ->where('hosting_plan_id', $subscription->hosting_plan_id)
                    ->where('billing_cycle', 'monthly')
                    ->where('status', 'active')
                    ->first();

                if (! $monthlyPlanPrice) {
                    throw new Exception(
                        sprintf('No active monthly pricing found for plan %s', $subscription->hosting_plan_id)
                    );
                }

                // Validate payment amount matches expected amount for the quantity of months
                $expectedMonthlyPrice = $monthlyPlanPrice->getPriceInBaseCurrency('renewal_price');
                $expectedTotalAmount = $expectedMonthlyPrice * $quantityMonths;

                // Use a more lenient tolerance (0.50) to account for currency conversion rounding
                // Currency conversions can introduce small rounding differences (typically 0.01-0.05)
                $tolerance = 0.50;
                $difference = abs($paidAmount - $expectedTotalAmount);

                if ($difference > $tolerance) {
                    Log::error('Payment amount validation failed', [
                        'subscription_id' => $subscription->id,
                        'expected_total' => $expectedTotalAmount,
                        'paid_amount' => $paidAmount,
                        'difference' => $difference,
                        'tolerance' => $tolerance,
                        'monthly_price' => $expectedMonthlyPrice,
                        'quantity_months' => $quantityMonths,
                        'order_item_currency' => $orderItemCurrency,
                    ]);

                    throw new Exception(
                        sprintf('Payment amount mismatch. Expected: %s (monthly price %s × %d months), Paid: %s (difference: %s)', $expectedTotalAmount, $expectedMonthlyPrice, $quantityMonths, $paidAmount, $difference)
                    );
                }

                // Log successful validation if there was a small difference within tolerance
                if ($difference > 0.01) {
                    Log::info('Payment amount validation passed with small rounding difference', [
                        'subscription_id' => $subscription->id,
                        'expected_total' => $expectedTotalAmount,
                        'paid_amount' => $paidAmount,
                        'difference' => $difference,
                    ]);
                }

                $renewalSnapshot = [
                    'id' => $monthlyPlanPrice->id,
                    'regular_price' => $monthlyPlanPrice->regular_price,
                    'renewal_price' => $monthlyPlanPrice->renewal_price,
                    'billing_cycle' => $monthlyPlanPrice->billing_cycle,
                ];

                // Extend subscription by the quantity of months
                $subscription->extendSubscriptionByMonths(
                    $quantityMonths,
                    $paidAmount,
                    renewalSnapshot: $renewalSnapshot
                );

                $results['successful'][] = [
                    'subscription' => $subscription->domain ?? 'Subscription UUID: '.$subscription->uuid,
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
}
