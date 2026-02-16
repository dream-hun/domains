<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\ProcessDomainRegistrationJob;
use App\Jobs\ProcessDomainRenewalJob;
use App\Jobs\ProcessSubscriptionActivationJob;
use App\Jobs\ProcessSubscriptionRenewalJob;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

final readonly class OrderJobDispatcherService
{
    public function __construct(
        private OrderProcessingService $orderProcessingService
    ) {}

    /**
     * Dispatch appropriate jobs for an order after payment confirmation
     */
    public function dispatchJobsForOrder(Order $order, array $contactIds = []): void
    {
        $this->orderProcessingService->createOrderItemsFromJson($order);

        if (! in_array($order->type, ['renewal', 'subscription_renewal'], true) && $contactIds !== []) {
            dispatch(new ProcessDomainRegistrationJob($order, $contactIds));
            Log::info('Dispatched domain registration job', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
        }

        $hasHostingItems = $order->orderItems()
            ->where('domain_type', 'hosting')
            ->exists();

        if ($hasHostingItems && ! in_array($order->type, ['renewal', 'subscription_renewal'], true)) {
            dispatch(new ProcessSubscriptionActivationJob($order));
            Log::info('Dispatched subscription activation job', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
        }

        if ($this->orderHasRenewalItems($order)) {
            $this->dispatchRenewalJobs($order);
        }
    }

    /**
     * Whether the order has any domain or subscription renewal items (supports mixed renewal orders)
     */
    private function orderHasRenewalItems(Order $order): bool
    {
        return $order->orderItems()
            ->whereIn('domain_type', ['renewal', 'subscription_renewal'])
            ->exists();
    }

    /**
     * Dispatch renewal jobs based on order item types (not order type)
     * Supports mixed orders: dispatches both jobs when order contains both domain and subscription renewals.
     * Renewal jobs are processed synchronously to ensure immediate processing after payment.
     */
    private function dispatchRenewalJobs(Order $order): void
    {
        $hasDomainRenewals = $order->orderItems()
            ->where('domain_type', 'renewal')
            ->exists();

        $hasSubscriptionRenewals = $order->orderItems()
            ->where('domain_type', 'subscription_renewal')
            ->exists();

        if ($hasDomainRenewals) {
            dispatch_sync(new ProcessDomainRenewalJob($order));
            Log::info('Processed domain renewal job synchronously', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
        }

        if ($hasSubscriptionRenewals) {
            dispatch_sync(new ProcessSubscriptionRenewalJob($order));
            Log::info('Processed subscription renewal job synchronously', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
        }
    }
}
