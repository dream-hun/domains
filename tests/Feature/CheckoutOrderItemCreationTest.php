<?php

declare(strict_types=1);

use App\Http\Controllers\CheckoutController;
use App\Models\Currency;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

test('createOrderItemsFromJson creates OrderItem records with billing_cycle in metadata for subscription renewals', function (): void {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'billing_cycle' => 'monthly',
    ]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'type' => 'subscription_renewal',
        'status' => 'processing',
        'payment_status' => 'paid',
        'items' => [
            [
                'id' => 'subscription-renewal-'.$subscription->id,
                'name' => 'Test Subscription (Renewal)',
                'price' => 270.00,
                'quantity' => 1,
                'attributes' => [
                    'type' => 'subscription_renewal',
                    'subscription_id' => $subscription->id,
                    'subscription_uuid' => $subscription->uuid,
                    'billing_cycle' => 'quarterly', // 3 months
                    'hosting_plan_id' => $subscription->hosting_plan_id,
                    'hosting_plan_price_id' => $subscription->hosting_plan_price_id,
                    'domain' => $subscription->domain,
                    'currency' => 'USD',
                ],
            ],
        ],
    ]);

    // Ensure currency exists
    Currency::factory()->create(['code' => 'USD', 'exchange_rate' => 1.0]);

    // Verify no OrderItem records exist yet
    expect($order->orderItems()->count())->toBe(0);

    // Use reflection to call the private method
    $controller = new CheckoutController(app(\App\Services\TransactionLogger::class));
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('createOrderItemsFromJson');
    $method->setAccessible(true);
    $method->invoke($controller, $order);

    // Verify OrderItem was created
    $orderItem = $order->orderItems()->first();
    expect($orderItem)->not->toBeNull()
        ->and($orderItem->domain_type)->toBe('subscription_renewal')
        ->and($orderItem->metadata)->toHaveKey('subscription_id')
        ->and($orderItem->metadata['subscription_id'])->toBe($subscription->id)
        ->and($orderItem->metadata)->toHaveKey('billing_cycle')
        ->and($orderItem->metadata['billing_cycle'])->toBe('quarterly');
});

test('createOrderItemsFromJson does not create duplicate OrderItems if they already exist', function (): void {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
    ]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'type' => 'subscription_renewal',
        'items' => [
            [
                'id' => 'subscription-renewal-'.$subscription->id,
                'name' => 'Test Subscription (Renewal)',
                'price' => 270.00,
                'quantity' => 1,
                'attributes' => [
                    'type' => 'subscription_renewal',
                    'subscription_id' => $subscription->id,
                    'billing_cycle' => 'quarterly',
                    'currency' => 'USD',
                ],
            ],
        ],
    ]);

    // Create an existing OrderItem
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'domain_type' => 'subscription_renewal',
    ]);

    Currency::factory()->create(['code' => 'USD', 'exchange_rate' => 1.0]);

    // Verify one OrderItem exists
    expect($order->orderItems()->count())->toBe(1);

    // Call the method - should not create duplicates
    $controller = new CheckoutController(app(\App\Services\TransactionLogger::class));
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('createOrderItemsFromJson');
    $method->setAccessible(true);
    $method->invoke($controller, $order);

    // Should still be only one OrderItem
    expect($order->orderItems()->count())->toBe(1);
});

test('createOrderItemsFromJson handles multiple billing cycles correctly', function (): void {
    $user = User::factory()->create();
    $subscription1 = Subscription::factory()->create(['user_id' => $user->id]);
    $subscription2 = Subscription::factory()->create(['user_id' => $user->id]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'type' => 'subscription_renewal',
        'items' => [
            [
                'id' => 'subscription-renewal-'.$subscription1->id,
                'name' => 'Subscription 1 (Renewal)',
                'price' => 270.00,
                'quantity' => 1,
                'attributes' => [
                    'type' => 'subscription_renewal',
                    'subscription_id' => $subscription1->id,
                    'billing_cycle' => 'quarterly', // 3 months
                    'currency' => 'USD',
                ],
            ],
            [
                'id' => 'subscription-renewal-'.$subscription2->id,
                'name' => 'Subscription 2 (Renewal)',
                'price' => 540.00,
                'quantity' => 1,
                'attributes' => [
                    'type' => 'subscription_renewal',
                    'subscription_id' => $subscription2->id,
                    'billing_cycle' => 'semi-annually', // 6 months
                    'currency' => 'USD',
                ],
            ],
        ],
    ]);

    Currency::factory()->create(['code' => 'USD', 'exchange_rate' => 1.0]);

    $controller = new CheckoutController(app(\App\Services\TransactionLogger::class));
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('createOrderItemsFromJson');
    $method->setAccessible(true);
    $method->invoke($controller, $order);

    $orderItems = $order->orderItems()->get();
    expect($orderItems)->toHaveCount(2);

    $item1 = $orderItems->firstWhere('metadata.subscription_id', $subscription1->id);
    $item2 = $orderItems->firstWhere('metadata.subscription_id', $subscription2->id);

    expect($item1->metadata['billing_cycle'])->toBe('quarterly')
        ->and($item2->metadata['billing_cycle'])->toBe('semi-annually');
});
