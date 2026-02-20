<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Order\ProcessOrderAfterPaymentAction;
use App\Actions\Payment\CreateStripeCheckoutSessionAction;
use App\Models\Order;
use App\Services\GeolocationService;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Throwable;

final class PaymentController extends Controller
{
    public function __construct(

        private readonly GeolocationService $geolocationService,

    ) {
        Stripe::setApiKey(config('services.payment.stripe.secret_key'));
    }

    /**
     * Process Stripe checkout from cart
     *
     * @throws Exception|Throwable
     */
    public function stripeCheckout(): RedirectResponse
    {
        $cartItems = Cart::getContent();

        if ($cartItems->isEmpty()) {
            return to_route('cart.index')->with('error', 'Your cart is empty.');
        }

        $user = Auth::user();

        try {
            $currency = session('selected_currency');
            if (! $currency) {
                $isFromRwanda = $this->geolocationService->isUserFromRwanda();
                $currency = $isFromRwanda ? 'RWF' : 'USD';
                session(['selected_currency' => $currency]);
            }

            $primaryContact = $user->contacts()->where('is_primary', true)->first();
            $billingData = null;
            if ($primaryContact) {
                $billingData = [
                    'billing_email' => $primaryContact->email ?? $user->email,
                    'billing_name' => $primaryContact->full_name ?? $user->name,
                    'billing_address' => [
                        'address_one' => $primaryContact->address_one ?? '',
                        'address_two' => $primaryContact->address_two ?? '',
                        'city' => $primaryContact->city ?? '',
                        'state_province' => $primaryContact->state_province ?? '',
                        'postal_code' => $primaryContact->postal_code ?? '',
                        'country_code' => $primaryContact->country_code ?? '',
                    ],
                ];
            }

            $contactIds = [];
            if ($primaryContact) {
                $contactIds = [
                    'registrant' => $primaryContact->id,
                    'admin' => $primaryContact->id,
                    'tech' => $primaryContact->id,
                    'billing' => $primaryContact->id,
                ];
            }

            $createStripeCheckoutAction = resolve(CreateStripeCheckoutSessionAction::class);
            $result = $createStripeCheckoutAction->handle(
                $user,
                $cartItems,
                $currency,
                $contactIds,
                $billingData
            );

            return redirect()->away($result['url']);

        } catch (ApiErrorException $e) {
            Log::error('Stripe checkout error: '.$e->getMessage());

            return back()->with('error', 'Payment processing failed. Please try again.');
        } catch (Exception $e) {
            Log::error('Checkout processing error: '.$e->getMessage());

            return back()->with('error', 'An error occurred while processing your payment.');
        }
    }

    /**
     * Handle successful payment
     *
     * @throws ApiErrorException
     */
    public function success(Request $request, Order $order): Factory|\Illuminate\Contracts\View\View|View|RedirectResponse
    {
        // Verify order belongs to current user
        abort_if($order->user_id !== auth()->id(), 403);

        $sessionId = $request->query('session_id');

        // If session_id is provided, verify payment status and process order
        if ($sessionId) {
            try {
                $session = Session::retrieve($sessionId);

                // Verify the session belongs to this order
                // Both the stored session_id and metadata order_id must match
                $sessionIdMismatch = $order->stripe_session_id && $order->stripe_session_id !== $sessionId;
                $orderIdMismatch = ! isset($session->metadata->order_id) || $session->metadata->order_id !== (string) $order->id;

                if ($sessionIdMismatch || $orderIdMismatch) {
                    Log::warning('Session verification failed - security check', [
                        'order_id' => $order->id,
                        'order_session_id' => $order->stripe_session_id,
                        'provided_session_id' => $sessionId,
                        'session_order_id' => $session->metadata->order_id ?? null,
                        'session_id_mismatch' => $sessionIdMismatch,
                        'order_id_mismatch' => $orderIdMismatch,
                    ]);

                    return to_route('dashboard')
                        ->with('error', 'Invalid payment session. Please contact support if you believe this is an error.');
                }

                // If payment is not paid, show error
                if ($session->payment_status !== 'paid') {
                    Log::warning('Payment not successful on success page', [
                        'order_id' => $order->id,
                        'session_id' => $sessionId,
                        'payment_status' => $session->payment_status,
                    ]);

                    return to_route('payment.failed', $order)
                        ->with('error', 'Payment was not successful.');
                }

                // Only process if order is still pending payment
                if ($order->payment_status === 'pending') {
                    DB::beginTransaction();

                    try {
                        $order->update([
                            'payment_status' => 'paid',
                            'status' => $order->status === 'pending' ? 'processing' : $order->status,
                            'processed_at' => now(),
                            'stripe_payment_intent_id' => $session->payment_intent ?? $order->stripe_payment_intent_id,
                            'stripe_session_id' => $sessionId,
                        ]);

                        // Update payment record
                        $payment = $order->payments()
                            ->where('payment_method', 'stripe')
                            ->where(function ($query) use ($sessionId, $session): void {
                                $query->where('stripe_session_id', $sessionId);

                                if (! empty($session->payment_intent)) {
                                    $query->orWhere('stripe_payment_intent_id', $session->payment_intent);
                                }
                            })
                            ->orderByDesc('attempt_number')
                            ->orderByDesc('id')
                            ->first();

                        if ($payment && ! $payment->isSuccessful()) {
                            $payment->update([
                                'status' => 'succeeded',
                                'stripe_payment_intent_id' => $session->payment_intent ?? $payment->stripe_payment_intent_id,
                                'stripe_session_id' => $sessionId,
                                'paid_at' => now(),
                                'last_attempted_at' => now(),
                                'metadata' => array_merge($payment->metadata ?? [], [
                                    'stripe_payment_status' => $session->payment_status,
                                ]),
                            ]);
                        }

                        DB::commit();

                        // Process order after payment (idempotent - safe to call multiple times)
                        try {
                            $primaryContact = $order->user->contacts()->where('is_primary', true)->first();
                            $contactIds = [];
                            if ($primaryContact) {
                                $contactIds = [
                                    'registrant' => $primaryContact->id,
                                    'admin' => $primaryContact->id,
                                    'tech' => $primaryContact->id,
                                    'billing' => $primaryContact->id,
                                ];
                            }

                            $processOrderAction = resolve(ProcessOrderAfterPaymentAction::class);
                            $processOrderAction->handle($order, $contactIds, false);

                            Log::info('Order processed successfully on success page', [
                                'order_id' => $order->id,
                                'order_number' => $order->order_number,
                            ]);
                        } catch (Exception $e) {
                            Log::warning('Order processing failed on success page', [
                                'order_id' => $order->id,
                                'error' => $e->getMessage(),
                            ]);
                            // Don't fail the page load if processing fails - webhook will handle it
                        }

                        $order->refresh();
                    } catch (Exception $e) {
                        DB::rollBack();
                        Log::error('Failed to update order on success page', [
                            'order_id' => $order->id,
                            'error' => $e->getMessage(),
                        ]);
                        // Continue to show success page even if update fails
                    }
                }
            } catch (ApiErrorException $e) {
                Log::error('Failed to retrieve Stripe session on success page', [
                    'order_id' => $order->id,
                    'session_id' => $sessionId,
                    'error' => $e->getMessage(),
                ]);
                // Continue to show success page even if session retrieval fails
            } catch (Exception $e) {
                Log::error('Error processing payment success', [
                    'order_id' => $order->id,
                    'session_id' => $sessionId,
                    'error' => $e->getMessage(),
                ]);
                // Continue to show success page
            }
        }

        $order->load('user.address');

        return view('payment.success', ['order' => $order]);
    }

    /**
     * Handle cancelled payment
     */
    public function handlePaymentCancel(Order $order): RedirectResponse
    {
        $order->update([
            'payment_status' => 'cancelled',
            'status' => 'cancelled',
        ]);

        $pendingPayment = $order->payments()
            ->where('status', 'pending')
            ->orderByDesc('attempt_number')
            ->orderByDesc('id')
            ->first();

        $pendingPayment?->update([
            'status' => 'cancelled',
            'failure_details' => array_merge($pendingPayment->failure_details ?? [], [
                'message' => 'Payment cancelled by user',
            ]),
            'last_attempted_at' => now(),
        ]);

        return to_route('cart.index')->with('error', 'Payment was cancelled.');
    }

    /**
     * Show payment failed page
     */
    public function showPaymentFailed(Order $order): View
    {
        return view('payment.failed', ['order' => $order]);
    }
}
