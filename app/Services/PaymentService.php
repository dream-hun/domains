<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Hosting\BillingCycle;
use App\Helpers\CurrencyHelper;
use App\Helpers\StripeHelper;
use App\Models\HostingPlan;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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

            $checkoutSession = $this->createStripeCheckoutSession($order, $paymentAttempt, $validationResult);

            $paymentAttempt->update([
                'stripe_session_id' => $checkoutSession->id,
                'metadata' => array_merge($paymentAttempt->metadata ?? [], [
                    'checkout_url' => $checkoutSession->url,
                ]),
            ]);

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
     * @throws ApiErrorException
     */
    private function createStripeCheckoutSession(Order $order, Payment $payment, array $validationResult): Session
    {
        Stripe::setApiKey(config('services.payment.stripe.secret_key'));

        // Use the potentially converted currency and amount
        $processingCurrency = $validationResult['currency'];
        $processingAmount = $validationResult['amount'];

        $payment->update([
            'currency' => $processingCurrency,
            'amount' => $processingAmount,
            'metadata' => array_merge($payment->metadata ?? [], [
                'original_currency' => $order->currency,
                'original_amount' => $order->total_amount,
                'processing_currency' => $processingCurrency,
                'processing_amount' => $processingAmount,
                'converted' => $validationResult['converted'] ?? false,
            ]),
            'last_attempted_at' => now(),
        ]);

        /** @var User $user */
        $user = $order->user;

        if (! $user->stripe_id) {
            $customer = Customer::create([
                'name' => $user->name,
                'email' => $user->email,
                'metadata' => [
                    'user_id' => (string) $user->id,
                ],
            ]);

            $user->update(['stripe_id' => $customer->id]);
        }

        $successUrl = $this->resolveStripeSuccessUrl($order);
        $cancelUrl = $this->resolveStripeCancelUrl($order);

        // Build line items from order items individually
        $lineItems = [];
        $orderItems = $order->orderItems;

        if ($orderItems->isEmpty()) {
            throw new Exception('Order has no items to process');
        }

        foreach ($orderItems as $orderItem) {
            $displayName = $this->getItemDisplayName($orderItem);
            $period = $this->getItemPeriod($orderItem);

            // Convert item total amount to Stripe format
            $itemStripeAmount = StripeHelper::convertToStripeAmount(
                (float) $orderItem->total_amount,
                $processingCurrency
            );

            $lineItems[] = [
                'price_data' => [
                    'currency' => mb_strtolower((string) $processingCurrency),
                    'product_data' => [
                        'name' => $displayName,
                        'description' => $period,
                    ],
                    'unit_amount' => $itemStripeAmount,
                ],
                'quantity' => 1,
            ];
        }

        $session = Session::create([
            'customer' => $user->stripe_id,
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => sprintf(
                '%s%ssession_id={CHECKOUT_SESSION_ID}',
                $successUrl,
                str_contains($successUrl, '?') ? '&' : '?'
            ),
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'order_id' => (string) $order->id,
                'order_number' => (string) $order->order_number,
                'user_id' => (string) $user->id,
                'payment_id' => (string) $payment->id,
                'original_currency' => (string) $order->currency,
                'original_amount' => (string) $order->total_amount,
            ],
        ]);

        // Store session ID in order
        $order->update(['stripe_session_id' => $session->id]);

        return $session;
    }

    private function resolveStripeSuccessUrl(Order $order): string
    {
        if ($order->type === 'renewal') {
            return route('checkout.stripe.success', ['order' => $order->order_number]);
        }

        return route('payment.success', ['order' => $order]);
    }

    private function resolveStripeCancelUrl(Order $order): string
    {
        if ($order->type === 'renewal') {
            return route('checkout.stripe.cancel', ['order' => $order->order_number]);
        }

        return route('payment.cancel', ['order' => $order]);
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

    /**
     * Get display name for order item (plan name for subscriptions/hosting, domain name for domains)
     */
    private function getItemDisplayName(OrderItem $item): string
    {
        $itemType = $item->domain_type ?? 'registration';
        $itemName = $item->domain_name ?? '';

        // For subscription renewals and hosting, show only plan name
        if (in_array($itemType, ['subscription_renewal', 'hosting'], true)) {
            $metadata = $item->metadata ?? [];
            $hostingPlanId = $metadata['hosting_plan_id'] ?? null;

            if ($hostingPlanId) {
                $plan = HostingPlan::query()->find($hostingPlanId);

                if ($plan && $plan->name) {
                    return $plan->name;
                }
            }

            // Fallback: try to extract plan name from metadata
            $planData = $metadata['plan'] ?? null;

            if ($planData && isset($planData['name']) && $planData['name'] !== 'N/A') {
                return $planData['name'];
            }

            // Last resort: parse the name to extract plan name
            // Format: "domain - Plan Name (Renewal)" or "domain Hosting (cycle)" or "Hosting - Plan Name (Renewal)"
            if ($itemName && str_contains($itemName, ' - ')) {
                // Format: "domain - Plan Name (Renewal)" or "Hosting - Plan Name (Renewal)"
                $parts = explode(' - ', $itemName, 2);
                if (count($parts) === 2) {
                    $planPart = $parts[1];
                    // Remove "(Renewal)" suffix
                    $planPart = preg_replace('/\s*\(Renewal\)\s*$/i', '', $planPart);
                    $planPart = mb_trim($planPart);

                    // Don't return if it's "N/A" or empty
                    if ($planPart && $planPart !== 'N/A') {
                        return $planPart;
                    }
                }
            }

            if ($itemName && str_contains($itemName, ' Hosting (')) {
                // Format: "domain Hosting (cycle)"
                $planName = str_replace(' Hosting (', '', $itemName);
                $planName = preg_replace('/\s*\([^)]*\)\s*$/', '', $planName);
                $planName = mb_trim($planName);

                // Don't return if it's "N/A" or empty
                if ($planName && $planName !== 'N/A') {
                    return $planName;
                }
            }

            // If all else fails and we have a name, try to clean it up
            if ($itemName && $itemName !== 'N/A') {
                // Remove common prefixes like "N/A - " or "Hosting - "
                $cleaned = preg_replace('/^(N\/A|N\/A\s*-\s*|Hosting\s*-\s*)/i', '', $itemName);
                $cleaned = mb_trim($cleaned);

                if ($cleaned && $cleaned !== 'N/A') {
                    return $cleaned;
                }
            }
        }

        // For other item types (domains), return the name as-is, but filter out "N/A"
        if ($itemName && $itemName !== 'N/A') {
            return $itemName;
        }

        // Ultimate fallback
        return 'Item';
    }

    /**
     * Get formatted period for display in Stripe checkout
     */
    private function getItemPeriod(OrderItem $item): string
    {
        $itemType = $item->domain_type ?? 'registration';
        $metadata = $item->metadata ?? [];

        // For subscription renewals, use duration_months or billing_cycle to determine the actual period
        if ($itemType === 'subscription_renewal') {
            // Check duration_months first
            $durationMonths = $metadata['duration_months'] ?? null;

            if (! $durationMonths && $item->quantity) {
                // If duration_months is not set, use quantity (which should be months for subscription renewals)
                $durationMonths = $item->quantity;
            }

            if ($durationMonths) {
                return $this->formatDurationLabel((int) $durationMonths).' renewal';
            }

            // Fallback: try billing_cycle if duration_months is not available
            $billingCycle = $metadata['billing_cycle'] ?? null;
            if ($billingCycle) {
                $billingCycleEnum = BillingCycle::tryFrom($billingCycle);
                if ($billingCycleEnum) {
                    return $this->formatBillingCycleLabel($billingCycleEnum).' renewal';
                }
            }
        }

        // For hosting items, use billing_cycle to determine the period
        if ($itemType === 'hosting') {
            $billingCycle = $metadata['billing_cycle'] ?? null;

            if ($billingCycle) {
                $billingCycleEnum = BillingCycle::tryFrom($billingCycle);

                if ($billingCycleEnum) {
                    return $this->formatBillingCycleLabel($billingCycleEnum);
                }
            }
        }

        // For domain renewals and registrations, use years or quantity as years
        $years = $item->years ?? $item->quantity ?? 1;
        $suffix = ($itemType === 'renewal') ? 'renewal' : 'of registration';

        return $years.' '.Str::plural('year', $years).' '.$suffix;
    }

    /**
     * Format billing cycle enum to readable label
     */
    private function formatBillingCycleLabel(BillingCycle $cycle): string
    {
        return match ($cycle) {
            BillingCycle::Monthly => '1 month',
            BillingCycle::Quarterly => '3 months',
            BillingCycle::SemiAnnually => '6 months',
            BillingCycle::Annually => '1 year',
            BillingCycle::Biennially => '2 years',
            BillingCycle::Triennially => '3 years',
        };
    }

    /**
     * Format duration in months to readable label
     */
    private function formatDurationLabel(int $months): string
    {
        if ($months < 12) {
            return $months.' '.Str::plural('month', $months);
        }

        $years = (int) ($months / 12);

        return $years.' '.Str::plural('year', $years);
    }
}
