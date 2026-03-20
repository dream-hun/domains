<?php

declare(strict_types=1);

namespace App\Actions\Vps;

use App\Models\Subscription;
use App\Services\Vps\ContaboService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final readonly class ShutdownVpsAction
{
    public function __construct(
        private ContaboService $contaboService,
    ) {}

    /**
     * @return array{success: bool, message: string}
     */
    public function execute(Subscription $subscription): array
    {
        try {
            $instanceId = (int) $subscription->provider_resource_id;
            $this->contaboService->shutdownInstance($instanceId);

            Log::info('VPS instance shutdown', [
                'subscription_id' => $subscription->id,
                'instance_id' => $instanceId,
            ]);

            return ['success' => true, 'message' => 'VPS instance is shutting down.'];
        } catch (RuntimeException $e) {
            Log::error('Failed to shutdown VPS instance', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Failed to shutdown instance: '.$e->getMessage()];
        }
    }
}
