<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\Hosting\BillingCycle;
use App\Models\Order;
use App\Models\Subscription;
use App\Notifications\SubscriptionRenewedNotification;
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
    public function handle(): void
    {
        try {
            Log::info('Processing subscription renewals for order', [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
            ]);

            $allSuccessful = true;
            $failedSubscriptions = [];
            $renewedSubscriptions = [];

            // Process each order item
            foreach ($this->order->orderItems as $orderItem) {
                // Check if this is a subscription renewal item
                if ($orderItem->domain_type !== 'subscription_renewal') {
                    continue;
                }

                $subscriptionId = $orderItem->metadata['subscription_id'] ?? null;

                if (! $subscriptionId) {
                    Log::error('Subscription ID not found in order item metadata', [
                        'order_item_id' => $orderItem->id,
                        'order_id' => $this->order->id,
                    ]);
                    $allSuccessful = false;
                    $failedSubscriptions[] = $orderItem->domain_name ?? 'Unknown';

                    continue;
                }

                $subscription = Subscription::query()->find($subscriptionId);

                if (! $subscription) {
                    Log::error('Subscription not found for renewal', [
                        'subscription_id' => $subscriptionId,
                        'order_id' => $this->order->id,
                    ]);
                    $allSuccessful = false;
                    $failedSubscriptions[] = $orderItem->domain_name ?? "Subscription ID: {$subscriptionId}";

                    continue;
                }

                Log::info('Processing renewal for subscription', [
                    'subscription_id' => $subscription->id,
                    'subscription_uuid' => $subscription->uuid,
                    'domain' => $subscription->domain,
                    'billing_cycle' => $subscription->billing_cycle,
                ]);

                try {
                    // Resolve billing cycle
                    $billingCycle = $this->resolveBillingCycle($subscription->billing_cycle);

                    // Extend subscription
                    $subscription->extendSubscription($billingCycle);

                    $renewedSubscriptions[] = $subscription;

                    Log::info('Subscription renewed successfully', [
                        'subscription_id' => $subscription->id,
                        'subscription_uuid' => $subscription->uuid,
                        'new_expiry' => $subscription->expires_at->toDateString(),
                    ]);

                } catch (Exception $exception) {
                    $allSuccessful = false;
                    $failedSubscriptions[] = $subscription->domain ?? "Subscription UUID: {$subscription->uuid}";

                    Log::error('Subscription renewal failed', [
                        'subscription_id' => $subscription->id,
                        'subscription_uuid' => $subscription->uuid,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }

            // Send notification to user about renewed subscriptions
            if ($renewedSubscriptions !== []) {
                $this->notifyUserSubscriptionRenewed($renewedSubscriptions);
            }

            // Update order status based on results
            if ($allSuccessful) {
                $this->order->update([
                    'status' => 'completed',
                ]);

                Log::info('All subscription renewals completed successfully', [
                    'order_id' => $this->order->id,
                    'order_number' => $this->order->order_number,
                ]);
            } else {
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

            // Update order status to failed
            $this->order->update([
                'status' => 'failed',
                'notes' => 'Subscription renewal processing failed: '.$exception->getMessage(),
            ]);

            // Re-throw to trigger job retry
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

        // Update order status to failed
        $this->order->update([
            'status' => 'failed',
            'notes' => 'Subscription renewal processing failed after multiple attempts: '.($exception?->getMessage() ?? 'Unknown error'),
        ]);
    }

    private function resolveBillingCycle(string $cycle): BillingCycle
    {
        foreach (BillingCycle::cases() as $case) {
            if ($case->value === $cycle) {
                return $case;
            }
        }

        return BillingCycle::Monthly;
    }

    /**
     * @param  array<int, Subscription>  $subscriptions
     */
    private function notifyUserSubscriptionRenewed(array $subscriptions): void
    {
        $this->order->loadMissing('user');

        $user = $this->order->user;

        if (! $user) {
            Log::warning('Skipping subscription renewal notification because order has no associated user', [
                'order_id' => $this->order->id,
            ]);

            return;
        }

        $user->notify(new SubscriptionRenewedNotification($this->order, $subscriptions));
    }
}
