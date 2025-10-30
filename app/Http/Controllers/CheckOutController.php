<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

final class CheckOutController extends Controller
{
    public function index(): Factory|View|RedirectResponse
    {
        if (Cart::isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        return view('checkout.index');
    }

    /**
     * @throws ApiErrorException
     */
    public function stripeRedirect(string $orderNumber): RedirectResponse
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        // Verify order belongs to current user
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        // Get Stripe session and redirect
        if ($order->stripe_session_id) {
            Stripe::setApiKey(config('services.payment.stripe.secret_key'));
            $session = Session::retrieve($order->stripe_session_id);

            return redirect($session->url);
        }

        return redirect()->route('checkout.index')->with('error', 'Payment session not found.');
    }

    /**
     * @throws ApiErrorException
     */
    public function stripeSuccess(string $orderNumber): Factory|View|RedirectResponse
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        // Verify order belongs to current user
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

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

                // Process domain registrations
                $orderService = app(OrderService::class);
                $orderService->processDomainRegistrations($order);
                $orderService->sendOrderConfirmation($order);

                // Clear cart
                Cart::clear();

                return view('checkout.success', ['order' => $order]);
            }
        }

        return redirect()->route('checkout.index')->with('error', 'Payment verification failed.');
    }

    public function stripeCancel(string $orderNumber): RedirectResponse
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        // Verify order belongs to current user
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        // Update order status
        $order->update([
            'payment_status' => 'cancelled',
            'notes' => 'Payment cancelled by user',
        ]);

        return redirect()->route('checkout.index')->with('error', 'Payment was cancelled. You can try again.');
    }
}
