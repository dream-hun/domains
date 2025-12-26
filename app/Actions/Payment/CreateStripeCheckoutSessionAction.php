<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Actions\Order\CreateOrderFromCartAction;
use App\Helpers\StripeHelper;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\CartPriceConverter;
use App\Services\OrderItemFormatterService;
use Darryldecode\Cart\CartCollection;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Throwable;

final readonly class CreateStripeCheckoutSessionAction
{
    public function __construct(
        private CreateOrderFromCartAction $createOrderAction,
        private CartPriceConverter $cartPriceConverter,
        private OrderItemFormatterService $orderItemFormatter
    ) {}

    /**
     * Create Stripe checkout session from cart
     *
     * @param  array<string, int|null>  $contactIds
     * @param  array<string, mixed>|null  $billingData
     * @param  array<string, mixed>|null  $coupon
     * @return array{order: Order, session: Session, url: string}
     *
     * @throws ApiErrorException|Throwable
     */
    public function handle(
        User $user,
        CartCollection $cartItems,
        string $currency,
        array $contactIds = [],
        ?array $billingData = null,
        ?array $coupon = null,
        float $discountAmount = 0.0
    ): array {
        Stripe::setApiKey(config('services.payment.stripe.secret_key'));

        return DB::transaction(function () use ($user, $cartItems, $currency, $contactIds, $billingData, $coupon, $discountAmount): array {
            $order = $this->createOrderAction->handle(
                $user,
                $cartItems,
                $currency,
                'stripe',
                $contactIds,
                $billingData,
                $coupon,
                $discountAmount
            );

            $nextAttempt = (int) ($order->payments()->max('attempt_number') ?? 0) + 1;
            $payment = Payment::query()->create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'status' => 'pending',
                'payment_method' => 'stripe',
                'amount' => $order->total_amount,
                'currency' => $currency,
                'metadata' => [
                    'order_type' => $order->type,
                ],
                'attempt_number' => $nextAttempt,
                'stripe_payment_intent_id' => Payment::generatePendingIntentId($order, $nextAttempt),
                'last_attempted_at' => now(),
            ]);

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

            $lineItems = [];
            foreach ($cartItems as $item) {
                try {
                    $itemTotal = $this->cartPriceConverter->calculateItemTotal($item, $currency);
                } catch (Exception $exception) {
                    Log::error('Failed to convert cart item price for Stripe', [
                        'item_id' => $item->id,
                        'currency' => $currency,
                        'error' => $exception->getMessage(),
                    ]);
                    throw $exception;
                }

                $stripeAmount = StripeHelper::convertToStripeAmount($itemTotal, $currency);

                $displayName = $this->orderItemFormatter->getCartItemDisplayName($item);
                $period = $this->orderItemFormatter->getCartItemPeriod($item);

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

            $session = Session::create([
                'customer' => (string) $user->stripe_id,
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => route('payment.success', $order).'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('payment.cancel', $order).'?session_id={CHECKOUT_SESSION_ID}',
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'order_number' => (string) $order->order_number,
                    'user_id' => (string) $user->id,
                    'payment_id' => (string) $payment->id,
                ],
            ]);

            $order->update([
                'stripe_session_id' => $session->id,
                'stripe_payment_intent_id' => $session->payment_intent ?? null,
            ]);
            $payment->update([
                'stripe_session_id' => $session->id,
                'metadata' => array_merge($payment->metadata ?? [], [
                    'stripe_checkout_url' => $session->url,
                    'stripe_payment_status' => $session->payment_status ?? 'open',
                ]),
            ]);

            Cart::clear();

            return [
                'order' => $order,
                'session' => $session,
                'url' => $session->url,
            ];
        });
    }
}
