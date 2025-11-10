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
use Illuminate\Support\Str;
use Throwable;

final readonly class UpdateNameserversAction
{
    public function __construct(
        private EppDomainService $eppDomainService,
        private NamecheapDomainService $namecheapDomainService
    ) {}

    public function handle(Domain $domain, array $nameservers): array
    {
        try {
            return DB::transaction(function () use ($domain, $nameservers): array {
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
        } catch (Throwable $e) {
            Log::error('Nameserver update failed with throwable', [
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

    /**
     * @throws Exception
     */
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

    /**
     * @throws Exception
     */
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

    /**
     * @throws Exception
     */
    private function syncNameserversInDatabase(Domain $domain, array $nameservers): void
    {
        $normalized = collect($nameservers)
            ->map(fn ($nameserver): string => mb_strtolower(mb_trim((string) $nameserver)))
            ->filter(fn (string $nameserver): bool => $nameserver !== '' && $nameserver !== '0')
            ->unique()
            ->values();

        $nameserverIds = $normalized->map(function (string $nameserver, int $index): int {
            $record = Nameserver::query()->firstOrCreate(
                ['name' => $nameserver],
                [
                    'uuid' => (string) Str::uuid(),
                    'type' => 'custom',
                    'priority' => $index + 1,
                    'status' => 'active',
                ]
            );

            if (! $record->wasRecentlyCreated) {
                $record->update([
                    'priority' => $index + 1,
                    'status' => 'active',
                ]);
            }

            return $record->id;
        })->all();

        $domain->nameservers()->sync($nameserverIds);
        $domain->touch();

        Log::info('Nameservers synchronized in database', [
            'domain_id' => $domain->id,
            'domain_name' => $domain->name,
            'nameservers' => $normalized->all(),
        ]);
    }

    private function isLocalDomain(string $domainName): bool
    {
        return str_ends_with(mb_strtolower($domainName), '.rw');
    }
}
