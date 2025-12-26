<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Order\ProcessOrderAfterPaymentAction;
use App\Actions\Payment\CreateStripeCheckoutSessionAction;
use App\Actions\Payment\ProcessKPayPaymentAction;
use App\Http\Requests\KPayPaymentRequest;
use App\Livewire\CartComponent;
use App\Models\Order;
use App\Models\Payment;
use App\Services\GeolocationService;
use App\Services\OrderProcessingService;
use App\Services\PaymentService;
use App\Services\TransactionLogger;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
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
        private readonly OrderProcessingService $orderProcessingService,
        private readonly PaymentService $paymentService,
        private readonly TransactionLogger $transactionLogger,
        private readonly GeolocationService $geolocationService
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
     * Show KPay payment form
     *
     * @throws Exception
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
     * Handle successful payment
     */
    public function success(Request $request, Order $order): Factory|\Illuminate\Contracts\View\View|View
    {
        $sessionId = $request->get('session_id');
        $session = Session::retrieve($sessionId);

        return view('payment.success', ['order' => $order, 'session' => $session]);
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

    /**
     * Process KPay payment
     */
    public function processKPayPayment(KPayPaymentRequest $request): RedirectResponse
    {
        $user = Auth::user();

        try {
            $cartItems = Cart::getContent();
            $currency = session('selected_currency', 'USD');
            $billingData = $request->only(['billing_name', 'billing_email', 'billing_address', 'billing_city', 'billing_country', 'billing_postal_code']);

            $primaryContact = $user->contacts()->where('is_primary', true)->first();
            $contactIds = [];
            if ($primaryContact) {
                $contactIds = [
                    'registrant' => $primaryContact->id,
                    'admin' => $primaryContact->id,
                    'tech' => $primaryContact->id,
                    'billing' => $primaryContact->id,
                ];
            }

            $processKPayAction = resolve(ProcessKPayPaymentAction::class);
            $result = $processKPayAction->handle(
                $user,
                mb_trim((string) $request->input('msisdn', '')),
                $request->input('pmethod'),
                $cartItems,
                $currency,
                $contactIds,
                $billingData
            );

            if (! $result['success']) {
                return back()->with('error', $result['error'] ?? 'Payment processing failed. Please try again.');
            }

            if (isset($result['redirect_url'])) {
                return redirect($result['redirect_url']);
            }

            if (isset($result['payment_id'])) {
                return to_route('payment.kpay.status', $result['payment_id'])
                    ->with('payment_id', $result['payment_id']);
            }

            if (isset($result['order'])) {
                return to_route('payment.kpay.success', $result['order']);
            }

            return to_route('payment.kpay.show');

        } catch (Exception $exception) {
            Log::error('KPay payment processing error: '.$exception->getMessage());

            return back()->with('error', 'An error occurred while processing your payment.');
        }
    }

    /**
     * Check KPay payment status
     */
    public function checkKPayStatus(Payment $payment): JsonResponse|RedirectResponse
    {
        try {
            $statusResult = $this->paymentService->checkKPayPaymentStatus($payment);

            // If request expects JSON (AJAX), return JSON
            if (request()->expectsJson() || request()->wantsJson()) {
                return response()->json($statusResult);
            }

            // Otherwise redirect based on status
            if ($statusResult['success'] && ($statusResult['status'] ?? '') === 'succeeded') {
                return to_route('payment.kpay.success', $payment->order)
                    ->with('success', 'Payment successful!');
            }

            if (($statusResult['status'] ?? '') === 'failed') {
                return to_route('payment.failed', $payment->order)
                    ->with('error', $statusResult['error'] ?? 'Payment failed.');
            }

            // Still pending - return to status check page
            return back()->with('info', 'Payment is still being processed. Please wait...');

        } catch (Exception $exception) {
            Log::error('KPay status check error: '.$exception->getMessage());

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

        if ($order->payment_status === 'paid' && $order->status !== 'pending') {
            try {
                $this->dispatchOrderProcessingJobs($order);

                // Clear cart
                Cart::clear();
                session()->forget(['cart', 'checkout', 'kpay_order_number']);
            } catch (Exception $e) {
                Log::error('Failed to dispatch order processing jobs in KPay success handler', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $redirectUrl = $this->orderProcessingService->getServiceDetailsRedirectUrl($order);

            return redirect($redirectUrl)
                ->with('success', $this->getSuccessMessage());
        }

        $statusResult = $this->paymentService->checkKPayPaymentStatus($paymentAttempt);
        $paymentAttempt->refresh();

        $isPaymentSucceeded = $paymentAttempt->isSuccessful();
        $statusid = $statusResult['statusid'] ?? null;
        $paymentConfirmed = ($statusResult['success'] && ($statusResult['status'] ?? '') === 'succeeded') ||
            ($statusid === '01' || $statusid === 1) ||
            $isPaymentSucceeded;

        if ($paymentConfirmed) {
            try {
                DB::beginTransaction();

                if ($order->payment_status !== 'paid') {
                    $order->update([
                        'payment_status' => 'paid',
                        'status' => 'processing',
                        'payment_method' => 'kpay',
                        'processed_at' => now(),
                    ]);
                }

                if (! $paymentAttempt->isSuccessful()) {
                    $paymentAttempt->update([
                        'status' => 'succeeded',
                        'paid_at' => $paymentAttempt->paid_at ?? now(),
                    ]);
                }

                DB::commit();

                $order->refresh();

                try {
                    $this->dispatchOrderProcessingJobs($order);
                } catch (Exception $e) {
                    Log::error('Failed to dispatch order processing jobs after KPay payment', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                // Log success
                $this->transactionLogger->logSuccess(
                    order: $order,
                    method: 'kpay',
                    transactionId: $paymentAttempt->kpay_transaction_id ?? '',
                    amount: (float) $paymentAttempt->amount,
                    payment: $paymentAttempt
                );

                $message = $this->getSuccessMessage();
                $redirectUrl = $this->orderProcessingService->getServiceDetailsRedirectUrl($order);

                return redirect($redirectUrl)
                    ->with('success', $message);

            } catch (Exception $e) {
                DB::rollBack();
                Log::error('KPay payment success processing error: '.$e->getMessage());

                // Always clear cart even if transaction failed
                Cart::clear();
                session()->forget(['cart', 'checkout', 'kpay_order_number']);

                return to_route('payment.failed', $order)->with('error', 'Failed to process payment. Please contact support.');
            } catch (Throwable) {
            }
        }

        // Payment not yet confirmed - show pending message
        return view('payment.kpay-pending', [
            'order' => $order,
            'payment' => $paymentAttempt,
        ]);
    }

    /**
     * Handle canceled KPay payment
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
     * Dispatch jobs to process order after payment
     */
    private function dispatchOrderProcessingJobs(Order $order): void
    {
        // Get contact IDs
        $checkoutData = session('checkout', []);
        $contactId = $checkoutData['selected_contact_id']
            ?? $order->metadata['selected_contact_id']
            ?? $order->metadata['contact_ids']['registrant'] ?? null
            ?? $order->user->contacts()->where('is_primary', true)->first()?->id;

        $contactIds = [];
        if ($contactId) {
            $contactIds = [
                'registrant' => $contactId,
                'admin' => $contactId,
                'tech' => $contactId,
                'billing' => $contactId,
            ];
        }

        // Use ProcessOrderAfterPaymentAction to handle job dispatching
        $processOrderAction = resolve(ProcessOrderAfterPaymentAction::class);
        $processOrderAction->handle($order, $contactIds, true);
    }

    private function getSuccessMessage(): string
    {
        return 'Payment successful! Your order is being processed.';
    }

    /**
     * Get cart data from Cart facade or session
     *
     * @throws Exception
     */
    private function getCartData(): array
    {
        if (session()->has('cart') && session()->has('cart_total')) {
            return [
                'items' => session('cart', []),
                'subtotal' => (float) session('cart_subtotal', 0),
                'total' => (float) session('cart_total', 0),
                'currency' => session('selected_currency', 'USD'),
            ];
        }

        $cartItems = Cart::getContent();

        if ($cartItems->isEmpty()) {
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
}
