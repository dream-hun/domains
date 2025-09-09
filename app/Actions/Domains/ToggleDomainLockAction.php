<?php

declare(strict_types=1);

namespace App\Actions\Domains;

use App\Models\Domain;
use App\Services\Domain\NamecheapDomainService;
use Exception;

final readonly class ToggleDomainLockAction
{
    public function __construct(
        private NamecheapDomainService $domainService,
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
            $desiredLock = $forceLock;

            if ($desiredLock === null) {
                $current = $this->domainService->getDomainLock($domain->name);
                if (! ($current['success'] ?? false)) {
                    return [
                        'success' => false,
                        'message' => $current['message'] ?? 'Failed to get current domain lock status',
                    ];
                }

                $currentLocked = (bool) ($current['locked'] ?? false);
                $desiredLock = ! $currentLocked; // toggle
            }

            $result = $this->domainService->setDomainLock($domain->name, $desiredLock);

            if (! ($result['success'] ?? false)) {
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to update domain lock status',
                ];
            }

            // Update the database lock status using the authoritative value from the API if available
            $remoteLocked = $result['locked'] ?? $desiredLock;
            $domain->is_locked = (bool) $remoteLocked;
            $domain->save();

            return [
                'success' => true,
                'message' => $domain->is_locked ? 'Domain locked successfully' : 'Domain unlocked successfully',
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update domain lock: '.$e->getMessage(),
            ];
        }
    }
}
