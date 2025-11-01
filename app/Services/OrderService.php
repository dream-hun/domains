<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contact;
use App\Models\Currency;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Notifications\OrderConfirmationNotification;
use Exception;
use Illuminate\Support\Facades\Log;

final readonly class OrderService
{
    public function __construct(
        private BillingService      $billingService,
        private NotificationService $notificationService,
        private RenewalService      $renewalService
    ) {}

    public function createOrder(array $data): Order
    {
        $currency = Currency::where('code', $data['currency'])->first();

        // Determine order type based on cart items
        $orderType = $this->determineOrderType($data['cart_items']);

        // Get billing contact
        $billingContactId = $data['contact_ids']['billing'] ?? null;
        $contact = $billingContactId ? Contact::find($billingContactId) : null;

        // For renewals, use user's email if no contact specified
        if (! $contact && $orderType === 'renewal') {
            $user = User::find($data['user_id']);
            $billingEmail = $user->email;
            $billingName = $user->name;
            $billingAddress = [];
        } else {
            $billingEmail = $contact->email ?? '';
            $billingName = $contact->full_name ?? '';
            $billingAddress = [
                'address_one' => $contact->address_one ?? '',
                'address_two' => $contact->address_two ?? '',
                'city' => $contact->city ?? '',
                'state_province' => $contact->state_province ?? '',
                'postal_code' => $contact->postal_code ?? '',
                'country_code' => $contact->country_code ?? '',
            ];
        }

        // Calculate total - items already have correct currency from cart
        $total = 0;
        foreach ($data['cart_items'] as $item) {
            $total += $item->getPriceSum();
        }

        $discountAmount = $data['discount_amount'] ?? 0;
        $convertedTotal = max(0, $total - $discountAmount);

        $couponCode = $data['coupon']?->code ?? null;
        $discountType = $data['coupon']?->type->value ?? null;

        $order = Order::create([
            'user_id' => $data['user_id'],
            'order_number' => Order::generateOrderNumber(),
            'type' => $orderType,
            'status' => 'pending',
            'payment_method' => $data['payment_method'],
            'payment_status' => 'pending',
            'total_amount' => $convertedTotal,
            'subtotal' => $total,
            'tax' => 0,
            'currency' => $currency->code,
            'coupon_code' => $couponCode,
            'discount_type' => $discountType,
            'discount_amount' => $discountAmount,
            'billing_email' => $billingEmail,
            'billing_name' => $billingName,
            'billing_address' => $billingAddress,
            'items' => $data['cart_items']->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'price' => $item->price,
                'quantity' => $item->quantity,
                'attributes' => $item->attributes->toArray(),
            ])->toArray(),
        ]);

        // Create order items - prices already in correct currency
        foreach ($data['cart_items'] as $item) {
            // No conversion needed - items are already in the correct currency
            $itemPrice = $item->price;
            $itemTotal = $item->getPriceSum();
            $itemCurrency = $item->attributes->currency ?? 'USD';
            $itemType = $item->attributes->type ?? 'registration';
            $domainId = $item->attributes->domain_id ?? null;

            // Get the exchange rate for the item's currency
            $itemCurrencyModel = Currency::where('code', $itemCurrency)->first();
            $exchangeRate = $itemCurrencyModel?->exchange_rate ?? 1.0;

            OrderItem::create([
                'order_id' => $order->id,
                'domain_name' => $item->attributes->domain_name ?? $item->name,
                'domain_type' => $itemType,
                'domain_id' => $domainId,
                'price' => $itemPrice,
                'currency' => $itemCurrency,
                'exchange_rate' => $exchangeRate,
                'quantity' => $item->quantity,
                'years' => $item->quantity,
                'total_amount' => $itemTotal,
            ]);
        }

        return $order->fresh(['orderItems']);
    }

    /**
     * Determine order type based on cart items
     */
    private function determineOrderType($cartItems): string
    {
        $hasRegistration = false;
        $hasRenewal = false;
        $hasTransfer = false;

        foreach ($cartItems as $item) {
            $itemType = $item->attributes->type ?? 'registration';

            match ($itemType) {
                'renewal' => $hasRenewal = true,
                'transfer' => $hasTransfer = true,
                default => $hasRegistration = true,
            };
        }

        // If all items are renewals, mark as renewal order
        if ($hasRenewal && ! $hasRegistration && ! $hasTransfer) {
            return 'renewal';
        }

        // If all items are transfers, mark as transfer order
        if ($hasTransfer && ! $hasRegistration && ! $hasRenewal) {
            return 'transfer';
        }

        // Default to registration (or mixed)
        return 'registration';
    }

    public function processDomainRegistrations(Order $order, array $contactIds = []): void
    {
        $order->update(['status' => 'processing']);

        try {
            // Check order type
            if ($order->type === 'renewal') {
                // Process renewals
                Log::info('Processing renewal order', ['order_id' => $order->id]);
                $results = $this->renewalService->processDomainRenewals($order);
            } else {
                // Process registrations
                if (! isset($contactIds['registrant'], $contactIds['admin'], $contactIds['tech'], $contactIds['billing'])) {
                    throw new Exception('All contact IDs (registrant, admin, tech, billing) are required for domain registration.');
                }

                $contacts = [
                    'registrant' => $contactIds['registrant'],
                    'admin' => $contactIds['admin'],
                    'technical' => $contactIds['tech'],
                    'billing' => $contactIds['billing'],
                ];

                Log::info('Processing registration order', ['order_id' => $order->id]);
                $results = $this->billingService->processDomainRegistrations($order, $contacts);
            }

            // Update order status based on results
            if (empty($results['failed'])) {
                $order->update(['status' => 'completed']);
            } elseif (empty($results['successful'])) {
                $order->update(['status' => 'failed']);
                $this->notificationService->notifyAdminOfFailedRegistration($order, $results);
            } else {
                $order->update(['status' => 'partially_completed']);
                $this->notificationService->notifyAdminOfPartialFailure($order, $results);
            }

        } catch (Exception $e) {
            Log::error('Order processing failed', [
                'order_id' => $order->id,
                'order_type' => $order->type,
                'error' => $e->getMessage(),
            ]);

            $order->update([
                'status' => 'requires_attention',
                'notes' => 'Payment succeeded but processing failed: '.$e->getMessage(),
            ]);

            $this->notificationService->notifyAdminOfCriticalFailure($order, $e);
        }
    }

    public function sendOrderConfirmation(Order $order): void
    {
        $order->user->notify(new OrderConfirmationNotification($order));
    }

    /**
     * Get selected contact for domain registration
     */
    private function getSelectedContact(User $user, ?int $contactId): ?Contact
    {
        if ($contactId) {
            $contact = $user->contacts()->find($contactId);
            if ($contact) {
                return $contact;
            }
        }

        $contact = $user->contacts()->where('is_primary', true)->first();
        if ($contact) {
            return $contact;
        }
        return $user->contacts()->first();
    }
}
