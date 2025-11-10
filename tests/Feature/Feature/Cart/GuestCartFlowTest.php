<?php

declare(strict_types=1);

use App\Models\Coupon;
use App\Services\Cart\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows guest to add items to cart', function (): void {
    $cartService = app(CartService::class);

    $pricing = [
        'base_price' => 12.99,
        'base_currency' => 'USD',
        'domain_type' => 'registration',
        'tld' => 'com',
        'years' => 1,
    ];

    $item = $cartService->addItem('example.com', $pricing);

    expect($item)->toBeArray()
        ->and($item['domain_name'])->toBe('example.com')
        ->and(session()->has('cart.items'))->toBeTrue();
});

it('persists guest cart in session', function (): void {
    $cartService = app(CartService::class);

    $pricing = [
        'base_price' => 12.99,
        'base_currency' => 'USD',
        'years' => 1,
    ];

    $cartService->addItem('example.com', $pricing);
    $cartService->addItem('test.com', $pricing);

    $items = $cartService->getItems();

    expect($items)->toHaveCount(2)
        ->and(session('cart.items'))->toHaveCount(2);
});

it('allows guest to update item quantity', function (): void {
    $cartService = app(CartService::class);

    $pricing = [
        'base_price' => 12.99,
        'base_currency' => 'USD',
        'years' => 1,
    ];

    $item = $cartService->addItem('example.com', $pricing);
    $itemId = $item['id'];

    $cartService->updateQuantity($itemId, 3);

    $updatedItem = $cartService->getItem($itemId);

    expect($updatedItem['years'])->toBe(3);
});

it('allows guest to remove items from cart', function (): void {
    $cartService = app(CartService::class);

    $pricing = [
        'base_price' => 12.99,
        'base_currency' => 'USD',
        'years' => 1,
    ];

    $item = $cartService->addItem('example.com', $pricing);
    $itemId = $item['id'];

    $cartService->removeItem($itemId);

    expect($cartService->getItemCount())->toBe(0);
});

it('allows guest to apply coupon', function (): void {
    $cartService = app(CartService::class);

    $pricing = [
        'base_price' => 100.00,
        'base_currency' => 'USD',
        'years' => 1,
    ];

    $cartService->addItem('example.com', $pricing);

    $coupon = Coupon::factory()->create([
        'code' => 'GUEST20',
        'type' => 'percentage',
        'value' => 20,
    ]);

    $appliedCoupon = $cartService->applyCoupon('GUEST20');

    expect($appliedCoupon)->toBeInstanceOf(Coupon::class)
        ->and($cartService->getDiscount())->toBe(20.00)
        ->and($cartService->getTotal('USD'))->toBe(80.00);
});

it('calculates correct totals for guest cart', function (): void {
    $cartService = app(CartService::class);

    $pricing = [
        'base_price' => 10.00,
        'base_currency' => 'USD',
        'years' => 1,
        'eap_fee' => 5.00,
    ];

    $cartService->addItem('example.com', $pricing);
    $cartService->addItem('test.com', $pricing);

    $subtotal = $cartService->getSubtotal('USD');
    $total = $cartService->getTotal('USD');

    // Each item: 10 + 5 = 15, Total: 30
    expect($subtotal)->toBe(30.00)
        ->and($total)->toBe(30.00);
});

it('clears guest cart session', function (): void {
    $cartService = app(CartService::class);

    $pricing = [
        'base_price' => 12.99,
        'base_currency' => 'USD',
        'years' => 1,
    ];

    $cartService->addItem('example.com', $pricing);
    $cartService->clearCart();

    expect($cartService->getItemCount())->toBe(0)
        ->and(session()->has('cart.items'))->toBeFalse();
});
