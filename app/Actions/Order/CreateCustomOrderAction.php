<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Models\Currency;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\Log;

final readonly class CreateCustomOrderAction
{
    /**
     * Create an order for admin-initiated custom operations.
     *
     * @param  array<string, mixed>  $itemData
     * @param  array<string, mixed>  $metadata
     */
    public function handle(
        User $user,
        string $orderType,
        array $itemData,
        int $adminId,
        array $metadata = []
    ): Order {
        $currency = $itemData['currency'] ?? 'USD';
        $currencyModel = Currency::query()->where('code', $currency)->first();
        $exchangeRate = $currencyModel !== null ? $currencyModel->exchange_rate : 1.0;

        $price = (float) $itemData['price'];
        $quantity = (int) ($itemData['quantity'] ?? 1);
        $totalAmount = $price * $quantity;

        $orderMetadata = array_merge($metadata, [
            'created_by_admin_id' => $adminId,
            'is_custom_order' => true,
        ]);

        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_number' => Order::generateOrderNumber(),
            'type' => $orderType,
            'status' => 'completed',
            'payment_method' => 'manual',
            'payment_status' => 'manual',
            'total_amount' => $totalAmount,
            'subtotal' => $totalAmount,
            'tax' => 0,
            'currency' => $currency,
            'billing_email' => $user->email,
            'billing_name' => $user->name,
            'items' => [$itemData],
            'metadata' => $orderMetadata,
            'processed_at' => now(),
        ]);

        $itemMetadata = $itemData['metadata'] ?? [];
        $itemMetadata['admin_created'] = true;
        $itemMetadata['admin_id'] = $adminId;

        OrderItem::query()->create([
            'order_id' => $order->id,
            'domain_id' => $itemData['domain_id'] ?? null,
            'domain_name' => $itemData['name'],
            'domain_type' => $itemData['type'],
            'price' => $price,
            'currency' => $currency,
            'exchange_rate' => $exchangeRate,
            'quantity' => $quantity,
            'years' => $itemData['years'] ?? $quantity,
            'total_amount' => $totalAmount,
            'metadata' => $itemMetadata,
        ]);

        Log::info('Custom order created by admin', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'order_type' => $orderType,
            'user_id' => $user->id,
            'admin_id' => $adminId,
            'total_amount' => $totalAmount,
            'currency' => $currency,
        ]);

        return $order->fresh(['orderItems']);
    }
}
