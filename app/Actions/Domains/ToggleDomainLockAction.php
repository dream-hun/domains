<?php

declare(strict_types=1);

namespace App\Actions\Domains;

use App\Models\Domain;
use App\Services\Domain\DomainServiceInterface;
use Exception;

final readonly class ToggleDomainLockAction
{
    public function __construct(
        private DomainServiceInterface $domainService,
    ) {}

    /**
     * Execute the domain lock toggle action
     *
     * @param  Domain  $domain  The domain to lock/unlock
     * @param  bool  $lock  True to lock, false to unlock
     * @return array{success: bool, message?: string}
     */
    public function execute(Domain $domain, bool $lock): array
    {
        try {
            $result = $this->domainService->setDomainLock($domain->name, $lock);

            if (! $result['success']) {
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to update domain lock status',
                ];
            }

            // Update the database lock status
            $domain->is_locked = $lock;
            $domain->save();

            return [
                'success' => true,
                'message' => $lock ? 'Domain locked successfully' : 'Domain unlocked successfully',
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update domain lock: '.$e->getMessage(),
            ];
        }
    }
}
