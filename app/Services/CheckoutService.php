<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class CheckoutService
{
    public function __construct(
        private PaymentService $paymentService,
        private OrderService $orderService,
        private DomainRegistrationService $domainRegistrationService
    ) {}

    /**
     * @throws Throwable
     */
    public function processCheckout(array $data): Order
    {
        // Transaction 1: Create order and process payment
        $order = DB::transaction(function () use ($data): Order {
            $order = $this->orderService->createOrder([
                'user_id' => $data['user_id'],
                'currency' => $data['currency'],
                'payment_method' => $data['payment_method'],
                'cart_items' => $data['cart_items'],
                'contact_ids' => $data['contact_ids'],
                'coupon' => $data['coupon'] ?? null,
                'discount_amount' => $data['discount_amount'] ?? 0,
            ]);

            $paymentResult = $this->paymentService->processPayment(
                $order,
                $data['payment_method']
            );

            if ($paymentResult['success']) {
                if (isset($paymentResult['requires_action']) && $paymentResult['requires_action']) {
                    return $order;
                }

                $order->update([
                    'payment_status' => 'paid',
                    'processed_at' => now(),
                    'stripe_payment_intent_id' => $paymentResult['transaction_id'] ?? null,
                ]);

                return $order;
            }

            $order->update([
                'payment_status' => 'failed',
                'notes' => $paymentResult['error'] ?? 'Payment failed',
            ]);

            throw new Exception($paymentResult['error'] ?? 'Payment processing failed');
        });

        // Payment is now committed - process domain registrations outside transaction
        if ($order->isPaid()) {
            try {
                $this->domainRegistrationService->processDomainRegistrations($order, $data['contact_ids']);
                $this->orderService->sendOrderConfirmation($order);
            } catch (Exception $e) {
                // Registration failed but payment succeeded - this is handled by DomainRegistrationService
                // Order status will be updated to 'requires_attention' and notifications sent
                Log::error('Domain registration failed after successful payment', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $order->fresh(['orderItems']);
    }
}
