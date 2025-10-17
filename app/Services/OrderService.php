<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contact;
use App\Models\Currency;
use App\Models\Order;
use App\Models\OrderItem;
use App\Notifications\OrderConfirmationNotification;

final class OrderService
{
    public function createOrder(array $data): Order
    {
        $currency = Currency::where('code', $data['currency'])->first();
        $contact = Contact::find($data['contact_id']);

        // Calculate total
        $total = 0;
        foreach ($data['cart_items'] as $item) {
            $total += $item->getPriceSum();
        }

        // Convert to user's currency
        $baseCurrency = Currency::getBaseCurrency();
        $convertedTotal = $baseCurrency->convertTo($total, $currency);

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

        // Create order items
        foreach ($data['cart_items'] as $item) {
            $itemPrice = $baseCurrency->convertTo($item->price, $currency);
            $itemTotal = $baseCurrency->convertTo($item->getPriceSum(), $currency);

            OrderItem::create([
                'order_id' => $order->id,
                'domain_name' => $item->name,
                'domain_type' => $item->attributes['tld'] ?? 'registration',
                'price' => $itemPrice,
                'currency' => $currency->code,
                'quantity' => $item->quantity,
                'years' => $item->quantity,
                'total_amount' => $itemTotal,
            ]);
        }

        return $order->fresh(['orderItems']);
    }

    public function processDomainRegistrations(Order $order): void
    {
        // Update order status to processing
        $order->update(['status' => 'processing']);

        // TODO: Integrate with domain registration service
        // This will be implemented when domain registration workflow is ready
        // For now, we just mark the order as processing
    }

    public function sendOrderConfirmation(Order $order): void
    {
        $order->user->notify(new OrderConfirmationNotification($order));
    }
}
