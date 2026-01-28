<?php

declare(strict_types=1);

use App\Actions\Order\CreateCustomOrderAction;
use App\Models\Currency;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->admin = User::factory()->create();

    Currency::query()->create([
        'code' => 'USD',
        'name' => 'US Dollar',
        'symbol' => '$',
        'exchange_rate' => 1.0,
        'is_base' => true,
        'is_active' => true,
    ]);

    Currency::query()->create([
        'code' => 'RWF',
        'name' => 'Rwandan Franc',
        'symbol' => 'Fr',
        'exchange_rate' => 1200.0,
        'is_base' => false,
        'is_active' => true,
    ]);
});

test('creates order with correct attributes', function (): void {
    $action = resolve(CreateCustomOrderAction::class);

    $itemData = [
        'name' => 'example.com',
        'type' => 'custom_registration',
        'price' => 25.00,
        'currency' => 'USD',
        'quantity' => 1,
        'years' => 2,
    ];

    $order = $action->handle(
        $this->user,
        'custom_registration',
        $itemData,
        $this->admin->id
    );

    expect($order)->toBeInstanceOf(Order::class)
        ->and($order->user_id)->toBe($this->user->id)
        ->and($order->type)->toBe('custom_registration')
        ->and($order->status)->toBe('completed')
        ->and($order->payment_method)->toBe('manual')
        ->and($order->payment_status)->toBe('manual')
        ->and((float) $order->total_amount)->toBe(25.00)
        ->and($order->currency)->toBe('USD')
        ->and($order->billing_email)->toBe($this->user->email)
        ->and($order->processed_at)->not->toBeNull();
});

test('creates order item with correct attributes', function (): void {
    $action = resolve(CreateCustomOrderAction::class);

    $itemData = [
        'name' => 'example.com',
        'type' => 'custom_registration',
        'price' => 50.00,
        'currency' => 'USD',
        'quantity' => 1,
        'years' => 3,
        'metadata' => ['notes' => 'VIP pricing'],
    ];

    $order = $action->handle(
        $this->user,
        'custom_registration',
        $itemData,
        $this->admin->id
    );

    expect($order->orderItems)->toHaveCount(1);

    $orderItem = $order->orderItems->first();
    expect($orderItem)->toBeInstanceOf(OrderItem::class)
        ->and($orderItem->domain_name)->toBe('example.com')
        ->and($orderItem->domain_type)->toBe('custom_registration')
        ->and($orderItem->domain_id)->toBeNull()
        ->and((float) $orderItem->price)->toBe(50.00)
        ->and($orderItem->currency)->toBe('USD')
        ->and($orderItem->years)->toBe(3)
        ->and($orderItem->metadata['admin_created'])->toBeTrue()
        ->and($orderItem->metadata['admin_id'])->toBe($this->admin->id);
});

test('stores admin id in order metadata', function (): void {
    $action = resolve(CreateCustomOrderAction::class);

    $itemData = [
        'name' => 'test.com',
        'type' => 'custom_subscription',
        'price' => 100.00,
        'currency' => 'USD',
        'quantity' => 1,
    ];

    $order = $action->handle(
        $this->user,
        'custom_subscription',
        $itemData,
        $this->admin->id
    );

    expect($order->metadata['created_by_admin_id'])->toBe($this->admin->id)
        ->and($order->metadata['is_custom_order'])->toBeTrue();
});

test('creates order with non-USD currency', function (): void {
    $action = resolve(CreateCustomOrderAction::class);

    $itemData = [
        'name' => 'example.rw',
        'type' => 'custom_registration',
        'price' => 30000.00,
        'currency' => 'RWF',
        'quantity' => 1,
        'years' => 1,
    ];

    $order = $action->handle(
        $this->user,
        'custom_registration',
        $itemData,
        $this->admin->id
    );

    expect($order->currency)->toBe('RWF')
        ->and((float) $order->total_amount)->toBe(30000.00);

    $orderItem = $order->orderItems->first();
    expect($orderItem->currency)->toBe('RWF')
        ->and((float) $orderItem->exchange_rate)->toBe(1200.0);
});

test('merges additional metadata into order', function (): void {
    $action = resolve(CreateCustomOrderAction::class);

    $itemData = [
        'name' => 'test.com',
        'type' => 'custom_registration',
        'price' => 25.00,
        'currency' => 'USD',
        'quantity' => 1,
    ];

    $additionalMetadata = [
        'domain_id' => 456,
        'domain_name' => 'test.com',
    ];

    $order = $action->handle(
        $this->user,
        'custom_registration',
        $itemData,
        $this->admin->id,
        $additionalMetadata
    );

    expect($order->metadata['domain_id'])->toBe(456)
        ->and($order->metadata['domain_name'])->toBe('test.com')
        ->and($order->metadata['created_by_admin_id'])->toBe($this->admin->id);
});

test('calculates total amount correctly with quantity', function (): void {
    $action = resolve(CreateCustomOrderAction::class);

    $itemData = [
        'name' => 'test.com',
        'type' => 'custom_registration',
        'price' => 10.00,
        'currency' => 'USD',
        'quantity' => 3,
        'years' => 3,
    ];

    $order = $action->handle(
        $this->user,
        'custom_registration',
        $itemData,
        $this->admin->id
    );

    expect((float) $order->total_amount)->toBe(30.00)
        ->and((float) $order->subtotal)->toBe(30.00);

    $orderItem = $order->orderItems->first();
    expect((float) $orderItem->total_amount)->toBe(30.00)
        ->and($orderItem->quantity)->toBe(3);
});
