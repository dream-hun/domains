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
        private NotificationService $notificationService
    ) {}

    public function createOrder(array $data): Order
    {
        $currency = Currency::where('code', $data['currency'])->first();

        // Get billing contact (use billing contact from contact_ids)
        $billingContactId = $data['contact_ids']['billing'];
        $contact = Contact::find($billingContactId);

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
            'status' => 'pending',
            'payment_method' => $data['payment_method'],
            'payment_status' => 'pending',
            'total_amount' => $convertedTotal,
            'currency' => $currency->code,
            'coupon_code' => $couponCode,
            'discount_type' => $discountType,
            'discount_amount' => $discountAmount,
            'billing_email' => $contact->email,
            'billing_name' => $contact->full_name,
            'billing_address' => [
                'address_one' => $contact->address_one,
                'address_two' => $contact->address_two,
                'city' => $contact->city,
                'state_province' => $contact->state_province,
                'postal_code' => $contact->postal_code,
                'country_code' => $contact->country_code,
            ],
        ]);

        // Create order items - prices already in correct currency
        foreach ($data['cart_items'] as $item) {
            // No conversion needed - items are already in the correct currency
            $itemPrice = $item->price;
            $itemTotal = $item->getPriceSum();
            $itemCurrency = $item->attributes->currency ?? 'USD';

            // Get the exchange rate for the item's currency
            $itemCurrencyModel = Currency::where('code', $itemCurrency)->first();
            $exchangeRate = $itemCurrencyModel?->exchange_rate ?? 1.0;

            OrderItem::create([
                'order_id' => $order->id,
                'domain_name' => $item->name,
                'domain_type' => $item->attributes['tld'] ?? 'registration',
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

    public function processDomainRegistrations(Order $order, array $contactIds): void
    {
        $order->update(['status' => 'processing']);

        try {
            if (! isset($contactIds['registrant'], $contactIds['admin'], $contactIds['tech'], $contactIds['billing'])) {
                throw new Exception('All contact IDs (registrant, admin, tech, billing) are required.');
            }

            $contacts = [
                'registrant' => $contactIds['registrant'],
                'admin' => $contactIds['admin'],
                'technical' => $contactIds['tech'],
                'billing' => $contactIds['billing'],
            ];

            $results = $this->billingService->processDomainRegistrations($order, $contacts);

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
            Log::error('Domain registration failed for order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            $order->update([
                'status' => 'requires_attention',
                'notes' => 'Payment succeeded but domain registration failed: '.$e->getMessage(),
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
