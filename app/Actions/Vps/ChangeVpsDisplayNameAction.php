<?php

declare(strict_types=1);

namespace App\Actions\Vps;

use App\Models\Subscription;
use App\Services\Vps\ContaboService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final readonly class ChangeVpsDisplayNameAction
{
    public function __construct(
        private ContaboService $contaboService,
    ) {}

    /**
     * @return array{success: bool, message: string, data?: array}
     */
    public function execute(Subscription $subscription, string $displayName): array
    {
        try {
            $instanceId = (int) $subscription->provider_resource_id;
            $data = $this->contaboService->updateInstance($instanceId, $displayName);

            Log::info('VPS instance display name changed', [
                'subscription_id' => $subscription->id,
                'instance_id' => $instanceId,
                'display_name' => $displayName,
            ]);

            return ['success' => true, 'message' => 'Display name updated successfully.', 'data' => $data];
        } catch (RuntimeException $e) {
            Log::error('Failed to change VPS display name', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Failed to update display name: '.$e->getMessage()];
        }
    }
}
