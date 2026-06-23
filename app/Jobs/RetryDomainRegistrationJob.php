<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\RegisterDomainAction;
use App\Models\DomainContact;
use App\Models\FailedDomainRegistration;
use App\Services\NotificationService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Backoff([3600, 3600, 3600])]
#[Tries(3)]
final class RetryDomainRegistrationJob implements ShouldQueue
{
    use Queueable; // 1hr, 1hr, 1hr

    /**
     * Create a new job instance.
     */
    public function __construct(
        public FailedDomainRegistration $failedRegistration
    ) {}

    /**
     * Execute the job.
     */
    public function handle(RegisterDomainAction $registerDomainAction): void
    {
        // Check if we can retry
        if (! $this->failedRegistration->canRetry()) {
            Log::warning('Cannot retry domain registration', [
                'failed_registration_id' => $this->failedRegistration->id,
                'domain' => $this->failedRegistration->domain_name,
                'status' => $this->failedRegistration->status,
                'retry_count' => $this->failedRegistration->retry_count,
            ]);

            return;
        }

        Log::info('Retrying domain registration', [
            'failed_registration_id' => $this->failedRegistration->id,
            'domain' => $this->failedRegistration->domain_name,
            'retry_count' => $this->failedRegistration->retry_count + 1,
        ]);

        try {
            $ownerId = $this->failedRegistration->order->user_id;
            $contactIds = $this->failedRegistration->contact_ids;

            // Verify every contact ID belongs to the order's user before retrying.
            if (is_array($contactIds) && $contactIds !== []) {
                $validIds = DomainContact::query()
                    ->where('user_id', $ownerId)
                    ->whereIn('id', array_values($contactIds))
                    ->pluck('id')
                    ->all();

                $submittedIds = array_values(array_unique(array_values($contactIds)));
                sort($submittedIds);
                sort($validIds);

                if ($submittedIds !== $validIds) {
                    throw new Exception('One or more contact IDs do not belong to the order owner — aborting retry for security.');
                }
            }

            // Attempt registration
            $result = $registerDomainAction->handle(
                $this->failedRegistration->domain_name,
                $contactIds,
                $this->failedRegistration->orderItem->years,
                [], // Use default nameservers
                true, // Use single contact
                $ownerId
            );

            if ($result['success']) {
                // Registration succeeded - update order item and mark as resolved
                if (isset($result['domain_id'])) {
                    $this->failedRegistration->orderItem->update([
                        'domain_id' => $result['domain_id'],
                    ]);
                }

                $this->failedRegistration->markResolved();

                Log::info('Domain registration retry succeeded', [
                    'failed_registration_id' => $this->failedRegistration->id,
                    'domain' => $this->failedRegistration->domain_name,
                    'domain_id' => $result['domain_id'] ?? null,
                ]);

                // Check if all domains for this order are now registered
                $this->checkOrderCompletion();

            } else {
                // Registration still failed - increment retry count
                $this->failedRegistration->incrementRetryCount();

                Log::warning('Domain registration retry failed', [
                    'failed_registration_id' => $this->failedRegistration->id,
                    'domain' => $this->failedRegistration->domain_name,
                    'retry_count' => $this->failedRegistration->retry_count,
                    'error' => $result['message'] ?? 'Unknown error',
                ]);

                // Update failure reason with latest error
                $this->failedRegistration->update([
                    'failure_reason' => $result['message'] ?? 'Registration failed',
                ]);

                // If we've exhausted retries, mark as abandoned
                if (! $this->failedRegistration->canRetry()) {
                    $this->failedRegistration->markAbandoned();
                    $this->notifyAbandoned();
                } else {
                    // Schedule next retry
                    $this->scheduleNextRetry();
                }
            }

        } catch (Exception $exception) {
            // Exception during retry - increment retry count
            $this->failedRegistration->incrementRetryCount();

            Log::error('Exception during domain registration retry', [
                'failed_registration_id' => $this->failedRegistration->id,
                'domain' => $this->failedRegistration->domain_name,
                'retry_count' => $this->failedRegistration->retry_count,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            // Update failure reason with latest error
            $this->failedRegistration->update([
                'failure_reason' => $exception->getMessage(),
            ]);

            // If we've exhausted retries, mark as abandoned
            if (! $this->failedRegistration->canRetry()) {
                $this->failedRegistration->markAbandoned();
                $this->notifyAbandoned();
            } else {
                // Re-throw to trigger job retry mechanism
                throw $exception;
            }
        }
    }

    /**
     * Handle a job failure after all retries exhausted
     */
    public function failed(?Throwable $exception): void
    {
        $this->failedRegistration->markAbandoned();

        Log::error('Domain registration job failed after all retries', [
            'failed_registration_id' => $this->failedRegistration->id,
            'domain' => $this->failedRegistration->domain_name,
            'error' => $exception?->getMessage() ?? 'Unknown error',
        ]);

        $this->notifyAbandoned();
    }

    /**
     * Schedule the next retry attempt
     */
    private function scheduleNextRetry(): void
    {
        // Always retry after 1 hour
        $delay = 3600;

        $nextRetryAt = now()->addSeconds($delay);

        $this->failedRegistration->update([
            'next_retry_at' => $nextRetryAt,
        ]);

        dispatch(new self($this->failedRegistration))
            ->delay($nextRetryAt);

        Log::info('Next retry scheduled', [
            'failed_registration_id' => $this->failedRegistration->id,
            'domain' => $this->failedRegistration->domain_name,
            'next_retry_at' => $nextRetryAt->toDateTimeString(),
        ]);
    }

    /**
     * Check if all domains for the order are now complete
     */
    private function checkOrderCompletion(): void
    {
        $order = $this->failedRegistration->order->fresh(['failedDomainRegistrations', 'orderItems']);

        // Check if there are any remaining failed registrations
        $remainingFailures = $order->failedDomainRegistrations()
            ->whereIn('status', ['pending', 'retrying'])
            ->count();

        if ($remainingFailures === 0) {
            // All registrations completed - update order status
            $order->update(['status' => 'completed']);

            Log::info('All domain registrations completed for order', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
        }
    }

    /**
     * Notify admin that registration has been abandoned
     */
    private function notifyAbandoned(): void
    {
        $notificationService = resolve(NotificationService::class);
        $notificationService->notifyAdminOfAbandonedRegistration(
            $this->failedRegistration->order,
            $this->failedRegistration
        );
    }
}
