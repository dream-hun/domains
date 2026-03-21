<?php

declare(strict_types=1);

namespace App\Actions\Vps;

use App\Models\Subscription;
use App\Services\Vps\ContaboService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final readonly class ReinstallVpsAction
{
    public function __construct(
        private ContaboService $contaboService,
    ) {}

    /**
     * @param  array{imageId: string, sshKeys?: int[], rootPassword?: int, defaultUser?: string}  $payload
     * @return array{success: bool, message: string, data?: array}
     */
    public function execute(Subscription $subscription, array $payload): array
    {
        try {
            $instanceId = (int) $subscription->provider_resource_id;
            $data = $this->contaboService->reinstallInstance($instanceId, $payload);

            Log::info('VPS instance reinstalled', [
                'subscription_id' => $subscription->id,
                'instance_id' => $instanceId,
                'image_id' => $payload['imageId'],
            ]);

            return ['success' => true, 'message' => 'VPS instance is being reinstalled.', 'data' => $data];
        } catch (RuntimeException $runtimeException) {
            Log::error('Failed to reinstall VPS instance', [
                'subscription_id' => $subscription->id,
                'error' => $runtimeException->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Failed to reinstall instance: '.$runtimeException->getMessage()];
        }
    }
}
