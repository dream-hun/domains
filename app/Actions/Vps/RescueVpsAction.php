<?php

declare(strict_types=1);

namespace App\Actions\Vps;

use App\Models\Subscription;
use App\Services\Vps\ContaboService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class RescueVpsAction
{
    public function __construct(
        private ContaboService $contaboService,
    ) {}

    /**
     * @return array{success: bool, message: string, data?: array, password?: string}
     */
    public function execute(Subscription $subscription): array
    {
        try {
            $instanceId = (int) $subscription->provider_resource_id;

            $password = Str::password(24);
            $secret = $this->contaboService->createSecret([
                'name' => "rescue-{$instanceId}-".now()->timestamp,
                'type' => 'password',
                'value' => $password,
            ]);

            $data = $this->contaboService->rescueInstance($instanceId, [
                'rootPassword' => (int) $secret['secretId'],
            ]);

            Log::info('VPS instance booted into rescue mode', [
                'subscription_id' => $subscription->id,
                'instance_id' => $instanceId,
            ]);

            return ['success' => true, 'message' => 'VPS instance is booting into rescue mode.', 'data' => $data];
        } catch (RuntimeException $runtimeException) {
            Log::error('Failed to rescue VPS instance', [
                'subscription_id' => $subscription->id,
                'error' => $runtimeException->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Failed to enter rescue mode: '.$runtimeException->getMessage()];
        }
    }
}
