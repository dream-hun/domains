<?php

declare(strict_types=1);

use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Seed currencies
    Currency::create([
        'code' => 'USD',
        'name' => 'US Dollar',
        'symbol' => '$',
        'exchange_rate' => 1.0,
        'is_base' => true,
        'is_active' => true,
    ]);

    Currency::create([
        'code' => 'FRW',
        'name' => 'Rwandan Franc',
        'symbol' => 'FRW',
        'exchange_rate' => 1350.0,
        'is_base' => false,
        'is_active' => true,
    ]);
});

it('formats whole dollar amounts without decimals', function (): void {
    $currency = Currency::where('code', 'USD')->first();
    $formatted = $currency->format(100.0);

    expect($formatted)->toBe('$100');
});

it('formats dollar amounts with cents using 2 decimals', function (): void {
    $currency = Currency::where('code', 'USD')->first();
    $formatted = $currency->format(99.50);

    expect($formatted)->toBe('$99.50');
});

it('formats FRW amounts without decimals', function (): void {
    $currency = Currency::where('code', 'FRW')->first();
    $formatted = $currency->format(135000.0);

    expect($formatted)->toBe('FRW135,000');
});

it('formats FRW amounts even with fractional parts', function (): void {
    $currency = Currency::where('code', 'FRW')->first();
    $formatted = $currency->format(135000.75);

    expect($formatted)->toBe('FRW135,001'); // Rounds to whole number
});

it('formats small dollar amounts correctly', function (): void {
    $currency = Currency::where('code', 'USD')->first();
    $formatted = $currency->format(0.99);

    expect($formatted)->toBe('$0.99');
});

it('formats zero amounts without decimals', function (): void {
    $currency = Currency::where('code', 'USD')->first();
    $formatted = $currency->format(0.0);

    expect($formatted)->toBe('$0');
});

it('rounds FRW amounts with fractional cents consistently', function (): void {
    $currency = Currency::where('code', 'FRW')->first();

    // Test the exact scenario from the cart issue
    $formatted1 = $currency->format(203755.41);
    $formatted2 = $currency->format(203756.0);

    // Both should round to the same value
    expect($formatted1)->toBe('FRW203,755');
    expect($formatted2)->toBe('FRW203,756');
});

it('ensures consistent rounding across multiple calls', function (): void {
    $currency = Currency::where('code', 'FRW')->first();

    // Multiple calls with the same fractional value should be consistent
    $formatted1 = $currency->format(203755.41);
    $formatted2 = $currency->format(203755.41);
    $formatted3 = $currency->format(203755.41);

    expect($formatted1)->toBe($formatted2);
    expect($formatted2)->toBe($formatted3);
});
