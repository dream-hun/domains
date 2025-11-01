<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Helpers\StripeHelper;
use App\Jobs\ProcessDomainRenewalJob;
use App\Models\Order;
use App\Services\TransactionLogger;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

final class CheckoutController extends Controller
{
    public function __construct(
        private readonly TransactionLogger $transactionLogger
    ) {
        Stripe::setApiKey(config('services.payment.stripe.secret_key'));
    }

    /**
     * Show checkout page - immediately create session and redirect to Stripe
     */
    public function index(): View|RedirectResponse
    {
        $cartItems = Cart::getContent();

        if ($cartItems->isEmpty()) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Your cart is empty.');
        }

        try {
            // Create order first
            $orderNumber = $this->generateOrderNumber();
            $cartTotal = Cart::getTotal();
            $currency = $cartItems->first()->attributes['currency'] ?? 'USD';
            $user = auth()->user();

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => $orderNumber,
                'type' => 'renewal',
                'subtotal' => $cartTotal,
                'tax' => 0,
                'total_amount' => $cartTotal,
                'currency' => $currency,
                'status' => 'pending',
                'payment_status' => 'pending',
                'billing_email' => $user->email,
                'billing_name' => $user->first_name.' '.$user->last_name,
                'items' => $cartItems->toArray(),
            ]);

            // Create Stripe Checkout Session
            $session = $this->createStripeCheckoutSession($order, $cartItems);
            
            // Store session ID in order
            $order->update(['stripe_session_id' => $session->id]);

            // Redirect to Stripe Checkout (external URL)
            return redirect()->away($session->url);

        } catch (Exception $e) {
            Log::error('Failed to create checkout session', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('cart.index')
                ->with('error', 'Failed to initialize checkout. Please try again.');
        }
    }

    /**
     * Create Stripe Checkout Session
     *
     * @throws ApiErrorException
     */
    private function createStripeCheckoutSession(Order $order, $cartItems): Session
    {
        $user = $order->user;

        // Create or get Stripe customer
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

        // Build line items from cart
        $lineItems = [];
        foreach ($cartItems as $item) {
            $itemPrice = $item->getPriceSum();
            $stripeAmount = StripeHelper::convertToStripeAmount(
                $itemPrice,
                $order->currency
            );

            $lineItems[] = [
                'price_data' => [
                    'currency' => mb_strtolower($order->currency),
                    'product_data' => [
                        'name' => $item->name,
                        'description' => $item->quantity.' '.Str::plural('year', $item->quantity).' - '.ucfirst($item->attributes['type'] ?? 'renewal'),
                    ],
                    'unit_amount' => $stripeAmount,
                ],
                'quantity' => 1,
            ];
        }

        $session = Session::create([
            'customer' => $user->stripe_id,
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => route('checkout.success', ['order' => $order->order_number]).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('checkout.cancel').'?order='.$order->order_number,
            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $user->id,
                'order_type' => 'renewal',
            ],
        ]);

        return $session;
    }

    /**
     * Handle successful payment from Stripe
     */
    public function success(Request $request, string $order): View|RedirectResponse
    {
        try {
            $sessionId = $request->query('session_id');
            
            if (! $sessionId) {
                return redirect()
                    ->route('dashboard')
                    ->with('error', 'Invalid session.');
            }

            // Retrieve the order
            $order = Order::where('order_number', $order)->firstOrFail();

            // Verify order belongs to current user
            if ($order->user_id !== auth()->id()) {
                abort(403);
            }

            // Verify the session with Stripe
            $session = Session::retrieve($sessionId);

            if ($session->payment_status !== 'paid') {
                return redirect()
                    ->route('checkout.cancel')
                    ->with('error', 'Payment was not successful.');
            }

            // Update order
            DB::beginTransaction();

            try {
                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'processing',
                    'processed_at' => now(),
                    'stripe_payment_intent_id' => $session->payment_intent,
                ]);

                DB::commit();

                // Clear cart
                Cart::clear();

                // Dispatch job to process domain renewals
                ProcessDomainRenewalJob::dispatch($order);

                $this->transactionLogger->logSuccess(
                    order: $order,
                    method: 'stripe',
                    transactionId: $session->payment_intent,
                    amount: (float) $order->total_amount
                );

                Log::info('Renewal order completed', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'user_id' => $order->user_id,
                ]);

                return view('checkout.success', ['order' => $order]);

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            Log::error('Payment success handling failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('dashboard')
                ->with('error', 'An error occurred while processing your payment.');
        }
    }

    /**
     * Handle payment cancellation
     */
    public function cancel(Request $request): View|RedirectResponse
    {
        $orderNumber = $request->query('order');
        
        if ($orderNumber) {
            try {
                $order = Order::where('order_number', $orderNumber)->first();
                
                if ($order && $order->user_id === auth()->id()) {
                    $order->update([
                        'payment_status' => 'cancelled',
                        'status' => 'cancelled',
                        'notes' => 'Payment cancelled by user',
                    ]);
                }
            } catch (Exception $e) {
                Log::error('Failed to update cancelled order', [
                    'order_number' => $orderNumber,
                    'error' => $e->getMessage(),
                ]);
            }
        }

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
