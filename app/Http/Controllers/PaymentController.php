<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StripePaymentRequest;
use App\Models\Order;
use App\Services\BillingService;
use App\Services\HostingSubscriptionService;
use App\Services\OrderProcessingService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\TransactionLogger;
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
        private readonly OrderProcessingService $orderProcessingService,
        private readonly HostingSubscriptionService $hostingSubscriptionService,
        private readonly PaymentService $paymentService,
        private readonly TransactionLogger $transactionLogger
    ) {
        Stripe::setApiKey(config('services.payment.stripe.secret_key'));
    }

    /**
     * Show the payment page with available payment methods
     */
    public function showPaymentPage(Request $request): View|RedirectResponse
    {
        $checkoutData = session('checkout', []);

        if (empty($checkoutData['cart_items'])) {
            return to_route('cart.index')->with('error', 'Your cart is empty.');
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
    public function processStripePayment(StripePaymentRequest $request): RedirectResponse
    {
        $checkoutData = session('checkout', []);
        $cartItems = Cart::getContent();

        if ($cartItems->isEmpty()) {
            return to_route('cart.index')->with('error', 'Your cart is empty.');
        }

        $user = Auth::user();

        try {
            DB::beginTransaction();

            // Create order using BillingService
            Log::error('Processing Stripe payment with session totals', [
                'user_id' => $user?->id,
                'cart_total' => session('cart_total'),
                'cart_subtotal' => session('cart_subtotal'),
                'selected_currency' => session('selected_currency'),
                'checkout' => session('checkout'),
            ]);

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

                return back()->with('error', $paymentResult['error'] ?? 'Payment processing failed. Please try again.');
            }

            DB::commit();

            return redirect($paymentResult['checkout_url']);

        } catch (ApiErrorException $e) {
            DB::rollBack();
            Log::error('Stripe payment error: '.$e->getMessage());

            return back()->with('error', 'Payment processing failed. Please try again.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Payment processing error: '.$e->getMessage());

            return back()->with('error', 'An error occurred while processing your payment.');
        }
    }

    /**
     * Handle successful payment
     */
    public function handlePaymentSuccess(Request $request, Order $order): RedirectResponse
    {
        $sessionId = $request->query('session_id');

        if (! $sessionId) {
            return to_route('payment.failed', $order)->with('error', 'Invalid payment session.');
        }

        /** @var Session|null $session */
        $session = null;

        try {
            $session = Session::retrieve($sessionId);

            if ($session->payment_status === 'paid') {
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
                    Log::warning('Stripe session completed without matching payment attempt', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'session_id' => $sessionId,
                        'payment_intent' => $session->payment_intent,
                    ]);
                }

                // Transaction 1: Update payment status (commit payment)
                DB::transaction(function () use ($order, $session, $sessionId, $paymentAttempt): void {
                    $order->update([
                        'payment_status' => 'paid',
                        'status' => 'processing',
                        'stripe_payment_intent_id' => $session->payment_intent,
                        'stripe_session_id' => $sessionId,
                        'processed_at' => now(),
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
                });

                // Payment is now committed - process domain registrations outside transaction
                try {
                    // Create OrderItem records from order's items JSON if they don't exist
                    $this->orderProcessingService->createOrderItemsFromJson($order);

                    // Create hosting subscriptions from order items
                    $this->hostingSubscriptionService->createSubscriptionsFromOrder($order);

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

                    // Dispatch appropriate renewal jobs based on order items
                    $this->orderProcessingService->dispatchRenewalJobs($order);

                    // Clear cart and checkout session
                    Cart::clear();
                    session()->forget(['cart', 'checkout']);

                    // Refresh order to get updated status
                    $order->refresh();

                    if (isset($paymentAttempt)) {
                        $paymentAttempt->refresh();
                    }

                    $this->transactionLogger->logSuccess(
                        order: $order,
                        method: 'stripe',
                        transactionId: (string) $session->payment_intent,
                        amount: (float) ($paymentAttempt->amount ?? $order->total_amount),
                        payment: $paymentAttempt
                    );

                    $message = $this->getSuccessMessage($order);
                    $redirectUrl = $this->orderProcessingService->getServiceDetailsRedirectUrl($order);

                    return redirect($redirectUrl)
                        ->with('success', $message);

                } catch (Exception $e) {
                    // Registration failed but payment succeeded - this is OK
                    // The DomainRegistrationService has already logged and scheduled retries
                    Log::error('Domain registration failed after payment', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);

                    // Still show success page since payment succeeded
                    $redirectUrl = $this->orderProcessingService->getServiceDetailsRedirectUrl($order);

                    return redirect($redirectUrl)
                        ->with('success', "Payment successful! We're processing your domain registration and will notify you once complete.");
                }
            }
        } catch (ApiErrorException $apiErrorException) {
            Log::error('Stripe session retrieval error: '.$apiErrorException->getMessage());
        }

        if ($session?->payment_status !== 'paid') {
            $this->markPaymentAttemptFailed(
                $order,
                $sessionId,
                $session?->last_payment_error->message ?? 'Payment verification failed.'
            );
        }

        return to_route('payment.failed', $order)->with('error', 'Payment verification failed.');
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

        if ($pendingPayment) {
            $pendingPayment->update([
                'status' => 'cancelled',
                'failure_details' => array_merge($pendingPayment->failure_details ?? [], [
                    'message' => 'Payment cancelled by user',
                ]),
                'last_attempted_at' => now(),
            ]);
        }

        return to_route('cart.index')->with('error', 'Payment was cancelled.');
    }

    /**
     * Show payment failed page
     */
    public function showPaymentFailed(Order $order): View
    {
        return view('payment.failed', ['order' => $order]);
    }

    private function getSuccessMessage(Order $order): string
    {
        $orderItems = $order->orderItems;
        $hasDomainRegistration = false;
        $hasDomainRenewal = false;
        $hasSubscription = false;
        $hasSubscriptionRenewal = false;

        foreach ($orderItems as $item) {
            if ($item->domain_type === 'registration') {
                $hasDomainRegistration = true;
            } elseif ($item->domain_type === 'renewal') {
                $hasDomainRenewal = true;
            } elseif ($item->domain_type === 'hosting') {
                $hasSubscription = true;
            } elseif ($item->domain_type === 'subscription_renewal') {
                $hasSubscriptionRenewal = true;
            }
        }

        $messages = [];
        if ($hasDomainRegistration && $hasSubscription) {
            $messages[] = 'Payment successful! Your domain and hosting subscription have been processed.';
        } elseif ($hasDomainRegistration && $hasSubscriptionRenewal) {
            $messages[] = 'Payment successful! Your domain registration and subscription renewal have been processed.';
        } elseif ($hasDomainRenewal && $hasSubscription) {
            $messages[] = 'Payment successful! Your domain renewal and hosting subscription have been processed.';
        } elseif ($hasDomainRenewal && $hasSubscriptionRenewal) {
            $messages[] = 'Payment successful! Your domain and subscription renewals have been processed.';
        } elseif ($hasDomainRegistration) {
            if ($order->isCompleted()) {
                $messages[] = 'Payment successful! Your domain has been registered.';
            } elseif ($order->isPartiallyCompleted()) {
                $messages[] = "Payment successful! Some domains were registered successfully. We're retrying others automatically.";
            } elseif ($order->requiresAttention()) {
                $messages[] = "Payment successful! We're processing your domain registration and will notify you once complete.";
            } else {
                $messages[] = 'Payment successful! Your domain registration is being processed.';
            }
        } elseif ($hasDomainRenewal) {
            $messages[] = 'Payment successful! Your domain renewal has been processed.';
        } elseif ($hasSubscription) {
            $messages[] = 'Payment successful! Your hosting subscription has been activated.';
        } elseif ($hasSubscriptionRenewal) {
            $messages[] = 'Payment successful! Your subscription renewal has been processed.';
        } else {
            if ($order->isCompleted()) {
                $messages[] = 'Payment successful! Your order has been completed.';
            } elseif ($order->isPartiallyCompleted()) {
                $messages[] = "Payment successful! Some items were processed successfully. We're retrying others automatically.";
            } elseif ($order->requiresAttention()) {
                $messages[] = "Payment successful! We're processing your order and will notify you once complete.";
            } else {
                $messages[] = 'Payment successful! Your order is being processed.';
            }
        }

        return implode(' ', $messages);
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

        if (! $paymentAttempt) {
            return;
        }

        if ($paymentAttempt->isSuccessful() || $paymentAttempt->status === 'failed') {
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
