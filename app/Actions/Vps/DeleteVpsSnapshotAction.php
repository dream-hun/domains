<?php

declare(strict_types=1);

namespace App\Actions\Vps;

use App\Models\Subscription;
use App\Services\Vps\ContaboService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final readonly class DeleteVpsSnapshotAction
{
    public function __construct(
        private ContaboService $contaboService,
    ) {}

    /**
     * @return array{success: bool, message: string}
     */
    public function execute(Subscription $subscription, string $snapshotId): array
    {
        try {
            $instanceId = (int) $subscription->provider_resource_id;
            $this->contaboService->deleteSnapshot($instanceId, $snapshotId);

            Log::info('VPS snapshot deleted', [
                'subscription_id' => $subscription->id,
                'instance_id' => $instanceId,
                'snapshot_id' => $snapshotId,
            ]);

            return ['success' => true, 'message' => 'Snapshot deleted successfully.'];
        } catch (RuntimeException $runtimeException) {
            Log::error('Failed to delete VPS snapshot', [
                'subscription_id' => $subscription->id,
                'error' => $runtimeException->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Failed to delete snapshot: '.$runtimeException->getMessage()];
        }
    }
}
