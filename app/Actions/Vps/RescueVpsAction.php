<?php

declare(strict_types=1);

namespace App\Actions\Vps;

use App\Models\Subscription;
use App\Services\Vps\ContaboService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final readonly class RescueVpsAction
{
    public function __construct(
        private ContaboService $contaboService,
    ) {}

    /**
     * @param  array{rootPassword?: int, sshKeys?: int[], userData?: string}  $payload
     * @return array{success: bool, message: string, data?: array}
     */
    public function execute(Subscription $subscription, array $payload = []): array
    {
        try {
            $instanceId = (int) $subscription->provider_resource_id;
            $data = $this->contaboService->rescueInstance($instanceId, $payload);

            Log::info('VPS instance booted into rescue mode', [
                'subscription_id' => $subscription->id,
                'instance_id' => $instanceId,
            ]);

            return ['success' => true, 'message' => 'VPS instance is booting into rescue mode.', 'data' => $data];
        } catch (RuntimeException $e) {
            Log::error('Failed to rescue VPS instance', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Failed to enter rescue mode: '.$e->getMessage()];
        }
    }
}
