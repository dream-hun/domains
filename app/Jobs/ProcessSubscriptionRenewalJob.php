<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Order;
use App\Models\Subscription;
use App\Notifications\SubscriptionRenewedNotification;
use App\Services\SubscriptionRenewalService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class ProcessSubscriptionRenewalJob implements ShouldQueue
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
     */
    public function handle(SubscriptionRenewalService $subscriptionRenewalService): void
    {
        try {
            Log::info('Processing subscription renewals for order', [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
            ]);

            $results = $subscriptionRenewalService->processSubscriptionRenewals($this->order);

            if ($results['successful'] !== []) {
                $this->notifyUserSubscriptionRenewed($results['successful']);
            }

            if ($results['failed'] === []) {
                $this->order->update([
                    'status' => 'completed',
                ]);

                Log::info('All subscription renewals completed successfully', [
                    'order_id' => $this->order->id,
                    'order_number' => $this->order->order_number,
                ]);
            } elseif ($results['successful'] === []) {
                $this->order->update([
                    'status' => 'failed',
                    'notes' => 'All subscription renewals failed: '.implode(', ', array_column($results['failed'], 'subscription')),
                ]);

                Log::error('All subscription renewals failed', [
                    'order_id' => $this->order->id,
                    'order_number' => $this->order->order_number,
                    'failed_subscriptions' => $results['failed'],
                ]);
            } else {
                $failedSubscriptions = array_column($results['failed'], 'subscription');
                $this->order->update([
                    'status' => 'partially_completed',
                    'notes' => 'Some subscriptions failed to renew: '.implode(', ', $failedSubscriptions),
                ]);

                Log::warning('Order partially completed - some renewals failed', [
                    'order_id' => $this->order->id,
                    'order_number' => $this->order->order_number,
                    'failed_subscriptions' => $failedSubscriptions,
                ]);
            }

        } catch (Exception $exception) {
            Log::error('Exception in ProcessSubscriptionRenewalJob', [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            $this->order->update([
                'status' => 'failed',
                'notes' => 'Subscription renewal processing failed: '.$exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Exception $exception): void
    {
        Log::error('ProcessSubscriptionRenewalJob failed after all retries', [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'error' => $exception?->getMessage() ?? 'Unknown error',
        ]);

        $this->order->update([
            'status' => 'failed',
            'notes' => 'Subscription renewal processing failed after multiple attempts: '.($exception?->getMessage() ?? 'Unknown error'),
        ]);
    }

    /**
     * @param  array<int, array{subscription_id: int, subscription_uuid: string}>  $successfulResults
     */
    private function notifyUserSubscriptionRenewed(array $successfulResults): void
    {
        $this->order->loadMissing('user');

        $user = $this->order->user;

        if (! $user) {
            Log::warning('Skipping subscription renewal notification because order has no associated user', [
                'order_id' => $this->order->id,
            ]);

            return;
        }

        $subscriptionIds = array_column($successfulResults, 'subscription_id');
        $subscriptions = Subscription::query()->whereIn('id', $subscriptionIds)->get()->all();

        $user->notify(new SubscriptionRenewedNotification($this->order, $subscriptions));
    }
}
