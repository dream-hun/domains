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
        private PawaPayService $pawaPayService,
    ) {}

    public function processPayment(Order $order, string $paymentMethod, array $paymentData = []): array
    {
        return match ($paymentMethod) {
            'stripe' => $this->processStripePayment($order),
            'pawapay' => $this->processPawaPayPayment($order, $paymentData),
            default => ['success' => false, 'error' => 'Invalid payment method'],
        };
    }

    public function processPawaPayPayment(Order $order, array $paymentData = []): array
    {
        $paymentAttempt = null;

        try {
            if (! $this->isPawaPayConfigured()) {
                return [
                    'success' => false,
                    'error' => 'PawaPay payment is not configured. Please contact support.',
                ];
            }

            $msisdn = mb_trim((string) ($paymentData['msisdn'] ?? ''));
            if ($msisdn === '') {
                return [
                    'success' => false,
                    'error' => 'Phone number (MSISDN) is required for PawaPay payment.',
                ];
            }

            $paymentAttempt = $this->createPaymentAttempt($order, 'pawapay');

            $amount = (float) $order->total_amount;
            $currency = mb_strtoupper($order->currency);
            $pawaPayCurrency = 'RWF';

            if ($currency !== $pawaPayCurrency) {
                try {
                    $amount = CurrencyHelper::convert($amount);
                    $currency = $pawaPayCurrency;
                } catch (Exception $e) {
                    Log::error('Currency conversion to RWF failed for PawaPay payment', [
                        'order_id' => $order->id,
                        'original_currency' => $currency,
                        'original_amount' => $order->total_amount,
                        'error' => $e->getMessage(),
                    ]);

                    return [
                        'success' => false,
                        'error' => 'Unable to convert currency to RWF for PawaPay payment. Please try again or contact support.',
                    ];
                }
            }

            // Resolve MMO provider via PawaPay predict-provider API
            try {
                $predictResult = $this->pawaPayService->predictProvider($msisdn);
                $provider = $predictResult['provider'] ?? ($predictResult[0]['provider'] ?? null);

                if (! $provider) {
                    $this->markPaymentFailed($paymentAttempt, 'Could not determine mobile money provider for this number');

                    return [
                        'success' => false,
                        'error' => 'Could not determine mobile money provider. Please check your phone number.',
                    ];
                }
            } catch (Exception $e) {
                Log::error('PawaPay predictProvider failed', [
                    'msisdn' => $msisdn,
                    'error' => $e->getMessage(),
                ]);

                $this->markPaymentFailed($paymentAttempt, 'Provider lookup failed: '.$e->getMessage());

                return [
                    'success' => false,
                    'error' => 'Unable to verify phone number provider. Please try again.',
                ];
            }

            $depositId = Str::uuid()->toString();

            $depositResponse = $this->pawaPayService->initiateDeposit(
                $depositId,
                (string) (int) round($amount),
                $currency,
                $msisdn,
                $provider
            );

            $depositStatus = $depositResponse['status'] ?? null;

            if ($depositStatus !== 'ACCEPTED') {
                $errorMsg = $depositResponse['rejectionReason']['rejectionMessage']
                    ?? $depositResponse['errorCode']
                    ?? 'Deposit initiation failed';

                $this->markPaymentFailed($paymentAttempt, $errorMsg);

                $this->transactionLogger->logFailure(
                    order: $order,
                    method: 'pawapay',
                    error: 'Failed to initiate PawaPay deposit',
                    details: $errorMsg,
                    payment: $paymentAttempt
                );

                return [
                    'success' => false,
                    'error' => $errorMsg,
                ];
            }

            $paymentAttempt->update([
                'pawapay_deposit_id' => $depositId,
                'metadata' => array_merge($paymentAttempt->metadata ?? [], [
                    'pawapay_response' => $depositResponse,
                    'pawapay_currency' => $currency,
                    'pawapay_amount' => $amount,
                    'pawapay_provider' => $provider,
                    'pawapay_msisdn' => $msisdn,
                ]),
            ]);

            return [
                'success' => true,
                'requires_action' => true,
                'payment_id' => $paymentAttempt->id,
                'deposit_id' => $depositId,
            ];

        } catch (Exception $exception) {
            $this->markPaymentFailed($paymentAttempt, $exception->getMessage(), $exception->getCode());

            $this->transactionLogger->logFailure(
                order: $order,
                method: 'pawapay',
                error: 'Failed to process PawaPay payment',
                details: $exception->getMessage(),
                payment: $paymentAttempt
            );

            return [
                'success' => false,
                'error' => 'Failed to initialize payment. Please try again or contact support.',
            ];
        }
    }

    public function checkPawaPayDepositStatus(Payment $payment): array
    {
        if (empty($payment->pawapay_deposit_id)) {
            Log::warning('PawaPay status check: Missing deposit ID', [
                'payment_id' => $payment->id,
            ]);

            return [
                'success' => false,
                'error' => 'Payment deposit ID not found.',
            ];
        }

        try {
            $response = $this->pawaPayService->checkDepositStatus($payment->pawapay_deposit_id);
            $depositData = is_array($response) && isset($response[0]) ? $response[0] : $response;
            $status = $depositData['status'] ?? null;

            Log::info('PawaPay deposit status check', [
                'payment_id' => $payment->id,
                'deposit_id' => $payment->pawapay_deposit_id,
                'status' => $status,
            ]);

            if ($status === 'COMPLETED') {
                if (! $payment->isSuccessful()) {
                    $payment->update([
                        'status' => 'succeeded',
                        'paid_at' => now(),
                        'metadata' => array_merge($payment->metadata ?? [], [
                            'pawapay_status_response' => $depositData,
                        ]),
                    ]);
                }

                return [
                    'success' => true,
                    'status' => 'succeeded',
                    'payment_id' => $payment->id,
                ];
            }

            if (in_array($status, ['FAILED', 'TIMED_OUT'], true)) {
                if ($payment->isPending()) {
                    $this->markPaymentFailed($payment, 'Deposit '.$status);
                }

                return [
                    'success' => false,
                    'status' => 'failed',
                    'error' => 'Payment '.mb_strtolower($status),
                ];
            }

            return [
                'success' => true,
                'status' => 'pending',
                'payment_id' => $payment->id,
            ];

        } catch (Exception $exception) {
            Log::error('PawaPay deposit status check error', [
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
        } catch (Throwable $exception) {
            $this->markPaymentFailed($paymentAttempt, $exception->getMessage(), $exception->getCode());

            return [
                'success' => false,
                'error' => 'An unexpected error occurred. Please try again or contact support.',
            ];
        }
    }

    /**
     * Validate and potentially convert currency to meet Stripe's minimum amount requirement
     */
    private function validateStripeMinimumAmount(Order $order): array
    {
        $amount = (float) $order->total_amount;
        $currency = mb_strtoupper($order->currency);

        $minUsdAmount = 0.50;

        try {
            $amountInUsd = $currency === 'USD'
                ? $amount
                : CurrencyHelper::convert($amount);

            if ($amountInUsd >= $minUsdAmount) {
                return [
                    'valid' => true,
                    'currency' => $currency,
                    'amount' => $amount,
                ];
            }

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

    private function isPawaPayConfigured(): bool
    {
        return ! empty(config('services.payment.pawapay.token'))
            && ! empty(config('services.payment.pawapay.base_url'));
    }

    private function isStripeConfigured(): bool
    {
        return ! empty(config('services.payment.stripe.publishable_key'))
            && ! empty(config('services.payment.stripe.secret_key'));
    }

    private function createPaymentAttempt(Order $order, string $method): Payment
    {
        $nextAttemptNumber = (int) ($order->payments()->max('attempt_number') ?? 0) + 1;

        $paymentData = [
            'user_id' => $order->user_id,
            'status' => 'pending',
            'payment_method' => $method,
            'amount' => $order->total_amount,
            'currency' => $order->currency,
            'metadata' => [
                'attempt_identifier' => Str::uuid()->toString(),
            ],
            'attempt_number' => $nextAttemptNumber,
            'last_attempted_at' => now(),
        ];

        if ($method === 'stripe') {
            $paymentData['stripe_payment_intent_id'] = Payment::generatePendingIntentId($order, $nextAttemptNumber);
        }

        /** @var Payment */
        return $order->payments()->create($paymentData);
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
