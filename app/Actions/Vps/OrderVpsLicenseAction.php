<?php

declare(strict_types=1);

namespace App\Actions\Vps;

use App\Models\Subscription;
use App\Services\Vps\ContaboService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final readonly class OrderVpsLicenseAction
{
    public function __construct(
        private ContaboService $contaboService,
    ) {}

    /**
     * @return array{success: bool, message: string, data?: array}
     */
    public function execute(Subscription $subscription, string $licenseType): array
    {
        try {
            $instanceId = (int) $subscription->provider_resource_id;
            $data = $this->contaboService->upgradeInstance($instanceId, [
                'license' => $licenseType,
            ]);

            Log::info('VPS license ordered', [
                'subscription_id' => $subscription->id,
                'instance_id' => $instanceId,
                'license_type' => $licenseType,
            ]);

            return ['success' => true, 'message' => "License ($licenseType) order initiated.", 'data' => $data];
        } catch (RuntimeException $e) {
            Log::error('Failed to order VPS license', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Failed to order license: '.$e->getMessage()];
        }
    }
}
