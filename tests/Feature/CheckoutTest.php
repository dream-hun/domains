<?php

declare(strict_types=1);

use App\Enums\Coupons\CouponType;
use App\Models\Coupon;
use App\Models\Currency;
use App\Models\Role;
use App\Models\User;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Support\Str;

beforeEach(function (): void {
    // Create necessary roles for user creation
    Role::query()->create(['id' => 1, 'title' => 'Admin']);
    Role::query()->create(['id' => 2, 'title' => 'User']);

    // Seed currencies for testing
    Currency::query()->create([
        'code' => 'USD',
        'name' => 'US Dollar',
        'symbol' => '$',
        'exchange_rate' => 1.0,
        'is_base' => true,
        'is_active' => true,
    ]);
});

it('displays checkout page with empty cart', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('checkout.index'));

    $response->assertStatus(200);
    $response->assertSee('Your cart is empty');
});

it('displays checkout page with cart items', function (): void {
    $user = User::factory()->create();

    // Add items to cart
    Cart::add([
        'id' => 'test-domain.com',
        'name' => 'test-domain.com',
        'price' => 10.99,
        'quantity' => 1,
        'attributes' => [
            'type' => 'domain',
            'currency' => 'USD',
        ],
    ]);

    $response = $this->actingAs($user)->get(route('checkout.index'));

    $response->assertStatus(200);
    $response->assertSee('Order Review');
    $response->assertSee('test-domain.com');
    $response->assertSee('$10.99');
});

it('applies coupon successfully', function (): void {
    $user = User::factory()->create();

    // Add items to cart
    Cart::add([
        'id' => 'test-domain.com',
        'name' => 'test-domain.com',
        'price' => 100.00,
        'quantity' => 1,
        'attributes' => [
            'type' => 'domain',
            'currency' => 'USD',
        ],
    ]);

    // Create a test coupon
    $coupon = Coupon::query()->create([
        'uuid' => Str::uuid(),
        'code' => 'TEST10',
        'type' => CouponType::Percentage,
        'value' => 10,
        'valid_from' => now()->subDay(),
        'valid_to' => now()->addMonth(),
        'max_uses' => 100,
        'uses' => 0,
    ]);

    $response = $this->actingAs($user)->post(route('checkout.apply-coupon', 'TEST10'));

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'coupon' => 'TEST10',
        'discount' => 10.0, // 10% of $100
    ]);
});

it('rejects invalid coupon', function (): void {
    $user = User::factory()->create();

    // Add items to cart
    Cart::add([
        'id' => 'test-domain.com',
        'name' => 'test-domain.com',
        'price' => 100.00,
        'quantity' => 1,
        'attributes' => [
            'type' => 'domain',
            'currency' => 'USD',
        ],
    ]);

    $response = $this->actingAs($user)->post(route('checkout.apply-coupon', 'INVALID'));

    $response->assertStatus(400);
    $response->assertJson([
        'success' => false,
    ]);
});

it('proceeds to payment with valid data', function (): void {
    $user = User::factory()->create();

    // Add items to cart
    Cart::add([
        'id' => 'test-domain.com',
        'name' => 'test-domain.com',
        'price' => 100.00,
        'quantity' => 1,
        'attributes' => [
            'type' => 'domain',
            'currency' => 'USD',
        ],
    ]);

    $response = $this->actingAs($user)->post(route('checkout.proceed'), [
        'payment_method' => 'stripe',
        'coupon_code' => null,
        'discount' => 0,
        'total' => 100.00,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
    ]);

    // Verify checkout data is stored in session
    expect(session('checkout'))->not->toBeNull();
});

it('fails to proceed with empty cart', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('checkout.proceed'), [
        'payment_method' => 'stripe',
        'coupon_code' => null,
        'discount' => 0,
        'total' => 0,
    ]);

    $response->assertStatus(400);
    $response->assertJson([
        'success' => false,
        'message' => 'Your cart is empty',
    ]);
});
