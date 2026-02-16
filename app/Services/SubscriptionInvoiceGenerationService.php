<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Hosting\BillingCycle;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Subscription;
use Exception;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class SubscriptionInvoiceGenerationService
{
    public function __construct() {}

    /**
     * Generate renewal invoices for subscriptions due for renewal
     *
     * @return array{generated: int, failed: array<int, array{subscription_id: int, error: string}>}
     */
    public function generateRenewalInvoices(int $daysBeforeRenewal = 7): array
    {
        $now = Date::now();
        $endDate = $now->copy()->addDays($daysBeforeRenewal);

        $subscriptions = Subscription::query()
            ->where('status', 'active')
            ->where('auto_renew', true)
            ->whereNotNull('next_renewal_at')
            ->whereBetween('next_renewal_at', [$now, $endDate])
            ->with(['user', 'plan', 'planPrice'])
            ->get();

        $generated = 0;
        $failed = [];

        foreach ($subscriptions as $subscription) {
            if (! $this->shouldGenerateInvoice($subscription, $daysBeforeRenewal)) {
                continue;
            }

            try {
                $this->createRenewalInvoiceOrder($subscription);
                $generated++;

                Log::info('Renewal invoice generated for subscription', [
                    'subscription_id' => $subscription->id,
                    'subscription_uuid' => $subscription->uuid,
                    'next_renewal_at' => $subscription->next_renewal_at?->toDateString(),
                ]);
            } catch (Throwable $e) {
                $failed[] = [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ];

                Log::error('Failed to generate renewal invoice for subscription', [
                    'subscription_id' => $subscription->id,
                    'subscription_uuid' => $subscription->uuid,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return [
            'generated' => $generated,
            'failed' => $failed,
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
        $subscription->loadMissing(['user', 'plan', 'planPrice']);

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

        // Create order item
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
