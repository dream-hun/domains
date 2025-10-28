<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use Exception;
use Illuminate\Support\Facades\DB;

final class CheckoutService
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly OrderService $orderService
    ) {}

    public function processCheckout(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            // Create order
            $order = $this->orderService->createOrder([
                'user_id' => $data['user_id'],
                'currency' => $data['currency'],
                'payment_method' => $data['payment_method'],
                'cart_items' => $data['cart_items'],
                'contact_id' => $data['contact_id'],
            ]);

            // Process payment
            $paymentResult = $this->paymentService->processPayment(
                $order,
                $data['payment_method']
            );

            if ($paymentResult['success']) {
                // Check if payment requires action (redirect to Stripe Checkout)
                if (isset($paymentResult['requires_action']) && $paymentResult['requires_action']) {
                    return $order; // Return order with checkout_url for redirect
                }

                // Payment completed immediately (e.g., account credit)
                $order->update([
                    'payment_status' => 'paid',
                    'processed_at' => now(),
                    'stripe_payment_intent_id' => $paymentResult['transaction_id'] ?? null,
                ]);

                // Trigger domain registration
                $this->orderService->processDomainRegistrations($order);

                // Send confirmation email
                $this->orderService->sendOrderConfirmation($order);
            } else {
                $order->update([
                    'payment_status' => 'failed',
                    'notes' => $paymentResult['error'] ?? 'Payment failed',
                ]);

                throw new Exception($paymentResult['error'] ?? 'Payment processing failed');
            }

            return $order->fresh(['orderItems']);
        });
    }
}
