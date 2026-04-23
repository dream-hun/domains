<?php

declare(strict_types=1);

namespace App\Actions\Vps;

use App\Models\Subscription;
use App\Services\Vps\ContaboService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final readonly class CancelVpsAction
{
    public function __construct(
        private ContaboService $contaboService,
    ) {}

    /**
     * @return array{success: bool, message: string, data?: array}
     */
    public function execute(Subscription $subscription, ?string $cancelDate = null): array
    {
        try {
            $instanceId = (int) $subscription->provider_resource_id;
            $data = $this->contaboService->cancelInstance($instanceId, $cancelDate);

            Log::info('VPS instance cancellation scheduled', [
                'subscription_id' => $subscription->id,
                'instance_id' => $instanceId,
                'cancel_date' => $cancelDate,
            ]);

            return ['success' => true, 'message' => 'VPS instance cancellation has been scheduled.', 'data' => $data];
        } catch (RuntimeException $runtimeException) {
            Log::error('Failed to cancel VPS instance', [
                'subscription_id' => $subscription->id,
                'error' => $runtimeException->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Failed to cancel instance: '.$runtimeException->getMessage()];
        }
    }
}
