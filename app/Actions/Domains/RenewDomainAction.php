<?php

declare(strict_types=1);

namespace App\Actions\Domains;

use App\Models\Domain;
use App\Services\Domain\EppDomainService;
use App\Services\Domain\NamecheapDomainService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class RenewDomainAction
{
    public function __construct(
        private EppDomainService $eppDomainService,
        private NamecheapDomainService $namecheapDomainService
    ) {}

    public function handle(Domain $domain, int $years): array
    {
        try {
            return DB::transaction(function () use ($domain, $years): array {
                // Determine which service to use based on TLD
                $isLocalDomain = $this->isLocalDomain($domain->name);

                if ($isLocalDomain) {
                    return $this->renewWithEpp($domain, $years);
                }

                return $this->renewWithNamecheap($domain, $years);
            });
        } catch (Exception $exception) {
            Log::error('Domain renewal failed', [
                'domain_id' => $domain->id,
                'domain_name' => $domain->name,
                'years' => $years,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Domain renewal failed: '.$exception->getMessage(),
            ];
        }
    }

    private function renewWithEpp(Domain $domain, int $years): array
    {
        Log::info('Renewing local domain via EPP', [
            'domain' => $domain->name,
            'years' => $years,
        ]);

        $result = $this->eppDomainService->renewDomainRegistration($domain->name, $years);

        if ($result['success']) {
            $this->updateDomainRecord($domain, $years);
        }

        return $result;
    }

    private function renewWithNamecheap(Domain $domain, int $years): array
    {
        Log::info('Renewing international domain via Namecheap', [
            'domain' => $domain->name,
            'years' => $years,
        ]);

        $result = $this->namecheapDomainService->renewDomainRegistration($domain->name, $years);

        if ($result['success']) {
            $this->updateDomainRecord($domain, $years);
        }

        return $result;
    }

    private function updateDomainRecord(Domain $domain, int $years): void
    {
        // Calculate new expiry date
        $currentExpiry = $domain->expires_at ?? now();
        $newExpiry = $currentExpiry->addYears($years);

        $domain->update([
            'expires_at' => $newExpiry,
            'last_renewed_at' => now(),
            'status' => 'active',
            'updated_at' => now(),
        ]);

        Log::info('Domain record updated after renewal', [
            'domain_id' => $domain->id,
            'domain_name' => $domain->name,
            'years_renewed' => $years,
            'new_expiry' => $newExpiry->format('Y-m-d'),
        ]);
    }

    private function isLocalDomain(string $domainName): bool
    {
        return str_ends_with(mb_strtolower($domainName), '.rw');
    }
}
