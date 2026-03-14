<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Domain;
use App\Models\Order;
use App\Models\OrderItem;
use Exception;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class DomainInvoiceGenerationService
{
    /**
     * Generate renewal invoices for domains due for renewal.
     *
     * @return array{generated: int, failed: array<int, array{domain_id: int, error: string}>}
     */
    public function generateRenewalInvoices(int $daysBeforeRenewal = 7): array
    {
        $now = Date::now();
        $endDate = $now->copy()->addDays($daysBeforeRenewal);

        $domains = Domain::query()
            ->withoutGlobalScopes()
            ->where('status', 'active')
            ->where('auto_renew', true)
            ->whereBetween('expires_at', [$now, $endDate])
            ->with(['owner', 'tldPricing.currency'])
            ->get();

        $generated = 0;
        $failed = [];

        foreach ($domains as $domain) {
            if ($this->hasPendingRenewalOrder($domain)) {
                continue;
            }

            try {
                $this->createRenewalInvoiceOrder($domain);
                $generated++;

                Log::info('Renewal invoice generated for domain', [
                    'domain_id' => $domain->id,
                    'domain_name' => $domain->name,
                    'expires_at' => $domain->expires_at?->toDateString(),
                ]);
            } catch (Throwable $e) {
                $failed[] = [
                    'domain_id' => $domain->id,
                    'error' => $e->getMessage(),
                ];

                Log::error('Failed to generate renewal invoice for domain', [
                    'domain_id' => $domain->id,
                    'domain_name' => $domain->name,
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
     * Check if a pending renewal order already exists for this domain.
     */
    public function hasPendingRenewalOrder(Domain $domain): bool
    {
        return OrderItem::query()
            ->where('domain_name', $domain->name)
            ->where('domain_type', 'renewal')
            ->whereHas('order', fn ($query) => $query->where('status', 'pending'))
            ->exists();
    }

    /**
     * Create a renewal invoice order for a domain.
     *
     * @throws Throwable
     */
    public function createRenewalInvoiceOrder(Domain $domain): Order
    {
        $domain->loadMissing(['owner', 'tldPricing.currency']);

        $user = $domain->owner;

        throw_unless($user, Exception::class, 'Domain has no associated owner');

        $renewalPrice = $domain->getRenewalPrice();
        $renewalCurrency = $domain->getRenewalCurrency();
        $years = $domain->years ?: 1;

        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_number' => Order::generateOrderNumber(),
            'type' => 'renewal',
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
                    'id' => 'renewal-'.$domain->id,
                    'name' => $domain->name.' - Domain Renewal',
                    'price' => $renewalPrice,
                    'quantity' => $years,
                    'attributes' => [
                        'type' => 'renewal',
                        'domain_id' => $domain->id,
                        'years' => $years,
                        'currency' => $renewalCurrency,
                        'is_custom_price' => $domain->is_custom_price,
                        'auto_renewal' => true,
                    ],
                ],
            ],
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'domain_id' => $domain->id,
            'domain_name' => $domain->name,
            'domain_type' => 'renewal',
            'price' => $renewalPrice,
            'currency' => $renewalCurrency,
            'exchange_rate' => 1.0,
            'quantity' => $years,
            'years' => $years,
            'total_amount' => $renewalPrice,
            'metadata' => [
                'domain_id' => $domain->id,
                'is_custom_price' => $domain->is_custom_price,
                'custom_price' => $domain->custom_price,
                'custom_price_currency' => $domain->custom_price_currency,
                'renewal_currency' => $renewalCurrency,
                'auto_renewal' => true,
            ],
        ]);

        return $order->fresh(['orderItems']);
    }
}
