<?php

declare(strict_types=1);

namespace App\Actions\Domains;

use App\Models\Domain;
use App\Models\Nameserver;
use App\Services\Domain\EppDomainService;
use App\Services\Domain\NamecheapDomainService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class UpdateNameserversAction
{
    public function __construct(
        private readonly EppDomainService $eppDomainService,
        private readonly NamecheapDomainService $namecheapDomainService
    ) {}

    public function handle(Domain $domain, array $nameservers): array
    {
        try {
            return DB::transaction(function () use ($domain, $nameservers): array {
                // Determine which service to use based on TLD
                $isLocalDomain = $this->isLocalDomain($domain->name);

                if ($isLocalDomain) {
                    return $this->updateWithEpp($domain, $nameservers);
                }

                return $this->updateWithNamecheap($domain, $nameservers);
            });
        } catch (Exception $e) {
            Log::error('Nameserver update failed', [
                'domain_id' => $domain->id,
                'domain_name' => $domain->name,
                'nameservers' => $nameservers,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Nameserver update failed: '.$e->getMessage(),
            ];
        }
    }

    private function updateWithEpp(Domain $domain, array $nameservers): array
    {
        Log::info('Updating nameservers for local domain via EPP', [
            'domain' => $domain->name,
            'nameservers' => $nameservers,
        ]);

        $result = $this->eppDomainService->updateNameservers($domain->name, $nameservers);

        if ($result['success']) {
            $this->syncNameserversInDatabase($domain, $nameservers);
        }

        return $result;
    }

    private function updateWithNamecheap(Domain $domain, array $nameservers): array
    {
        Log::info('Updating nameservers for international domain via Namecheap', [
            'domain' => $domain->name,
            'nameservers' => $nameservers,
        ]);

        $result = $this->namecheapDomainService->updateNameservers($domain->name, $nameservers);

        if ($result['success']) {
            $this->syncNameserversInDatabase($domain, $nameservers);
        }

        return $result;
    }

    private function syncNameserversInDatabase(Domain $domain, array $nameservers): void
    {
        // Remove existing nameservers for this domain
        $domain->nameservers()->delete();

        // Add new nameservers
        foreach ($nameservers as $nameserver) {
            Nameserver::create([
                'domain_id' => $domain->id,
                'name' => mb_trim($nameserver),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Update domain's last modification timestamp
        $domain->update([
            'updated_at' => now(),
        ]);

        Log::info('Nameservers synced in database', [
            'domain_id' => $domain->id,
            'domain_name' => $domain->name,
            'nameservers_count' => count($nameservers),
            'nameservers' => $nameservers,
        ]);
    }

    private function isLocalDomain(string $domainName): bool
    {
        return str_ends_with(mb_strtolower($domainName), '.rw');
    }
}
