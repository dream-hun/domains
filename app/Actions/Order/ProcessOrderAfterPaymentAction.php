<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Models\Order;
use App\Services\HostingSubscriptionService;
use App\Services\OrderJobDispatcherService;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Support\Facades\Log;

final readonly class ProcessOrderAfterPaymentAction
{
    public function __construct(
        private HostingSubscriptionService $hostingSubscriptionService,
        private OrderJobDispatcherService $orderJobDispatcherService
    ) {}

    public function handle(Order $order, array $contactIds = [], bool $clearCart = true): void
    {
        try {
            $this->hostingSubscriptionService->createSubscriptionsFromOrder($order);
            $this->orderJobDispatcherService->dispatchJobsForOrder($order, $contactIds);

        } catch (Exception $exception) {
            Log::error('Order processing failed after payment', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        } finally {
            if ($clearCart) {
                Cart::clear();
                session()->forget(['cart', 'checkout', 'kpay_order_number']);
            }
        }
    }
}
