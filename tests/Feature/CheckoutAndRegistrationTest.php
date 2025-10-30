<?php

declare(strict_types=1);

use App\Models\Contact;
use App\Models\Currency;
use App\Models\DomainPrice;
use App\Models\Order;
use App\Models\User;
use App\Services\BillingService;
use Darryldecode\Cart\Facades\CartFacade as Cart;

beforeEach(function () {
    // Seed roles first
    $this->artisan('db:seed', ['--class' => 'RolesSeeder']);

    // Create base USD currency
    Currency::factory()->create([
        'code' => 'USD',
        'name' => 'US Dollar',
        'symbol' => '$',
        'exchange_rate' => 1.0,
        'is_base' => true,
        'is_active' => true,
    ]);

    $this->user = User::factory()->create();
    $this->contact = Contact::factory()->create([
        'user_id' => $this->user->id,
        'is_primary' => true,
    ]);
    $this->domainPrice = DomainPrice::factory()->create([
        'tld' => '.com',
        'register_price' => 1299, // Price in cents
    ]);
});

it('allows user to view checkout page with items in cart', function () {
    // Add item to cart
    Cart::add([
        'id' => 'example.com',
        'name' => 'example.com',
        'price' => 12.99,
        'quantity' => 1,
        'attributes' => [
            'type' => 'registration',
            'currency' => 'USD',
        ],
    ]);

    $response = $this->actingAs($this->user)->get(route('checkout.index'));

    $response->assertOk();
    $response->assertSeeLivewire('checkout-process');
});

it('shows empty cart message when cart is empty', function () {
    Cart::clear();

    $response = $this->actingAs($this->user)->get(route('checkout.index'));

    $response->assertOk();
    $response->assertSee('Your cart is empty');
});

it('creates order from cart using billing service', function () {
    // Add items to cart
    Cart::add([
        'id' => 'example.com',
        'name' => 'example.com',
        'price' => 12.99,
        'quantity' => 2,
        'attributes' => [
            'type' => 'registration',
            'currency' => 'USD',
        ],
    ]);

    $billingService = app(BillingService::class);

    $billingData = [
        'billing_name' => 'John Doe',
        'billing_email' => 'john@example.com',
        'billing_address' => '123 Main St',
        'billing_city' => 'Anytown',
        'billing_country' => 'US',
        'billing_postal_code' => '12345',
    ];

    $checkoutData = [
        'payment_method' => 'stripe',
        'coupon_code' => null,
        'discount' => 0,
        'total' => 25.98,
    ];

    $order = $billingService->createOrderFromCart($this->user, $billingData, $checkoutData);

    expect($order)->toBeInstanceOf(Order::class);
    expect($order->user_id)->toBe($this->user->id);
    expect($order->payment_method)->toBe('stripe');
    expect($order->total_amount)->toBe('25.98');
    expect($order->status)->toBe('pending');
    expect($order->payment_status)->toBe('pending');

    // Check order items
    expect($order->orderItems)->toHaveCount(1);
    $orderItem = $order->orderItems->first();
    expect($orderItem->domain_name)->toBe('example.com');
    expect($orderItem->price)->toBe('12.99');
    expect($orderItem->years)->toBe(2);
    expect($orderItem->total_amount)->toBe('25.98');
});

it('stores checkout session data when proceeding to payment', function () {
    Cart::add([
        'id' => 'example.com',
        'name' => 'example.com',
        'price' => 12.99,
        'quantity' => 1,
        'attributes' => [
            'type' => 'registration',
            'currency' => 'USD',
        ],
    ]);

    $response = $this->actingAs($this->user)->postJson(route('checkout.proceed'), [
        'payment_method' => 'stripe',
        'total' => 12.99,
    ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'redirect_url' => route('payment.index'),
    ]);

    expect(session('checkout'))->not->toBeNull();
    $checkoutData = session('checkout');
    expect($checkoutData['payment_method'])->toBe('stripe');
    expect($checkoutData['total'])->toBe(12.99);
});

it('uses selected contact for domain registration', function () {
    // Add items to cart
    Cart::add([
        'id' => 'example.com',
        'name' => 'example.com',
        'price' => 12.99,
        'quantity' => 1,
        'attributes' => [
            'type' => 'registration',
            'currency' => 'USD',
        ],
    ]);

    // Set checkout session with selected contact
    session([
        'checkout' => [
            'payment_method' => 'stripe',
            'selected_contact_id' => $this->contact->id,
            'total' => 12.99,
        ],
    ]);

    $billingService = app(BillingService::class);

    $order = $billingService->createOrderFromCart(
        $this->user,
        ['billing_name' => 'John Doe', 'billing_email' => 'john@example.com'],
        session('checkout')
    );

    expect($order)->toBeInstanceOf(Order::class);
    expect($order->user_id)->toBe($this->user->id);
});

it('stores exchange rate on order items when creating order', function () {
    // Add items to cart with USD currency
    Cart::add([
        'id' => 'example.com',
        'name' => 'example.com',
        'price' => 10.99,
        'quantity' => 1,
        'attributes' => [
            'type' => 'registration',
            'currency' => 'USD',
        ],
    ]);

    $billingService = app(BillingService::class);

    $billingData = [
        'billing_name' => 'John Doe',
        'billing_email' => 'john@example.com',
        'billing_address' => '123 Main St',
        'billing_city' => 'Anytown',
        'billing_country' => 'US',
        'billing_postal_code' => '12345',
    ];

    $checkoutData = [
        'payment_method' => 'stripe',
        'coupon_code' => null,
        'discount' => 0,
        'total' => 10.99,
    ];

    $order = $billingService->createOrderFromCart($this->user, $billingData, $checkoutData);

    expect($order->orderItems)->toHaveCount(1);
    $orderItem = $order->orderItems->first();
    expect($orderItem->currency)->toBe('USD');
    expect($orderItem->exchange_rate)->toBe('1.000000'); // USD base rate is 1.0
});
