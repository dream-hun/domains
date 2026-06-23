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

        // Single query replaces 3 separate EXISTS checks.
        $itemTypes = $order->orderItems()
            ->whereIn('domain_type', ['hosting', 'renewal', 'subscription_renewal'])
            ->distinct()
            ->pluck('domain_type');

        $isRenewalOrder = in_array($order->type, ['renewal', 'subscription_renewal'], true);

        if (! $isRenewalOrder && $contactIds !== []) {
            dispatch(new ProcessDomainRegistrationJob($order, $contactIds));
            Log::info('Dispatched domain registration job', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
        }

        if (! $isRenewalOrder && $itemTypes->contains('hosting')) {
            dispatch(new ProcessSubscriptionActivationJob($order));
            Log::info('Dispatched subscription activation job', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
        }

        if ($itemTypes->contains('renewal')) {
            dispatch(new ProcessDomainRenewalJob($order));
            Log::info('Dispatched domain renewal job', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
        }

        if ($itemTypes->contains('subscription_renewal')) {
            dispatch(new ProcessSubscriptionRenewalJob($order));
            Log::info('Dispatched subscription renewal job', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
        }
    }
}
