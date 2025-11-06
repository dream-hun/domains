<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\BillingService;
use App\Services\OrderService;
use App\Services\PaymentService;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

final class PaymentController extends Controller
{
    public function __construct(
        private readonly BillingService $billingService,
        private readonly OrderService $orderService,
        private readonly PaymentService $paymentService
    ) {
        Stripe::setApiKey(config('services.payment.stripe.secret_key'));
    }

    /**
     * Show the payment page with available payment methods
     */
    public function showPaymentPage(Request $request)
    {
        $checkoutData = session('checkout', []);

        if (empty($checkoutData['cart_items'])) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $cartItems = $checkoutData['cart_items'];
        $totalAmount = $checkoutData['total'] ?? 0;
        $user = Auth::user();

        return view('payment.index', [
            'cartItems' => $cartItems,
            'totalAmount' => $totalAmount,
            'user' => $user,
        ]);
    }

    /**
     * Process Stripe payment
     */
    public function processStripePayment(Request $request): RedirectResponse
    {
        $request->validate([
            'billing_name' => 'required|string|max:255',
            'billing_email' => 'required|email|max:255',
            'billing_address' => 'nullable|string|max:255',
            'billing_city' => 'nullable|string|max:255',
            'billing_country' => 'nullable|string|max:255',
            'billing_postal_code' => 'nullable|string|max:20',
        ]);

        $checkoutData = session('checkout', []);
        $cartItems = Cart::getContent();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $user = Auth::user();

        try {
            DB::beginTransaction();

            // Create order using BillingService
            $order = $this->billingService->createOrderFromCart(
                $user,
                $request->only(['billing_name', 'billing_email', 'billing_address', 'billing_city', 'billing_country', 'billing_postal_code']),
                $checkoutData
            );

            // Use PaymentService to create Stripe checkout session
            // This ensures proper currency handling and discount application
            $paymentResult = $this->paymentService->processPayment($order, 'stripe');

            if (! $paymentResult['success']) {
                DB::rollBack();

                return redirect()->back()->with('error', $paymentResult['error'] ?? 'Payment processing failed. Please try again.');
            }

            DB::commit();

            return redirect($paymentResult['checkout_url']);

        } catch (ApiErrorException $e) {
            DB::rollBack();
            Log::error('Stripe payment error: '.$e->getMessage());

            return redirect()->back()->with('error', 'Payment processing failed. Please try again.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Payment processing error: '.$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while processing your payment.');
        }
    }

    /**
     * Handle successful payment
     */
    public function handlePaymentSuccess(Request $request, Order $order): RedirectResponse
    {
        $sessionId = $request->query('session_id');

        if (! $sessionId) {
            return redirect()->route('payment.failed', $order)->with('error', 'Invalid payment session.');
        }

        try {
            $session = Session::retrieve($sessionId);

            if ($session->payment_status === 'paid') {
                // Transaction 1: Update payment status (commit payment)
                DB::transaction(function () use ($order, $session, $sessionId) {
                    $order->update([
                        'payment_status' => 'paid',
                        'status' => 'processing',
                        'stripe_payment_intent_id' => $session->payment_intent,
                        'stripe_session_id' => $sessionId,
                        'processed_at' => now(),
                    ]);
                });

                // Payment is now committed - process domain registrations outside transaction
                try {
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

                        // Process registrations - failures are handled internally
                        $this->orderService->processDomainRegistrations($order, $contactIds);
                    }

                    // Clear cart and checkout session
                    Cart::clear();
                    session()->forget(['cart', 'checkout']);

                    // Refresh order to get updated status
                    $order->refresh();

                    // Prepare success message based on order status
                    if ($order->isCompleted()) {
                        $message = 'Payment successful! Your domain has been registered.';
                    } elseif ($order->isPartiallyCompleted()) {
                        $message = 'Payment successful! Some domains were registered successfully. We\'re retrying others automatically.';
                    } elseif ($order->requiresAttention()) {
                        $message = 'Payment successful! We\'re processing your domain registration and will notify you once complete.';
                    } else {
                        $message = 'Payment successful! Your order is being processed.';
                    }

                    return redirect()->route('payment.success.show', $order)
                        ->with('success', $message);

                } catch (Exception $e) {
                    // Registration failed but payment succeeded - this is OK
                    // The DomainRegistrationService has already logged and scheduled retries
                    Log::error('Domain registration failed after payment', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);

                    // Still show success page since payment succeeded
                    return redirect()->route('payment.success.show', $order)
                        ->with('success', 'Payment successful! We\'re processing your domain registration and will notify you once complete.');
                }
            }
        } catch (ApiErrorException $e) {
            Log::error('Stripe session retrieval error: '.$e->getMessage());
        }

        return redirect()->route('payment.failed', $order)->with('error', 'Payment verification failed.');
    }

    /**
     * Show payment success page
     */
    public function showPaymentSuccess(Order $order): View
    {
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

        return redirect()->route('cart.index')->with('error', 'Payment was cancelled.');
    }

    /**
     * Show payment failed page
     */
    public function showPaymentFailed(Order $order): View
    {
        return view('payment.failed', ['order' => $order]);
    }
}
