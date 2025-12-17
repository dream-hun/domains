<?php

declare(strict_types=1);

use App\Models\Currency;
use App\Services\CartPriceConverter;
use Darryldecode\Cart\CartCollection;
use Darryldecode\Cart\ItemAttributeCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Mock HTTP responses for currency API
    Http::fake([
        'https://api.exchangerate-api.com/v4/latest/USD' => Http::response([
            'rates' => [
                'USD' => 1.0,
                'EUR' => 0.92,
                'RWF' => 1350.0,
            ],
        ]),
    ]);

    // Create base currency
    Currency::query()->create([
        'code' => 'USD',
        'name' => 'US Dollar',
        'symbol' => '$',
        'exchange_rate' => 1.0,
        'is_base' => true,
        'is_active' => true,
    ]);

    // Create other currencies
    Currency::query()->create([
        'code' => 'EUR',
        'name' => 'Euro',
        'symbol' => '€',
        'exchange_rate' => 0.92,
        'is_base' => false,
        'is_active' => true,
    ]);

    Currency::query()->create([
        'code' => 'RWF',
        'name' => 'Rwandan Franc',
        'symbol' => 'FRW',
        'exchange_rate' => 1350.0,
        'is_base' => false,
        'is_active' => true,
    ]);
});

/**
 * Create a lightweight cart item object compatible with CartPriceConverter.
 */
function makeCartItem(string $id, string $name, float $price, int $quantity, array $attributes = []): object
{
    return (object) [
        'id' => $id,
        'name' => $name,
        'price' => $price,
        'quantity' => $quantity,
        'attributes' => new ItemAttributeCollection($attributes),
    ];
}

describe('convertItemPrice', function (): void {
    it('converts domain item price from USD to EUR', function (): void {
        $item = makeCartItem('test-domain', 'example.com', 100.0, 1, [
            'type' => 'registration',
            'currency' => 'USD',
        ]);

        $converter = resolve(CartPriceConverter::class);
        $converted = $converter->convertItemPrice($item, 'EUR');

        expect($converted)->toBe(92.0); // 100 * 0.92
    });

    it('returns same price when currencies match', function (): void {
        $item = makeCartItem('test-domain', 'example.com', 100.0, 1, [
            'type' => 'registration',
            'currency' => 'USD',
        ]);

        $converter = resolve(CartPriceConverter::class);
        $converted = $converter->convertItemPrice($item, 'USD');

        expect($converted)->toBe(100.0);
    });

    it('converts hosting item using monthly_unit_price', function (): void {
        $item = makeCartItem('test-hosting', 'Hosting Plan', 120.0, 12, [
            'type' => 'hosting',
            'currency' => 'USD',
            'monthly_unit_price' => 10.0,
            'billing_cycle' => 'annually',
        ]);

        $converter = resolve(CartPriceConverter::class);
        $converted = $converter->convertItemPrice($item, 'EUR');

        // Should convert monthly price: 10 * 0.92 = 9.2
        expect($converted)->toBe(9.2);
    });

    it('calculates monthly_unit_price for hosting if not set', function (): void {
        $item = makeCartItem('test-hosting', 'Hosting Plan', 120.0, 12, [
            'type' => 'hosting',
            'currency' => 'USD',
            'billing_cycle' => 'annually', // 12 months
        ]);

        $converter = resolve(CartPriceConverter::class);
        $converted = $converter->convertItemPrice($item, 'EUR');

        // Monthly price = 120 / 12 = 10, converted = 10 * 0.92 = 9.2
        expect($converted)->toBe(9.2);
    });

    it('converts subscription_renewal item using display_unit_price', function (): void {
        $item = makeCartItem('test-sub', 'Subscription', 30.0, 1, [
            'type' => 'subscription_renewal',
            'currency' => 'USD',
            'display_unit_price' => 30.0,
            'billing_cycle' => 'monthly',
        ]);

        $converter = resolve(CartPriceConverter::class);
        $converted = $converter->convertItemPrice($item, 'EUR');

        expect($converted)->toBe(27.6); // 30 * 0.92
    });

    it('falls back to unit_price for subscription_renewal if display_unit_price not set', function (): void {
        $item = makeCartItem('test-sub', 'Subscription', 30.0, 1, [
            'type' => 'subscription_renewal',
            'currency' => 'USD',
            'unit_price' => 30.0,
            'billing_cycle' => 'monthly',
        ]);

        $converter = resolve(CartPriceConverter::class);
        $converted = $converter->convertItemPrice($item, 'EUR');

        expect($converted)->toBe(27.6);
    });
});

describe('calculateItemTotal', function (): void {
    it('calculates total for domain item with quantity', function (): void {
        $item = makeCartItem('test-domain', 'example.com', 100.0, 2, [
            'type' => 'registration',
            'currency' => 'USD',
        ]);

        $converter = resolve(CartPriceConverter::class);
        $total = $converter->calculateItemTotal($item, 'EUR');

        // 100 * 0.92 * 2 = 184
        expect($total)->toBe(184.0);
    });

    it('calculates total for hosting item using monthly price', function (): void {
        $item = makeCartItem('test-hosting', 'Hosting Plan', 120.0, 12, [
            'type' => 'hosting',
            'currency' => 'USD',
            'monthly_unit_price' => 10.0,
            'billing_cycle' => 'annually',
        ]);

        $converter = resolve(CartPriceConverter::class);
        $total = $converter->calculateItemTotal($item, 'EUR');

        // Monthly price 10 * 0.92 = 9.2, total = 9.2 * 12 = 110.4
        expect(round($total, 2))->toBe(110.4);
    });

    it('calculates total for subscription_renewal with annual billing', function (): void {
        $item = makeCartItem('test-sub', 'Subscription', 120.0, 12, [
            'type' => 'subscription_renewal',
            'currency' => 'USD',
            'display_unit_price' => 120.0, // Annual price
            'billing_cycle' => 'annually',
        ]);

        $converter = resolve(CartPriceConverter::class);
        $total = $converter->calculateItemTotal($item, 'EUR');

        // Convert annual price: 120 * 0.92 = 110.4
        // Years = 12 / 12 = 1
        // Total = 110.4 * 1 = 110.4
        expect($total)->toBe(110.4);
    });

    it('calculates total for subscription_renewal with monthly billing', function (): void {
        $item = makeCartItem('test-sub', 'Subscription', 10.0, 3, [
            'type' => 'subscription_renewal',
            'currency' => 'USD',
            'display_unit_price' => 10.0,
            'billing_cycle' => 'monthly',
        ]);

        $converter = resolve(CartPriceConverter::class);
        $total = $converter->calculateItemTotal($item, 'EUR');

        // Convert monthly price: 10 * 0.92 = 9.2
        // Total = 9.2 * 3 = 27.6
        expect(round($total, 2))->toBe(27.6);
    });
});

describe('calculateCartSubtotal', function (): void {
    it('calculates subtotal for mixed cart items', function (): void {
        $items = collect([
            makeCartItem('domain-1', 'example.com', 100.0, 1, [
                'type' => 'registration',
                'currency' => 'USD',
            ]),
            makeCartItem('domain-2', 'example.net', 50.0, 2, [
                'type' => 'registration',
                'currency' => 'USD',
            ]),
        ]);

        $cartCollection = new CartCollection($items);
        $converter = resolve(CartPriceConverter::class);
        $subtotal = $converter->calculateCartSubtotal($cartCollection, 'EUR');

        // (100 * 0.92) + (50 * 0.92 * 2) = 92 + 92 = 184
        expect($subtotal)->toBe(184.0);
    });

    it('handles empty cart', function (): void {
        $items = collect([]);
        $cartCollection = new CartCollection($items);
        $converter = resolve(CartPriceConverter::class);
        $subtotal = $converter->calculateCartSubtotal($cartCollection, 'EUR');

        expect($subtotal)->toBe(0.0);
    });

    it('calculates subtotal with different item types', function (): void {
        $items = collect([
            makeCartItem('domain-1', 'example.com', 100.0, 1, [
                'type' => 'registration',
                'currency' => 'USD',
            ]),
            makeCartItem('hosting-1', 'Hosting', 120.0, 12, [
                'type' => 'hosting',
                'currency' => 'USD',
                'monthly_unit_price' => 10.0,
                'billing_cycle' => 'annually',
            ]),
        ]);

        $cartCollection = new CartCollection($items);
        $converter = resolve(CartPriceConverter::class);
        $subtotal = $converter->calculateCartSubtotal($cartCollection, 'EUR');

        // Domain: 100 * 0.92 = 92
        // Hosting: (10 * 0.92) * 12 = 110.4
        // Total: 92 + 110.4 = 202.4
        expect(round($subtotal, 2))->toBe(202.4);
    });
});

describe('convertCartItemsToCurrency', function (): void {
    it('converts all cart items to target currency', function (): void {
        $items = collect([
            makeCartItem('domain-1', 'example.com', 100.0, 1, [
                'type' => 'registration',
                'currency' => 'USD',
            ]),
            makeCartItem('domain-2', 'example.net', 50.0, 1, [
                'type' => 'registration',
                'currency' => 'USD',
            ]),
        ]);

        $cartCollection = new CartCollection($items);
        $converter = resolve(CartPriceConverter::class);
        $converted = $converter->convertCartItemsToCurrency($cartCollection, 'EUR');

        expect($converted)->toHaveCount(2)
            ->and($converted->first()->price)->toBe(92.0)
            ->and($converted->first()->attributes->currency)->toBe('EUR')
            ->and($converted->last()->price)->toBe(46.0)
            ->and($converted->last()->attributes->currency)->toBe('EUR');
    });

    it(/**
     * @throws Throwable
     */ 'preserves item attributes when converting', function (): void {
        $items = collect([
            makeCartItem('domain-1', 'example.com', 100.0, 1, [
                'type' => 'registration',
                'currency' => 'USD',
                'domain_id' => 123,
                'domain_name' => 'example.com',
            ]),
        ]);

        $cartCollection = new CartCollection($items);
        $converter = resolve(CartPriceConverter::class);
        $converted = $converter->convertCartItemsToCurrency($cartCollection, 'EUR');

        $convertedItem = $converted->first();
        expect($convertedItem->attributes->domain_id)->toBe(123)
            ->and($convertedItem->attributes->domain_name)->toBe('example.com')
            ->and($convertedItem->attributes->type)->toBe('registration');
    });
});

describe('error handling', function (): void {
    it('throws exception when currency conversion fails', function (): void {
        $item = makeCartItem('test-domain', 'example.com', 100.0, 1, [
            'type' => 'registration',
            'currency' => 'USD',
        ]);

        // Mock the HTTP client to simulate API failure
        Http::fake([
            'https://api.exchangerate-api.com/v4/latest/USD' => Http::response(
                ['error' => 'Invalid currency'],
                404
            ),
        ]);

        $converter = resolve(CartPriceConverter::class);

        expect(/**
         * @throws Throwable
         */ fn () => $converter->convertItemPrice($item, 'INVALID'))
            ->toThrow(Exception::class);
    });

    it('handles missing monthly_unit_price for hosting gracefully', function (): void {
        $item = makeCartItem('test-hosting', 'Hosting Plan', 120.0, 12, [
            'type' => 'hosting',
            'currency' => 'USD',
            'billing_cycle' => 'annually',
            // monthly_unit_price not set
        ]);

        // Mock the HTTP client to return a rate that makes the helper fail
        // so it falls back to CurrencyService which uses database rates
        Http::fake([
            'https://api.exchangerate-api.com/v4/latest/USD' => Http::response(
                ['error' => 'API temporarily unavailable'],
                500
            ),
        ]);

        $converter = resolve(CartPriceConverter::class);
        $converted = $converter->convertItemPrice($item, 'EUR');

        // Should calculate: 120 / 12 = 10, then convert: 10 * 0.92 = 9.2 (using database rate)
        expect($converted)->toBe(9.2);
    });
});
