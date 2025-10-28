<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\BillingService;
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
        private readonly BillingService $billingService
    ) {
        Stripe::setApiKey(config('cashier.secret'));
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

            // Create Stripe checkout session
            $lineItems = [];
            foreach ($cartItems as $item) {
                $itemCurrency = mb_strtolower($item->attributes->currency ?? 'usd');
                $lineItems[] = [
                    'price_data' => [
                        'currency' => $itemCurrency,
                        'product_data' => [
                            'name' => "Domain: {$item->name}",
                            'description' => "Domain registration for {$item->quantity} year(s)",
                        ],
                        'unit_amount' => (int) (round((float) $item->price, 2) * 100), // Convert to smallest currency unit
                    ],
                    'quantity' => $item->quantity ?? 1,
                ];
            }

            $checkoutSession = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => route('payment.success', ['order' => $order->order_number]).'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('payment.cancel', ['order' => $order->order_number]),
                'customer_email' => $user->email,
                'metadata' => [
                    'order_number' => $order->order_number,
                    'user_id' => $user->id,
                ],
            ]);

            // Update order with Stripe session ID
            $order->update([
                'stripe_session_id' => $checkoutSession->id,
            ]);

            DB::commit();

            return redirect($checkoutSession->url);

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
                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'processing',
                    'stripe_payment_intent_id' => $session->payment_intent,
                    'stripe_session_id' => $sessionId,
                    'processed_at' => now(),
                ]);

                // Process domain registrations
                try {
                    $registrationResults = $this->billingService->processDomainRegistrations($order);

                    // Clear cart and checkout session
                    Cart::clear();
                    session()->forget(['cart', 'checkout']);

                    // Prepare success message based on registration results
                    $successfulCount = count($registrationResults['successful']);
                    $failedCount = count($registrationResults['failed']);

                    if ($successfulCount > 0 && $failedCount === 0) {
                        $message = $successfulCount === 1
                            ? 'Payment successful! Your domain has been registered.'
                            : "Payment successful! All {$successfulCount} domains have been registered.";
                    } elseif ($successfulCount > 0 && $failedCount > 0) {
                        $message = "Payment successful! {$successfulCount} domains registered successfully, {$failedCount} failed. Check your email for details.";
                    } else {
                        $message = 'Payment successful, but domain registration failed. Our support team will contact you shortly.';
                    }

                    return redirect()->route('payment.success.show', $order)
                        ->with('success', $message)
                        ->with('registration_results', $registrationResults);

                } catch (Exception $e) {
                    Log::error('Domain registration failed after payment', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);

                    return redirect()->route('payment.success.show', $order)
                        ->with('warning', 'Payment successful, but there was an issue with domain registration. Our support team will contact you shortly.');
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
