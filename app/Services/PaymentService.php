<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use DB;
use Exception;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Stripe;

final class PaymentService
{
    public function __construct(
        private readonly TransactionLogger $transactionLogger
    ) {}

    public function processPayment(Order $order, string $paymentMethod): array
    {
        return match ($paymentMethod) {
            'stripe' => $this->processStripePayment($order),
            'account_credit' => $this->processAccountCreditPayment($order),
            'paypal' => $this->processPayPalPayment($order),
            default => ['success' => false, 'error' => 'Invalid payment method'],
        };
    }

    private function processStripePayment(Order $order): array
    {
        try {
            // Validate Stripe configuration
            if (! $this->isStripeConfigured()) {
                return [
                    'success' => false,
                    'error' => 'Stripe payment is not configured. Please contact support.',
                ];
            }

            // Create Stripe Checkout Session instead of direct charge
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
     * Create Stripe Checkout Session
     */
    private function createStripeCheckoutSession(Order $order): Session
    {
        Stripe::setApiKey(config('services.payment.stripe.secret_key'));

        $user = $order->user;

        // Ensure user has Stripe customer ID
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

        // Prepare line items
        $lineItems = [];
        foreach ($order->orderItems as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => mb_strtolower($order->currency),
                    'product_data' => [
                        'name' => $item->domain_name,
                        'description' => "Domain Registration - {$item->years} year(s)",
                    ],
                    'unit_amount' => (int) (round((float) $item->price, 2) * 100), // Convert to smallest currency unit
                ],
                'quantity' => $item->quantity,
            ];
        }

        // Create checkout session
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

    private function processAccountCreditPayment(Order $order): array
    {
        try {
            $user = $order->user;

            // Check if user has sufficient balance
            if (! $user->hasAccountCredit($order->total_amount)) {
                return [
                    'success' => false,
                    'error' => 'Insufficient account credit. Please add funds or use a different payment method.',
                ];
            }

            // Deduct balance within transaction
            DB::transaction(function () use ($user, $order) {
                $user->deductAccountCredit($order->total_amount);

                // Log transaction
                $this->transactionLogger->logSuccess(
                    order: $order,
                    method: 'account_credit',
                    transactionId: 'CREDIT-'.$order->order_number,
                    amount: $order->total_amount
                );
            });

            return [
                'success' => true,
                'transaction_id' => 'CREDIT-'.$order->order_number,
            ];

        } catch (Exception $e) {
            $this->transactionLogger->logFailure(
                order: $order,
                method: 'account_credit',
                error: 'Credit deduction failed',
                details: $e->getMessage()
            );

            return [
                'success' => false,
                'error' => 'Failed to process account credit payment. Please try again.',
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
