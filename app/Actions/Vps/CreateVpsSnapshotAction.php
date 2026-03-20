<?php

declare(strict_types=1);

namespace App\Actions\Vps;

use App\Models\Subscription;
use App\Services\Vps\ContaboService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final readonly class CreateVpsSnapshotAction
{
    public function __construct(
        private ContaboService $contaboService,
    ) {}

    /**
     * @return array{success: bool, message: string, data?: array}
     */
    public function execute(Subscription $subscription, string $name, string $description = ''): array
    {
        try {
            $instanceId = (int) $subscription->provider_resource_id;
            $data = $this->contaboService->createSnapshot($instanceId, $name, $description);

            Log::info('VPS snapshot created', [
                'subscription_id' => $subscription->id,
                'instance_id' => $instanceId,
                'snapshot_name' => $name,
            ]);

            return ['success' => true, 'message' => 'Snapshot created successfully.', 'data' => $data];
        } catch (RuntimeException $e) {
            Log::error('Failed to create VPS snapshot', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Failed to create snapshot: '.$e->getMessage()];
        }
    }
}
