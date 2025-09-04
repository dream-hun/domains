<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Domain;
use App\Services\Domain\NamecheapDomainService;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class FetchDomainsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public NamecheapDomainService $domainService
    ) {}

    public function handle(): void
    {
        try {
            $result = $this->domainService->getDomainList();
            \Log::debug('Namecheap getDomainList result', ['result' => $result]);
            if (! ($result['success'] ?? false) || empty($result['domains'])) {
                Log::warning('No domains fetched from Namecheap.', ['result' => $result]);

                return;
            }

            $count = 0;
            foreach ($result['domains'] as $domainData) {
                // Parse dates properly
                $registeredAt = isset($domainData['created_date'])
                    ? Carbon::parse($domainData['created_date'])
                    : now();

                $expiresAt = isset($domainData['expiry_date'])
                    ? Carbon::parse($domainData['expiry_date'])
                    : now()->addYear();

                // Calculate years based on registration and expiry dates
                $years = $registeredAt->diffInYears($expiresAt) ?: 1;

                // Ensure uuid is present
                $uuid = $domainData['uuid'] ?? Str::uuid()->toString();

                Domain::updateOrCreate(
                    ['uuid' => $uuid],
                    [
                        'name' => $domainData['name'],
                        'registered_at' => $registeredAt,
                        'expires_at' => $expiresAt,
                        'status' => $domainData['status'] ?? 'active',
                        'auto_renew' => $domainData['auto_renew'] ?? false,
                        'is_locked' => $domainData['locked'] ?? false,
                        'provider' => 'namecheap',
                        'registrar' => $domainData['registrar'] ?? 'Namecheap',
                        'owner_id' => $domainData['owner_id'] ?? 1, // Use from API or default
                        'years' => $years,
                        'auth_code' => $domainData['auth_code'] ?? null,
                        'is_premium' => $domainData['is_premium'] ?? false,
                        'domain_price_id' => $this->getOrCreateDomainPrice($domainData),
                    ]
                );
                $count++;
            }
            Log::info("Fetched and upserted $count domains from Namecheap.");
        } catch (Exception $exception) {
            Log::error('Error fetching or saving domains: '.$exception->getMessage(), [
                'exception' => $exception,
            ]);
        }
    }

    /**
     * Get or create a domain price record for the domain
     */
    private function getOrCreateDomainPrice(array $domainData): int
    {
        // Extract TLD from domain name
        $domainParts = explode('.', $domainData['name']);
        end($domainParts);

        // You might want to create a DomainPrice model and handle this properly
        // For now, returning a default ID - you should implement this based on your domain_prices table
        // Example:
        /*
        $domainPrice = DomainPrice::firstOrCreate(
            ['tld' => $tld, 'provider' => 'namecheap'],
            [
                'registration_price' => $domainData['price'] ?? 0,
                'renewal_price' => $domainData['renewal_price'] ?? 0,
                'years' => 1,
            ]
        );
        return $domainPrice->id;
        */

        // Temporary fallback - you should replace this with actual logic
        return 1; // Make sure this ID exists in your domain_prices table
    }
}
