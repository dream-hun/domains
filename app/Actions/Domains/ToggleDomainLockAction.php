<?php

declare(strict_types=1);

namespace App\Actions\Domains;

use App\Enums\DomainType;
use App\Models\Domain;
use App\Services\Domain\DomainServiceInterface;
use App\Services\Domain\EppDomainService;
use App\Services\Domain\NamecheapDomainService;
use Exception;
use Illuminate\Support\Facades\Log;

final readonly class ToggleDomainLockAction
{
    public function __construct(
        private EppDomainService       $eppDomainService,
        private NamecheapDomainService $namecheapDomainService,
    ) {}

    /**
     * Execute the domain lock toggle action
     *
     * @param  Domain  $domain The domain to lock/unlock
     * @param  bool  $lock True to lock, false to unlock
     * @return array{success: bool, message?: string}
     */
    public function execute(Domain $domain, bool $lock): array
    {
        try {
            Log::info('Toggling domain lock', [
                'domain' => $domain->name,
                'lock' => $lock,
                'type' => $domain->domainPrice?->type?->value,
            ]);

            // Get the appropriate domain service based on domain type
            $service = $this->getDomainService($domain);

            // Execute the lock/unlock operation
            $result = $service->setDomainLock($domain->name, $lock);

            if (! $result['success']) {
                Log::error('Failed to toggle domain lock', [
                    'domain' => $domain->name,
                    'lock' => $lock,
                    'error' => $result['message'] ?? 'Unknown error',
                ]);

                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to update domain lock status',
                ];
            }

            return [
                'success' => true,
                'message' => $lock ? 'Domain locked successfully' : 'Domain unlocked successfully',
            ];

        } catch (Exception $e) {
            Log::error('Domain lock toggle failed', [
                'domain' => $domain->name,
                'lock' => $lock,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update domain lock: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get the appropriate domain service based on domain type
     * @throws Exception
     */
    private function getDomainService(Domain $domain): DomainServiceInterface
    {
        $type = $domain->domainPrice?->type;

        return match ($type) {
            DomainType::Local => $this->eppDomainService,
            DomainType::International => $this->namecheapDomainService,
            default => throw new Exception('Unknown domain type'),
        };
    }
}
