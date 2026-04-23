<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\RetryPaymentRequest;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

final class RetryPaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService
    ) {}

    public function show(Order $order): View|RedirectResponse
    {
        abort_if($order->user_id !== Auth::id(), 403);

        if (! $order->canRetryPayment()) {
            return to_route('billing.show', $order)->with('error', 'This order cannot be retried.');
        }

        $order->load('orderItems');

        $paymentMethods = $this->getAvailablePaymentMethods();

        return view('payment.retry', [
            'order' => $order,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    public function process(RetryPaymentRequest $request, Order $order): RedirectResponse
    {
        abort_if($order->user_id !== Auth::id(), 403);

        if (! $order->canRetryPayment()) {
            return to_route('billing.show', $order)->with('error', 'This order cannot be retried.');
        }

        $paymentMethod = $request->validated('payment_method');

        $order->update([
            'payment_status' => 'pending',
            'payment_method' => $paymentMethod,
        ]);

        if ($paymentMethod === 'kpay') {
            session(['kpay_order_number' => $order->order_number]);

            return to_route('payment.kpay.show');
        }

        try {
            $result = $this->paymentService->processPayment($order, $paymentMethod);

            if (! $result['success']) {
                Log::warning('Retry payment failed', [
                    'order_id' => $order->id,
                    'payment_method' => $paymentMethod,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);

                return to_route('billing.retry-payment', $order)
                    ->with('error', $result['error'] ?? 'Payment initiation failed. Please try again.');
            }

            if (isset($result['checkout_url'])) {
                return redirect()->away($result['checkout_url']);
            }

            return to_route('billing.show', $order)
                ->with('info', 'Payment is being processed.');

        } catch (Throwable $throwable) {
            Log::error('Retry payment error', [
                'order_id' => $order->id,
                'payment_method' => $paymentMethod,
                'error' => $throwable->getMessage(),
            ]);

            return to_route('billing.retry-payment', $order)
                ->with('error', 'An error occurred while processing your payment. Please try again.');
        }
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    private function getAvailablePaymentMethods(): array
    {
        $methods = [];

        if (config('services.payment.stripe.publishable_key')) {
            $methods[] = [
                'id' => 'stripe',
                'name' => 'Credit Card (Stripe)',
            ];
        }

        if (config('services.payment.kpay.base_url')
            && config('services.payment.kpay.username')
            && config('services.payment.kpay.password')
            && config('services.payment.kpay.retailer_id')) {
            $methods[] = [
                'id' => 'kpay',
                'name' => 'Mobile Money & Card (KPay)',
            ];
        }

        return $methods;
    }
}
