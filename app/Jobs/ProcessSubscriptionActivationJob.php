<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Order;
use App\Services\HostingSubscriptionService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Backoff(60)]
#[Tries(3)]
final class ProcessSubscriptionActivationJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Order $order
    ) {}

    /**
     * Execute the job.
     *
     * @throws Exception
     */
    public function handle(HostingSubscriptionService $service): void
    {
        try {
            Log::info('Processing subscription activation job', [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
            ]);

            $service->createSubscriptionsFromOrder($this->order);

        } catch (Exception $exception) {
            Log::error('Subscription activation job failed', [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('ProcessSubscriptionActivationJob permanently failed after all retries', [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'error' => $exception?->getMessage() ?? 'Unknown error',
        ]);

        $this->order->update([
            'status' => 'failed',
            'notes' => 'Subscription activation permanently failed after multiple attempts: '.($exception?->getMessage() ?? 'Unknown error'),
        ]);
    }
}
