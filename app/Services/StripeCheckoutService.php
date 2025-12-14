<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\StripeHelper;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Exception;
use Illuminate\Support\Collection;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

final readonly class StripeCheckoutService
{
    public function __construct(
        private OrderItemFormatterService $formatter
    ) {}

    /**
     * Create Stripe Checkout Session from cart items
     *
     * @param  Collection<int, object>  $cartItems
     *
     * @throws ApiErrorException
     */
    public function createSessionFromCart(Order $order, Collection $cartItems, Payment $payment, ?string $successUrl = null, ?string $cancelUrl = null): Session
    {
        Stripe::setApiKey(config('services.payment.stripe.secret_key'));

        $user = $this->ensureStripeCustomer($order->user);

        $lineItems = $this->buildLineItemsFromCart($cartItems, $order->currency);

        $successUrl ??= route('checkout.success', ['order' => $order->order_number]).'?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl ??= route('checkout.cancel').'?order='.$order->order_number;

        $session = $this->createSession($order, $user, $payment, $lineItems, $successUrl, $cancelUrl);

        $this->updatePaymentWithSession($payment, $session);

        return $session;
    }

    /**
     * Create Stripe Checkout Session from order items
     *
     * @throws ApiErrorException
     */
    public function createSessionFromOrder(Order $order, Payment $payment, array $validationResult, ?string $successUrl = null, ?string $cancelUrl = null): Session
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

        $user = $this->ensureStripeCustomer($order->user);

        $orderItems = $order->orderItems;

        throw_if($orderItems->isEmpty(), Exception::class, 'Order has no items to process');

        $lineItems = $this->buildLineItemsFromOrderItems($orderItems, $processingCurrency);

        $successUrl ??= $this->resolveStripeSuccessUrl($order);
        $cancelUrl ??= $this->resolveStripeCancelUrl($order);

        $session = $this->createSession($order, $user, $payment, $lineItems, $successUrl, $cancelUrl, $processingCurrency);

        // Store session ID in order and payment attempt
        $order->update(['stripe_session_id' => $session->id]);
        $payment->update(['stripe_session_id' => $session->id]);

        return $session;
    }

    /**
     * Ensure user has a Stripe customer ID, create if needed
     */
    private function ensureStripeCustomer(User $user): User
    {
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

        return $user;
    }

    /**
     * Build line items from cart items
     *
     * @param  Collection<int, object>  $cartItems
     * @return array<int, array<string, mixed>>
     */
    private function buildLineItemsFromCart(Collection $cartItems, string $currency): array
    {
        $lineItems = [];

        foreach ($cartItems as $item) {
            $itemPrice = $item->getPriceSum();
            $stripeAmount = StripeHelper::convertToStripeAmount(
                $itemPrice,
                $currency
            );

            $displayName = $this->formatter->getItemDisplayName($item);
            $period = $this->formatter->getItemPeriod($item);

            $lineItems[] = [
                'price_data' => [
                    'currency' => mb_strtolower($currency),
                    'product_data' => [
                        'name' => $displayName,
                        'description' => $period,
                    ],
                    'unit_amount' => $stripeAmount,
                ],
                'quantity' => 1,
            ];
        }

        return $lineItems;
    }

    /**
     * Build line items from OrderItem models
     *
     * @param  Collection<int, OrderItem>  $orderItems
     * @return array<int, array<string, mixed>>
     */
    private function buildLineItemsFromOrderItems(Collection $orderItems, string $currency): array
    {
        $lineItems = [];

        foreach ($orderItems as $orderItem) {
            $displayName = $this->formatter->getItemDisplayName($orderItem);
            $period = $this->formatter->getItemPeriod($orderItem);

            // Convert item total amount to Stripe format
            $itemStripeAmount = StripeHelper::convertToStripeAmount(
                (float) $orderItem->total_amount,
                $currency
            );

            $lineItems[] = [
                'price_data' => [
                    'currency' => mb_strtolower($currency),
                    'product_data' => [
                        'name' => $displayName,
                        'description' => $period,
                    ],
                    'unit_amount' => $itemStripeAmount,
                ],
                'quantity' => 1,
            ];
        }

        return $lineItems;
    }

    /**
     * Create Stripe Checkout Session
     *
     * @param  array<int, array<string, mixed>>  $lineItems
     *
     * @throws ApiErrorException
     */
    private function createSession(
        Order $order,
        User $user,
        Payment $payment,
        array $lineItems,
        string $successUrl,
        string $cancelUrl,
        ?string $currency = null
    ): Session {
        $metadata = [
            'order_id' => (string) $order->id,
            'order_number' => (string) $order->order_number,
            'user_id' => (string) $user->id,
            'payment_id' => (string) $payment->id,
        ];

        // Add order type if it's renewal
        if ($order->type === 'renewal') {
            $metadata['order_type'] = 'renewal';
        }

        // Add currency info if different from order currency
        if ($currency && $currency !== $order->currency) {
            $metadata['original_currency'] = (string) $order->currency;
            $metadata['original_amount'] = (string) $order->total_amount;
        }

        return Session::create([
            'customer' => $user->stripe_id,
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => str_contains($successUrl, '?') ? $successUrl.'&session_id={CHECKOUT_SESSION_ID}' : $successUrl.'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Update payment record with session information
     */
    private function updatePaymentWithSession(Payment $payment, Session $session): void
    {
        $payment->update([
            'stripe_session_id' => $session->id,
            'metadata' => array_merge($payment->metadata ?? [], [
                'stripe_checkout_url' => $session->url,
                'stripe_payment_status' => $session->payment_status ?? 'open',
            ]),
            'last_attempted_at' => now(),
        ]);
    }

    /**
     * Resolve Stripe success URL based on order type
     */
    private function resolveStripeSuccessUrl(Order $order): string
    {
        if ($order->type === 'renewal') {
            return route('checkout.stripe.success', ['order' => $order->order_number]);
        }

        return route('payment.success', ['order' => $order]);
    }

    /**
     * Resolve Stripe cancel URL based on order type
     */
    private function resolveStripeCancelUrl(Order $order): string
    {
        if ($order->type === 'renewal') {
            return route('checkout.stripe.cancel', ['order' => $order->order_number]);
        }

        return route('payment.cancel', ['order' => $order]);
    }
}
