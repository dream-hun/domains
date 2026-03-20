<?php

declare(strict_types=1);

namespace App\Actions\Vps;

use App\Models\Subscription;
use App\Services\Vps\ContaboService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final readonly class UpgradeVpsAction
{
    public function __construct(
        private ContaboService $contaboService,
    ) {}

    /**
     * @param  array{privateNetworking?: array, backup?: array}  $addOns
     * @return array{success: bool, message: string, data?: array}
     */
    public function execute(Subscription $subscription, array $addOns): array
    {
        try {
            $instanceId = (int) $subscription->provider_resource_id;
            $data = $this->contaboService->upgradeInstance($instanceId, $addOns);

            Log::info('VPS instance upgraded', [
                'subscription_id' => $subscription->id,
                'instance_id' => $instanceId,
                'add_ons' => array_keys($addOns),
            ]);

            return ['success' => true, 'message' => 'VPS instance upgrade initiated.', 'data' => $data];
        } catch (RuntimeException $runtimeException) {
            Log::error('Failed to upgrade VPS instance', [
                'subscription_id' => $subscription->id,
                'error' => $runtimeException->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Failed to upgrade instance: '.$runtimeException->getMessage()];
        }
    }
}
