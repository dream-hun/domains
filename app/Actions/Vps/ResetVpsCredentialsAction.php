<?php

declare(strict_types=1);

namespace App\Actions\Vps;

use App\Models\Subscription;
use App\Services\Vps\ContaboService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final readonly class ResetVpsCredentialsAction
{
    public function __construct(
        private ContaboService $contaboService,
    ) {}

    /**
     * @param  array{sshKeys?: int[], rootPassword?: int, userData?: string}  $payload
     * @return array{success: bool, message: string, data?: array}
     */
    public function execute(Subscription $subscription, array $payload): array
    {
        try {
            $instanceId = (int) $subscription->provider_resource_id;
            $data = $this->contaboService->resetInstancePassword($instanceId, $payload);

            Log::info('VPS instance credentials reset', [
                'subscription_id' => $subscription->id,
                'instance_id' => $instanceId,
            ]);

            return ['success' => true, 'message' => 'VPS instance credentials have been reset.', 'data' => $data];
        } catch (RuntimeException $e) {
            Log::error('Failed to reset VPS credentials', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Failed to reset credentials: '.$e->getMessage()];
        }
    }
}
