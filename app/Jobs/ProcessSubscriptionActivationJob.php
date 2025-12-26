<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Order;
use App\Services\HostingSubscriptionService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class ProcessSubscriptionActivationJob implements ShouldQueue
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
}
