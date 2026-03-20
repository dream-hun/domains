<?php

declare(strict_types=1);

namespace App\Actions\Vps;

use App\Models\Subscription;
use App\Services\Vps\ContaboService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final readonly class RestartVpsAction
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
            $this->contaboService->restartInstance($instanceId);

            Log::info('VPS instance restarted', [
                'subscription_id' => $subscription->id,
                'instance_id' => $instanceId,
            ]);

            return ['success' => true, 'message' => 'VPS instance is restarting.'];
        } catch (RuntimeException $e) {
            Log::error('Failed to restart VPS instance', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Failed to restart instance: '.$e->getMessage()];
        }
    }
}
