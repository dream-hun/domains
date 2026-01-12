<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Order\CreateOrderFromCartAction;
use App\Actions\Order\ProcessOrderAfterPaymentAction;
use App\Http\Requests\KPayPaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Services\CartPriceConverter;
use App\Services\GeolocationService;
use App\Services\PaymentService;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

final class KpayPaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly CreateOrderFromCartAction $createOrderAction,
        private readonly CartPriceConverter $cartPriceConverter,
        private readonly GeolocationService $geolocationService
    ) {}

    /**
     * Show the KPay payment form
     */
    public function show(): View|RedirectResponse
    {
        $orderNumber = session('kpay_order_number');
        $order = null;

        if ($orderNumber) {
            $order = Order::query()
                ->where('order_number', $orderNumber)
                ->where('user_id', Auth::id())
                ->where('payment_method', 'kpay')
                ->where('payment_status', 'pending')
                ->first();
        }

        // If no order in session but the cart has items, we'll create an order on the form submitted
        if (! $order && Cart::isEmpty()) {
            session()->forget('kpay_order_number');

            return to_route('checkout.index')->with('error', 'No order found. Please start the checkout process again.');
        }

        $user = Auth::user();
        $currency = $this->getLocationBasedCurrency();

        // Use order items if we have an order, otherwise use cart
        if ($order) {
            $cartItems = $this->prepareCartItemsForDisplay($order);
            $totalAmount = (float) $order->total_amount;
            $subtotal = (float) $order->subtotal;
            $currency = $order->currency;

            // Ensure the session currency matches the order currency for header consistency
            session(['selected_currency' => $currency]);
        } else {
            $cartContent = Cart::getContent();
            $cartItems = $cartContent->map(fn (object $item): array => [
                'domain_name' => $item->attributes->get('domain_name', $item->name),
                'domain_type' => $item->attributes->get('type', 'registration'),
                'price' => $this->cartPriceConverter->convertItemPrice($item, $currency),
                'quantity' => $item->quantity,
                'years' => $item->attributes->get('years', $item->quantity),
                'currency' => $currency,
            ])->toArray();
            $totalAmount = $this->cartPriceConverter->calculateCartSubtotal($cartContent, $currency);
            $subtotal = $totalAmount;
        }

        return view('payment.kpay', [
            'order' => $order,
            'user' => $user,
            'cartItems' => $cartItems,
            'totalAmount' => $totalAmount,
            'subtotal' => $subtotal,
            'currency' => $currency,
        ]);
    }

    /**
     * Process the KPay payment
     *
     * @throws Throwable
     */
    public function process(KPayPaymentRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $user = Auth::user();

        // Try to find existing order from session
        $orderNumber = session('kpay_order_number');
        $order = null;

        if ($orderNumber) {
            $order = Order::query()
                ->where('order_number', $orderNumber)
                ->where('user_id', $user->id)
                ->where('payment_method', 'kpay')
                ->where('payment_status', 'pending')
                ->first();
        }

        // If no order exists, create one from the cart
        if (! $order) {
            $cartItems = Cart::getContent();

            if ($cartItems->isEmpty()) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['error' => 'No order found. Please start the checkout process again.'], 400);
                }

                return to_route('checkout.index')->with('error', 'No order found. Please start the checkout process again.');
            }

            try {
                $currency = $this->getLocationBasedCurrency();

                // Get contact IDs from the checkout session if available
                $checkoutState = session('checkout_state', []);
                $contactIds = [
                    'registrant' => $checkoutState['selected_registrant_id'] ?? null,
                    'admin' => $checkoutState['selected_admin_id'] ?? null,
                    'tech' => $checkoutState['selected_tech_id'] ?? null,
                    'billing' => $checkoutState['selected_billing_id'] ?? null,
                ];

                $billingData = [
                    'billing_name' => $validated['billing_name'],
                    'billing_email' => $validated['billing_email'],
                    'billing_address' => [
                        'address_one' => $validated['billing_address'] ?? '',
                        'city' => $validated['billing_city'] ?? '',
                        'country_code' => $validated['billing_country'] ?? '',
                        'postal_code' => $validated['billing_postal_code'] ?? '',
                    ],
                ];

                $order = $this->createOrderAction->handle(
                    $user,
                    $cartItems,
                    $currency,
                    'kpay',
                    $contactIds,
                    $billingData
                );

            } catch (Exception $exception) {
                Log::error('Failed to create order for KPay payment', [
                    'user_id' => $user->id,
                    'error' => $exception->getMessage(),
                ]);

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['error' => 'Failed to create order. Please try again.'], 500);
                }

                return back()->with('error', 'Failed to create order. Please try again.');
            }
        } else {
            // Update existing order billing information
            $order->update([
                'billing_name' => $validated['billing_name'],
                'billing_email' => $validated['billing_email'],
                'billing_address' => $validated['billing_address'] ?? null,
                'billing_city' => $validated['billing_city'] ?? null,
                'billing_country' => $validated['billing_country'] ?? null,
                'billing_postal_code' => $validated['billing_postal_code'] ?? null,
            ]);
        }

        try {
            $paymentResult = $this->paymentService->processKPayPayment($order, [
                'msisdn' => $validated['msisdn'],
                'pmethod' => $validated['pmethod'] ?? 'momo',
            ]);

            if (! $paymentResult['success']) {
                Log::warning('KPay payment initiation failed', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'error' => $paymentResult['error'] ?? 'Unknown error',
                ]);

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['error' => $paymentResult['error'] ?? 'Payment initiation failed. Please try again.'], 400);
                }

                return back()->with('error', $paymentResult['error'] ?? 'Payment initiation failed. Please try again.');
            }

            // Clear the session order number since payment is now in progress
            session()->forget('kpay_order_number');

            // DO NOT Clear the cart here - we'll clear it on success confirmation
            // Cart::clear();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'redirect_url' => $paymentResult['redirect_url'] ?? null,
                    'requires_action' => $paymentResult['requires_action'] ?? false,
                    'payment_id' => $paymentResult['payment_id'] ?? null,
                    'check_status_url' => isset($paymentResult['payment_id']) ? route('payment.kpay.status', $paymentResult['payment_id']) : null,
                    'success_url' => route('payment.kpay.success', $order),
                    'order_number' => $order->order_number,
                    'amount' => $order->total_amount,
                    'currency' => $order->currency,
                ]);
            }

            // If there's a redirect URL (for card checkout page), redirect there
            if (isset($paymentResult['redirect_url'])) {
                return redirect()->away($paymentResult['redirect_url']);
            }

            // If payment requires action (pending confirmation), show the pending page
            if (isset($paymentResult['requires_action']) && $paymentResult['requires_action']) {
                $payment = Payment::query()->find($paymentResult['payment_id']);

                if ($payment) {
                    return to_route('payment.kpay.status', $payment)
                        ->with('info', 'Please complete the payment on your mobile device.');
                }
            }

            // Payment was auto-processed successfully
            return to_route('payment.kpay.success', $order)
                ->with('success', 'Payment completed successfully!');

        } catch (Exception $exception) {
            Log::error('KPay payment processing error', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'error' => $exception->getMessage(),
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['error' => 'An error occurred while processing your payment. Please try again.'], 500);
            }

            return back()->with('error', 'An error occurred while processing your payment. Please try again.');
        }
    }

    /**
     * Handle a successful payment callback /redirect
     */
    public function success(Order $order): RedirectResponse
    {
        // Verify the order belongs to the current user
        if ($order->user_id !== Auth::id()) {
            return to_route('dashboard')->with('error', 'Order not found.');
        }

        $latestPayment = $order->latestPaymentAttempt();

        Log::info('KPay success callback received', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'order_payment_status' => $order->payment_status,
            'payment_id' => $latestPayment?->id,
            'payment_status' => $latestPayment?->status,
        ]);

        // If payment is already successful, process the order if not yet done
        if ($latestPayment && $latestPayment->isSuccessful()) {
            $this->processSuccessfulPayment($order->fresh(), $latestPayment->fresh());

            return to_route('payment.success', $order)
                ->with('success', 'Payment completed successfully!');
        }

        // Check if payment is pending and verify with KPay
        if ($latestPayment && $latestPayment->isPending()) {
            // Check the actual status from KPay - this may update the payment internally
            $statusResult = $this->paymentService->checkKPayPaymentStatus($latestPayment);

            Log::info('KPay status check result in success handler', [
                'order_id' => $order->id,
                'payment_id' => $latestPayment->id,
                'status_result' => $statusResult,
            ]);

            // Refresh the payment and order to get updated values
            $latestPayment->refresh();
            $order->refresh();

            if ($statusResult['success'] && $statusResult['status'] === 'succeeded') {
                // Payment confirmed - process the order with fresh instances
                $this->processSuccessfulPayment($order, $latestPayment);

                return to_route('payment.success', $order)
                    ->with('success', 'Payment completed successfully!');
            }

            if (isset($statusResult['status']) && $statusResult['status'] === 'pending') {
                // Still pending, redirect to the status page
                return to_route('payment.kpay.status', $latestPayment)
                    ->with('info', 'Payment is still being processed. Please wait.');
            }

            if (isset($statusResult['status']) && $statusResult['status'] === 'failed') {
                session(['kpay_order_number' => $order->order_number]);

                return to_route('payment.kpay.show')
                    ->with('error', $statusResult['error'] ?? 'Payment failed.');
            }
        }

        // If the order is already paid
        if ($order->isPaid()) {
            return to_route('payment.success', $order)
                ->with('success', 'Payment completed successfully!');
        }

        // Default redirect back to payment form if not paid
        session(['kpay_order_number' => $order->order_number]);

        return to_route('payment.kpay.show');
    }

    /**
     * Handle cancelled payment
     */
    public function cancel(Order $order): RedirectResponse
    {
        // Verify the order belongs to the current user
        if ($order->user_id !== Auth::id()) {
            return to_route('dashboard')->with('error', 'Order not found.');
        }

        // Update order status
        $order->update([
            'payment_status' => 'cancelled',
            'status' => 'cancelled',
        ]);

        // Mark any pending payment as canceled
        $pendingPayment = $order->payments()
            ->where('status', 'pending')
            ->orderByDesc('attempt_number')
            ->first();

        $pendingPayment?->update([
            'status' => 'cancelled',
            'failure_details' => array_merge($pendingPayment->failure_details ?? [], [
                'message' => 'Payment cancelled by user',
            ]),
            'last_attempted_at' => now(),
        ]);

        Log::info('KPay payment cancelled by user', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ]);

        session(['kpay_order_number' => $order->order_number]);

        return to_route('payment.kpay.show')->with('error', 'Payment was cancelled.');
    }

    /**
     * Show payment status / pending page
     */
    public function status(Payment $payment): View|RedirectResponse|JsonResponse
    {
        // Verify the payment belongs to the current user
        if ($payment->user_id !== Auth::id()) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Payment not found.'], 404);
            }

            return to_route('dashboard')->with('error', 'Payment not found.');
        }

        $order = $payment->order;

        if (! $order) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Order not found.'], 404);
            }

            return to_route('dashboard')->with('error', 'Order not found.');
        }

        // Check the current status
        if ($payment->isSuccessful()) {
            $this->processSuccessfulPayment($order->fresh(), $payment->fresh());

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'status' => 'succeeded',
                    'redirect_url' => route('payment.kpay.success', $order),
                ]);
            }

            return to_route('payment.kpay.success', $order)
                ->with('success', 'Payment completed successfully!');
        }

        if ($payment->isFailed()) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'status' => 'failed',
                    'error' => 'Payment failed. Please try again.',
                ]);
            }

            session(['kpay_order_number' => $order->order_number]);

            return to_route('payment.kpay.show')
                ->with('error', 'Payment failed. Please try again.');
        }

        // Check with KPay for updated status - this may update the payment internally
        $statusResult = $this->paymentService->checkKPayPaymentStatus($payment);

        // Refresh payment and order to get updated values after a status check
        $payment->refresh();
        $order->refresh();

        if ($statusResult['success'] && isset($statusResult['status'])) {
            if ($statusResult['status'] === 'succeeded') {
                $this->processSuccessfulPayment($order, $payment);

                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'status' => 'succeeded',
                        'redirect_url' => route('payment.kpay.success', $order),
                    ]);
                }

                return to_route('payment.kpay.success', $order)
                    ->with('success', 'Payment completed successfully!');
            }

            if ($statusResult['status'] === 'failed') {
                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'status' => 'failed',
                        'error' => $statusResult['error'] ?? 'Payment failed.',
                    ]);
                }

                session(['kpay_order_number' => $order->order_number]);

                return to_route('payment.kpay.show')
                    ->with('error', $statusResult['error'] ?? 'Payment failed.');
            }
        }

        // Still pending
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'status' => 'pending',
                'message' => 'Payment is still being processed. Please wait.',
            ]);
        }

        // Still pending - show the pending page
        return view('payment.kpay-pending', [
            'payment' => $payment->fresh(),
            'order' => $order->fresh(),
        ]);
    }

    /**
     * Prepare cart items from order for display
     *
     * @return array<int, array<string, mixed>>
     */
    private function prepareCartItemsForDisplay(Order $order): array
    {
        $items = [];

        foreach ($order->orderItems as $orderItem) {
            $items[] = [
                'domain_name' => $orderItem->domain_name,
                'domain_type' => $orderItem->domain_type,
                'price' => (float) $orderItem->price,
                'quantity' => $orderItem->quantity,
                'years' => $orderItem->years,
                'currency' => $orderItem->currency,
            ];
        }

        // Fallback to order items JSON if no orderItems relationship
        if ($items === [] && ! empty($order->items)) {
            foreach ($order->items as $item) {
                $items[] = [
                    'domain_name' => $item['name'] ?? $item['attributes']['domain_name'] ?? 'Item',
                    'domain_type' => $item['attributes']['type'] ?? 'registration',
                    'price' => (float) ($item['price'] ?? 0),
                    'quantity' => (int) ($item['quantity'] ?? 1),
                    'years' => (int) ($item['attributes']['years'] ?? $item['quantity'] ?? 1),
                    'currency' => $item['attributes']['currency'] ?? $order->currency,
                ];
            }
        }

        return $items;
    }

    private function processSuccessfulPayment(Order $order, Payment $payment): void
    {
        try {
            // Update payment and order status
            if (! $payment->isSuccessful()) {
                $payment->update([
                    'status' => 'succeeded',
                    'paid_at' => now(),
                ]);
            }

            if (! $order->isPaid()) {
                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'processing',
                ]);

                // Clear the cart only after successful payment confirmation
                Cart::clear();

                $contactIds = $this->getContactIdsFromOrder($order);

                $processOrderAction = resolve(ProcessOrderAfterPaymentAction::class);
                $processOrderAction->handle($order, $contactIds);
            }

            Log::info('KPay payment processed successfully', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payment_id' => $payment->id,
            ]);

        } catch (Exception $exception) {
            Log::error('Error processing successful KPay payment', [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Extract contact IDs from order metadata
     *
     * @return array<string, int|null>
     */
    private function getContactIdsFromOrder(Order $order): array
    {
        $metadata = $order->metadata;

        // Ensure metadata is an array
        if (! is_array($metadata)) {
            return [];
        }

        // Check if we have contact_ids in metadata
        if (isset($metadata['contact_ids'])) {
            return $metadata['contact_ids'];
        }

        // Check for selected_contact_id (use the same contact for all roles)
        if (isset($metadata['selected_contact_id'])) {
            $contactId = $metadata['selected_contact_id'];

            return [
                'registrant' => $contactId,
                'admin' => $contactId,
                'tech' => $contactId,
                'billing' => $contactId,
            ];
        }

        return [];
    }

    /**
     * Get currency based on user location, with session persistence
     */
    private function getLocationBasedCurrency(): string
    {
        $currency = session('selected_currency');

        if (! $currency) {
            $isFromRwanda = $this->geolocationService->isUserFromRwanda();
            $currency = $isFromRwanda ? 'RWF' : 'USD';
            session(['selected_currency' => $currency]);
        }

        return $currency;
    }
}
