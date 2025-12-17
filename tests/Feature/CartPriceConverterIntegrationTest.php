<?php

declare(strict_types=1);

use App\Livewire\CartComponent;
use App\Models\Currency;
use App\Models\User;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cart::clear();

    // Mock HTTP requests for currency conversion API
    Http::fake([
        '*/latest/USD' => Http::response([
            'result' => 'success',
            'base' => 'USD',
            'rates' => [
                'EUR' => 0.92,
                'RWF' => 1350.0,
            ],
        ]),
    ]);

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
    session()->forget(['cart', 'cart_subtotal', 'cart_total', 'coupon', 'selected_currency']);
});

it('calculates cart totals correctly with mixed currencies', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    session(['selected_currency' => 'EUR']);

    // Add items in different currencies
    Cart::add([
        'id' => 'domain-usd',
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
        'id' => 'domain-rwf',
        'name' => 'example.rw',
        'price' => 135000.0,
        'quantity' => 1,
        'attributes' => [
            'type' => 'registration',
            'currency' => 'RWF',
            'domain_name' => 'example.rw',
        ],
    ]);

    $component = Livewire::test(CartComponent::class);

    // Both items should be converted to EUR
    // USD item: 100 * 0.92 = 92 EUR
    // RWF item: 135000 / 1350 * 0.92 = 100 * 0.92 = 92 EUR
    // Total: 184 EUR
    expect($component->get('subtotalAmount'))->toBe(184.0);
    expect($component->get('totalAmount'))->toBe(184.0);
});

it('updates cart totals when currency changes', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    Cart::add([
        'id' => 'domain-usd',
        'name' => 'example.com',
        'price' => 100.0,
        'quantity' => 1,
        'attributes' => [
            'type' => 'registration',
            'currency' => 'USD',
            'domain_name' => 'example.com',
        ],
    ]);

    $component = Livewire::test(CartComponent::class)
        ->set('currency', 'USD')
        ->call('updateCartTotal');

    expect($component->get('subtotalAmount'))->toBe(100.0);

    $component->call('updateCurrency', 'EUR')
        ->call('updateCartTotal');

    expect($component->get('subtotalAmount'))->toBe(92.0);
});

it('handles hosting items with monthly pricing correctly', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    session(['selected_currency' => 'EUR']);

    Cart::add([
        'id' => 'hosting-annual',
        'name' => 'Hosting Plan',
        'price' => 120.0,
        'quantity' => 12, // 12 months
        'attributes' => [
            'type' => 'hosting',
            'currency' => 'USD',
            'billing_cycle' => 'annually',
            'monthly_unit_price' => 10.0,
        ],
    ]);

    $component = Livewire::test(CartComponent::class);

    // Monthly price: 10 USD
    // Converted monthly: 10 * 0.92 = 9.2 EUR
    // Total: 9.2 * 12 = 110.4 EUR
    $subtotal = $component->get('subtotalAmount');
    expect(abs($subtotal - 110.4))->toBeLessThan(0.01);
});

it('handles subscription renewal items with annual billing', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    session(['selected_currency' => 'EUR']);

    Cart::add([
        'id' => 'sub-renewal',
        'name' => 'Subscription Renewal',
        'price' => 120.0,
        'quantity' => 12, // 12 months
        'attributes' => [
            'type' => 'subscription_renewal',
            'currency' => 'USD',
            'billing_cycle' => 'annually',
            'display_unit_price' => 120.0, // Annual price
        ],
    ]);

    $component = Livewire::test(CartComponent::class);

    // Annual price: 120 USD
    // Converted: 120 * 0.92 = 110.4 EUR
    // Years: 12 / 12 = 1
    // Total: 110.4 * 1 = 110.4 EUR
    expect($component->get('subtotalAmount'))->toBe(110.4);
});

it('prepares cart for payment with converted prices', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    session(['selected_currency' => 'EUR']);

    Cart::add([
        'id' => 'domain-usd',
        'name' => 'example.com',
        'price' => 100.0,
        'quantity' => 1,
        'attributes' => [
            'type' => 'registration',
            'currency' => 'USD',
            'domain_name' => 'example.com',
        ],
    ]);

    $component = Livewire::test(CartComponent::class);

    // Test via direct method invocation on component instance
    $paymentData = $component->instance()->prepareCartForPayment();

    expect($paymentData['items'])->toHaveCount(1);
    expect($paymentData['items'][0]['price'])->toBe(92.0); // Converted to EUR
    expect($paymentData['items'][0]['currency'])->toBe('EUR');
    expect($paymentData['currency'])->toBe('EUR');
});

it('formats item prices correctly in display currency', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    session(['selected_currency' => 'EUR']);

    Cart::add([
        'id' => 'domain-usd',
        'name' => 'example.com',
        'price' => 100.0,
        'quantity' => 1,
        'attributes' => [
            'type' => 'registration',
            'currency' => 'USD',
            'domain_name' => 'example.com',
        ],
    ]);

    $component = Livewire::test(CartComponent::class);
    $items = $component->get('items');
    $componentInstance = $component->instance();

    $formattedPrice = $componentInstance->getFormattedItemPrice($items->first());
    $formattedTotal = $componentInstance->getFormattedItemTotal($items->first());

    // Should format in EUR
    expect($formattedPrice)->toContain('€');
    expect($formattedTotal)->toContain('€');
});

it('handles multiple items with different currencies', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    session(['selected_currency' => 'EUR']);

    Cart::add([
        'id' => 'domain-1',
        'name' => 'example.com',
        'price' => 100.0,
        'quantity' => 1,
        'attributes' => [
            'type' => 'registration',
            'currency' => 'USD',
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
        ],
    ]);

    $component = Livewire::test(CartComponent::class);

    // Item 1: 100 * 0.92 = 92
    // Item 2: 50 * 0.92 * 2 = 92
    // Total: 184
    expect($component->get('subtotalAmount'))->toBe(184.0);
});
