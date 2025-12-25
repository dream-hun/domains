<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\CurrencyHelper;
use App\Models\Order;
use App\Models\Payment;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final readonly class PaymentService
{
    public function __construct(
        private TransactionLogger $transactionLogger,
        private StripeCheckoutService $stripeCheckoutService,
        private KPayService $kPayService,
    ) {}

    public function processPayment(Order $order, string $paymentMethod, array $paymentData = []): array
    {
        return match ($paymentMethod) {
            'stripe' => $this->processStripePayment($order),
            'kpay' => $this->processKPayPayment($order, $paymentData),
            default => ['success' => false, 'error' => 'Invalid payment method'],
        };
    }

    public function processKPayPayment(Order $order, array $paymentData = []): array
    {
        $paymentAttempt = null;

        try {
            if (! $this->isKPayConfigured()) {
                return [
                    'success' => false,
                    'error' => 'KPay payment is not configured. Please contact support.',
                ];
            }

            // Validate required KPay fields
            $msisdn = mb_trim((string) ($paymentData['msisdn'] ?? ''));
            if (empty($msisdn)) {
                return [
                    'success' => false,
                    'error' => 'Phone number (MSISDN) is required for KPay payment.',
                ];
            }

            if (empty($paymentData['pmethod'])) {
                return [
                    'success' => false,
                    'error' => 'Payment method type is required.',
                ];
            }

            $paymentAttempt = $this->createPaymentAttempt($order, 'kpay');

            // Convert currency to RWF if needed (KPay primarily uses RWF)
            $amount = (float) $order->total_amount;
            $currency = mb_strtoupper($order->currency);
            $kpayCurrency = 'RWF';

            if ($currency !== $kpayCurrency) {
                try {
                    $amount = CurrencyHelper::convert($amount, $currency, $kpayCurrency);
                    $currency = $kpayCurrency;
                } catch (Exception $e) {
                    Log::warning('Currency conversion to RWF failed, using original currency', [
                        'order_id' => $order->id,
                        'original_currency' => $currency,
                        'original_amount' => $order->total_amount,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Generate reference ID (order number + payment attempt number)
            $refId = $order->order_number.'-'.$paymentAttempt->attempt_number;

            // Prepare payment data for KPay
            // Ensure all required fields are present and not empty
            $kpayPaymentData = [
                'msisdn' => mb_trim((string) $msisdn),
                'email' => mb_trim((string) ($order->billing_email ?? $order->user->email ?? '')),
                'details' => 'Order '.$order->order_number,
                'refid' => mb_trim((string) $refId), // Use 'refid' not 'ref_id' for KPay API
                'amount' => (int) round($amount), // KPay expects integer amount
                'currency' => mb_strtoupper(mb_trim((string) $currency)),
                'cname' => mb_trim((string) ($order->billing_name ?? $order->user->name ?? '')),
                'cnumber' => mb_trim((string) $msisdn), // Use MSISDN as contact number
                'pmethod' => mb_trim((string) ($paymentData['pmethod'] ?? 'mobile_money')),
                'returl' => route('payment.kpay.success', $order),
                'redirecturl' => route('payment.kpay.success', $order),
            ];

            // Validate all required fields are present
            $requiredFields = ['msisdn', 'email', 'details', 'refid', 'amount', 'cname', 'pmethod'];
            $missingFields = [];
            foreach ($requiredFields as $field) {
                $value = $kpayPaymentData[$field] ?? null;
                if (empty($value) && $value !== 0 && $value !== '0') {
                    $missingFields[] = $field;
                }
            }

            if (! empty($missingFields)) {
                throw new Exception('Missing required KPay fields: '.implode(', ', $missingFields));
            }

            // Call KPay service to initiate payment
            $kpayResponse = $this->kPayService->initiatePayment($kpayPaymentData);

            if (! $kpayResponse['success']) {
                $this->markPaymentFailed($paymentAttempt, $kpayResponse['error'] ?? 'Payment initiation failed');

                $this->transactionLogger->logFailure(
                    order: $order,
                    method: 'kpay',
                    error: 'Failed to initiate KPay payment',
                    details: $kpayResponse['error'] ?? 'Unknown error',
                    payment: $paymentAttempt
                );

                return [
                    'success' => false,
                    'error' => $kpayResponse['error'] ?? 'Failed to initialize payment. Please try again.',
                ];
            }

            // Extract transaction details from response
            $responseData = $kpayResponse['data'] ?? [];
            $transactionId = $responseData['tid'] ?? $responseData['transaction_id'] ?? null;

            // Update payment attempt with KPay transaction details
            $paymentAttempt->update([
                'kpay_transaction_id' => $transactionId,
                'kpay_ref_id' => $refId,
                'metadata' => array_merge($paymentAttempt->metadata ?? [], [
                    'kpay_response' => $responseData,
                    'kpay_currency' => $currency,
                    'kpay_amount' => $amount,
                ]),
            ]);

            // Check if KPay returns a redirect URL
            $redirectUrl = $responseData['redirecturl'] ?? $responseData['redirect_url'] ?? null;

            if ($redirectUrl) {
                return [
                    'success' => true,
                    'requires_action' => true,
                    'redirect_url' => $redirectUrl,
                    'payment_id' => $paymentAttempt->id,
                    'transaction_id' => $transactionId,
                    'ref_id' => $refId,
                ];
            }

            // If no redirect URL, payment might be processed synchronously
            // Check status immediately
            if ($transactionId) {
                $statusResponse = $this->kPayService->checkPaymentStatus($transactionId, $refId);

                if ($statusResponse['success']) {
                    $statusData = $statusResponse['data'] ?? [];
                    $paymentStatus = $statusData['status'] ?? $statusData['payment_status'] ?? 'pending';

                    if ($paymentStatus === 'success' || $paymentStatus === 'completed') {
                        $paymentAttempt->update([
                            'status' => 'succeeded',
                            'paid_at' => now(),
                        ]);

                        return [
                            'success' => true,
                            'payment_id' => $paymentAttempt->id,
                            'transaction_id' => $transactionId,
                            'ref_id' => $refId,
                        ];
                    }
                }
            }

            // Payment is pending - return status check endpoint
            return [
                'success' => true,
                'requires_action' => true,
                'payment_id' => $paymentAttempt->id,
                'transaction_id' => $transactionId,
                'ref_id' => $refId,
                'status_check_url' => route('payment.kpay.status', $paymentAttempt),
            ];

        } catch (Exception $exception) {
            $this->markPaymentFailed($paymentAttempt, $exception->getMessage(), $exception->getCode());

            $this->transactionLogger->logFailure(
                order: $order,
                method: 'kpay',
                error: 'Failed to process KPay payment',
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
     * Check KPay payment status
     */
    public function checkKPayPaymentStatus(Payment $payment): array
    {
        if (empty($payment->kpay_transaction_id) || empty($payment->kpay_ref_id)) {
            return [
                'success' => false,
                'error' => 'Payment transaction details not found.',
            ];
        }

        try {
            $statusResponse = $this->kPayService->checkPaymentStatus(
                $payment->kpay_transaction_id,
                $payment->kpay_ref_id
            );

            if (! $statusResponse['success']) {
                return [
                    'success' => false,
                    'error' => $statusResponse['error'] ?? 'Failed to check payment status.',
                ];
            }

            $statusData = $statusResponse['data'] ?? [];
            $paymentStatus = $statusData['status'] ?? $statusData['payment_status'] ?? 'pending';

            // Update payment status based on KPay response
            if ($paymentStatus === 'success' || $paymentStatus === 'completed') {
                if (! $payment->isSuccessful()) {
                    $payment->update([
                        'status' => 'succeeded',
                        'paid_at' => now(),
                        'metadata' => array_merge($payment->metadata ?? [], [
                            'kpay_status_response' => $statusData,
                        ]),
                    ]);
                }

                return [
                    'success' => true,
                    'status' => 'succeeded',
                    'payment_id' => $payment->id,
                ];
            }

            if ($paymentStatus === 'failed' || $paymentStatus === 'error') {
                if ($payment->isPending()) {
                    $this->markPaymentFailed($payment, $statusData['statusdesc'] ?? 'Payment failed');
                }

                return [
                    'success' => false,
                    'status' => 'failed',
                    'error' => $statusData['statusdesc'] ?? 'Payment failed',
                ];
            }

            // Still pending
            return [
                'success' => true,
                'status' => 'pending',
                'payment_id' => $payment->id,
            ];

        } catch (Exception $exception) {
            Log::error('KPay status check error', [
                'payment_id' => $payment->id,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'An error occurred while checking payment status.',
            ];
        }
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
        } catch (Throwable $e) {
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

    /**
     * Validate KPay configuration
     */
    private function isKPayConfigured(): bool
    {
        return ! empty(config('services.payment.kpay.base_url'))
            && ! empty(config('services.payment.kpay.username'))
            && ! empty(config('services.payment.kpay.password'))
            && ! empty(config('services.payment.kpay.retailer_id'));
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
