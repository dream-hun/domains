<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Domain;
use App\Models\DomainRenewal;
use App\Models\Order;
use App\Services\Domain\DomainRegistrationServiceInterface;
use Exception;
use Illuminate\Support\Facades\Log;

final readonly class RenewDomainAction
{
    private DomainRegistrationServiceInterface $eppDomainService;

    private DomainRegistrationServiceInterface $namecheapDomainService;

    public function __construct()
    {
        $this->eppDomainService = app('epp_domain_service');
        $this->namecheapDomainService = app('namecheap_domain_service');
    }

    /**
     * Renew a domain using the appropriate service based on TLD
     */
    public function handle(Domain $domain, int $years, Order $order): array
    {
        try {
            $domainService = $this->getDomainService($domain);
            $serviceName = $domainService === $this->eppDomainService ? 'EPP' : 'Namecheap';

            Log::info("Starting domain renewal with $serviceName service", [
                'domain' => $domain->name,
                'domain_id' => $domain->id,
                'years' => $years,
                'order_id' => $order->id,
                'service' => $serviceName,
            ]);

            $result = $domainService->renewDomainRegistration($domain->name, $years);

            if ($result['success']) {
                $oldExpiryDate = $domain->expires_at;
                $newExpiryDate = $domain->expires_at->addYears($years);

                // Update domain
                $domain->update([
                    'expires_at' => $newExpiryDate,
                    'last_renewed_at' => now(),
                ]);

                // Calculate the renewal amount from the order
                $renewalAmount = 0;
                foreach ($order->items as $item) {
                    if ($item['id'] === $domain->id && $item['attributes']['type'] === 'renewal') {
                        $renewalAmount = $item['price'] * $item['quantity'];
                        break;
                    }
                }

                // Create renewal record
                DomainRenewal::create([
                    'domain_id' => $domain->id,
                    'order_id' => $order->id,
                    'years' => $years,
                    'amount' => $renewalAmount,
                    'currency' => $order->currency,
                    'old_expiry_date' => $oldExpiryDate->toDateString(),
                    'new_expiry_date' => $newExpiryDate->toDateString(),
                    'status' => 'completed',
                ]);

                Log::info("Domain renewed successfully with $serviceName", [
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
                    'message' => "Domain {$domain->name} has been successfully renewed for {$years} year(s) using {$serviceName}!",
                ];
            }

            $errorMessage = $result['message'] ?? 'Domain renewal failed';

            Log::error("Domain renewal failed with $serviceName", [
                'domain' => $domain->name,
                'domain_id' => $domain->id,
                'years' => $years,
                'service' => $serviceName,
                'error' => $errorMessage,
            ]);

            // Create a failed renewal record
            DomainRenewal::create([
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

        } catch (Exception $e) {
            Log::error('Domain renewal exception', [
                'domain' => $domain->name,
                'domain_id' => $domain->id,
                'years' => $years,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'An unexpected error occurred during domain renewal: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Determine which domain service to use based on TLD
     */
    private function getDomainService(Domain $domain): DomainRegistrationServiceInterface
    {
        $tld = $this->extractTld($domain->name);

        return $tld === 'rw' ? $this->eppDomainService : $this->namecheapDomainService;
    }

    /**
     * Extract TLD from domain name
     */
    private function extractTld(string $domain): string
    {
        $parts = explode('.', $domain);

        return end($parts) ?: '';
    }
}
