<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\CurrencyHelper;
use App\Livewire\CartComponent;
use App\Models\Currency;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class BillingService
{
    /**
     * Create an order from cart items
     *
     * @throws Throwable
     */
    public function createOrderFromCart(User $user, array $billingData, array $checkoutData, ?array $preparedCartData = null): Order
    {
        return DB::transaction(function () use ($user, $billingData, $checkoutData, $preparedCartData) {

            $cartData = $this->getPreparedCartData($preparedCartData);

            $items = $cartData['items'];
            $orderType = $this->determineOrderType($items);
            $subtotal = $cartData['subtotal'];
            $total = $cartData['total'];
            $currency = $cartData['currency'];
            $coupon = $cartData['coupon'] ?? null;

            // Prepare order data with coupon information
            $notes = null;
            $couponCode = null;
            $discountType = null;
            $discountAmount = null;

            if ($coupon) {
                $formattedDiscount = CurrencyHelper::formatMoney($coupon['discount_amount'], $currency);
                $notes = sprintf('Coupon applied: %s (%s discount)', $coupon['code'], $formattedDiscount);
                $couponCode = $coupon['code'];
                $discountType = $coupon['type'];
                $discountAmount = $coupon['discount_amount'];
            }

            // Create the order
            $order = Order::query()->create([
                'user_id' => $user->id,
                'order_number' => Order::generateOrderNumber(),
                'type' => $orderType,
                'status' => 'pending',
                'payment_method' => $checkoutData['payment_method'] ?? 'stripe',
                'payment_status' => 'pending',
                'subtotal' => $subtotal,
                'tax' => 0,
                'total_amount' => $total,
                'currency' => $currency,
                'coupon_code' => $couponCode,
                'discount_type' => $discountType,
                'discount_amount' => $discountAmount,
                'billing_name' => $billingData['billing_name'] ?? $user->name,
                'billing_email' => $billingData['billing_email'] ?? $user->email,
                'billing_address' => $billingData['billing_address'] ?? '',
                'billing_city' => $billingData['billing_city'] ?? '',
                'billing_country' => $billingData['billing_country'] ?? '',
                'billing_postal_code' => $billingData['billing_postal_code'] ?? '',
                'notes' => $notes,
            ]);

            // Create order items from prepared cart data
            foreach ($items as $item) {
                // Get the exchange rate for the item's currency
                $itemCurrency = Currency::query()->where('code', $item['currency'])->first();
                $exchangeRate = $itemCurrency?->exchange_rate ?? 1.0;

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'domain_name' => $item['domain_name'],
                    'domain_type' => $item['domain_type'],
                    'price' => $item['price'],
                    'currency' => $item['currency'],
                    'exchange_rate' => $exchangeRate,
                    'quantity' => $item['quantity'],
                    'years' => $item['years'],
                    'total_amount' => $item['price'] * $item['quantity'],
                    'domain_id' => $item['domain_id'] ?? null,
                ]);
            }

            Log::info('Order created successfully', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $user->id,
                'total_amount' => $total,
                'currency' => $currency,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount ?? 0,
                'coupon_code' => $couponCode,
            ]);

            return $order;
        });
    }

    /**
     * Get prepared cart data from provided data, session, or Cart facade
     *
     * @throws Exception
     */
    private function getPreparedCartData(?array $preparedCartData): array
    {
        // Use provided prepared data if available
        if ($preparedCartData !== null) {
            Log::info('Using provided prepared cart data for order creation');
            $this->validateCartData($preparedCartData);

            return $preparedCartData;
        }

        // Try to get from session
        if (session()->has('cart')) {
            Log::info('Using cart data from session for order creation');
            $sessionCartData = [
                'items' => session('cart'),
                'subtotal' => session('cart_subtotal', 0),
                'total' => session('cart_total', 0),
                'currency' => session('selected_currency', 'USD'),
            ];

            if (session()->has('coupon')) {
                $sessionCartData['coupon'] = session('coupon');
            }

            $this->validateCartData($sessionCartData);

            return $sessionCartData;
        }

        // Fall back to Cart facade and prepare data
        Log::info('Preparing cart data from Cart facade for order creation');
        $cartItems = Cart::getContent();

        throw_if($cartItems->isEmpty(), Exception::class, 'Cart is empty');

        // Instantiate CartComponent to use its prepareCartForPayment method
        $cartComponent = new CartComponent;
        $cartComponent->mount();

        return $cartComponent->prepareCartForPayment();
    }

    /**
     * Validate cart data structure
     *
     * @throws Exception
     */
    private function validateCartData(array &$cartData): void
    {
        throw_if(empty($cartData['items']), Exception::class, 'Cart data is missing items');

        throw_unless(isset($cartData['subtotal']), Exception::class, 'Cart data is missing subtotal');

        throw_unless(isset($cartData['total']), Exception::class, 'Cart data is missing total');

        if (! isset($cartData['currency'])) {
            Log::warning('Cart data is missing currency, defaulting to USD');
            $cartData['currency'] = 'USD';
        }
    }

    private function determineOrderType(array $items): string
    {
        $hasRegistration = false;
        $hasRenewal = false;
        $hasTransfer = false;

        foreach ($items as $item) {
            $itemType = $item['domain_type'] ?? 'registration';

            match ($itemType) {
                'renewal' => $hasRenewal = true,
                'transfer' => $hasTransfer = true,
                default => $hasRegistration = true,
            };
        }

        if ($hasRenewal && ! $hasRegistration && ! $hasTransfer) {
            return 'renewal';
        }

        if ($hasTransfer && ! $hasRegistration && ! $hasRenewal) {
            return 'transfer';
        }

        return 'registration';
    }
}
