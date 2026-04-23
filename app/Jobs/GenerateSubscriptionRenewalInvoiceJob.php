<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Subscription;
use App\Notifications\RenewalInvoiceNotification;
use App\Services\SubscriptionInvoiceGenerationService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class GenerateSubscriptionRenewalInvoiceJob implements ShouldQueue
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
        public Subscription $subscription
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SubscriptionInvoiceGenerationService $service): void
    {
        if (! $service->shouldGenerateInvoice($this->subscription)) {
            Log::info('Skipping subscription renewal invoice generation (race condition guard)', [
                'subscription_id' => $this->subscription->id,
                'subscription_uuid' => $this->subscription->uuid,
            ]);

            return;
        }

        $order = $service->createRenewalInvoiceOrder($this->subscription);

        $this->subscription->user->notify(new RenewalInvoiceNotification($order));

        Log::info('Renewal invoice generated for subscription', [
            'subscription_id' => $this->subscription->id,
            'subscription_uuid' => $this->subscription->uuid,
            'order_id' => $order->id,
            'next_renewal_at' => $this->subscription->next_renewal_at?->toDateString(),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Exception $exception): void
    {
        Log::error('GenerateSubscriptionRenewalInvoiceJob failed after all retries', [
            'subscription_id' => $this->subscription->id,
            'subscription_uuid' => $this->subscription->uuid,
            'error' => $exception?->getMessage() ?? 'Unknown error',
        ]);
    }
}
