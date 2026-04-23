<?php

declare(strict_types=1);

namespace App\Actions\Vps;

use App\Models\HostingPlan;
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
     * @return array{success: bool, message: string, data?: array}
     */
    public function execute(Subscription $subscription): array
    {
        try {
            $currentPlan = $subscription->plan;

            $nextPlan = HostingPlan::query()
                ->where('category_id', $currentPlan->category_id)
                ->where('sort_order', '>', $currentPlan->sort_order)
                ->orderBy('sort_order')
                ->first();

            if (! $nextPlan) {
                return ['success' => false, 'message' => 'This instance is already on the highest plan.'];
            }

            if (! $nextPlan->contabo_product_id) {
                return ['success' => false, 'message' => 'Upgrade target plan is not configured with a provider product ID.'];
            }

            $instanceId = (int) $subscription->provider_resource_id;
            $data = $this->contaboService->upgradeInstance($instanceId, [
                'productId' => $nextPlan->contabo_product_id,
            ]);

            $subscription->update(['hosting_plan_id' => $nextPlan->id]);

            Log::info('VPS instance upgraded', [
                'subscription_id' => $subscription->id,
                'instance_id' => $instanceId,
                'from_plan' => $currentPlan->name,
                'to_plan' => $nextPlan->name,
            ]);

            return ['success' => true, 'message' => sprintf('VPS instance upgraded to %s.', $nextPlan->name), 'data' => $data];
        } catch (RuntimeException $runtimeException) {
            Log::error('Failed to upgrade VPS instance', [
                'subscription_id' => $subscription->id,
                'error' => $runtimeException->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Failed to upgrade instance: '.$runtimeException->getMessage()];
        }
    }
}
