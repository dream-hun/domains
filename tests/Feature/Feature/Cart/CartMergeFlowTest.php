<?php

declare(strict_types=1);

use App\Models\CartItem;
use App\Models\User;
use App\Services\Cart\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('merges guest cart into user cart on login', function () {
    $user = User::factory()->create();
    $cartService = app(CartService::class);

    // Add items as guest
    $pricing = [
        'base_price' => 12.99,
        'base_currency' => 'USD',
        'years' => 1,
    ];

    $cartService->addItem('guest-domain.com', $pricing);

    expect(session()->has('cart.items'))->toBeTrue();

    // Login
    $this->actingAs($user);

    // Trigger login event manually (in real app, this happens automatically)
    event(new Illuminate\Auth\Events\Login('web', $user, false));

    // Check that items were merged
    $items = CartItem::where('user_id', $user->id)->get();

    expect($items)->toHaveCount(1)
        ->and($items->first()->domain_name)->toBe('guest-domain.com')
        ->and(session()->has('cart.items'))->toBeFalse();
});

it('keeps higher quantity when merging duplicate domains', function () {
    $user = User::factory()->create();
    $cartService = app(CartService::class);

    // Add item to user cart first
    $this->actingAs($user);

    $pricing = [
        'base_price' => 12.99,
        'base_currency' => 'USD',
        'years' => 2,
    ];

    $cartService->addItem('example.com', $pricing);

    // Logout and add same domain as guest with higher quantity
    auth()->logout();

    $guestPricing = [
        'base_price' => 12.99,
        'base_currency' => 'USD',
        'years' => 5,
    ];

    $cartService->addItem('example.com', $guestPricing);

    // Login again
    $this->actingAs($user);
    event(new Illuminate\Auth\Events\Login('web', $user, false));

    // Check that higher quantity was kept
    $item = CartItem::where('user_id', $user->id)
        ->where('domain_name', 'example.com')
        ->first();

    expect($item->years)->toBe(5);
});

it('adds unique domains from guest cart to user cart', function () {
    $user = User::factory()->create();
    $cartService = app(CartService::class);

    // Add item to user cart
    $this->actingAs($user);

    $pricing = [
        'base_price' => 12.99,
        'base_currency' => 'USD',
        'years' => 1,
    ];

    $cartService->addItem('user-domain.com', $pricing);

    // Logout and add different domain as guest
    auth()->logout();

    $cartService->addItem('guest-domain.com', $pricing);

    // Login again
    $this->actingAs($user);
    event(new Illuminate\Auth\Events\Login('web', $user, false));

    // Check that both domains exist
    $items = CartItem::where('user_id', $user->id)->get();

    expect($items)->toHaveCount(2)
        ->and($items->pluck('domain_name')->toArray())->toContain('user-domain.com', 'guest-domain.com');
});

it('clears session cart after successful merge', function () {
    $user = User::factory()->create();
    $cartService = app(CartService::class);

    // Add items as guest
    $pricing = [
        'base_price' => 12.99,
        'base_currency' => 'USD',
        'years' => 1,
    ];

    $cartService->addItem('example.com', $pricing);

    expect(session()->has('cart.items'))->toBeTrue();

    // Login
    $this->actingAs($user);
    event(new Illuminate\Auth\Events\Login('web', $user, false));

    // Session cart should be cleared
    expect(session()->has('cart.items'))->toBeFalse();
});

it('handles empty guest cart on login', function () {
    $user = User::factory()->create();

    // Login without any guest cart
    $this->actingAs($user);
    event(new Illuminate\Auth\Events\Login('web', $user, false));

    // Should not cause any errors
    $items = CartItem::where('user_id', $user->id)->get();

    expect($items)->toHaveCount(0);
});

it('preserves cart item attributes during merge', function () {
    $user = User::factory()->create();
    $cartService = app(CartService::class);

    // Add item as guest with attributes
    $pricing = [
        'base_price' => 12.99,
        'base_currency' => 'USD',
        'years' => 1,
        'eap_fee' => 10.00,
        'premium_fee' => 50.00,
        'privacy_fee' => 8.99,
    ];

    $cartService->addItem('premium.com', $pricing);

    // Login
    $this->actingAs($user);
    event(new Illuminate\Auth\Events\Login('web', $user, false));

    // Check that fees were preserved
    $item = CartItem::where('user_id', $user->id)
        ->where('domain_name', 'premium.com')
        ->first();

    expect($item->eap_fee)->toBe(10.00)
        ->and($item->premium_fee)->toBe(50.00)
        ->and($item->privacy_fee)->toBe(8.99);
});
