<?php

declare(strict_types=1);

use App\Models\Contact;
use App\Models\Currency;
use App\Models\User;
use App\Services\OrderService;
use Darryldecode\Cart\Facades\CartFacade as Cart;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    Cart::clear();

    Currency::query()->firstOrCreate(
        ['code' => 'USD'],
        [
            'name' => 'US Dollar',
            'symbol' => '$',
            'exchange_rate' => 1.0,
            'is_base' => true,
            'is_active' => true,
        ]
    );

    Currency::query()->firstOrCreate(
        ['code' => 'EUR'],
        [
            'name' => 'Euro',
            'symbol' => '€',
            'exchange_rate' => 0.92,
            'is_base' => false,
            'is_active' => true,
        ]
    );

    Currency::query()->firstOrCreate(
        ['code' => 'RWF'],
        [
            'name' => 'Rwandan Franc',
            'symbol' => 'FRW',
            'exchange_rate' => 1350.0,
            'is_base' => false,
            'is_active' => true,
        ]
    );
});

afterEach(function (): void {
    Cart::clear();
});

it('creates order with prices converted to order currency', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $contact = Contact::factory()->create(['user_id' => $user->id]);

    // Add item in USD
    Cart::add([
        'id' => 'domain-1',
        'name' => 'example.com',
        'price' => 100.0,
        'quantity' => 1,
        'attributes' => [
            'type' => 'registration',
            'currency' => 'USD',
            'domain_name' => 'example.com',
        ],
    ]);

    $cartItems = Cart::getContent();
    $orderService = resolve(OrderService::class);

    $order = $orderService->createOrder([
        'user_id' => $user->id,
        'currency' => 'EUR',
        'payment_method' => 'stripe',
        'cart_items' => $cartItems,
        'contact_ids' => [
            'registrant' => $contact->id,
            'admin' => $contact->id,
            'tech' => $contact->id,
            'billing' => $contact->id,
        ],
        'discount_amount' => 0,
    ]);

    expect($order->currency)->toBe('EUR');
    expect($order->subtotal)->toBe(92.0); // 100 * 0.92
    expect($order->total_amount)->toBe(92.0);

    $orderItem = $order->orderItems()->first();
    expect($orderItem->currency)->toBe('EUR');
    expect($orderItem->price)->toBe(92.0);
    expect($orderItem->total_amount)->toBe(92.0);
    expect($orderItem->metadata['original_currency'])->toBe('USD');
    expect($orderItem->metadata['original_price'])->toBe(100.0);
});

it('creates order with multiple items in different currencies', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $contact = Contact::factory()->create(['user_id' => $user->id]);

    // Add items in different currencies
    Cart::add([
        'id' => 'domain-1',
        'name' => 'example.com',
        'price' => 100.0,
        'quantity' => 1,
        'attributes' => [
            'type' => 'registration',
            'currency' => 'USD',
            'domain_name' => 'example.com',
        ],
    ]);

    Cart::add([
        'id' => 'domain-2',
        'name' => 'example.net',
        'price' => 50.0,
        'quantity' => 2,
        'attributes' => [
            'type' => 'registration',
            'currency' => 'USD',
            'domain_name' => 'example.net',
        ],
    ]);

    $cartItems = Cart::getContent();
    $orderService = resolve(OrderService::class);

    $order = $orderService->createOrder([
        'user_id' => $user->id,
        'currency' => 'EUR',
        'payment_method' => 'stripe',
        'cart_items' => $cartItems,
        'contact_ids' => [
            'registrant' => $contact->id,
            'admin' => $contact->id,
            'tech' => $contact->id,
            'billing' => $contact->id,
        ],
        'discount_amount' => 0,
    ]);

    // Item 1: 100 * 0.92 = 92
    // Item 2: 50 * 0.92 * 2 = 92
    // Total: 184
    expect($order->subtotal)->toBe(184.0);
    expect($order->total_amount)->toBe(184.0);
    expect($order->orderItems()->count())->toBe(2);

    $orderItems = $order->orderItems()->get();
    expect($orderItems->pluck('currency')->unique()->toArray())->toBe(['EUR']);
});

it('creates order with hosting items converted correctly', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $contact = Contact::factory()->create(['user_id' => $user->id]);

    Cart::add([
        'id' => 'hosting-1',
        'name' => 'Hosting Plan',
        'price' => 120.0,
        'quantity' => 12,
        'attributes' => [
            'type' => 'hosting',
            'currency' => 'USD',
            'billing_cycle' => 'annually',
            'monthly_unit_price' => 10.0,
        ],
    ]);

    $cartItems = Cart::getContent();
    $orderService = resolve(OrderService::class);

    $order = $orderService->createOrder([
        'user_id' => $user->id,
        'currency' => 'EUR',
        'payment_method' => 'stripe',
        'cart_items' => $cartItems,
        'contact_ids' => [
            'registrant' => $contact->id,
            'admin' => $contact->id,
            'tech' => $contact->id,
            'billing' => $contact->id,
        ],
        'discount_amount' => 0,
    ]);

    // Monthly price: 10 USD
    // Converted monthly: 10 * 0.92 = 9.2 EUR
    // Total: 9.2 * 12 = 110.4 EUR
    expect($order->subtotal)->toBe(110.4);
    expect($order->total_amount)->toBe(110.4);

    $orderItem = $order->orderItems()->first();
    expect($orderItem->currency)->toBe('EUR');
    expect($orderItem->price)->toBe(9.2); // Monthly price in EUR
    expect($orderItem->total_amount)->toBe(110.4);
});

it('creates order with subscription renewal items converted correctly', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $contact = Contact::factory()->create(['user_id' => $user->id]);

    Cart::add([
        'id' => 'sub-renewal',
        'name' => 'Subscription Renewal',
        'price' => 120.0,
        'quantity' => 12,
        'attributes' => [
            'type' => 'subscription_renewal',
            'currency' => 'USD',
            'billing_cycle' => 'annually',
            'display_unit_price' => 120.0,
        ],
    ]);

    $cartItems = Cart::getContent();
    $orderService = resolve(OrderService::class);

    $order = $orderService->createOrder([
        'user_id' => $user->id,
        'currency' => 'EUR',
        'payment_method' => 'stripe',
        'cart_items' => $cartItems,
        'contact_ids' => [
            'registrant' => $contact->id,
            'admin' => $contact->id,
            'tech' => $contact->id,
            'billing' => $contact->id,
        ],
        'discount_amount' => 0,
    ]);

    // Annual price: 120 USD
    // Converted: 120 * 0.92 = 110.4 EUR
    // Years: 12 / 12 = 1
    // Total: 110.4 * 1 = 110.4 EUR
    expect($order->subtotal)->toBe(110.4);
    expect($order->total_amount)->toBe(110.4);

    $orderItem = $order->orderItems()->first();
    expect($orderItem->currency)->toBe('EUR');
    expect($orderItem->total_amount)->toBe(110.4);
});

it('applies discount after currency conversion', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $contact = Contact::factory()->create(['user_id' => $user->id]);

    Cart::add([
        'id' => 'domain-1',
        'name' => 'example.com',
        'price' => 100.0,
        'quantity' => 1,
        'attributes' => [
            'type' => 'registration',
            'currency' => 'USD',
            'domain_name' => 'example.com',
        ],
    ]);

    $cartItems = Cart::getContent();
    $orderService = resolve(OrderService::class);

    $order = $orderService->createOrder([
        'user_id' => $user->id,
        'currency' => 'EUR',
        'payment_method' => 'stripe',
        'cart_items' => $cartItems,
        'contact_ids' => [
            'registrant' => $contact->id,
            'admin' => $contact->id,
            'tech' => $contact->id,
            'billing' => $contact->id,
        ],
        'discount_amount' => 9.2, // 10 EUR discount
    ]);

    // Subtotal: 100 * 0.92 = 92 EUR
    // Discount: 9.2 EUR
    // Total: 92 - 9.2 = 82.8 EUR
    expect($order->subtotal)->toBe(92.0);
    expect($order->discount_amount)->toBe(9.2);
    expect($order->total_amount)->toBe(82.8);
});

it('stores exchange rate in order items', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $contact = Contact::factory()->create(['user_id' => $user->id]);

    Cart::add([
        'id' => 'domain-1',
        'name' => 'example.com',
        'price' => 100.0,
        'quantity' => 1,
        'attributes' => [
            'type' => 'registration',
            'currency' => 'USD',
            'domain_name' => 'example.com',
        ],
    ]);

    $cartItems = Cart::getContent();
    $orderService = resolve(OrderService::class);

    $order = $orderService->createOrder([
        'user_id' => $user->id,
        'currency' => 'EUR',
        'payment_method' => 'stripe',
        'cart_items' => $cartItems,
        'contact_ids' => [
            'registrant' => $contact->id,
            'admin' => $contact->id,
            'tech' => $contact->id,
            'billing' => $contact->id,
        ],
        'discount_amount' => 0,
    ]);

    $orderItem = $order->orderItems()->first();
    // Exchange rate from USD to EUR: 0.92 / 1.0 = 0.92
    expect($orderItem->exchange_rate)->toBe(0.92);
});

it('handles order in same currency as items', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $contact = Contact::factory()->create(['user_id' => $user->id]);

    Cart::add([
        'id' => 'domain-1',
        'name' => 'example.com',
        'price' => 100.0,
        'quantity' => 1,
        'attributes' => [
            'type' => 'registration',
            'currency' => 'USD',
            'domain_name' => 'example.com',
        ],
    ]);

    $cartItems = Cart::getContent();
    $orderService = resolve(OrderService::class);

    $order = $orderService->createOrder([
        'user_id' => $user->id,
        'currency' => 'USD',
        'payment_method' => 'stripe',
        'cart_items' => $cartItems,
        'contact_ids' => [
            'registrant' => $contact->id,
            'admin' => $contact->id,
            'tech' => $contact->id,
            'billing' => $contact->id,
        ],
        'discount_amount' => 0,
    ]);

    expect($order->currency)->toBe('USD');
    expect($order->subtotal)->toBe(100.0);
    expect($order->total_amount)->toBe(100.0);

    $orderItem = $order->orderItems()->first();
    expect($orderItem->currency)->toBe('USD');
    expect($orderItem->price)->toBe(100.0);
    expect($orderItem->exchange_rate)->toBe(1.0);
});
