<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ProcessPaymentRequest;
use App\Jobs\ProcessDomainRenewalJob;
use App\Models\Order;
use App\Models\Payment;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;

final class CheckoutController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Show checkout page with cart summary
     */
    public function index(): View|RedirectResponse
    {
        $cartItems = Cart::getContent();

        if ($cartItems->isEmpty()) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Your cart is empty.');
        }

        $cartTotal = Cart::getTotal();
        $currency = $cartItems->first()->attributes['currency'] ?? 'USD';

        return view('checkout.index', [
            'cartItems' => $cartItems,
            'cartTotal' => $cartTotal,
            'currency' => $currency,
            'stripeKey' => config('services.stripe.key'),
        ]);
    }

    /**
     * Create Stripe Payment Intent
     */
    public function createPaymentIntent(Request $request): JsonResponse
    {
        try {
            $cartItems = Cart::getContent();

            if ($cartItems->isEmpty()) {
                return response()->json([
                    'error' => 'Cart is empty',
                ], 400);
            }

            $cartTotal = Cart::getTotal();
            $currency = mb_strtolower($cartItems->first()->attributes['currency'] ?? 'USD');

            // Convert amount to cents (Stripe requires amount in smallest currency unit)
            $amountInCents = (int) ($cartTotal * 100);

            // Create or retrieve existing payment intent
            $user = $request->user();

            // Create Payment Intent with idempotency key to prevent duplicate charges
            $idempotencyKey = 'renewal_'.$user->id.'_'.time();

            $paymentIntent = PaymentIntent::create([
                'amount' => $amountInCents,
                'currency' => $currency,
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'metadata' => [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'cart_total' => $cartTotal,
                    'order_type' => 'renewal',
                ],
            ], [
                'idempotency_key' => $idempotencyKey,
            ]);

            Log::info('Stripe Payment Intent created', [
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $amountInCents,
                'currency' => $currency,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
                'paymentIntentId' => $paymentIntent->id,
            ]);

        } catch (ApiErrorException $e) {
            Log::error('Stripe API Error', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id ?? null,
            ]);

            return response()->json([
                'error' => 'Payment processing error: '.$e->getMessage(),
            ], 500);

        } catch (Exception $e) {
            Log::error('Payment Intent creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id ?? null,
            ]);

            return response()->json([
                'error' => 'An unexpected error occurred',
            ], 500);
        }
    }

    /**
     * Process successful payment
     */
    public function success(ProcessPaymentRequest $request): RedirectResponse
    {
        try {
            $paymentIntentId = $request->validated()['payment_intent_id'];
            $user = $request->user();

            // Retrieve payment intent from Stripe
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

            if ($paymentIntent->status !== 'succeeded') {
                return redirect()
                    ->route('checkout.cancel')
                    ->with('error', 'Payment was not successful.');
            }

            // Get cart items before clearing
            $cartItems = Cart::getContent();
            $cartTotal = Cart::getTotal();
            $currency = $cartItems->first()->attributes['currency'] ?? 'USD';

            DB::beginTransaction();

            try {
                // Create Payment record
                $payment = Payment::create([
                    'user_id' => $user->id,
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'stripe_charge_id' => $paymentIntent->latest_charge ?? null,
                    'amount' => $cartTotal,
                    'currency' => $currency,
                    'status' => 'succeeded',
                    'payment_method' => $paymentIntent->payment_method ?? 'card',
                    'metadata' => [
                        'cart_items' => $cartItems->toArray(),
                    ],
                    'paid_at' => now(),
                ]);

                // Create Order record
                $orderNumber = $this->generateOrderNumber();

                $order = Order::create([
                    'user_id' => $user->id,
                    'payment_id' => $payment->id,
                    'order_number' => $orderNumber,
                    'type' => 'renewal',
                    'subtotal' => $cartTotal,
                    'tax' => 0,
                    'total_amount' => $cartTotal,
                    'currency' => $currency,
                    'status' => 'processing',
                    'payment_status' => 'paid',
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'billing_email' => $user->email,
                    'billing_name' => $user->first_name.' '.$user->last_name,
                    'items' => $cartItems->toArray(),
                    'processed_at' => now(),
                ]);

                DB::commit();

                // Clear cart after successful order creation
                Cart::clear();

                // Dispatch job to process domain renewals
                ProcessDomainRenewalJob::dispatch($order);

                Log::info('Order created successfully', [
                    'order_id' => $order->id,
                    'order_number' => $orderNumber,
                    'payment_id' => $payment->id,
                    'user_id' => $user->id,
                ]);

                return redirect()
                    ->route('checkout.success')
                    ->with('success', 'Payment successful! Your domain renewal is being processed.')
                    ->with('order_number', $orderNumber);

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            Log::error('Payment processing failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id ?? null,
                'payment_intent_id' => $request->validated()['payment_intent_id'] ?? null,
            ]);

            return redirect()
                ->route('checkout.cancel')
                ->with('error', 'An error occurred while processing your payment.');
        }
    }

    /**
     * Handle payment cancellation
     */
    public function cancel(): View
    {
        return view('checkout.cancel');
    }

    /**
     * Generate a unique order number
     */
    private function generateOrderNumber(): string
    {
        return 'ORD-'.mb_strtoupper(Str::random(10));
    }
}
