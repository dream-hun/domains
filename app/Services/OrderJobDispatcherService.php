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

        if (in_array($order->type, ['renewal', 'subscription_renewal'], true)) {
            $this->dispatchRenewalJobs($order);
        }
    }

    /**
     * Dispatch renewal jobs based on order type
     */
    private function dispatchRenewalJobs(Order $order): void
    {
        if ($order->type === 'renewal') {
            dispatch(new ProcessDomainRenewalJob($order));
            Log::info('Dispatched domain renewal job', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
        }

        if ($order->type === 'subscription_renewal') {
            dispatch(new ProcessSubscriptionRenewalJob($order));
            Log::info('Dispatched subscription renewal job', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
        }
    }
}
