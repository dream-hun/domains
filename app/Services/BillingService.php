<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\RegisterDomainAction;
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
    public function createOrderFromCart(User $user, array $billingData, array $checkoutData): Order
    {
        return DB::transaction(function () use ($user, $billingData, $checkoutData) {
            $cartItems = Cart::getContent();

            if ($cartItems->isEmpty()) {
                throw new Exception('Cart is empty');
            }

            // Calculate total amount
            $subtotal = $cartItems->sum(fn ($item) => $item->price * $item->quantity);
            $discount = $checkoutData['discount'] ?? 0;
            $totalAmount = $subtotal - $discount;

            // Create the order
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => Order::generateOrderNumber(),
                'status' => 'pending',
                'payment_method' => $checkoutData['payment_method'] ?? 'stripe',
                'payment_status' => 'pending',
                'total_amount' => $totalAmount,
                'currency' => 'USD',
                'billing_name' => $billingData['billing_name'] ?? $user->name,
                'billing_email' => $billingData['billing_email'] ?? $user->email,
                'billing_address' => $billingData['billing_address'] ?? '',
                'billing_city' => $billingData['billing_city'] ?? '',
                'billing_country' => $billingData['billing_country'] ?? '',
                'billing_postal_code' => $billingData['billing_postal_code'] ?? '',
                'notes' => isset($checkoutData['coupon_code']) && $checkoutData['coupon_code'] ? "Coupon applied: {$checkoutData['coupon_code']}" : null,
            ]);

            // Create order items
            foreach ($cartItems as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'domain_name' => $cartItem->name,
                    'domain_type' => $cartItem->attributes->type ?? 'registration',
                    'price' => $cartItem->price,
                    'currency' => $cartItem->attributes->currency ?? 'USD',
                    'quantity' => $cartItem->quantity,
                    'years' => $cartItem->quantity, // For domains, quantity represents years
                    'total_amount' => $cartItem->price * $cartItem->quantity,
                ]);
            }

            Log::info('Order created successfully', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $user->id,
                'total_amount' => $totalAmount,
            ]);

            return $order;
        });
    }

    /**
     * Process domain registrations after successful payment
     */
    public function processDomainRegistrations(Order $order): array
    {
        $results = [
            'successful' => [],
            'failed' => [],
        ];

        try {
            // Get selected contact from checkout session or fallback to primary contact
            $checkoutData = session('checkout', []);
            $selectedContactId = $checkoutData['selected_contact_id'] ?? null;

            $selectedContact = null;
            if ($selectedContactId) {
                $selectedContact = $order->user->contacts()->find($selectedContactId);
            }

            if (! $selectedContact) {
                // Fallback to primary contact
                $selectedContact = $order->user->contacts()->where('is_primary', true)->first();
            }

            if (! $selectedContact) {
                // Use the first available contact
                $selectedContact = $order->user->contacts()->first();
            }

            if (! $selectedContact) {
                throw new Exception('No contact information found for user. Please create a contact profile first.');
            }

            // Prepare contacts for registration (use same contact for all types)
            $contacts = [
                'registrant' => $selectedContact->id,
                'admin' => $selectedContact->id,
                'technical' => $selectedContact->id,
                'billing' => $selectedContact->id,
            ];

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
}
