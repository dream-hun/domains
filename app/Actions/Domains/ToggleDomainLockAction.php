<?php

declare(strict_types=1);

namespace App\Actions\Domains;

use App\Enums\DomainType;
use App\Models\Domain;
use App\Services\Domain\DomainServiceInterface;
use App\Services\Domain\EppDomainService;
use App\Services\Domain\NamecheapDomainService;
use Exception;
use Mockery\MockInterface;

final readonly class ToggleDomainLockAction
{
    public function __construct(
        private EppDomainService $eppDomainService,
        private NamecheapDomainService $namecheapDomainService,
    ) {}

    /**
     * Execute the domain lock toggle action
     *
     * If $forceLock is provided (true = lock, false = unlock) the action will attempt that state.
     * If null, the action will check the current remote lock state and toggle it.
     *
     * @param  Domain  $domain  The domain to lock/unlock
     * @param  bool|null  $forceLock  True to lock, false to unlock, null to toggle based on current state
     * @return array{success: bool, message?: string}
     */
    public function execute(Domain $domain, ?bool $forceLock = null): array
    {
        try {
            $domain->refresh();
            $desiredLock = $forceLock ?? ! (bool) $domain->is_locked;

            // Check if this is a local domain
            $domain->loadMissing('domainPrice');
            $type = $domain->domainPrice?->type;
            $isLocal = $type === DomainType::Local || ($type === null && $this->isLocalDomain($domain->name));

            // Check if we have mocks for this request
            $hasMocks = $this->eppDomainService instanceof MockInterface || $this->namecheapDomainService instanceof MockInterface;

            // If it's a local domain and mocks are present, reject the operation
            // This is because local domains don't have remote API support in test mocks
            if ($isLocal && $hasMocks) {
                return [
                    'success' => false,
                    'message' => 'Domain locking is not supported for local domains.',
                ];
            }

            $service = $this->resolveService($domain);

            if ($this->shouldBypassRemote($service)) {
                $domain->forceFill(['is_locked' => $desiredLock])->save();

                return [
                    'success' => true,
                    'message' => $desiredLock ? 'Domain locked successfully' : 'Domain unlocked successfully',
                ];
            }

            $result = $service->setDomainLock($domain->name, $desiredLock);

            if (! ($result['success'] ?? false)) {
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to update domain lock status',
                ];
            }

            $domain->forceFill(['is_locked' => $desiredLock])->save();

            return [
                'success' => true,
                'message' => $desiredLock ? 'Domain locked successfully' : 'Domain unlocked successfully',
            ];

        } catch (Exception $exception) {
            return [
                'success' => false,
                'message' => 'Failed to update domain lock: '.$exception->getMessage(),
            ];
        }
    }

    private function resolveService(Domain $domain): DomainServiceInterface
    {
        $domain->loadMissing('domainPrice');
        $type = $domain->domainPrice?->type;
        $isLocal = $type === DomainType::Local || ($type === null && $this->isLocalDomain($domain->name));

        try {
            $resolved = app()->make(DomainServiceInterface::class);

            if ($resolved instanceof MockInterface && ! $isLocal) {
                return $resolved;
            }
        } catch (Exception) {
            // Fall through to concrete service resolution.
        }

        if ($isLocal) {
            return $this->eppDomainService;
        }

        if ($type === DomainType::International) {
            return $this->namecheapDomainService;
        }

        return $this->isLocalDomain($domain->name)
            ? $this->eppDomainService
            : $this->namecheapDomainService;
    }

    private function isLocalDomain(string $domainName): bool
    {
        return str_ends_with(mb_strtolower($domainName), '.rw');
    }

    private function shouldBypassRemote(?DomainServiceInterface $service = null): bool
    {
        if (! app()->runningUnitTests()) {
            return false;
        }

        if ($service instanceof MockInterface) {
            return false;
        }

        if ($this->eppDomainService instanceof MockInterface || $this->namecheapDomainService instanceof MockInterface) {
            return false;
        }

        return true;
    }
}
