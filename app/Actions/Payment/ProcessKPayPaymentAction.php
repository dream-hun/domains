<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Models\Order;
use App\Models\User;
use App\Services\BillingService;
use App\Services\PaymentService;
use Darryldecode\Cart\CartCollection;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class ProcessKPayPaymentAction
{
    public function __construct(
        private BillingService $billingService,
        private PaymentService $paymentService
    ) {}

    /**
     * Process KPay payment
     *
     * @param  array<string, int|null>  $contactIds
     * @param  array<string, mixed>|null  $billingData
     * @param  array<string, mixed>|null  $coupon
     * @return array{success: bool, order?: Order, redirect_url?: string, payment_id?: int, error?: string}
     *
     * @throws Throwable
     */
    public function handle(
        User $user,
        string $msisdn,
        ?string $pmethod = null,
        ?CartCollection $cartItems = null,
        string $currency = 'USD',
        array $contactIds = [],
        ?array $billingData = null,
        ?array $coupon = null,
        float $discountAmount = 0.0
    ): array {
        if ($msisdn === '' || $msisdn === '0') {
            return [
                'success' => false,
                'error' => 'Phone number is required for KPay payment.',
            ];
        }

        try {
            return DB::transaction(function () use ($user, $msisdn, $pmethod, $cartItems, $billingData): array {
                $order = $this->getOrCreateOrder($user, $cartItems, $billingData);

                if (! $order instanceof Order) {
                    return [
                        'success' => false,
                        'error' => 'Your cart is empty.',
                    ];
                }

                $paymentResult = $this->paymentService->processPayment($order, 'kpay', [
                    'msisdn' => $msisdn,
                    'pmethod' => $pmethod,
                ]);

                if (! $paymentResult['success']) {
                    return [
                        'success' => false,
                        'error' => $paymentResult['error'] ?? 'Payment processing failed. Please try again.',
                    ];
                }

                session()->forget('kpay_order_number');

                return [
                    'success' => true,
                    'order' => $order,
                    'redirect_url' => $paymentResult['redirect_url'] ?? null,
                    'payment_id' => $paymentResult['payment_id'] ?? null,
                ];
            });
        } catch (Exception $exception) {
            Log::error('KPay payment processing error: '.$exception->getMessage());

            return [
                'success' => false,
                'error' => 'An error occurred while processing your payment.',
            ];
        }
    }

    private function getOrCreateOrder(
        User $user,
        ?CartCollection $cartItems,
        ?array $billingData
    ): ?Order {
        $orderNumber = session('kpay_order_number');
        if ($orderNumber) {
            $order = Order::query()
                ->where('order_number', $orderNumber)
                ->where('user_id', $user->id)
                ->where('payment_method', 'kpay')
                ->where('payment_status', 'pending')
                ->first();

            if ($order) {
                if ($billingData) {
                    $order->update($billingData);
                }

                return $order;
            }
        }

        if (! $cartItems instanceof CartCollection) {
            $cartItems = Cart::getContent();
        }

        if ($cartItems->isEmpty()) {
            return null;
        }

        $checkoutData = array_merge(session('checkout', []), ['payment_method' => 'kpay']);

        return $this->billingService->createOrderFromCart(
            $user,
            $billingData ?? [],
            $checkoutData
        );
    }
}
