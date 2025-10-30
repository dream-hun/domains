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

final class OrderService
{
    public function __construct(
        private readonly BillingService $billingService,
        private readonly NotificationService $notificationService
    ) {}

    public function createOrder(array $data): Order
    {
        $currency = Currency::where('code', $data['currency'])->first();
        $contact = Contact::find($data['contact_id']);

        // Calculate total - items already have correct currency from cart
        $total = 0;
        foreach ($data['cart_items'] as $item) {
            $total += $item->getPriceSum();
        }

        // Cart items are already in the correct currency, no conversion needed
        $convertedTotal = $total;

        // Create order
        $order = Order::create([
            'user_id' => $data['user_id'],
            'order_number' => Order::generateOrderNumber(),
            'status' => 'pending',
            'payment_method' => $data['payment_method'],
            'payment_status' => 'pending',
            'total_amount' => $convertedTotal,
            'currency' => $currency->code,
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

    public function processDomainRegistrations(Order $order): void
    {
        // Update order status to process
        $order->update(['status' => 'processing']);

        try {
            // Get selected contact from checkout session
            $checkoutData = session('checkout', []);
            $selectedContactId = $checkoutData['selected_contact_id'] ?? null;

            $selectedContact = $this->getSelectedContact($order->user, $selectedContactId);

            if (! $selectedContact) {
                throw new Exception('No contact information found for domain registration.');
            }

            // Prepare contacts (use same contact for all types)
            $contacts = [
                'registrant' => $selectedContact->id,
                'admin' => $selectedContact->id,
                'technical' => $selectedContact->id,
                'billing' => $selectedContact->id,
            ];

            $results = $this->billingService->processDomainRegistrations($order, $contacts);

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
        // Try selected contact first
        if ($contactId) {
            $contact = $user->contacts()->find($contactId);
            if ($contact) {
                return $contact;
            }
        }

        // Fallback to primary contact
        $contact = $user->contacts()->where('is_primary', true)->first();
        if ($contact) {
            return $contact;
        }

        // Fallback to first available contact
        return $user->contacts()->first();
    }
}
