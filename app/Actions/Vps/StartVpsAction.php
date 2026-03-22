<?php

declare(strict_types=1);

namespace App\Actions\Vps;

use App\Models\Subscription;
use App\Services\Vps\ContaboService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final readonly class StartVpsAction
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
            $this->contaboService->startInstance($instanceId);

            Log::info('VPS instance started', [
                'subscription_id' => $subscription->id,
                'instance_id' => $instanceId,
            ]);

            return ['success' => true, 'message' => 'VPS instance is starting.'];
        } catch (RuntimeException $runtimeException) {
            Log::error('Failed to start VPS instance', [
                'subscription_id' => $subscription->id,
                'error' => $runtimeException->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Failed to start instance: '.$runtimeException->getMessage()];
        }
    }
}
