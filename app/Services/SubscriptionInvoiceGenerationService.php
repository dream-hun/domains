<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Hosting\BillingCycle;
use App\Jobs\GenerateSubscriptionRenewalInvoiceJob;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Subscription;
use Exception;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class SubscriptionInvoiceGenerationService
{
    /**
     * Generate renewal invoices for subscriptions due for renewal
     *
     * @return array{dispatched: int, skipped: int}
     */
    public function generateRenewalInvoices(int $daysBeforeRenewal = 7): array
    {
        $now = Date::now();
        $endDate = $now->copy()->addDays($daysBeforeRenewal);

        $subscriptions = Subscription::query()
            ->where('status', 'active')
            ->whereNotNull('next_renewal_at')
            ->whereBetween('next_renewal_at', [$now, $endDate])
            ->with(['user', 'plan', 'planPrice.currency'])
            ->get();

        $dispatched = 0;
        $skipped = 0;

        foreach ($subscriptions as $subscription) {
            if (! $this->shouldGenerateInvoice($subscription, $daysBeforeRenewal)) {
                $skipped++;

                continue;
            }

            GenerateSubscriptionRenewalInvoiceJob::dispatch($subscription);
            $dispatched++;

            Log::info('Dispatched subscription renewal invoice job', [
                'subscription_id' => $subscription->id,
                'subscription_uuid' => $subscription->uuid,
                'next_renewal_at' => $subscription->next_renewal_at?->toDateString(),
            ]);
        }

        return [
            'dispatched' => $dispatched,
            'skipped' => $skipped,
        ];
    }

    /**
     * Check if invoice should be generated for subscription
     */
    public function shouldGenerateInvoice(Subscription $subscription, int $daysBeforeRenewal = 7): bool
    {
        return $subscription->shouldGenerateInvoice($daysBeforeRenewal);
    }

    /**
     * Create a renewal invoice order for subscription
     *
     * @throws Throwable
     */
    public function createRenewalInvoiceOrder(Subscription $subscription): Order
    {
        $subscription->loadMissing(['user', 'plan', 'planPrice.currency']);

        $user = $subscription->user;

        throw_unless($user, Exception::class, 'Subscription has no associated user');

        $renewalCurrency = $subscription->getRenewalCurrency();
        $renewalPrice = $subscription->getRenewalPriceInCurrency($renewalCurrency);

        $exchangeRate = 1.0;

        $billingCycle = BillingCycle::from($subscription->billing_cycle);

        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_number' => Order::generateOrderNumber(),
            'type' => 'subscription_renewal',
            'status' => 'pending',
            'payment_method' => 'stripe',
            'payment_status' => 'pending',
            'total_amount' => $renewalPrice,
            'subtotal' => $renewalPrice,
            'tax' => 0,
            'currency' => $renewalCurrency,
            'billing_email' => $user->email,
            'billing_name' => $user->name,
            'billing_address' => [],
            'items' => [
                [
                    'id' => $subscription->id,
                    'name' => ($subscription->domain ?: 'Hosting').' - '.$subscription->plan->name.' (Renewal Invoice)',
                    'price' => $renewalPrice,
                    'quantity' => 1,
                    'attributes' => [
                        'type' => 'subscription_renewal',
                        'subscription_id' => $subscription->id,
                        'subscription_uuid' => $subscription->uuid,
                        'billing_cycle' => $billingCycle->value,
                        'hosting_plan_id' => $subscription->hosting_plan_id,
                        'hosting_plan_pricing_id' => $subscription->hosting_plan_pricing_id,
                        'domain' => $subscription->domain,
                        'currency' => $renewalCurrency,
                        'is_custom_price' => $subscription->is_custom_price,
                        'auto_renewal' => true,
                    ],
                ],
            ],
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'domain_name' => $subscription->domain ?: 'Hosting',
            'domain_type' => 'subscription_renewal',
            'price' => $renewalPrice,
            'currency' => $renewalCurrency,
            'exchange_rate' => $exchangeRate,
            'quantity' => 1,
            'years' => 1,
            'total_amount' => $renewalPrice,
            'metadata' => [
                'subscription_id' => $subscription->id,
                'subscription_uuid' => $subscription->uuid,
                'billing_cycle' => $billingCycle->value,
                'hosting_plan_id' => $subscription->hosting_plan_id,
                'hosting_plan_pricing_id' => $subscription->hosting_plan_pricing_id,
                'is_custom_price' => $subscription->is_custom_price,
                'custom_price' => $subscription->custom_price,
                'custom_price_currency' => $subscription->custom_price_currency,
                'renewal_currency' => $renewalCurrency,
                'exchange_rate' => $exchangeRate,
                'auto_renewal' => true,
            ],
        ]);

        $subscription->update([
            'last_invoice_generated_at' => Date::now(),
            'next_invoice_due_at' => $subscription->next_renewal_at,
        ]);

        return $order->fresh(['orderItems']);
    }
}
