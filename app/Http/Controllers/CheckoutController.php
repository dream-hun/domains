<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Helpers\StripeHelper;
use App\Jobs\ProcessDomainRenewalJob;
use App\Models\Order;
use App\Models\Payment;
use App\Services\TransactionLogger;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Contracts\View\View as ViewContract;
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
            return to_route('dashboard')
                ->with('error', 'Your cart is empty.');
        }

        try {
            // Create order first
            $orderNumber = $this->generateOrderNumber();
            $cartTotal = Cart::getTotal();
            $currency = $cartItems->first()->attributes['currency'] ?? 'USD';
            $user = auth()->user();

            $order = Order::query()->create([
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

            $paymentAttempt = $this->createPaymentAttempt($order);

            // Create Stripe Checkout Session
            $session = $this->createStripeCheckoutSession($order, $cartItems, $paymentAttempt);

            // Store session ID in order
            $order->update(['stripe_session_id' => $session->id]);

            // Redirect to Stripe Checkout (external URL)
            return redirect()->away($session->url);

        } catch (Exception $exception) {
            $this->failPaymentAttempt($paymentAttempt, $exception->getMessage());
            $this->transactionLogger->logFailure(
                order: $order,
                method: 'stripe',
                error: 'Failed to create checkout session',
                details: $exception->getMessage(),
                payment: $paymentAttempt
            );
            Log::error('Failed to create checkout session', [
                'error' => $exception->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return to_route('cart.index')
                ->with('error', 'Failed to initialize checkout. Please try again.');
        }
    }

    /**
     * Handle successful payment from Stripe
     */
    public function success(Request $request, string $order): View|RedirectResponse
    {
        try {
            $sessionId = $request->query('session_id');

            if (! $sessionId) {
                return to_route('dashboard')
                    ->with('error', 'Invalid session.');
            }

            // Retrieve the order
            $order = Order::query()->where('order_number', $order)->firstOrFail();

            // Verify order belongs to current user
            abort_if($order->user_id !== auth()->id(), 403);

            $session = Session::retrieve($sessionId);

            $paymentAttempt = $order->payments()
                ->where(function ($query) use ($sessionId, $session): void {
                    $query->where('stripe_session_id', $sessionId);

                    if (! empty($session->payment_intent)) {
                        $query->orWhere('stripe_payment_intent_id', $session->payment_intent);
                    }
                })
                ->orderByDesc('attempt_number')
                ->orderByDesc('id')
                ->first();

            if (! $paymentAttempt) {
                Log::warning('Checkout success without matching payment attempt', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'session_id' => $sessionId,
                    'payment_intent' => $session->payment_intent,
                ]);
            }

            if ($session->payment_status !== 'paid') {
                $this->markPaymentAttemptFailed($order, $sessionId, $session->last_payment_error->message ?? 'Payment was not successful.');

                return to_route('checkout.cancel')
                    ->with('error', 'Payment was not successful.');
            }

            DB::beginTransaction();

            try {
                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'processing',
                    'processed_at' => now(),
                    'stripe_payment_intent_id' => $session->payment_intent,
                ]);

                if ($paymentAttempt && ! $paymentAttempt->isSuccessful()) {
                    $paymentAttempt->update([
                        'status' => 'succeeded',
                        'stripe_payment_intent_id' => $session->payment_intent,
                        'paid_at' => now(),
                        'metadata' => array_merge($paymentAttempt->metadata ?? [], [
                            'stripe_payment_status' => $session->payment_status,
                        ]),
                        'last_attempted_at' => now(),
                    ]);
                }

                DB::commit();

                Cart::clear();

                dispatch(new ProcessDomainRenewalJob($order));

                $order->refresh();
                $paymentAttempt?->refresh();

                $this->transactionLogger->logSuccess(
                    order: $order,
                    method: 'stripe',
                    transactionId: (string) $session->payment_intent,
                    amount: (float) ($paymentAttempt?->amount ?? $order->total_amount),
                    payment: $paymentAttempt
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

        } catch (Exception $exception) {
            Log::error('Payment success handling failed', [
                'error' => $exception->getMessage(),
                'user_id' => auth()->id(),
            ]);

            $this->markPaymentAttemptFailed(
                $order,
                (string) $request->query('session_id', ''),
                $exception->getMessage()
            );

            return to_route('dashboard')
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
                $order = Order::query()->where('order_number', $orderNumber)->first();

                if ($order && $order->user_id === auth()->id()) {
                    $order->update([
                        'payment_status' => 'cancelled',
                        'status' => 'cancelled',
                        'notes' => 'Payment cancelled by user',
                    ]);

                    $pendingPayment = $order->payments()
                        ->where('status', 'pending')
                        ->orderByDesc('attempt_number')
                        ->orderByDesc('id')
                        ->first();

                    if ($pendingPayment) {
                        $pendingPayment->update([
                            'status' => 'cancelled',
                            'failure_details' => array_merge($pendingPayment->failure_details ?? [], [
                                'message' => 'Payment cancelled by user',
                            ]),
                            'last_attempted_at' => now(),
                        ]);
                    }
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
     * Redirect user to existing Stripe checkout session.
     */
    public function stripeRedirect(string $orderNumber): RedirectResponse
    {
        $order = Order::query()->where('order_number', $orderNumber)->firstOrFail();

        abort_if($order->user_id !== auth()->id(), 403);

        if ($order->stripe_session_id) {
            $session = Session::retrieve($order->stripe_session_id);

            return redirect()->away($session->url);
        }

        Log::warning('Stripe redirect attempted without session', [
            'order_number' => $orderNumber,
            'user_id' => auth()->id(),
        ]);

        return to_route('checkout.wizard')->with('error', 'Unable to locate the payment session. Please restart checkout.');
    }

    /**
     * Stripe success callback wrapper to retain legacy route.
     */
    public function stripeSuccess(Request $request, string $orderNumber): View|RedirectResponse|ViewContract
    {
        return $this->success($request, $orderNumber);
    }

    /**
     * Stripe cancel callback wrapper to retain legacy route.
     */
    public function stripeCancel(Request $request, string $orderNumber): View|RedirectResponse
    {
        $request->query->set('order', $orderNumber);

        return $this->cancel($request);
    }

    /**
     * Create Stripe Checkout Session
     *
     * @throws ApiErrorException
     */
    private function createStripeCheckoutSession(Order $order, $cartItems, Payment $paymentAttempt): Session
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
                'payment_id' => $paymentAttempt->id,
            ],
        ]);

        $paymentAttempt->update([
            'stripe_session_id' => $session->id,
            'metadata' => array_merge($paymentAttempt->metadata ?? [], [
                'stripe_checkout_url' => $session->url,
                'stripe_payment_status' => $session->payment_status ?? 'open',
            ]),
            'last_attempted_at' => now(),
        ]);

        return $session;
    }

    /**
     * Generate a unique order number
     */
    private function generateOrderNumber(): string
    {
        return 'ORD-'.mb_strtoupper(Str::random(10));
    }

    private function createPaymentAttempt(Order $order): Payment
    {
        $nextAttempt = (int) ($order->payments()->max('attempt_number') ?? 0) + 1;

        return $order->payments()->create([
            'user_id' => $order->user_id,
            'status' => 'pending',
            'payment_method' => 'stripe',
            'amount' => $order->total_amount,
            'currency' => $order->currency,
            'metadata' => [
                'order_type' => $order->type,
            ],
            'attempt_number' => $nextAttempt,
            'stripe_payment_intent_id' => Payment::generatePendingIntentId($order, $nextAttempt),
            'last_attempted_at' => now(),
        ]);
    }

    private function failPaymentAttempt(Payment $payment, string $message): void
    {
        $payment->update([
            'status' => 'failed',
            'failure_details' => array_merge($payment->failure_details ?? [], [
                'message' => $message,
            ]),
            'last_attempted_at' => now(),
        ]);
    }

    private function markPaymentAttemptFailed(Order $order, string $sessionId, ?string $message = null): void
    {
        if ($sessionId === '') {
            return;
        }

        $paymentAttempt = $order->payments()
            ->where('stripe_session_id', $sessionId)
            ->orderByDesc('attempt_number')
            ->orderByDesc('id')
            ->first();

        if (! $paymentAttempt || $paymentAttempt->isSuccessful() || $paymentAttempt->status === 'failed') {
            return;
        }

        $failureDetails = $paymentAttempt->failure_details ?? [];

        if ($message) {
            $failureDetails['message'] = $message;
        }

        $paymentAttempt->update([
            'status' => 'failed',
            'failure_details' => $failureDetails,
            'last_attempted_at' => now(),
        ]);
    }
}
