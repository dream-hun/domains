<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\CurrencyHelper;
use App\Models\Order;
use App\Models\Payment;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class PaymentService
{
    public function __construct(
        private TransactionLogger $transactionLogger,
        private StripeCheckoutService $stripeCheckoutService
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
        $paymentAttempt = null;

        try {
            $validationResult = $this->validateStripeMinimumAmount($order);
            if (! $validationResult['valid']) {
                return [
                    'success' => false,
                    'error' => $validationResult['message'],
                ];
            }

            if (! $this->isStripeConfigured()) {
                return [
                    'success' => false,
                    'error' => 'Stripe payment is not configured. Please contact support.',
                ];
            }

            $paymentAttempt = $this->createPaymentAttempt($order, 'stripe');

            $checkoutSession = $this->stripeCheckoutService->createSessionFromOrder($order, $paymentAttempt, $validationResult);

            return [
                'success' => true,
                'requires_action' => true,
                'checkout_url' => $checkoutSession->url,
                'session_id' => $checkoutSession->id,
                'payment_id' => $paymentAttempt->id,
            ];

        } catch (Exception $exception) {
            $this->markPaymentFailed($paymentAttempt, $exception->getMessage(), $exception->getCode());

            $this->transactionLogger->logFailure(
                order: $order,
                method: 'stripe',
                error: 'Failed to create checkout session',
                details: $exception->getMessage(),
                payment: $paymentAttempt
            );

            return [
                'success' => false,
                'error' => 'Failed to initialize payment. Please try again or contact support.',
            ];
        }
    }

    /**
     * Validate and potentially convert currency to meet Stripe's minimum amount requirement
     * Stripe requires minimum 50 cents USD equivalent
     */
    private function validateStripeMinimumAmount(Order $order): array
    {
        $amount = (float) $order->total_amount;
        $currency = mb_strtoupper($order->currency);

        // Stripe's minimum is 50 cents USD
        $minUsdAmount = 0.50;

        try {
            // Convert order amount to USD to check against Stripe's minimum
            $amountInUsd = $currency === 'USD'
                ? $amount
                : CurrencyHelper::convert($amount, $currency, 'USD');

            // If amount meets the minimum, use original currency
            if ($amountInUsd >= $minUsdAmount) {
                return [
                    'valid' => true,
                    'currency' => $currency,
                    'amount' => $amount,
                ];
            }

            // Amount is below minimum - automatically convert to USD
            return [
                'valid' => true,
                'currency' => 'USD',
                'amount' => $amountInUsd,
                'converted' => true,
            ];

        } catch (Exception $exception) {
            Log::error('Currency conversion failed for Stripe validation', [
                'order_id' => $order->id,
                'currency' => $currency,
                'amount' => $amount,
                'error' => $exception->getMessage(),
            ]);

            return [
                'valid' => false,
                'message' => 'Unable to process payment in '.$currency.'. Please try again or contact support.',
            ];
        }
    }

    private function processPayPalPayment(Order $order): array
    {
        $paymentAttempt = $this->createPaymentAttempt($order, 'paypal');

        Log::warning('PayPal payment attempted but not implemented', [
            'order_id' => $order->id,
        ]);

        $this->markPaymentFailed($paymentAttempt, 'PayPal integration not yet implemented');

        $this->transactionLogger->logFailure(
            order: $order,
            method: 'paypal',
            error: 'PayPal integration not yet implemented',
            payment: $paymentAttempt
        );

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

    private function createPaymentAttempt(Order $order, string $method): Payment
    {
        $nextAttemptNumber = (int) ($order->payments()->max('attempt_number') ?? 0) + 1;

        /** @var Payment */
        return $order->payments()->create([
            'user_id' => $order->user_id,
            'status' => 'pending',
            'payment_method' => $method,
            'amount' => $order->total_amount,
            'currency' => $order->currency,
            'metadata' => [
                'attempt_identifier' => Str::uuid()->toString(),
            ],
            'attempt_number' => $nextAttemptNumber,
            'stripe_payment_intent_id' => Payment::generatePendingIntentId($order, $nextAttemptNumber),
            'last_attempted_at' => now(),
        ]);
    }

    private function markPaymentFailed(?Payment $payment, string $message, int|string|null $code = null): void
    {
        if (! $payment instanceof Payment) {
            Log::warning('Attempted to mark payment as failed but no payment record was available', [
                'message' => $message,
                'code' => $code,
            ]);

            return;
        }

        $payment->update([
            'status' => 'failed',
            'failure_details' => array_merge($payment->failure_details ?? [], [
                'message' => $message,
                'code' => $code,
            ]),
            'last_attempted_at' => now(),
        ]);
    }
}
