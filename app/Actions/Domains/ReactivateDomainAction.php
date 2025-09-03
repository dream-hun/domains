<?php

declare(strict_types=1);

namespace App\Actions\Domains;

use App\Models\Domain;
use App\Services\Domain\EppDomainService;
use App\Services\Domain\NamecheapDomainService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ReactivateDomainAction
{
    public function __construct(
        private readonly EppDomainService $eppDomainService,
        private readonly NamecheapDomainService $namecheapDomainService
    ) {}

    public function handle(Domain $domain): array
    {
        try {
            return DB::transaction(function () use ($domain): array {
                // Check if domain is actually expired
                if ($domain->expires_at && $domain->expires_at->isFuture()) {
                    return [
                        'success' => false,
                        'message' => 'Domain is not expired and does not need reactivation',
                    ];
                }

                // Determine which service to use based on TLD
                $isLocalDomain = $this->isLocalDomain($domain->name);

                if ($isLocalDomain) {
                    return $this->reactivateWithEpp($domain);
                }

                return $this->reactivateWithNamecheap($domain);
            });
        } catch (Exception $e) {
            Log::error('Domain reactivation failed', [
                'domain_id' => $domain->id,
                'domain_name' => $domain->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Domain reactivation failed: '.$e->getMessage(),
            ];
        }
    }

    private function reactivateWithEpp(Domain $domain): array
    {
        Log::info('Reactivating local domain via EPP', [
            'domain' => $domain->name,
        ]);

        $result = $this->eppDomainService->reActivateDomain($domain->name);

        if ($result['success']) {
            $this->updateDomainRecord($domain, $result);
        }

        return $result;
    }

    private function reactivateWithNamecheap(Domain $domain): array
    {
        Log::info('Reactivating international domain via Namecheap', [
            'domain' => $domain->name,
        ]);

        $result = $this->namecheapDomainService->reActivateDomain($domain->name);

        if ($result['success']) {
            $this->updateDomainRecord($domain, $result);
        }

        return $result;
    }

    private function updateDomainRecord(Domain $domain, array $reactivationResult): void
    {
        // Update domain status to active and refresh information
        $domain->update([
            'status' => 'active',
            'last_renewed_at' => now(),
            'updated_at' => now(),
        ]);

        // Try to get updated domain info to refresh expiry date
        $isLocalDomain = $this->isLocalDomain($domain->name);
        $service = $isLocalDomain ? $this->eppDomainService : $this->namecheapDomainService;

        $domainInfo = $service->getDomainInfo($domain->name);
        if ($domainInfo['success'] && isset($domainInfo['expiry_date'])) {
            $domain->update([
                'expires_at' => $domainInfo['expiry_date'],
            ]);
        }

        Log::info('Domain record updated after reactivation', [
            'domain_id' => $domain->id,
            'domain_name' => $domain->name,
            'charged_amount' => $reactivationResult['charged_amount'] ?? 0,
            'order_id' => $reactivationResult['order_id'] ?? null,
            'transaction_id' => $reactivationResult['transaction_id'] ?? null,
        ]);
    }

    private function isLocalDomain(string $domainName): bool
    {
        return str_ends_with(mb_strtolower($domainName), '.rw');
    }
}
