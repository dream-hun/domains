<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Order\CreateOrderFromCartAction;
use App\Actions\Order\ProcessOrderAfterPaymentAction;
use App\Helpers\CurrencyHelper;
use App\Http\Requests\PawaPayPaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Services\CartPriceConverter;
use App\Services\PaymentService;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

final class PawaPayPaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly CreateOrderFromCartAction $createOrderAction,
        private readonly CartPriceConverter $cartPriceConverter
    ) {}

    /**
     * @throws Throwable
     */
    public function show(): View|RedirectResponse
    {
        $orderNumber = session('pawapay_order_number');
        $order = null;

        if ($orderNumber) {
            $order = Order::query()
                ->where('order_number', $orderNumber)
                ->where('user_id', Auth::id())
                ->where('payment_method', 'pawapay')
                ->where('payment_status', 'pending')
                ->first();
        }

        if (! $order && Cart::isEmpty()) {
            session()->forget('pawapay_order_number');

            return to_route('checkout.index')->with('error', 'No order found. Please start the checkout process again.');
        }

        $user = Auth::user();
        $currency = CurrencyHelper::getUserCurrency();

        if ($order) {
            $cartItems = $this->prepareCartItemsForDisplay($order);
            $totalAmount = (float) $order->total_amount;
            $subtotal = (float) $order->subtotal;
            $currency = $order->currency;

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

        return view('payment.pawapay', [
            'order' => $order,
            'user' => $user,
            'cartItems' => $cartItems,
            'totalAmount' => $totalAmount,
            'subtotal' => $subtotal,
            'currency' => $currency,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function process(PawaPayPaymentRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $user = Auth::user();

        $orderNumber = session('pawapay_order_number');
        $order = null;

        if ($orderNumber) {
            $order = Order::query()
                ->where('order_number', $orderNumber)
                ->where('user_id', $user->id)
                ->where('payment_method', 'pawapay')
                ->where('payment_status', 'pending')
                ->first();
        }

        if (! $order) {
            $cartItems = Cart::getContent();

            if ($cartItems->isEmpty()) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['error' => 'No order found. Please start the checkout process again.'], 400);
                }

                return to_route('checkout.index')->with('error', 'No order found. Please start the checkout process again.');
            }

            try {
                $currency = CurrencyHelper::getUserCurrency();
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
                    'pawapay',
                    $contactIds,
                    $billingData
                );
            } catch (Exception $exception) {
                Log::error('Failed to create order for PawaPay payment', [
                    'user_id' => $user->id,
                    'error' => $exception->getMessage(),
                ]);

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['error' => 'Failed to create order. Please try again.'], 500);
                }

                return back()->with('error', 'Failed to create order. Please try again.');
            }
        } else {
            $order->update([
                'billing_name' => $validated['billing_name'],
                'billing_email' => $validated['billing_email'],
                'billing_address' => [
                    'address' => $validated['billing_address'] ?? '',
                    'city' => $validated['billing_city'] ?? '',
                    'country_code' => $validated['billing_country'] ?? '',
                    'postal_code' => $validated['billing_postal_code'] ?? '',
                ],
            ]);
        }

        try {
            $paymentResult = $this->paymentService->processPawaPayPayment($order, [
                'msisdn' => $validated['msisdn'],
            ]);

            if (! $paymentResult['success']) {
                Log::warning('PawaPay payment initiation failed', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'error' => $paymentResult['error'] ?? 'Unknown error',
                ]);

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['error' => $paymentResult['error'] ?? 'Payment initiation failed.'], 400);
                }

                return back()->with('error', $paymentResult['error'] ?? 'Payment initiation failed. Please try again.');
            }

            session()->forget('pawapay_order_number');
            Cart::clear();

            $payment = Payment::query()->find($paymentResult['payment_id']);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'requires_action' => true,
                    'payment_id' => $paymentResult['payment_id'] ?? null,
                    'check_status_url' => $payment ? route('payment.pawapay.status', $payment) : null,
                    'success_url' => route('payment.success', $order),
                    'order_number' => $order->order_number,
                    'amount' => $order->total_amount,
                    'currency' => $order->currency,
                ]);
            }

            if ($payment) {
                return to_route('payment.pawapay.status', $payment)
                    ->with('info', 'Please complete the payment on your mobile device.');
            }

            return to_route('payment.success', $order)->with('success', 'Payment completed successfully!');

        } catch (Exception $exception) {
            Log::error('PawaPay payment processing error', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'error' => $exception->getMessage(),
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['error' => 'An error occurred while processing your payment.'], 500);
            }

            return back()->with('error', 'An error occurred while processing your payment. Please try again.');
        }
    }

    public function cancel(Order $order): RedirectResponse
    {
        if ($order->user_id !== Auth::id()) {
            return to_route('dashboard')->with('error', 'Order not found.');
        }

        $order->update([
            'payment_status' => 'cancelled',
            'status' => 'cancelled',
        ]);

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

        Log::info('PawaPay payment cancelled by user', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ]);

        session(['pawapay_order_number' => $order->order_number]);

        return to_route('payment.pawapay.show')->with('error', 'Payment was cancelled.');
    }

    public function status(Payment $payment): View|RedirectResponse|JsonResponse
    {
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

        if ($payment->isSuccessful()) {
            $this->processSuccessfulPayment($order->fresh(), $payment->fresh());

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'status' => 'succeeded',
                    'redirect_url' => route('payment.success', $order),
                ]);
            }

            return to_route('payment.success', $order)->with('success', 'Payment completed successfully!');
        }

        if ($payment->isFailed()) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'status' => 'failed',
                    'error' => 'Payment failed. Please try again.',
                ]);
            }

            session(['pawapay_order_number' => $order->order_number]);

            return to_route('payment.pawapay.show')->with('error', 'Payment failed. Please try again.');
        }

        $statusResult = $this->paymentService->checkPawaPayDepositStatus($payment);

        $payment->refresh();
        $order->refresh();

        if ($statusResult['success'] && isset($statusResult['status'])) {
            if ($statusResult['status'] === 'succeeded') {
                $this->processSuccessfulPayment($order, $payment);

                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'status' => 'succeeded',
                        'redirect_url' => route('payment.success', $order),
                    ]);
                }

                return to_route('payment.success', $order)->with('success', 'Payment completed successfully!');
            }

            if ($statusResult['status'] === 'failed') {
                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'status' => 'failed',
                        'error' => $statusResult['error'] ?? 'Payment failed.',
                    ]);
                }

                session(['pawapay_order_number' => $order->order_number]);

                return to_route('payment.pawapay.show')->with('error', $statusResult['error'] ?? 'Payment failed.');
            }
        }

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'status' => 'pending',
                'message' => 'Payment is still being processed. Please wait.',
            ]);
        }

        return view('payment.pawapay-pending', [
            'payment' => $payment->fresh(),
            'order' => $order->fresh(),
        ]);
    }

    /**
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

                Cart::clear();

                $contactIds = $this->getContactIdsFromOrder($order);

                $processOrderAction = resolve(ProcessOrderAfterPaymentAction::class);
                $processOrderAction->handle($order, $contactIds);
            }

            Log::info('PawaPay payment processed successfully', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payment_id' => $payment->id,
            ]);
        } catch (Exception $exception) {
            Log::error('Error processing successful PawaPay payment', [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @return array<string, int|null>
     */
    private function getContactIdsFromOrder(Order $order): array
    {
        $metadata = $order->metadata;

        if (! is_array($metadata)) {
            return [];
        }

        if (isset($metadata['contact_ids'])) {
            return $metadata['contact_ids'];
        }

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
}
