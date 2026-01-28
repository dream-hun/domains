<?php

declare(strict_types=1);

use App\Contracts\Currency\CurrencyConverterContract;
use App\Models\Currency;
use Cknow\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();

    // Seed currencies
    Currency::query()->create([
        'code' => 'USD',
        'name' => 'US Dollar',
        'symbol' => '$',
        'exchange_rate' => 1.0,
        'is_base' => true,
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

    Currency::query()->create([
        'code' => 'EUR',
        'name' => 'Euro',
        'symbol' => '€',
        'exchange_rate' => 0.92,
        'is_base' => false,
        'is_active' => true,
    ]);
});

it('uses ExchangeRateProvider for USD to RWF conversion', function (): void {
    Http::fake([
        '*/pair/USD/RWF' => Http::response([
            'result' => 'success',
            'conversion_rate' => 1350.0,
            'time_last_update_utc' => 'Fri, 27 Mar 2020 00:00:00 +0000',
            'time_next_update_utc' => 'Sat, 28 Mar 2020 01:00:00 +0000',
        ]),
    ]);

    $service = resolve(CurrencyConverterContract::class);
    $result = $service->convert(100.0, 'USD', 'RWF');

    expect($result)->toBe(135000.0);
});

it('uses ExchangeRateProvider for RWF to USD conversion', function (): void {
    Http::fake([
        '*/pair/RWF/USD' => Http::response([
            'result' => 'success',
            'conversion_rate' => 0.00074074,
            'time_last_update_utc' => 'Fri, 27 Mar 2020 00:00:00 +0000',
            'time_next_update_utc' => 'Sat, 28 Mar 2020 01:00:00 +0000',
        ]),
    ]);

    $service = resolve(CurrencyConverterContract::class);
    $result = $service->convert(100000.0, 'RWF', 'USD');

    expect($result)->toBeGreaterThan(0)
        ->and($result)->toBeLessThan(100);
});

it('uses database conversion for non-USD/RWF pairs', function (): void {
    $service = resolve(CurrencyConverterContract::class);
    $result = $service->convert(100.0, 'USD', 'EUR');

    expect($result)->toBe(92.0); // 100 * 0.92
});

it('falls back to database when API fails for USD/RWF', function (): void {
    Http::fake([
        '*/pair/USD/RWF' => Http::response(null, 500),
    ]);

    $service = resolve(CurrencyConverterContract::class);
    $result = $service->convert(100.0, 'USD', 'RWF');

    // Should fall back to database rate
    expect($result)->toBe(135000.0);
});

it('returns Money object from convertToMoney for USD/RWF', function (): void {
    Http::fake([
        '*/pair/USD/RWF' => Http::response([
            'result' => 'success',
            'conversion_rate' => 1350.0,
            'time_last_update_utc' => 'Fri, 27 Mar 2020 00:00:00 +0000',
            'time_next_update_utc' => 'Sat, 28 Mar 2020 01:00:00 +0000',
        ]),
    ]);

    $service = resolve(CurrencyConverterContract::class);
    $money = $service->convertToMoney(100.0, 'USD', 'RWF');

    expect($money)->toBeInstanceOf(Money::class)
        ->and($money->getCurrency()->getCode())->toBe('RWF');
});

it('returns Money object from convertToMoney for non-USD/RWF pairs', function (): void {
    $service = resolve(CurrencyConverterContract::class);
    $money = $service->convertToMoney(100.0, 'USD', 'EUR');

    expect($money)->toBeInstanceOf(Money::class)
        ->and($money->getCurrency()->getCode())->toBe('EUR')
        ->and($money->getAmount())->toBe('9200');
    // 92 * 100
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

    $service = resolve(CurrencyConverterContract::class);
    $formatted = $service->formatAsMoney(100.50, 'USD');

    expect($formatted)->toContain('$')
        ->and($formatted)->toContain('100.50');
});

it('formats as Money for RWF', function (): void {
    Http::fake([
        '*/pair/RWF/RWF' => Http::response([
            'result' => 'success',
            'conversion_rate' => 1.0,
            'time_last_update_utc' => 'Fri, 27 Mar 2020 00:00:00 +0000',
            'time_next_update_utc' => 'Sat, 28 Mar 2020 01:00:00 +0000',
        ]),
    ]);

    $service = resolve(CurrencyConverterContract::class);
    $formatted = $service->formatAsMoney(1350.0, 'RWF');

    expect($formatted)->toContain('FRW')
        ->and($formatted)->toContain('1,350');
});

it('throws exception for negative amounts', function (): void {
    $service = resolve(CurrencyConverterContract::class);

    $service->convert(-100.0, 'USD', 'EUR');
})->throws(Exception::class, 'negative');

it('returns same amount when currencies are identical', function (): void {
    $service = resolve(CurrencyConverterContract::class);
    $result = $service->convert(100.0, 'USD', 'USD');

    expect($result)->toBe(100.0);
});

it('throws exception for non-existent currency', function (): void {
    $service = resolve(CurrencyConverterContract::class);

    $service->convert(100.0, 'USD', 'XXX');
})->throws(Exception::class, 'not found');

it('throws exception when currency is inactive', function (): void {
    Currency::query()->where('code', 'EUR')->update(['is_active' => false]);

    $service = resolve(CurrencyConverterContract::class);

    $service->convert(100.0, 'USD', 'EUR');
})->throws(Exception::class, 'inactive');

it('gets user currency from session', function (): void {
    session(['selected_currency' => 'EUR']);

    $service = resolve(CurrencyConverterContract::class);
    $currency = $service->getUserCurrency();

    expect($currency)->toBeInstanceOf(Currency::class)
        ->and($currency->code)->toBe('EUR');
});

it('returns base currency when no session currency', function (): void {
    $service = resolve(CurrencyConverterContract::class);
    $currency = $service->getUserCurrency();

    expect($currency)->toBeInstanceOf(Currency::class)
        ->and($currency->code)->toBe('USD')
        ->and($currency->is_base)->toBeTrue();
});

it('gets all active currencies', function (): void {
    $currencies = Currency::query()->where('is_active', true)->get();
    expect($currencies)->toHaveCount(3)
        ->and($currencies->pluck('code')->toArray())->toContain('USD', 'RWF');
});

it('formats currency with symbol', function (): void {
    $service = resolve(CurrencyConverterContract::class);
    $formatted = $service->format(100.50, 'USD');

    expect($formatted)->toBe('$100.50');
});

it('caches currency lookups', function (): void {
    $service = resolve(CurrencyConverterContract::class);

    // First call to populate cache
    $currency1 = $service->getCurrency('USD');

    // Clear the database to test cache
    Currency::query()->where('code', 'USD')->delete();

    // Second call should use cache, not database
    $currency2 = $service->getCurrency('USD');

    expect($currency2)->toBeInstanceOf(Currency::class)
        ->and($currency2->code)->toBe('USD');
});
