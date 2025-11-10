<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\OrderService;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

final class CheckOutController extends Controller
{
    public function index(): Factory|View|RedirectResponse
    {
        if (Cart::isEmpty()) {
            return to_route('cart.index')->with('error', 'Your cart is empty.');
        }

        $cartItems = Cart::getContent();
        $cartTotal = Cart::getTotal();
        $currency = $cartItems->first()->attributes['currency'] ?? 'USD';

        return view('checkout.index', [
            'cartItems' => $cartItems,
            'cartTotal' => $cartTotal,
            'currency' => $currency,
            'stripeKey' => config('services.payment.stripe.publishable_key'),
        ]);
    }

    /**
     * @throws ApiErrorException
     */
    public function stripeRedirect(string $orderNumber): RedirectResponse
    {
        $order = Order::query()->where('order_number', $orderNumber)->firstOrFail();

        // Verify order belongs to current user
        abort_if($order->user_id !== auth()->id(), 403);

        // Get Stripe session and redirect
        if ($order->stripe_session_id) {
            Stripe::setApiKey(config('services.payment.stripe.secret_key'));
            $session = Session::retrieve($order->stripe_session_id);

            return redirect($session->url);
        }

        return to_route('checkout.index')->with('error', 'Payment session not found.');
    }

    /**
     * @throws ApiErrorException
     */
    public function stripeSuccess(string $orderNumber): Factory|View|RedirectResponse
    {
        $order = Order::query()->where('order_number', $orderNumber)->firstOrFail();

        // Verify order belongs to current user
        abort_if($order->user_id !== auth()->id(), 403);

        // Verify payment with Stripe
        if ($order->stripe_session_id) {
            Stripe::setApiKey(config('services.payment.stripe.secret_key'));
            $session = Session::retrieve($order->stripe_session_id);

            if ($session->payment_status === 'paid') {
                $order->update([
                    'payment_status' => 'paid',
                    'processed_at' => now(),
                    'stripe_payment_intent_id' => $session->payment_intent,
                ]);

                $this->markPaymentAttemptSucceeded(
                    $order,
                    $order->stripe_session_id,
                    (string) $session->payment_intent,
                    $session->payment_status
                );

                // Process domain registrations
                $orderService = app(OrderService::class);

                // Get contact ID from checkout session (use same contact for all roles)
                $checkoutData = session('checkout', []);
                $contactId = $checkoutData['selected_contact_id'] ?? $order->user->contacts()->where('is_primary', true)->first()?->id;

                if ($contactId) {
                    $contactIds = [
                        'registrant' => $contactId,
                        'admin' => $contactId,
                        'tech' => $contactId,
                        'billing' => $contactId,
                    ];
                    $orderService->processDomainRegistrations($order, $contactIds);
                }

                $orderService->sendOrderConfirmation($order);

                // Clear cart
                Cart::clear();

                return view('checkout.success', ['order' => $order]);
            }

            $this->markPaymentAttemptFailed(
                $order,
                $order->stripe_session_id,
                $session->last_payment_error->message ?? 'Payment verification failed.'
            );
        }

        return to_route('checkout.index')->with('error', 'Payment verification failed.');
    }

    public function stripeCancel(string $orderNumber): RedirectResponse
    {
        $order = Order::query()->where('order_number', $orderNumber)->firstOrFail();

        // Verify order belongs to current user
        abort_if($order->user_id !== auth()->id(), 403);

        // Update order status
        $order->update([
            'payment_status' => 'cancelled',
            'notes' => 'Payment cancelled by user',
        ]);

        $this->markPaymentAttemptCancelled($order, $order->stripe_session_id);

        return to_route('checkout.index')->with('error', 'Payment was cancelled. You can try again.');
    }

    private function findPaymentAttempt(Order $order, ?string $sessionId): ?Payment
    {
        if (! $sessionId) {
            return null;
        }

        return $order->payments()
            ->where('stripe_session_id', $sessionId)
            ->orderByDesc('attempt_number')
            ->orderByDesc('id')
            ->first();
    }

    private function markPaymentAttemptSucceeded(Order $order, string $sessionId, string $paymentIntentId, string $paymentStatus): void
    {
        $paymentAttempt = $this->findPaymentAttempt($order, $sessionId);

        if (! $paymentAttempt || $paymentAttempt->isSuccessful()) {
            if (! $paymentAttempt instanceof Payment) {
                Log::warning('Unable to locate payment attempt for successful payment', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'session_id' => $sessionId,
                ]);
            }

            return;
        }

        $paymentAttempt->update([
            'status' => 'succeeded',
            'stripe_payment_intent_id' => $paymentIntentId,
            'paid_at' => now(),
            'metadata' => array_merge($paymentAttempt->metadata ?? [], [
                'stripe_payment_status' => $paymentStatus,
            ]),
            'last_attempted_at' => now(),
        ]);
    }

    private function markPaymentAttemptFailed(Order $order, string $sessionId, string $message): void
    {
        $paymentAttempt = $this->findPaymentAttempt($order, $sessionId);

        if (! $paymentAttempt || $paymentAttempt->status === 'failed') {
            return;
        }

        $paymentAttempt->update([
            'status' => 'failed',
            'failure_details' => array_merge($paymentAttempt->failure_details ?? [], [
                'message' => $message,
            ]),
            'last_attempted_at' => now(),
        ]);
    }

    private function markPaymentAttemptCancelled(Order $order, ?string $sessionId): void
    {
        $paymentAttempt = $this->findPaymentAttempt($order, $sessionId);

        if (! $paymentAttempt || $paymentAttempt->status === 'cancelled') {
            return;
        }

        $paymentAttempt->update([
            'status' => 'cancelled',
            'failure_details' => array_merge($paymentAttempt->failure_details ?? [], [
                'message' => 'Payment cancelled by user',
            ]),
            'last_attempted_at' => now(),
        ]);
    }
}
