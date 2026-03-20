<?php

declare(strict_types=1);

namespace App\Actions\Vps;

use App\Models\Subscription;
use App\Services\Vps\ContaboService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final readonly class ExtendVpsStorageAction
{
    public function __construct(
        private ContaboService $contaboService,
    ) {}

    /**
     * @return array{success: bool, message: string, data?: array}
     */
    public function execute(Subscription $subscription, int $storageGb): array
    {
        try {
            $instanceId = (int) $subscription->provider_resource_id;
            $data = $this->contaboService->upgradeInstance($instanceId, [
                'extraStorage' => $storageGb,
            ]);

            Log::info('VPS storage extended', [
                'subscription_id' => $subscription->id,
                'instance_id' => $instanceId,
                'storage_gb' => $storageGb,
            ]);

            return ['success' => true, 'message' => "Storage extension of {$storageGb} GB initiated.", 'data' => $data];
        } catch (RuntimeException $e) {
            Log::error('Failed to extend VPS storage', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Failed to extend storage: '.$e->getMessage()];
        }
    }
}
