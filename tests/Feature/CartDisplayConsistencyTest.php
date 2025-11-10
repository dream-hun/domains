<?php

declare(strict_types=1);

use App\Helpers\CurrencyHelper;
use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Currency::query()->create([
        'code' => 'USD',
        'name' => 'US Dollar',
        'symbol' => '$',
        'exchange_rate' => 1.0,
        'is_base' => true,
        'is_active' => true,
    ]);

    Currency::query()->create([
        'code' => 'FRW',
        'name' => 'Rwandan Franc',
        'symbol' => 'FRW',
        'exchange_rate' => 1350.0,
        'is_base' => false,
        'is_active' => true,
    ]);
});

it('shows consistent FRW amounts between header and cart page', function (): void {
    $testAmount = 203755.41; // The exact amount from the screenshot

    // Simulate CartTotal formatting (uses Currency::format via formatCurrency)
    $currencyModel = Currency::query()->where('code', 'FRW')->first();
    $headerFormat = $currencyModel->format($testAmount);

    // Simulate CartComponent formatting (uses CurrencyHelper::formatMoney)
    $cartPageFormat = CurrencyHelper::formatMoney($testAmount, 'FRW');

    // Both should be identical
    expect($headerFormat)->toBe($cartPageFormat);
    expect($headerFormat)->toBe('FRW203,755');
    expect($cartPageFormat)->toBe('FRW203,755');
});

it('rounds fractional FRW amounts consistently', function (): void {
    $amounts = [
        203755.41 => 'FRW203,755', // Rounds down
        203755.51 => 'FRW203,756', // Rounds up
        203755.49 => 'FRW203,755', // Rounds down
        203756.00 => 'FRW203,756', // Already whole
    ];

    foreach ($amounts as $amount => $expected) {
        $currencyModel = Currency::query()->where('code', 'FRW')->first();
        $formatted1 = $currencyModel->format($amount);
        $formatted2 = CurrencyHelper::formatMoney($amount, 'FRW');

        expect($formatted1)->toBe($expected, sprintf('Currency::format(%s) should be %s', $amount, $expected));
        expect($formatted2)->toBe($expected, sprintf('CurrencyHelper::formatMoney(%s) should be %s', $amount, $expected));
        expect($formatted1)->toBe($formatted2, 'Both methods should return the same value for '.$amount);
    }
});

it('shows consistent USD amounts with cents', function (): void {
    $testAmount = 99.50;

    $currencyModel = Currency::query()->where('code', 'USD')->first();
    $format1 = $currencyModel->format($testAmount);
    $format2 = CurrencyHelper::formatMoney($testAmount, 'USD');

    expect($format1)->toBe($format2);
    expect($format1)->toBe('$99.50');
});

it('shows consistent USD whole amounts without decimals', function (): void {
    $testAmount = 100.0;

    $currencyModel = Currency::query()->where('code', 'USD')->first();
    $format1 = $currencyModel->format($testAmount);
    $format2 = CurrencyHelper::formatMoney($testAmount, 'USD');

    expect($format1)->toBe($format2);
    expect($format1)->toBe('$100');
});
