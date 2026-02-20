<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\TldPricing\ActivateTldPricingAction;
use App\Models\TldPricing;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class ActivateTldPricingJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $tldPricingUuid
    ) {}

    /**
     * Execute the job.
     *
     * @throws Exception
     */
    public function handle(ActivateTldPricingAction $action): void
    {
        $tldPricing = TldPricing::query()
            ->where('uuid', $this->tldPricingUuid)
            ->first();

        if ($tldPricing === null) {
            Log::warning('ActivateTldPricingJob: Pricing not found', [
                'uuid' => $this->tldPricingUuid,
            ]);

            return;
        }

        if ($tldPricing->is_current) {
            Log::info('ActivateTldPricingJob: Pricing already current', [
                'uuid' => $this->tldPricingUuid,
            ]);

            return;
        }

        try {
            Log::info('ActivateTldPricingJob: Activating pricing', [
                'uuid' => $this->tldPricingUuid,
                'tld_id' => $tldPricing->tld_id,
                'currency_id' => $tldPricing->currency_id,
                'effective_date' => $tldPricing->effective_date?->toDateString(),
            ]);

            $action->handle($tldPricing);

            Log::info('ActivateTldPricingJob: Pricing activated successfully', [
                'uuid' => $this->tldPricingUuid,
            ]);

        } catch (Exception $exception) {
            Log::error('ActivateTldPricingJob: Failed to activate pricing', [
                'uuid' => $this->tldPricingUuid,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
