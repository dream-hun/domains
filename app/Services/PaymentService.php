<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\StripeHelper;
use App\Models\Order;
use Exception;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

final readonly class PaymentService
{
    public function __construct(
        private TransactionLogger $transactionLogger
    ) {}

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

            if (! $this->isStripeConfigured()) {
                return [
                    'success' => false,
                    'error' => 'Stripe payment is not configured. Please contact support.',
                ];
            }

            $checkoutSession = $this->createStripeCheckoutSession($order);

            return [
                'success' => true,
                'requires_action' => true,
                'checkout_url' => $checkoutSession->url,
                'session_id' => $checkoutSession->id,
            ];

        } catch (Exception $e) {
            $this->transactionLogger->logFailure(
                order: $order,
                method: 'stripe',
                error: 'Failed to create checkout session',
                details: $e->getMessage()
            );

            return [
                'success' => false,
                'error' => 'Failed to initialize payment. Please try again or contact support.',
            ];
        }
    }

    /**
     * @throws ApiErrorException
     */
    private function createStripeCheckoutSession(Order $order): Session
    {
        Stripe::setApiKey(config('services.payment.stripe.secret_key'));

        $user = $order->user;

        if (! $user->stripe_id) {
            $customer = Customer::create([
                'name' => $user->name,
                'email' => $user->email,
                'metadata' => [
                    'user_id' => $user->id,
                ],
            ]);

            $user->update(['stripe_id' => $customer->id]);
        }

        // Build description from order items
        $itemDescriptions = $order->orderItems->map(function ($item) {
            return "{$item->domain_name} ({$item->years} year(s))";
        })->join(', ');

        // Use order total_amount which already includes any discounts
        $stripeAmount = StripeHelper::convertToStripeAmount(
            (float) $order->total_amount,
            $order->currency
        );

        $lineItems = [
            [
                'price_data' => [
                    'currency' => mb_strtolower($order->currency),
                    'product_data' => [
                        'name' => "Order {$order->order_number}",
                        'description' => $itemDescriptions,
                    ],
                    'unit_amount' => $stripeAmount,
                ],
                'quantity' => 1,
            ],
        ];

        $session = Session::create([
            'customer' => $user->stripe_id,
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => route('checkout.stripe.success', ['order' => $order->order_number]).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('checkout.stripe.cancel', ['order' => $order->order_number]),
            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $user->id,
            ],
        ]);

        // Store session ID in order
        $order->update(['stripe_session_id' => $session->id]);

        return $session;
    }

    private function processPayPalPayment(Order $order): array
    {

        Log::warning('PayPal payment attempted but not implemented', [
            'order_id' => $order->id,
        ]);

        return [
            'success' => false,
            'error' => 'PayPal integration not yet implemented',
        ];
    }

    /**
     * Validate Stripe configuration
     */
    private function isStripeConfigured(): bool
    {
        return ! empty(config('services.payment.stripe.publishable_key'))
            && ! empty(config('services.payment.stripe.secret_key'));
    }

    /**
     * Get user-friendly error message from Stripe error code
     */
    private function getStripeErrorMessage(string $code): string
    {
        return match ($code) {
            'card_declined' => 'Your card was declined. Please try a different payment method.',
            'insufficient_funds' => 'Your card has insufficient funds. Please try a different payment method.',
            'expired_card' => 'Your card has expired. Please use a different payment method.',
            'incorrect_cvc' => 'The card security code is incorrect. Please check and try again.',
            'processing_error' => 'An error occurred while processing your card. Please try again.',
            'rate_limit' => 'Too many requests. Please wait a moment and try again.',
            default => 'Payment failed. Please check your card details and try again.',
        };
    }
}
