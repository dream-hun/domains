<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use Exception;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Exceptions\IncompletePayment;

final class PaymentService
{
    public function processPayment(Order $order, string $paymentMethod): array
    {
        return match ($paymentMethod) {
            'stripe' => $this->processStripePayment($order),
            'paypal' => $this->processPayPalPayment($order),
            default => ['success' => false, 'error' => 'Invalid payment method'],
        };
    }

    private function processStripePayment(Order $order): array
    {
        try {
            $user = $order->user;

            // Create or retrieve Stripe customer
            if (! $user->hasStripeId()) {
                $user->createAsStripeCustomer();
            }

            // Get default payment method
            $paymentMethod = $user->defaultPaymentMethod();

            if (! $paymentMethod) {
                return [
                    'success' => false,
                    'error' => 'No payment method found. Please add a payment method.',
                ];
            }

            // Create payment intent
            $payment = $user->charge(
                (int) ($order->total_amount * 100), // Convert to cents
                $paymentMethod->id,
                [
                    'currency' => mb_strtolower($order->currency),
                    'description' => "Order {$order->order_number}",
                    'metadata' => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                    ],
                ]
            );

            Log::info('Stripe payment processed', [
                'order_id' => $order->id,
                'payment_intent' => $payment->id,
            ]);

            return [
                'success' => true,
                'transaction_id' => $payment->id,
            ];
        } catch (IncompletePayment $e) {
            Log::error('Stripe payment incomplete', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Payment requires additional authentication. Please try again.',
            ];
        } catch (Exception $e) {
            Log::error('Stripe payment failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function processPayPalPayment(Order $order): array
    {
        // PayPal integration would go here
        // For now, return placeholder
        Log::warning('PayPal payment attempted but not implemented', [
            'order_id' => $order->id,
        ]);

        return [
            'success' => false,
            'error' => 'PayPal integration not yet implemented',
        ];
    }
}
