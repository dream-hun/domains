<?php

declare(strict_types=1);

namespace App\Actions\Vps;

use App\Models\Subscription;
use App\Services\Vps\ContaboService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final readonly class RestoreVpsSnapshotAction
{
    public function __construct(
        private ContaboService $contaboService,
    ) {}

    /**
     * @return array{success: bool, message: string, data?: array}
     */
    public function execute(Subscription $subscription, string $snapshotId): array
    {
        try {
            $instanceId = (int) $subscription->provider_resource_id;
            $data = $this->contaboService->revertSnapshot($instanceId, $snapshotId);

            Log::info('VPS snapshot restored', [
                'subscription_id' => $subscription->id,
                'instance_id' => $instanceId,
                'snapshot_id' => $snapshotId,
            ]);

            return ['success' => true, 'message' => 'Snapshot is being restored.', 'data' => $data];
        } catch (RuntimeException $e) {
            Log::error('Failed to restore VPS snapshot', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Failed to restore snapshot: '.$e->getMessage()];
        }
    }
}
