<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\KPayPaymentRequest;
use App\Http\Requests\StripePaymentRequest;
use App\Livewire\CartComponent;
use App\Models\Order;
use App\Models\Payment;
use App\Services\BillingService;
use App\Services\CartPriceConverter;
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
use Throwable;

final class PaymentController extends Controller
{
    public function __construct(
        private readonly BillingService $billingService,
        private readonly OrderService $orderService,
        private readonly OrderProcessingService $orderProcessingService,
        private readonly HostingSubscriptionService $hostingSubscriptionService,
        private readonly PaymentService $paymentService,
        private readonly TransactionLogger $transactionLogger,
        private readonly CartPriceConverter $cartPriceConverter
    ) {
        Stripe::setApiKey(config('services.payment.stripe.secret_key'));
    }

    /**
     * Show the payment page with available payment methods
     */
    public function showPaymentPage(Request $request): View|RedirectResponse
    {
        $cartData = $this->getCartData();

        if (empty($cartData['items'])) {
            return to_route('cart.index')->with('error', 'Your cart is empty.');
        }

        return view('payment.index', [
            'cartItems' => $cartData['items'],
            'totalAmount' => $cartData['total'],
            'subtotal' => $cartData['subtotal'],
            'currency' => $cartData['currency'],
            'user' => Auth::user(),
        ]);
    }

    /**
     * Show KPay payment form
     */
    public function showKPayPaymentPage(): View|RedirectResponse
    {
        $cartData = $this->getCartData();

        if (empty($cartData['items'])) {
            return to_route('cart.index')->with('error', 'Your cart is empty.');
        }

        return view('payment.kpay', [
            'cartItems' => $cartData['items'],
            'totalAmount' => $cartData['total'],
            'subtotal' => $cartData['subtotal'],
            'currency' => $cartData['currency'],
            'user' => Auth::user(),
        ]);
    }

    /**
     * Process Stripe payment
     */
    public function processStripePayment(StripePaymentRequest $request): RedirectResponse
    {
        $cartData = $this->getCartData();

        if (empty($cartData['items'])) {
            return to_route('cart.index')->with('error', 'Your cart is empty.');
        }

        $user = Auth::user();

        try {
            DB::beginTransaction();

            $order = $this->billingService->createOrderFromCart(
                $user,
                $request->only(['billing_name', 'billing_email', 'billing_address', 'billing_city', 'billing_country', 'billing_postal_code']),
                array_merge(session('checkout', []), ['payment_method' => 'stripe']),
                $cartData
            );

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

    /**
     * Process KPay payment
     */
    public function processKPayPayment(KPayPaymentRequest $request): RedirectResponse
    {
        $user = Auth::user();

        try {
            DB::beginTransaction();

            $order = $this->getOrCreateOrder($user, $request);

            if (! $order) {
                DB::rollBack();

                return to_route('cart.index')->with('error', 'Your cart is empty.');
            }

            $msisdn = mb_trim((string) $request->input('msisdn', ''));
            if (empty($msisdn)) {
                DB::rollBack();

                return back()->withErrors(['msisdn' => 'Phone number is required for KPay payment.'])->withInput();
            }

            $paymentResult = $this->paymentService->processPayment($order, 'kpay', [
                'msisdn' => $msisdn,
                'pmethod' => $request->input('pmethod'),
            ]);

            if (! $paymentResult['success']) {
                DB::rollBack();

                return back()->with('error', $paymentResult['error'] ?? 'Payment processing failed. Please try again.');
            }

            DB::commit();
            session()->forget('kpay_order_number');

            if (isset($paymentResult['redirect_url'])) {
                return redirect($paymentResult['redirect_url']);
            }

            if (isset($paymentResult['payment_id'])) {
                return redirect()->route('payment.kpay.status', $paymentResult['payment_id'])
                    ->with('payment_id', $paymentResult['payment_id']);
            }

            return redirect()->route('payment.kpay.success', $order);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('KPay payment processing error: '.$e->getMessage());

            return back()->with('error', 'An error occurred while processing your payment.');
        }
    }

    /**
     * Check KPay payment status
     */
    public function checkKPayStatus(Payment $payment): \Illuminate\Http\JsonResponse|RedirectResponse
    {
        try {
            $statusResult = $this->paymentService->checkKPayPaymentStatus($payment);

            // If request expects JSON (AJAX), return JSON
            if (request()->expectsJson() || request()->wantsJson()) {
                return response()->json($statusResult);
            }

            // Otherwise redirect based on status
            if ($statusResult['success'] && ($statusResult['status'] ?? '') === 'succeeded') {
                return redirect()->route('payment.kpay.success', $payment->order)
                    ->with('success', 'Payment successful!');
            }

            if (($statusResult['status'] ?? '') === 'failed') {
                return redirect()->route('payment.failed', $payment->order)
                    ->with('error', $statusResult['error'] ?? 'Payment failed.');
            }

            // Still pending - return to status check page
            return back()->with('info', 'Payment is still being processed. Please wait...');

        } catch (Exception $e) {
            Log::error('KPay status check error: '.$e->getMessage());

            if (request()->expectsJson() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'An error occurred while checking payment status.',
                ], 500);
            }

            return back()->with('error', 'An error occurred while checking payment status.');
        }
    }

    /**
     * Handle successful KPay payment
     */
    public function handleKPaySuccess(Order $order): View|RedirectResponse
    {
        $paymentAttempt = $order->payments()
            ->where('payment_method', 'kpay')
            ->orderByDesc('attempt_number')
            ->orderByDesc('id')
            ->first();

        if (! $paymentAttempt) {
            return to_route('payment.failed', $order)->with('error', 'Payment record not found.');
        }

        // Check if order is already paid and processed
        if ($order->payment_status === 'paid' && $order->status !== 'pending') {
            // Order already processed - clear cart and redirect
            Cart::clear();
            session()->forget(['cart', 'checkout', 'kpay_order_number']);

            $redirectUrl = $this->orderProcessingService->getServiceDetailsRedirectUrl($order);

            return redirect($redirectUrl)
                ->with('success', $this->getSuccessMessage($order));
        }

        // Check payment status with KPay API (this may update the payment attempt status)
        $statusResult = $this->paymentService->checkKPayPaymentStatus($paymentAttempt);

        // Refresh payment attempt to get latest status after API check
        $paymentAttempt->refresh();

        // Check if payment is succeeded (either from API response or database)
        $isPaymentSucceeded = $paymentAttempt->isSuccessful();
        $paymentConfirmed = ($statusResult['success'] && ($statusResult['status'] ?? '') === 'succeeded') || $isPaymentSucceeded;

        if ($paymentConfirmed) {
            // Payment is confirmed - process order
            try {
                DB::beginTransaction();

                // Update order status if not already paid
                if ($order->payment_status !== 'paid') {
                    $order->update([
                        'payment_status' => 'paid',
                        'status' => 'processing',
                        'payment_method' => 'kpay',
                        'processed_at' => now(),
                    ]);
                }

                // Ensure payment attempt is marked as succeeded
                if (! $paymentAttempt->isSuccessful()) {
                    $paymentAttempt->update([
                        'status' => 'succeeded',
                        'paid_at' => $paymentAttempt->paid_at ?? now(),
                    ]);
                }

                DB::commit();

                // Refresh order to get latest status
                $order->refresh();

                // Process order items (only if not already processed)
                if ($order->status === 'processing' && $order->orderItems->isEmpty()) {
                    try {
                        // Create OrderItem records from order's items JSON if they don't exist
                        $this->orderProcessingService->createOrderItemsFromJson($order);

                        // Create hosting subscriptions from order items
                        $this->hostingSubscriptionService->createSubscriptionsFromOrder($order);

                        // Get contact ID from checkout session
                        $checkoutData = session('checkout', []);
                        $contactId = $checkoutData['selected_contact_id'] ?? $order->user->contacts()->where('is_primary', true)->first()?->id;

                        if ($contactId) {
                            $contactIds = [
                                'registrant' => $contactId,
                                'admin' => $contactId,
                                'tech' => $contactId,
                                'billing' => $contactId,
                            ];

                            // Process registrations
                            $this->orderService->processDomainRegistrations($order, $contactIds);
                        }

                        // Dispatch renewal jobs
                        $this->orderProcessingService->dispatchRenewalJobs($order);
                    } catch (Exception $e) {
                        // Registration failed but payment succeeded
                        Log::error('Domain registration failed after KPay payment', [
                            'order_id' => $order->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Clear cart and checkout session
                Cart::clear();
                session()->forget(['cart', 'checkout', 'kpay_order_number']);

                // Log success
                $this->transactionLogger->logSuccess(
                    order: $order,
                    method: 'kpay',
                    transactionId: $paymentAttempt->kpay_transaction_id ?? '',
                    amount: (float) $paymentAttempt->amount,
                    payment: $paymentAttempt
                );

                $message = $this->getSuccessMessage($order);
                $redirectUrl = $this->orderProcessingService->getServiceDetailsRedirectUrl($order);

                return redirect($redirectUrl)
                    ->with('success', $message);

            } catch (Exception $e) {
                DB::rollBack();
                Log::error('KPay payment success processing error: '.$e->getMessage());

                return to_route('payment.failed', $order)->with('error', 'Failed to process payment. Please contact support.');
            }
        }

        // Payment not yet confirmed - show pending message
        return view('payment.kpay-pending', [
            'order' => $order,
            'payment' => $paymentAttempt,
        ]);
    }

    /**
     * Handle cancelled KPay payment
     */
    public function handleKPayCancel(Order $order): RedirectResponse
    {
        $order->update([
            'payment_status' => 'cancelled',
            'status' => 'cancelled',
        ]);

        $pendingPayment = $order->payments()
            ->where('payment_method', 'kpay')
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
        } elseif ($order->isCompleted()) {
            $messages[] = 'Payment successful! Your order has been completed.';
        } elseif ($order->isPartiallyCompleted()) {
            $messages[] = "Payment successful! Some items were processed successfully. We're retrying others automatically.";
        } elseif ($order->requiresAttention()) {
            $messages[] = "Payment successful! We're processing your order and will notify you once complete.";
        } else {
            $messages[] = 'Payment successful! Your order is being processed.';
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

    /**
     * Get cart data from Cart facade or session
     */
    private function getCartData(): array
    {
        // Try to get from session first (prepared cart data)
        if (session()->has('cart') && session()->has('cart_total')) {
            return [
                'items' => session('cart', []),
                'subtotal' => (float) session('cart_subtotal', 0),
                'total' => (float) session('cart_total', 0),
                'currency' => session('selected_currency', 'USD'),
            ];
        }

        // Fallback to Cart facade
        $cartItems = Cart::getContent();

        if ($cartItems->isEmpty()) {
            // Check for order-based cart (from CheckoutWizard)
            $orderNumber = session('kpay_order_number');
            if ($orderNumber) {
                $order = Order::query()
                    ->where('order_number', $orderNumber)
                    ->where('user_id', Auth::id())
                    ->first();

                if ($order) {
                    return [
                        'items' => $order->items ?? [],
                        'subtotal' => (float) $order->subtotal,
                        'total' => (float) $order->total_amount,
                        'currency' => $order->currency ?? 'USD',
                    ];
                }
            }

            return [
                'items' => [],
                'subtotal' => 0,
                'total' => 0,
                'currency' => session('selected_currency', 'USD'),
            ];
        }

        // Use CartComponent to prepare cart data with proper currency conversion
        $cartComponent = new CartComponent;
        $cartComponent->mount();

        return $cartComponent->prepareCartForPayment();
    }

    /**
     * Get existing order or create new one from cart
     *
     * @throws Throwable
     */
    private function getOrCreateOrder($user, KPayPaymentRequest $request): ?Order
    {
        $orderNumber = session('kpay_order_number');
        if ($orderNumber) {
            $order = Order::query()
                ->where('order_number', $orderNumber)
                ->where('user_id', $user->id)
                ->where('payment_method', 'kpay')
                ->where('payment_status', 'pending')
                ->first();

            if ($order) {
                $order->update(
                    $request->only(['billing_name', 'billing_email', 'billing_address', 'billing_city', 'billing_country', 'billing_postal_code'])
                );

                return $order;
            }
        }

        $cartData = $this->getCartData();
        if (empty($cartData['items'])) {
            return null;
        }

        return $this->billingService->createOrderFromCart(
            $user,
            $request->only(['billing_name', 'billing_email', 'billing_address', 'billing_city', 'billing_country', 'billing_postal_code']),
            array_merge(session('checkout', []), ['payment_method' => 'kpay']),
            $cartData
        );
    }
}
