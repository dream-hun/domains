<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Domain;
use App\Models\DomainRenewal;
use App\Models\Order;
use App\Services\Domain\DomainRouter;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class RenewDomainAction
{
    public function __construct(
        private DomainRouter $domainRouter,
    ) {}

    /**
     * Renew a domain using the appropriate service based on TLD
     */
    public function handle(Domain $domain, int $years, Order $order): array
    {
        try {
            $domainService = $this->domainRouter->resolveForDomain($domain->name);
            $serviceName = $this->domainRouter->serviceName($domain->name);

            Log::info(sprintf('Starting domain renewal with %s service', $serviceName), [
                'domain' => $domain->name,
                'domain_id' => $domain->id,
                'years' => $years,
                'order_id' => $order->id,
                'service' => $serviceName,
            ]);

            $result = $domainService->renewDomainRegistration($domain->name, $years);

            if ($result['success']) {
                [$oldExpiryDate, $newExpiryDate, $renewalAmount] = DB::transaction(function () use ($domain, $years, $order): array {
                    $fresh = Domain::query()->lockForUpdate()->findOrFail($domain->id);

                    $oldExpiry = $fresh->expires_at ?? now();
                    $newExpiry = $oldExpiry->copy()->addYears($years);

                    $renewalAmount = 0;
                    foreach ($order->items as $item) {
                        $itemDomainId = $item['attributes']['domain_id'] ?? $item['id'];

                        if (is_string($itemDomainId) && str_starts_with($itemDomainId, 'renewal-')) {
                            $itemDomainId = (int) str_replace('renewal-', '', $itemDomainId);
                        }

                        if ((int) $itemDomainId === $fresh->id && ($item['attributes']['type'] ?? null) === 'renewal') {
                            $renewalAmount = $item['price'] * $item['quantity'];
                            break;
                        }
                    }

                    $fresh->update([
                        'expires_at' => $newExpiry,
                        'last_renewed_at' => now(),
                    ]);

                    DomainRenewal::query()->create([
                        'domain_id' => $fresh->id,
                        'order_id' => $order->id,
                        'years' => $years,
                        'amount' => $renewalAmount,
                        'currency' => $order->currency,
                        'old_expiry_date' => $oldExpiry->toDateString(),
                        'new_expiry_date' => $newExpiry->toDateString(),
                        'status' => 'completed',
                    ]);

                    $domain->expires_at = $newExpiry;

                    return [$oldExpiry, $newExpiry, $renewalAmount];
                });

                Log::info('Domain renewed successfully with '.$serviceName, [
                    'domain' => $domain->name,
                    'domain_id' => $domain->id,
                    'old_expiry' => $oldExpiryDate->toDateString(),
                    'new_expiry' => $newExpiryDate->toDateString(),
                    'service' => $serviceName,
                ]);

                return [
                    'success' => true,
                    'domain' => $domain->name,
                    'domain_id' => $domain->id,
                    'old_expiry' => $oldExpiryDate->toDateString(),
                    'new_expiry' => $newExpiryDate->toDateString(),
                    'service' => $serviceName,
                    'message' => sprintf('Domain %s has been successfully renewed for %d year(s) using %s!', $domain->name, $years, $serviceName),
                ];
            }

            $errorMessage = $result['message'] ?? 'Domain renewal failed';

            Log::error('Domain renewal failed with '.$serviceName, [
                'domain' => $domain->name,
                'domain_id' => $domain->id,
                'years' => $years,
                'service' => $serviceName,
                'error' => $errorMessage,
            ]);

            // Create a failed renewal record
            DomainRenewal::query()->create([
                'domain_id' => $domain->id,
                'order_id' => $order->id,
                'years' => $years,
                'amount' => 0,
                'currency' => $order->currency,
                'old_expiry_date' => $domain->expires_at->toDateString(),
                'new_expiry_date' => $domain->expires_at->toDateString(),
                'status' => 'failed',
            ]);

            return [
                'success' => false,
                'message' => $errorMessage,
            ];

        } catch (Exception $exception) {
            Log::error('Domain renewal exception', [
                'domain' => $domain->name,
                'domain_id' => $domain->id,
                'years' => $years,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'An unexpected error occurred during domain renewal: '.$exception->getMessage(),
            ];
        }
    }
}
