<?php

declare(strict_types=1);

use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\User;
use App\Services\Cart\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cartService = app(CartService::class);
});

it('can add item to cart for authenticated user', function () {
    $this->actingAs($this->user);

    $pricing = [
        'base_price' => 12.99,
        'base_currency' => 'USD',
        'domain_type' => 'registration',
        'tld' => 'com',
        'years' => 1,
    ];

    $item = $this->cartService->addItem('example.com', $pricing);

    expect($item)->toBeInstanceOf(CartItem::class)
        ->and($item->domain_name)->toBe('example.com')
        ->and($item->base_price)->toBe(12.99)
        ->and($item->user_id)->toBe($this->user->id);
});

it('can add item to cart for guest user', function () {
    $pricing = [
        'base_price' => 12.99,
        'base_currency' => 'USD',
        'domain_type' => 'registration',
        'tld' => 'com',
        'years' => 1,
    ];

    $item = $this->cartService->addItem('example.com', $pricing);

    expect($item)->toBeArray()
        ->and($item['domain_name'])->toBe('example.com')
        ->and($item['base_price'])->toBe(12.99);
});

it('can remove item from cart', function () {
    $this->actingAs($this->user);

    $pricing = [
        'base_price' => 12.99,
        'base_currency' => 'USD',
        'years' => 1,
    ];

    $item = $this->cartService->addItem('example.com', $pricing);
    $itemId = $item->id;

    $result = $this->cartService->removeItem($itemId);

    expect($result)->toBeTrue()
        ->and($this->cartService->getItemCount())->toBe(0);
});

it('can update item quantity', function () {
    $this->actingAs($this->user);

    $pricing = [
        'base_price' => 12.99,
        'base_currency' => 'USD',
        'years' => 1,
    ];

    $item = $this->cartService->addItem('example.com', $pricing);

    $result = $this->cartService->updateQuantity($item->id, 3);

    expect($result)->toBeTrue();

    $updatedItem = $this->cartService->getItem($item->id);
    expect($updatedItem->years)->toBe(3);
});

it('validates years between 1 and 10', function () {
    $this->actingAs($this->user);

    $pricing = [
        'base_price' => 12.99,
        'base_currency' => 'USD',
        'years' => 11,
    ];

    $this->cartService->addItem('example.com', $pricing);
})->throws(InvalidArgumentException::class);

it('can get cart items', function () {
    $this->actingAs($this->user);

    $pricing = [
        'base_price' => 12.99,
        'base_currency' => 'USD',
        'years' => 1,
    ];

    $this->cartService->addItem('example.com', $pricing);
    $this->cartService->addItem('test.com', $pricing);

    $items = $this->cartService->getItems();

    expect($items)->toHaveCount(2);
});

it('can calculate subtotal', function () {
    $this->actingAs($this->user);

    $pricing = [
        'base_price' => 12.99,
        'base_currency' => 'USD',
        'years' => 1,
    ];

    $this->cartService->addItem('example.com', $pricing);
    $this->cartService->addItem('test.com', $pricing);

    $subtotal = $this->cartService->getSubtotal('USD');

    expect($subtotal)->toBe(25.98);
});

it('can calculate total with discount', function () {
    $this->actingAs($this->user);

    $pricing = [
        'base_price' => 100.00,
        'base_currency' => 'USD',
        'years' => 1,
    ];

    $this->cartService->addItem('example.com', $pricing);

    // Create a percentage coupon
    $coupon = Coupon::factory()->create([
        'code' => 'TEST20',
        'type' => 'percentage',
        'value' => 20,
    ]);

    $this->cartService->applyCoupon('TEST20');

    $total = $this->cartService->getTotal('USD');

    expect($total)->toBe(80.00);
});

it('can clear cart', function () {
    $this->actingAs($this->user);

    $pricing = [
        'base_price' => 12.99,
        'base_currency' => 'USD',
        'years' => 1,
    ];

    $this->cartService->addItem('example.com', $pricing);
    $this->cartService->addItem('test.com', $pricing);

    $this->cartService->clearCart();

    expect($this->cartService->getItemCount())->toBe(0);
});

it('can prepare cart for checkout', function () {
    $this->actingAs($this->user);

    $pricing = [
        'base_price' => 12.99,
        'base_currency' => 'USD',
        'years' => 1,
        'eap_fee' => 5.00,
        'premium_fee' => 10.00,
    ];

    $this->cartService->addItem('example.com', $pricing);

    $checkoutData = $this->cartService->prepareForCheckout();

    expect($checkoutData)->toHaveKeys(['items', 'subtotal', 'total', 'currency'])
        ->and($checkoutData['items'])->toHaveCount(1)
        ->and($checkoutData['items'][0])->toHaveKey('domain_name', 'example.com');
});
