<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Domain;
use App\Models\DomainRenewal;
use App\Models\Order;
use App\Services\Domain\DomainRegistrationServiceInterface;
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

        foreach ($order->orderItems as $orderItem) {
            // Skip non-renewal items
            if ($orderItem->domain_type !== 'renewal') {
                continue;
            }

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
                    // Calculate new expiry date
                    $newExpiryDate = $oldExpiryDate->addYears($orderItem->years);

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
                        'old_expiry' => $oldExpiryDate->format('Y-m-d'),
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
     * @return array{price: float, currency: string}
     */
    public function getRenewalPrice(Domain $domain, int $years = 1): array
    {
        $domainPrice = $domain->domainPrice;

        if (! $domainPrice) {
            throw new Exception('Pricing information not available for domain: '.$domain->name);
        }

        // Get renewal price (stored in cents)
        $pricePerYear = $domainPrice->renewal_price / 100;
        $totalPrice = $pricePerYear * $years;

        // Determine currency based on domain type
        $currency = $domainPrice->type->value === 'local' ? 'RWF' : 'USD';

        return [
            'price' => $totalPrice,
            'currency' => $currency,
        ];
    }

    /**
     * Validate if a domain can be renewed
     *
     * @param  Domain  $domain  The domain to check
     * @param  int  $userId  The user attempting to renew
     * @return array{can_renew: bool, reason?: string}
     */
    public function canRenewDomain(Domain $domain, int $userId): array
    {
        // Check ownership
        if ($domain->owner_id !== $userId) {
            return [
                'can_renew' => false,
                'reason' => 'You do not own this domain',
            ];
        }

        // Check if domain has expired for too long (grace period)
        if ($domain->expires_at && $domain->expires_at->isPast()) {
            $daysSinceExpiry = now()->diffInDays($domain->expires_at);
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

    /**
     * Get the appropriate domain service based on the domain's registrar
     */
    private function getDomainService(Domain $domain): DomainRegistrationServiceInterface
    {
        // Determine which service to use based on domain registrar
        return match (mb_strtolower($domain->registrar ?? 'epp')) {
            'namecheap' => app(NamecheapDomainService::class),
            'epp', 'local' => app(EppDomainService::class),
            default => app(EppDomainService::class), // Default to EPP
        };
    }
}
