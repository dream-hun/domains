<?php

declare(strict_types=1);

namespace App\Actions\Vps;

use App\Models\Subscription;
use App\Services\Vps\ContaboService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final readonly class MoveVpsRegionAction
{
    public function __construct(
        private ContaboService $contaboService,
    ) {}

    /**
     * @return array{success: bool, message: string, data?: array}
     */
    public function execute(Subscription $subscription, string $targetRegion): array
    {
        try {
            $instanceId = (int) $subscription->provider_resource_id;

            $snapshotName = sprintf('region-migration-%s-', $targetRegion).now()->format('Y-m-d-His');
            $snapshotData = $this->contaboService->createSnapshot(
                $instanceId,
                $snapshotName,
                'Pre-migration snapshot for region move to '.$targetRegion,
            );

            Log::info('VPS region migration snapshot created', [
                'subscription_id' => $subscription->id,
                'instance_id' => $instanceId,
                'target_region' => $targetRegion,
                'snapshot_id' => $snapshotData['snapshotId'] ?? null,
            ]);

            return [
                'success' => true,
                'message' => sprintf('Migration snapshot created. Next steps: create a new instance in %s from this snapshot, then reassign the subscription.', $targetRegion),
                'data' => $snapshotData,
            ];
        } catch (RuntimeException $runtimeException) {
            Log::error('Failed to initiate VPS region migration', [
                'subscription_id' => $subscription->id,
                'error' => $runtimeException->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Failed to initiate region migration: '.$runtimeException->getMessage()];
        }
    }
}
