<?php

declare(strict_types=1);

use App\Models\CartItem;
use App\Models\Coupon;
use App\Services\Cart\CartPricingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->calculator = app(CartPricingCalculator::class);
});

it('calculates item total with base price only', function () {
    $item = CartItem::factory()->make([
        'base_price' => 10.00,
        'base_currency' => 'USD',
        'years' => 2,
        'eap_fee' => 0,
        'premium_fee' => 0,
        'privacy_fee' => 0,
    ]);

    $total = $this->calculator->calculateItemTotal($item, 'USD');

    expect($total)->toBe(20.00);
});

it('calculates item total with additional fees', function () {
    $item = CartItem::factory()->make([
        'base_price' => 10.00,
        'base_currency' => 'USD',
        'years' => 2,
        'eap_fee' => 5.00,
        'premium_fee' => 15.00,
        'privacy_fee' => 8.00,
    ]);

    $total = $this->calculator->calculateItemTotal($item, 'USD');

    // Base: 10 * 2 = 20, Fees: 5 + 15 + 8 = 28, Total: 48
    expect($total)->toBe(48.00);
});

it('calculates subtotal for multiple items', function () {
    $items = collect([
        CartItem::factory()->make([
            'base_price' => 10.00,
            'base_currency' => 'USD',
            'years' => 1,
            'eap_fee' => 0,
            'premium_fee' => 0,
            'privacy_fee' => 0,
        ]),
        CartItem::factory()->make([
            'base_price' => 15.00,
            'base_currency' => 'USD',
            'years' => 1,
            'eap_fee' => 5.00,
            'premium_fee' => 0,
            'privacy_fee' => 0,
        ]),
    ]);

    $subtotal = $this->calculator->calculateSubtotal($items, 'USD');

    // Item 1: 10, Item 2: 15 + 5 = 20, Total: 30
    expect($subtotal)->toBe(30.00);
});

it('calculates percentage discount correctly', function () {
    $coupon = Coupon::factory()->make([
        'type' => 'percentage',
        'value' => 20,
    ]);

    $discount = $this->calculator->calculateDiscount(100.00, $coupon);

    expect($discount)->toBe(20.00);
});

it('calculates fixed discount correctly', function () {
    $coupon = Coupon::factory()->make([
        'type' => 'fixed',
        'value' => 15.00,
    ]);

    $discount = $this->calculator->calculateDiscount(100.00, $coupon);

    expect($discount)->toBe(85.00);
});

it('ensures discount does not exceed subtotal', function () {
    $coupon = Coupon::factory()->make([
        'type' => 'fixed',
        'value' => 150.00,
    ]);

    $discount = $this->calculator->calculateDiscount(100.00, $coupon);

    expect($discount)->toBeLessThanOrEqual(100.00);
});

it('calculates total with discount', function () {
    $subtotal = 100.00;
    $discount = 20.00;

    $total = $this->calculator->calculateTotal($subtotal, $discount);

    expect($total)->toBe(80.00);
});

it('ensures total never goes below zero', function () {
    $subtotal = 50.00;
    $discount = 75.00;

    $total = $this->calculator->calculateTotal($subtotal, $discount);

    expect($total)->toBe(0.00);
});

it('handles items with multiple years correctly', function () {
    $item = CartItem::factory()->make([
        'base_price' => 12.99,
        'base_currency' => 'USD',
        'years' => 5,
        'eap_fee' => 10.00,
        'premium_fee' => 0,
        'privacy_fee' => 8.99,
    ]);

    $total = $this->calculator->calculateItemTotal($item, 'USD');

    // Base: 12.99 * 5 = 64.95, Fees: 10 + 8.99 = 18.99, Total: 83.94
    expect($total)->toBe(83.94);
});
