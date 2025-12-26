<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\RegisterDomainAction;
use App\Jobs\RetryDomainRegistrationJob;
use App\Models\FailedDomainRegistration;
use App\Models\Order;
use Exception;
use Illuminate\Support\Facades\Log;

final readonly class DomainRegistrationService
{
    public function __construct(
        private RegisterDomainAction $registerDomainAction,
        private NotificationService $notificationService
    ) {}

    /**
     * Process domain registrations for an order
     */
    public function processDomainRegistrations(Order $order, array $contactIds): array
    {
        $results = [
            'successful' => [],
            'failed' => [],
        ];

        Log::info('Starting domain registration process', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'item_count' => $order->orderItems->count(),
        ]);

        // Process each order item (domain)
        foreach ($order->orderItems as $orderItem) {

            if (in_array($orderItem->domain_type, ['hosting', 'renewal'], true)) {
                continue;
            }

            $result = $this->registerDomain($order, $orderItem, $contactIds);

            if ($result['success']) {
                $results['successful'][] = $result;
            } else {
                $results['failed'][] = $result;
            }
        }

        $this->updateOrderStatus($order, $results);

        $this->sendNotifications($order, $results);

        return $results;
    }

    /**
     * Register a single domain
     */
    private function registerDomain(Order $order, $orderItem, array $contactIds): array
    {
        try {
            Log::info('Attempting domain registration', [
                'order_id' => $order->id,
                'domain' => $orderItem->domain_name,
            ]);

            $result = $this->registerDomainAction->handle(
                $orderItem->domain_name,
                $contactIds,
                $orderItem->years,
                [],
                true,
                $order->user_id
            );

            if ($result['success']) {

                if (isset($result['domain_id'])) {
                    $orderItem->update(['domain_id' => $result['domain_id']]);
                }

                Log::info('Domain registered successfully', [
                    'order_id' => $order->id,
                    'domain' => $orderItem->domain_name,
                    'domain_id' => $result['domain_id'] ?? null,
                ]);

                return [
                    'success' => true,
                    'domain' => $orderItem->domain_name,
                    'domain_id' => $result['domain_id'] ?? null,
                    'message' => $result['message'] ?? 'Domain registered successfully',
                ];
            }

            $failedRegistration = $this->recordFailedRegistration(
                $order,
                $orderItem,
                $result['message'] ?? 'Registration failed',
                $contactIds
            );

            $this->dispatchRetryJob($failedRegistration);

            Log::error('Domain registration failed', [
                'order_id' => $order->id,
                'domain' => $orderItem->domain_name,
                'error' => $result['message'] ?? 'Unknown error',
            ]);

            return [
                'success' => false,
                'domain' => $orderItem->domain_name,
                'message' => $result['message'] ?? 'Registration failed',
                'failed_registration_id' => $failedRegistration->id,
            ];

        } catch (Exception $exception) {

            $failedRegistration = $this->recordFailedRegistration(
                $order,
                $orderItem,
                $exception->getMessage(),
                $contactIds
            );

            $this->dispatchRetryJob($failedRegistration);

            Log::error('Domain registration exception', [
                'order_id' => $order->id,
                'domain' => $orderItem->domain_name,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'domain' => $orderItem->domain_name,
                'message' => $exception->getMessage(),
                'failed_registration_id' => $failedRegistration->id,
            ];
        }
    }

    /**
     * Record a failed domain registration
     */
    private function recordFailedRegistration(Order $order, $orderItem, string $reason, array $contactIds): FailedDomainRegistration
    {
        return FailedDomainRegistration::query()->create([
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'domain_name' => $orderItem->domain_name,
            'failure_reason' => $reason,
            'retry_count' => 0,
            'max_retries' => 3,
            'last_attempted_at' => now(),
            'next_retry_at' => now()->addHour(), // Retry after 1 hour
            'status' => 'pending',
            'contact_ids' => $contactIds,
        ]);
    }

    /**
     * Dispatch retry job with appropriate delay
     */
    private function dispatchRetryJob(FailedDomainRegistration $failedRegistration): void
    {
        // Retry after 1 hour
        $delay = 3600;

        dispatch(new RetryDomainRegistrationJob($failedRegistration))
            ->delay(now()->addSeconds($delay));

        Log::info('Retry job dispatched', [
            'failed_registration_id' => $failedRegistration->id,
            'domain' => $failedRegistration->domain_name,
            'delay_seconds' => $delay,
            'retry_at' => now()->addSeconds($delay)->toDateTimeString(),
        ]);
    }

    /**
     * Update order status based on registration results
     */
    private function updateOrderStatus(Order $order, array $results): void
    {
        if (empty($results['failed'])) {
            $order->update(['status' => 'completed']);
            Log::info('All domains registered successfully', ['order_id' => $order->id]);
        } elseif (empty($results['successful'])) {
            $order->update(['status' => 'requires_attention']);
            Log::warning('All domains failed to register', ['order_id' => $order->id]);
        } else {
            $order->update(['status' => 'partially_completed']);
            Log::warning('Some domains failed to register', [
                'order_id' => $order->id,
                'successful_count' => count($results['successful']),
                'failed_count' => count($results['failed']),
            ]);
        }
    }

    /**
     * Send appropriate notifications based on results
     */
    private function sendNotifications(Order $order, array $results): void
    {
        if (! empty($results['failed'])) {

            $this->notificationService->notifyAdminOfFailedRegistration($order, $results);
        }
    }
}
