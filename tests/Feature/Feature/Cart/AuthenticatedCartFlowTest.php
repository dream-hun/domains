<?php

declare(strict_types=1);

use App\Models\CartItem;
use App\Models\User;
use App\Services\Cart\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('stores cart items in database for authenticated users', function () {
    $this->actingAs($this->user);

    $cartService = app(CartService::class);

    $pricing = [
        'base_price' => 12.99,
        'base_currency' => 'USD',
        'domain_type' => 'registration',
        'tld' => 'com',
        'years' => 1,
    ];

    $item = $cartService->addItem('example.com', $pricing);

    expect($item)->toBeInstanceOf(CartItem::class)
        ->and($item->user_id)->toBe($this->user->id)
        ->and(CartItem::where('user_id', $this->user->id)->count())->toBe(1);
});

it('persists cart across sessions for authenticated users', function () {
    $this->actingAs($this->user);

    $cartService = app(CartService::class);

    $pricing = [
        'base_price' => 12.99,
        'base_currency' => 'USD',
        'years' => 1,
    ];

    $cartService->addItem('example.com', $pricing);

    // Simulate new session
    session()->flush();

    $items = $cartService->getItems();

    expect($items)->toHaveCount(1);
});

it('allows authenticated user to update cart items', function () {
    $this->actingAs($this->user);

    $cartService = app(CartService::class);

    $pricing = [
        'base_price' => 12.99,
        'base_currency' => 'USD',
        'years' => 1,
    ];

    $item = $cartService->addItem('example.com', $pricing);

    $cartService->updateQuantity($item->id, 5);

    $updatedItem = CartItem::find($item->id);

    expect($updatedItem->years)->toBe(5);
});

it('allows authenticated user to remove cart items', function () {
    $this->actingAs($this->user);

    $cartService = app(CartService::class);

    $pricing = [
        'base_price' => 12.99,
        'base_currency' => 'USD',
        'years' => 1,
    ];

    $item = $cartService->addItem('example.com', $pricing);

    $cartService->removeItem($item->id);

    expect(CartItem::find($item->id))->toBeNull()
        ->and($cartService->getItemCount())->toBe(0);
});

it('preserves cart after logout and login', function () {
    $this->actingAs($this->user);

    $cartService = app(CartService::class);

    $pricing = [
        'base_price' => 12.99,
        'base_currency' => 'USD',
        'years' => 1,
    ];

    $cartService->addItem('example.com', $pricing);

    // Logout
    auth()->logout();

    // Login again
    $this->actingAs($this->user);

    $items = $cartService->getItems();

    expect($items)->toHaveCount(1);
});

it('calculates totals correctly for authenticated users', function () {
    $this->actingAs($this->user);

    $cartService = app(CartService::class);

    $pricing = [
        'base_price' => 20.00,
        'base_currency' => 'USD',
        'years' => 2,
        'premium_fee' => 50.00,
    ];

    $cartService->addItem('premium.com', $pricing);

    $subtotal = $cartService->getSubtotal('USD');
    $total = $cartService->getTotal('USD');

    // Base: 20 * 2 = 40, Premium: 50, Total: 90
    expect($subtotal)->toBe(90.00)
        ->and($total)->toBe(90.00);
});

it('allows multiple items in authenticated cart', function () {
    $this->actingAs($this->user);

    $cartService = app(CartService::class);

    $pricing = [
        'base_price' => 12.99,
        'base_currency' => 'USD',
        'years' => 1,
    ];

    $cartService->addItem('example.com', $pricing);
    $cartService->addItem('test.com', $pricing);
    $cartService->addItem('demo.com', $pricing);

    expect($cartService->getItemCount())->toBe(3)
        ->and(CartItem::where('user_id', $this->user->id)->count())->toBe(3);
});
