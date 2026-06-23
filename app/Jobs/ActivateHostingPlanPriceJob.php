<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Hosting\PlanPrices\ActivateHostingPlanPriceAction;
use App\Models\HostingPlanPrice;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Backoff(60)]
#[Tries(3)]
final class ActivateHostingPlanPriceJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $planPriceUuid
    ) {}

    /**
     * Execute the job.
     *
     * @throws Exception
     */
    public function handle(ActivateHostingPlanPriceAction $action): void
    {
        $planPrice = HostingPlanPrice::query()
            ->where('uuid', $this->planPriceUuid)
            ->first();

        if ($planPrice === null) {
            Log::warning('ActivateHostingPlanPriceJob: Price not found', [
                'uuid' => $this->planPriceUuid,
            ]);

            return;
        }

        if ($planPrice->is_current) {
            Log::info('ActivateHostingPlanPriceJob: Price already current', [
                'uuid' => $this->planPriceUuid,
            ]);

            return;
        }

        try {
            Log::info('ActivateHostingPlanPriceJob: Activating price', [
                'uuid' => $this->planPriceUuid,
                'plan_id' => $planPrice->hosting_plan_id,
                'effective_date' => $planPrice->effective_date?->toDateString(),
            ]);

            $action->handle($planPrice);

            Log::info('ActivateHostingPlanPriceJob: Price activated successfully', [
                'uuid' => $this->planPriceUuid,
            ]);

        } catch (Exception $exception) {
            Log::error('ActivateHostingPlanPriceJob: Failed to activate price', [
                'uuid' => $this->planPriceUuid,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('ActivateHostingPlanPriceJob: Permanently failed after all retries', [
            'uuid' => $this->planPriceUuid,
            'error' => $exception?->getMessage() ?? 'Unknown error',
        ]);
    }
}
