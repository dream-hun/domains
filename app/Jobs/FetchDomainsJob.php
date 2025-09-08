<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Domain;
use App\Models\Scopes\DomainScope;
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
        $page = 1;
        $perPage = 100;
        $totalFetched = 0;

        try {
            do {
                $result = $this->domainService->getDomainList($page, $perPage);
                \Log::debug('Namecheap getDomainList result', ['page' => $page, 'result' => $result]);
                if (!($result['success'] ?? false) || empty($result['domains'])) {
                    if ($page === 1) {
                        Log::warning('No domains fetched from Namecheap.', ['result' => $result]);
                    }
                    break;
                }

                $count = 0;
                foreach ($result['domains'] as $domainData) {
                    $registeredAt = isset($domainData['created_date'])
                        ? Carbon::parse($domainData['created_date'])
                        : now();

                    $expiresAt = isset($domainData['expiry_date'])
                        ? Carbon::parse($domainData['expiry_date'])
                        : now()->addYear();

                    $years = $registeredAt->diffInYears($expiresAt) ?: 1;
                    $uuid = $domainData['uuid'] ?? Str::uuid()->toString();
                    $status = isset($domainData['status']) ? strtolower(trim($domainData['status'])) : 'active';

                    Domain::query()->withoutGlobalScope(DomainScope::class)->updateOrCreate(
                        ['uuid' => $uuid],
                        [
                            'name' => $domainData['name'],
                            'registered_at' => $registeredAt,
                            'expires_at' => $expiresAt,
                            'status' => $status,
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
                $totalFetched += $count;
                $page++;
            } while (count($result['domains']) === $perPage);

            Log::info("Fetched and upserted $totalFetched domains from Namecheap.");
        } catch (Exception $exception) {
            Log::error('Error fetching or saving domains: ' . $exception->getMessage(), [
                'exception' => $exception,
            ]);
        }
    }

    /**
     * Get or create a domain price record for the domain
     */
    private function getOrCreateDomainPrice(array $domainData): int
    {

        $domainParts = explode('.', $domainData['name']);
        end($domainParts);
        return 1;
    }
}
