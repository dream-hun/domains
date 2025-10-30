<?php

declare(strict_types=1);

use App\Models\Currency;
use App\Services\CurrencyService;
use Cknow\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();

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
        'symbol' => 'FRw',
        'exchange_rate' => 1350.0,
        'is_base' => false,
        'is_active' => true,
    ]);

    Currency::create([
        'code' => 'EUR',
        'name' => 'Euro',
        'symbol' => '€',
        'exchange_rate' => 0.92,
        'is_base' => false,
        'is_active' => true,
    ]);
});

it('uses CurrencyExchangeHelper for USD to FRW conversion', function (): void {
    Http::fake([
        '*/pair/USD/FRW' => Http::response([
            'result' => 'success',
            'conversion_rate' => 1350.0,
            'time_last_update_utc' => 'Fri, 27 Mar 2020 00:00:00 +0000',
            'time_next_update_utc' => 'Sat, 28 Mar 2020 01:00:00 +0000',
        ]),
    ]);

    $service = app(CurrencyService::class);
    $result = $service->convert(100.0, 'USD', 'FRW');

    expect($result)->toBe(135000.0);
});

it('uses CurrencyExchangeHelper for FRW to USD conversion', function (): void {
    Http::fake([
        '*/pair/FRW/USD' => Http::response([
            'result' => 'success',
            'conversion_rate' => 0.00074074,
            'time_last_update_utc' => 'Fri, 27 Mar 2020 00:00:00 +0000',
            'time_next_update_utc' => 'Sat, 28 Mar 2020 01:00:00 +0000',
        ]),
    ]);

    $service = app(CurrencyService::class);
    $result = $service->convert(100000.0, 'FRW', 'USD');

    expect($result)->toBeGreaterThan(0);
    expect($result)->toBeLessThan(100);
});

it('uses database conversion for non-USD/FRW pairs', function (): void {
    $service = app(CurrencyService::class);
    $result = $service->convert(100.0, 'USD', 'EUR');

    expect($result)->toBe(92.0); // 100 * 0.92
});

it('falls back to database when API fails for USD/FRW', function (): void {
    Http::fake([
        '*/pair/USD/FRW' => Http::response(null, 500),
    ]);

    $service = app(CurrencyService::class);
    $result = $service->convert(100.0, 'USD', 'FRW');

    // Should fall back to database rate
    expect($result)->toBe(135000.0);
});

it('returns Money object from convertToMoney for USD/FRW', function (): void {
    Http::fake([
        '*/pair/USD/FRW' => Http::response([
            'result' => 'success',
            'conversion_rate' => 1350.0,
            'time_last_update_utc' => 'Fri, 27 Mar 2020 00:00:00 +0000',
            'time_next_update_utc' => 'Sat, 28 Mar 2020 01:00:00 +0000',
        ]),
    ]);

    $service = app(CurrencyService::class);
    $money = $service->convertToMoney(100.0, 'USD', 'FRW');

    expect($money)->toBeInstanceOf(Money::class);
    expect($money->getCurrency()->getCode())->toBe('FRW');
});

it('returns Money object from convertToMoney for non-USD/FRW pairs', function (): void {
    $service = app(CurrencyService::class);
    $money = $service->convertToMoney(100.0, 'USD', 'EUR');

    expect($money)->toBeInstanceOf(Money::class);
    expect($money->getCurrency()->getCode())->toBe('EUR');
    expect($money->getAmount())->toBe('9200'); // 92 * 100
});

it('formats as Money for USD', function (): void {
    Http::fake([
        '*/pair/USD/USD' => Http::response([
            'result' => 'success',
            'conversion_rate' => 1.0,
            'time_last_update_utc' => 'Fri, 27 Mar 2020 00:00:00 +0000',
            'time_next_update_utc' => 'Sat, 28 Mar 2020 01:00:00 +0000',
        ]),
    ]);

    $service = app(CurrencyService::class);
    $formatted = $service->formatAsMoney(100.50, 'USD');

    expect($formatted)->toContain('$');
    expect($formatted)->toContain('100.50');
});

it('formats as Money for FRW', function (): void {
    Http::fake([
        '*/pair/FRW/FRW' => Http::response([
            'result' => 'success',
            'conversion_rate' => 1.0,
            'time_last_update_utc' => 'Fri, 27 Mar 2020 00:00:00 +0000',
            'time_next_update_utc' => 'Sat, 28 Mar 2020 01:00:00 +0000',
        ]),
    ]);

    $service = app(CurrencyService::class);
    $formatted = $service->formatAsMoney(1350.0, 'FRW');

    expect($formatted)->toContain('FRw');
    expect($formatted)->toContain('1,350');
});

it('throws exception for negative amounts', function (): void {
    $service = app(CurrencyService::class);

    $service->convert(-100.0, 'USD', 'EUR');
})->throws(Exception::class, 'negative');

it('returns same amount when currencies are identical', function (): void {
    $service = app(CurrencyService::class);
    $result = $service->convert(100.0, 'USD', 'USD');

    expect($result)->toBe(100.0);
});

it('throws exception for non-existent currency', function (): void {
    $service = app(CurrencyService::class);

    $service->convert(100.0, 'USD', 'XXX');
})->throws(Exception::class, 'not found');

it('throws exception when currency is inactive', function (): void {
    Currency::where('code', 'EUR')->update(['is_active' => false]);

    $service = app(CurrencyService::class);

    $service->convert(100.0, 'USD', 'EUR');
})->throws(Exception::class, 'inactive');

it('gets user currency from session', function (): void {
    session(['selected_currency' => 'EUR']);

    $service = app(CurrencyService::class);
    $currency = $service->getUserCurrency();

    expect($currency)->toBeInstanceOf(Currency::class);
    expect($currency->code)->toBe('EUR');
});

it('returns base currency when no session currency', function (): void {
    $service = app(CurrencyService::class);
    $currency = $service->getUserCurrency();

    expect($currency)->toBeInstanceOf(Currency::class);
    expect($currency->code)->toBe('USD');
    expect($currency->is_base)->toBeTrue();
});

it('gets all active currencies', function (): void {
    $service = app(CurrencyService::class);
    $currencies = $service->getActiveCurrencies();

    expect($currencies)->toHaveCount(3);
    expect($currencies->pluck('code')->toArray())->toContain('USD', 'FRW', 'EUR');
});

it('formats currency with symbol', function (): void {
    $service = app(CurrencyService::class);
    $formatted = $service->format(100.50, 'USD');

    expect($formatted)->toBe('$100.50');
});

it('caches currency lookups', function (): void {
    $service = app(CurrencyService::class);

    // First call
    $currency1 = $service->getCurrency('USD');

    // Clear the database to test cache
    Currency::where('code', 'USD')->delete();

    // Second call should use cache
    $currency2 = $service->getCurrency('USD');

    expect($currency2)->toBeInstanceOf(Currency::class);
    expect($currency2->code)->toBe('USD');
});
