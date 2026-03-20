<?php

declare(strict_types=1);

namespace App\Actions\Vps;

use App\Models\Subscription;
use App\Services\Vps\ContaboService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final readonly class AssignVpsToSubscriptionAction
{
    public function __construct(
        private ContaboService $contaboService,
    ) {}

    /**
     * @return array{success: bool, message: string}
     */
    public function execute(Subscription $subscription, int $instanceId): array
    {
        try {
            $existing = Subscription::query()
                ->where('provider_resource_id', (string) $instanceId)
                ->where('id', '!=', $subscription->id)
                ->exists();

            if ($existing) {
                return ['success' => false, 'message' => 'This instance is already assigned to another subscription.'];
            }

            $this->contaboService->getInstance($instanceId);

            $subscription->update(['provider_resource_id' => (string) $instanceId]);

            Log::info('VPS instance assigned to subscription', [
                'subscription_id' => $subscription->id,
                'instance_id' => $instanceId,
            ]);

            return ['success' => true, 'message' => 'VPS instance assigned successfully.'];
        } catch (RuntimeException $e) {
            Log::error('Failed to assign VPS instance', [
                'subscription_id' => $subscription->id,
                'instance_id' => $instanceId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Failed to assign instance: '.$e->getMessage()];
        }
    }
}
