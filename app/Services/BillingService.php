<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\RegisterDomainAction;
use App\Helpers\CurrencyHelper;
use App\Livewire\CartComponent;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class BillingService
{
    public function __construct(
        private readonly RegisterDomainAction $registerDomainAction
    ) {}

    /**
     * Create an order from cart items
     */
    public function createOrderFromCart(User $user, array $billingData, array $checkoutData, ?array $preparedCartData = null): Order
    {
        return DB::transaction(function () use ($user, $billingData, $checkoutData, $preparedCartData) {
            // Get prepared cart data with currency conversion and discount calculations
            $cartData = $this->getPreparedCartData($preparedCartData);

            // Extract cart information
            $items = $cartData['items'];
            $subtotal = $cartData['subtotal'];
            $total = $cartData['total'];
            $currency = $cartData['currency'];
            $coupon = $cartData['coupon'] ?? null;

            // Prepare order notes with coupon information
            $notes = null;
            if ($coupon) {
                $formattedDiscount = CurrencyHelper::formatMoney($coupon['discount_amount'], $currency);
                $notes = "Coupon applied: {$coupon['code']} ({$formattedDiscount} discount)";
            }

            // Create the order
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => Order::generateOrderNumber(),
                'status' => 'pending',
                'payment_method' => $checkoutData['payment_method'] ?? 'stripe',
                'payment_status' => 'pending',
                'total_amount' => $total,
                'currency' => $currency,
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
                OrderItem::create([
                    'order_id' => $order->id,
                    'domain_name' => $item['domain_name'],
                    'domain_type' => $item['domain_type'],
                    'price' => $item['price'],
                    'currency' => $item['currency'],
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
                'discount' => $coupon['discount_amount'] ?? 0,
            ]);

            return $order;
        });
    }

    /**
     * Process domain registrations after successful payment
     */
    public function processDomainRegistrations(Order $order, array $contacts): array
    {
        $results = [
            'successful' => [],
            'failed' => [],
        ];

        try {

            // Process each order item (domain)
            foreach ($order->orderItems as $orderItem) {
                try {
                    $result = $this->registerDomainAction->handle(
                        $orderItem->domain_name,
                        $contacts,
                        $orderItem->years,
                        [], // Use default nameservers
                        true // Use single contact
                    );

                    if ($result['success']) {
                        $results['successful'][] = [
                            'domain' => $orderItem->domain_name,
                            'domain_id' => $result['domain_id'] ?? null,
                            'message' => $result['message'] ?? 'Domain registered successfully',
                        ];

                        // Update order item with domain ID if available
                        if (isset($result['domain_id'])) {
                            $orderItem->update(['domain_id' => $result['domain_id']]);
                        }

                        Log::info('Domain registered successfully via billing service', [
                            'order_id' => $order->id,
                            'domain' => $orderItem->domain_name,
                            'domain_id' => $result['domain_id'] ?? null,
                        ]);
                    } else {
                        $results['failed'][] = [
                            'domain' => $orderItem->domain_name,
                            'message' => $result['message'] ?? 'Registration failed',
                        ];

                        Log::error('Domain registration failed via billing service', [
                            'order_id' => $order->id,
                            'domain' => $orderItem->domain_name,
                            'error' => $result['message'] ?? 'Unknown error',
                        ]);
                    }
                } catch (Exception $e) {
                    $results['failed'][] = [
                        'domain' => $orderItem->domain_name,
                        'message' => $e->getMessage(),
                    ];

                    Log::error('Domain registration exception via billing service', [
                        'order_id' => $order->id,
                        'domain' => $orderItem->domain_name,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Update order status based on results
            if (empty($results['failed'])) {
                $order->update(['status' => 'completed']);
            } elseif (empty($results['successful'])) {
                $order->update(['status' => 'failed']);
            } else {
                $order->update(['status' => 'partially_completed']);
            }

        } catch (Exception $e) {
            Log::error('Failed to process domain registrations for order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            $order->update(['status' => 'failed']);

            throw $e;
        }

        return $results;
    }

    /**
     * Get prepared cart data from provided data, session, or Cart facade
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

        if ($cartItems->isEmpty()) {
            throw new Exception('Cart is empty');
        }

        // Instantiate CartComponent to use its prepareCartForPayment method
        $cartComponent = new CartComponent;
        $cartComponent->mount();

        return $cartComponent->prepareCartForPayment();
    }

    /**
     * Validate cart data structure
     */
    private function validateCartData(array $cartData): void
    {
        if (! isset($cartData['items']) || empty($cartData['items'])) {
            throw new Exception('Cart data is missing items');
        }

        if (! isset($cartData['subtotal'])) {
            throw new Exception('Cart data is missing subtotal');
        }

        if (! isset($cartData['total'])) {
            throw new Exception('Cart data is missing total');
        }

        if (! isset($cartData['currency'])) {
            Log::warning('Cart data is missing currency, defaulting to USD');
            $cartData['currency'] = 'USD';
        }
    }
}
