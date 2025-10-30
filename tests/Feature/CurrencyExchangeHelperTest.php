<?php

declare(strict_types=1);

use App\Exceptions\CurrencyExchangeException;
use App\Helpers\CurrencyExchangeHelper;
use Cknow\Money\Money;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Cache::flush();
    config(['currency_exchange.cache.enabled' => true]);
});

it('fetches and caches USD to FRW exchange rate', function (): void {
    Http::fake([
        '*/pair/USD/FRW' => Http::response([
            'result' => 'success',
            'base_code' => 'USD',
            'target_code' => 'FRW',
            'conversion_rate' => 1350.0,
            'time_last_update_unix' => 1585267200,
            'time_last_update_utc' => 'Fri, 27 Mar 2020 00:00:00 +0000',
            'time_next_update_unix' => 1585270800,
            'time_next_update_utc' => 'Sat, 28 Mar 2020 01:00:00 +0000',
        ]),
    ]);

    $helper = app(CurrencyExchangeHelper::class);
    $rate = $helper->getExchangeRate('USD', 'FRW');

    expect($rate)->toBe(1350.0);
    expect(Cache::has('exchange_rate:USD:FRW'))->toBeTrue();
});

it('fetches and caches FRW to USD exchange rate', function (): void {
    Http::fake([
        '*/pair/FRW/USD' => Http::response([
            'result' => 'success',
            'base_code' => 'FRW',
            'target_code' => 'USD',
            'conversion_rate' => 0.00074074,
            'time_last_update_unix' => 1585267200,
            'time_last_update_utc' => 'Fri, 27 Mar 2020 00:00:00 +0000',
            'time_next_update_unix' => 1585270800,
            'time_next_update_utc' => 'Sat, 28 Mar 2020 01:00:00 +0000',
        ]),
    ]);

    $helper = app(CurrencyExchangeHelper::class);
    $rate = $helper->getExchangeRate('FRW', 'USD');

    expect($rate)->toBe(0.00074074);
    expect(Cache::has('exchange_rate:FRW:USD'))->toBeTrue();
});

it('converts USD to FRW correctly', function (): void {
    Http::fake([
        '*/pair/USD/FRW' => Http::response([
            'result' => 'success',
            'conversion_rate' => 1350.0,
            'time_last_update_utc' => 'Fri, 27 Mar 2020 00:00:00 +0000',
            'time_next_update_utc' => 'Sat, 28 Mar 2020 01:00:00 +0000',
        ]),
    ]);

    $helper = app(CurrencyExchangeHelper::class);
    $money = $helper->convertUsdToFrw(100.0);

    expect($money)->toBeInstanceOf(Money::class);
    expect($money->getCurrency()->getCode())->toBe('FRW');
    expect($money->getAmount())->toBe('13500000'); // 100 * 1350 * 100 (converted to minor units)
});

it('converts FRW to USD correctly', function (): void {
    Http::fake([
        '*/pair/FRW/USD' => Http::response([
            'result' => 'success',
            'conversion_rate' => 0.00074074,
            'time_last_update_utc' => 'Fri, 27 Mar 2020 00:00:00 +0000',
            'time_next_update_utc' => 'Sat, 28 Mar 2020 01:00:00 +0000',
        ]),
    ]);

    $helper = app(CurrencyExchangeHelper::class);
    $money = $helper->convertFrwToUsd(100000.0);

    expect($money)->toBeInstanceOf(Money::class);
    expect($money->getCurrency()->getCode())->toBe('USD');
    expect($money->getAmount())->toBe('7407'); // 100000 * 0.00074074 * 100 rounded
});

it('uses cached rates on subsequent requests', function (): void {
    Http::fake([
        '*/pair/USD/FRW' => Http::response([
            'result' => 'success',
            'conversion_rate' => 1350.0,
            'time_last_update_utc' => 'Fri, 27 Mar 2020 00:00:00 +0000',
            'time_next_update_utc' => 'Sat, 28 Mar 2020 01:00:00 +0000',
        ]),
    ]);

    $helper = app(CurrencyExchangeHelper::class);

    // First call hits API
    $rate1 = $helper->getExchangeRate('USD', 'FRW');

    // Second call uses cache
    $rate2 = $helper->getExchangeRate('USD', 'FRW');

    expect($rate1)->toBe($rate2);
    Http::assertSentCount(1); // Only one API call should be made
});

it('throws exception for unsupported currency codes', function (): void {
    $helper = app(CurrencyExchangeHelper::class);

    $helper->getExchangeRate('USD', 'EUR');
})->throws(CurrencyExchangeException::class, 'Currency code \'EUR\' is not supported');

it('throws exception for negative amounts', function (): void {
    $helper = app(CurrencyExchangeHelper::class);

    $helper->convertUsdToFrw(-100.0);
})->throws(CurrencyExchangeException::class, 'Amount must be positive');

it('handles invalid API key error', function (): void {
    Http::fake([
        '*/pair/USD/FRW' => Http::response([
            'result' => 'error',
            'error-type' => 'invalid-key',
        ]),
    ]);

    $helper = app(CurrencyExchangeHelper::class);

    $helper->getExchangeRate('USD', 'FRW');
})->throws(CurrencyExchangeException::class, 'invalid');

it('handles quota reached error', function (): void {
    Http::fake([
        '*/pair/USD/FRW' => Http::response([
            'result' => 'error',
            'error-type' => 'quota-reached',
        ]),
    ]);

    $helper = app(CurrencyExchangeHelper::class);

    $helper->getExchangeRate('USD', 'FRW');
})->throws(CurrencyExchangeException::class, 'quota');

it('uses fallback rate when API fails', function (): void {
    Http::fake([
        '*/pair/USD/FRW' => Http::response(null, 500),
    ]);

    config(['currency_exchange.error_handling.use_fallback_on_error' => true]);
    config(['currency_exchange.fallback_rates.USD_TO_FRW' => 1350.0]);

    $helper = app(CurrencyExchangeHelper::class);
    $rate = $helper->getExchangeRate('USD', 'FRW');

    expect($rate)->toBe(1350.0);
});

it('formats Money objects correctly for USD', function (): void {
    $money = Money::USD(10050); // $100.50

    $helper = app(CurrencyExchangeHelper::class);
    $formatted = $helper->formatMoney($money);

    expect($formatted)->toBe('$100.50');
});

it('formats Money objects correctly for FRW', function (): void {
    $money = Money::FRW(135000); // 1,350 FRW

    $helper = app(CurrencyExchangeHelper::class);
    $formatted = $helper->formatMoney($money);

    expect($formatted)->toBe('FRW1,350');
});

it('returns rate metadata', function (): void {
    Http::fake([
        '*/pair/USD/FRW' => Http::response([
            'result' => 'success',
            'conversion_rate' => 1350.0,
            'time_last_update_utc' => 'Fri, 27 Mar 2020 00:00:00 +0000',
            'time_next_update_utc' => 'Sat, 28 Mar 2020 01:00:00 +0000',
        ]),
    ]);

    $helper = app(CurrencyExchangeHelper::class);
    $helper->getExchangeRate('USD', 'FRW');

    $metadata = $helper->getRateMetadata('USD', 'FRW');

    expect($metadata)->toHaveKeys(['from', 'to', 'last_updated', 'next_update', 'is_cached', 'is_fallback']);
    expect($metadata['from'])->toBe('USD');
    expect($metadata['to'])->toBe('FRW');
    expect($metadata['is_cached'])->toBeTrue();
    expect($metadata['is_fallback'])->toBeFalse();
});

it('clears cache for specific currency pair', function (): void {
    Http::fake([
        '*/pair/USD/FRW' => Http::response([
            'result' => 'success',
            'conversion_rate' => 1350.0,
            'time_last_update_utc' => 'Fri, 27 Mar 2020 00:00:00 +0000',
            'time_next_update_utc' => 'Sat, 28 Mar 2020 01:00:00 +0000',
        ]),
    ]);

    $helper = app(CurrencyExchangeHelper::class);
    $helper->getExchangeRate('USD', 'FRW');

    expect(Cache::has('exchange_rate:USD:FRW'))->toBeTrue();

    $helper->clearCache('USD', 'FRW');

    expect(Cache::has('exchange_rate:USD:FRW'))->toBeFalse();
});

it('converts with generic convertWithAmount method', function (): void {
    Http::fake([
        '*/pair/USD/FRW' => Http::response([
            'result' => 'success',
            'conversion_rate' => 1350.0,
            'time_last_update_utc' => 'Fri, 27 Mar 2020 00:00:00 +0000',
            'time_next_update_utc' => 'Sat, 28 Mar 2020 01:00:00 +0000',
        ]),
    ]);

    $helper = app(CurrencyExchangeHelper::class);
    $money = $helper->convertWithAmount('USD', 'FRW', 50.0);

    expect($money)->toBeInstanceOf(Money::class);
    expect($money->getCurrency()->getCode())->toBe('FRW');
    expect($money->getAmount())->toBe('6750000'); // 50 * 1350 * 100
});

it('returns same amount when converting to same currency', function (): void {
    $helper = app(CurrencyExchangeHelper::class);
    $rate = $helper->getExchangeRate('USD', 'USD');

    expect($rate)->toBe(1.0);
});

it('handles malformed request error', function (): void {
    Http::fake([
        '*/pair/USD/FRW' => Http::response([
            'result' => 'error',
            'error-type' => 'malformed-request',
        ]),
    ]);

    $helper = app(CurrencyExchangeHelper::class);

    $helper->getExchangeRate('USD', 'FRW');
})->throws(CurrencyExchangeException::class, 'malformed');

it('handles inactive account error', function (): void {
    Http::fake([
        '*/pair/USD/FRW' => Http::response([
            'result' => 'error',
            'error-type' => 'inactive-account',
        ]),
    ]);

    $helper = app(CurrencyExchangeHelper::class);

    $helper->getExchangeRate('USD', 'FRW');
})->throws(CurrencyExchangeException::class, 'inactive');
