<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DomainType;
use App\Helpers\CurrencyHelper;
use App\Models\Domain;
use App\Models\DomainPrice;
use App\Models\DomainRenewal;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Domain\DomainServiceInterface;
use App\Services\Domain\EppDomainService;
use App\Services\Domain\NamecheapDomainService;
use Exception;
use Illuminate\Support\Facades\Log;

final readonly class RenewalService
{
    /**
     * Process domain renewals for an order
     *
     * @param  Order  $order  The order containing renewal items
     * @return array{successful: array, failed: array}
     */
    public function processDomainRenewals(Order $order): array
    {
        $results = [
            'successful' => [],
            'failed' => [],
        ];

        /** @var OrderItem $orderItem */
        foreach ($order->orderItems as $orderItem) {
            // Skip non-renewal items
            if ($orderItem->domain_type !== 'renewal') {
                continue;
            }

            $oldExpiryDate = null;

            try {
                $domain = Domain::query()->find($orderItem->domain_id);

                if (! $domain) {
                    throw new Exception('Domain not found: '.$orderItem->domain_name);
                }

                // Verify ownership
                if ($domain->owner_id !== $order->user_id) {
                    throw new Exception('User does not own domain: '.$orderItem->domain_name);
                }

                $oldExpiryDate = $domain->expires_at;

                Log::info('Processing domain renewal', [
                    'domain' => $domain->name,
                    'years' => $orderItem->years,
                    'old_expiry' => $oldExpiryDate?->format('Y-m-d'),
                    'order_id' => $order->id,
                    'registrar' => $domain->registrar,
                ]);

                // Get the appropriate domain service for this domain
                $domainService = $this->getDomainService($domain);

                // Call domain service to renew at the registry
                $result = $domainService->renewDomainRegistration(
                    $domain->name,
                    $orderItem->years
                );

                if ($result['success']) {
                    // Calculate new expiry date (clone to avoid mutating the original)
                    $newExpiryDate = $oldExpiryDate ? (clone $oldExpiryDate)->addYears($orderItem->years) : now()->addYears($orderItem->years);

                    // Update domain expiry date
                    $domain->update([
                        'expires_at' => $newExpiryDate,
                        'last_renewed_at' => now(),
                    ]);

                    // Create renewal record
                    DomainRenewal::query()->create([
                        'domain_id' => $domain->id,
                        'order_id' => $order->id,
                        'years' => $orderItem->years,
                        'amount' => $orderItem->total_amount,
                        'currency' => $orderItem->currency,
                        'old_expiry_date' => $oldExpiryDate,
                        'new_expiry_date' => $newExpiryDate,
                        'status' => 'completed',
                    ]);

                    $results['successful'][] = [
                        'domain' => $domain->name,
                        'years' => $orderItem->years,
                        'old_expiry' => $oldExpiryDate?->format('Y-m-d'),
                        'new_expiry' => $newExpiryDate->format('Y-m-d'),
                        'message' => 'Domain renewed successfully',
                    ];

                    Log::info('Domain renewed successfully', [
                        'domain' => $domain->name,
                        'years' => $orderItem->years,
                        'new_expiry' => $newExpiryDate->format('Y-m-d'),
                        'order_id' => $order->id,
                    ]);
                } else {
                    throw new Exception($result['message'] ?? 'Renewal failed at registry');
                }

            } catch (Exception $e) {
                // Create failed renewal record
                DomainRenewal::query()->create([
                    'domain_id' => $orderItem->domain_id ?? null,
                    'order_id' => $order->id,
                    'years' => $orderItem->years,
                    'amount' => $orderItem->total_amount,
                    'currency' => $orderItem->currency,
                    'old_expiry_date' => $oldExpiryDate ?? now(),
                    'new_expiry_date' => null,
                    'status' => 'failed',
                ]);

                $results['failed'][] = [
                    'domain' => $orderItem->domain_name,
                    'error' => $e->getMessage(),
                ];

                Log::error('Domain renewal failed', [
                    'domain' => $orderItem->domain_name,
                    'years' => $orderItem->years,
                    'error' => $e->getMessage(),
                    'order_id' => $order->id,
                ]);
            }
        }

        return $results;
    }

    /**
     * Get renewal price for a domain
     *
     * @param  Domain  $domain  The domain to renew
     * @param  int  $years  Number of years to renew
     * @return array{
     *     unit_price: float,
     *     price: float,
     *     total_price: float,
     *     currency: string
     * }
     *
     * @throws Exception
     */
    public function getRenewalPrice(Domain $domain, int $years = 1): array
    {
        $domainPrice = $this->resolveDomainPrice($domain);

        if (! $domainPrice instanceof DomainPrice) {
            throw new Exception('Pricing information not available for domain: '.$domain->name);
        }

        $domain->setRelation('domainPrice', $domainPrice);

        $currency = $domainPrice->type === DomainType::Local ? 'RWF' : 'USD';

        $unitPrice = $domainPrice->getPriceInCurrency('renewal_price', $currency);

        $unitPrice = $currency === 'RWF' ? round($unitPrice) : round($unitPrice, 2);

        $totalPrice = $unitPrice * $years;
        $totalPrice = $currency === 'RWF' ? round($totalPrice) : round($totalPrice, 2);

        return [
            'unit_price' => $unitPrice,
            'price' => $unitPrice,
            'total_price' => $totalPrice,
            'currency' => $currency,
        ];
    }

    /**
     * Validate if a domain can be renewed
     *
     * @param  Domain  $domain  The domain to check
     * @param  User  $user  The user attempting to renew
     * @return array{can_renew: bool, reason?: string}
     */
    public function canRenewDomain(Domain $domain, User $user): array
    {
        // Check ownership
        if ($domain->owner_id !== $user->id && ! $user->isAdmin()) {
            return [
                'can_renew' => false,
                'reason' => 'You do not own this domain',
            ];
        }

        // Check if domain has expired for too long (grace period)
        $expiresAt = $domain->expires_at;

        if ($expiresAt && $expiresAt->isPast()) {
            $daysSinceExpiry = now()->diffInDays($expiresAt);
            if ($daysSinceExpiry > 30) { // 30 day grace period
                return [
                    'can_renew' => false,
                    'reason' => 'Domain has been expired for more than 30 days. Please contact support.',
                ];
            }
        }

        // Check if domain is in a transferring state
        if (in_array($domain->status, ['transfer_pending', 'transfer_in_progress'])) {
            return [
                'can_renew' => false,
                'reason' => 'Domain is currently being transferred',
            ];
        }

        return ['can_renew' => true];
    }

    public function resolveDomainPrice(Domain $domain): ?DomainPrice
    {
        if ($domain->relationLoaded('domainPrice') || $domain->domainPrice) {
            $domainPrice = $domain->domainPrice;

            return $domainPrice instanceof DomainPrice ? $domainPrice : null;
        }

        $tld = $this->extractTld($domain->name);

        if (! $tld) {
            return null;
        }

        return DomainPrice::query()->where('tld', '.'.$tld)->first();
    }

    /**
     * Validate that a renewal meets Stripe's minimum charge requirement.
     *
     * @return array{valid: bool, message?: string, min_years?: int, currency?: string, required_total?: float}
     */
    public function validateStripeMinimumAmountForRenewal(DomainPrice $domainPrice, int $years): array
    {
        $pricePerYearUsd = $domainPrice->getPriceInCurrency('renewal_price');

        if ($pricePerYearUsd <= 0) {
            return [
                'valid' => false,
                'message' => 'Renewal pricing is not configured correctly for this domain. Please contact support.',
            ];
        }

        $totalUsd = $pricePerYearUsd * $years;
        $minUsd = 0.50;

        if ($totalUsd >= $minUsd) {
            return ['valid' => true];
        }

        $minYears = (int) max(1, ceil($minUsd / max($pricePerYearUsd, 0.0001)));

        $displayCurrency = $domainPrice->type === DomainType::Local ? 'RWF' : 'USD';

        try {
            $requiredTotal = $displayCurrency === 'USD'
                ? $pricePerYearUsd * $minYears
                : CurrencyHelper::convert($pricePerYearUsd * $minYears, 'USD', $displayCurrency);
        } catch (Exception) {
            $requiredTotal = $pricePerYearUsd * $minYears;
        }

        $decimals = $displayCurrency === 'USD' ? 2 : 0;
        $requiredTotal = round($requiredTotal, $decimals);

        return [
            'valid' => false,
            'message' => sprintf(
                'Stripe requires a minimum charge of $0.50 USD. Please renew for at least %d year(s) (approximately %s %s).',
                $minYears,
                number_format($requiredTotal, $decimals),
                $displayCurrency
            ),
            'min_years' => $minYears,
            'currency' => $displayCurrency,
            'required_total' => $requiredTotal,
        ];
    }

    /**
     * Get the appropriate domain service based on the domain's registrar
     */
    private function getDomainService(Domain $domain): DomainServiceInterface
    {
        return match (mb_strtolower($domain->registrar ?? 'epp')) {
            'namecheap' => app(NamecheapDomainService::class),
            default => app(EppDomainService::class),
        };
    }

    private function extractTld(string $domain): ?string
    {
        $parts = explode('.', $domain);

        if (count($parts) < 2) {
            return null;
        }

        return end($parts) ?: null;
    }
}
