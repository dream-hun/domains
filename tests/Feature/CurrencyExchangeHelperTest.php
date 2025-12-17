<?php

declare(strict_types=1);

use App\Exceptions\CurrencyExchangeException;
use App\Helpers\CurrencyExchangeHelper;
use Cknow\Money\Money;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Cache::flush();
    CurrencyExchangeHelper::resetRequestCache();
    config(['currency_exchange.cache.enabled' => true]);
});

it('fetches and caches USD to RWF exchange rate', function (): void {
    Http::fake([
        '*/pair/USD/RWF' => Http::response([
            'result' => 'success',
            'base_code' => 'USD',
            'target_code' => 'RWF',
            'conversion_rate' => 1350.0,
            'time_last_update_unix' => 1585267200,
            'time_last_update_utc' => 'Fri, 27 Mar 2020 00:00:00 +0000',
            'time_next_update_unix' => 1585270800,
            'time_next_update_utc' => 'Sat, 28 Mar 2020 01:00:00 +0000',
        ]),
    ]);

    $helper = resolve(CurrencyExchangeHelper::class);
    $rate = $helper->getExchangeRate('USD', 'RWF');

    expect($rate)->toBe(1350.0)
        ->and(Cache::has('exchange_rate:USD:RWF'))->toBeTrue();
});

it('fetches and caches RWF to USD exchange rate', function (): void {
    Http::fake([
        '*/pair/RWF/USD' => Http::response([
            'result' => 'success',
            'base_code' => 'RWF',
            'target_code' => 'USD',
            'conversion_rate' => 0.00074074,
            'time_last_update_unix' => 1585267200,
            'time_last_update_utc' => 'Fri, 27 Mar 2020 00:00:00 +0000',
            'time_next_update_unix' => 1585270800,
            'time_next_update_utc' => 'Sat, 28 Mar 2020 01:00:00 +0000',
        ]),
    ]);

    $helper = resolve(CurrencyExchangeHelper::class);
    $rate = $helper->getExchangeRate('RWF', 'USD');

    expect($rate)->toBe(0.00074074)
        ->and(Cache::has('exchange_rate:RWF:USD'))->toBeTrue();
});

it('converts USD to RWF correctly', function (): void {
    Http::fake([
        '*/pair/USD/RWF' => Http::response([
            'result' => 'success',
            'conversion_rate' => 1350.0,
            'time_last_update_utc' => 'Fri, 27 Mar 2020 00:00:00 +0000',
            'time_next_update_utc' => 'Sat, 28 Mar 2020 01:00:00 +0000',
        ]),
    ]);

    $helper = resolve(CurrencyExchangeHelper::class);
    $money = $helper->convertUsdToRwf(100.0);

    expect($money)->toBeInstanceOf(Money::class)
        ->and($money->getCurrency()->getCode())->toBe('RWF')
        ->and($money->getAmount())->toBe('13500000');
    // 100 * 1350 * 100 (converted to minor units)
});

it('converts RWF to USD correctly', function (): void {
    Http::fake([
        '*/pair/RWF/USD' => Http::response([
            'result' => 'success',
            'conversion_rate' => 0.00074074,
            'time_last_update_utc' => 'Fri, 27 Mar 2020 00:00:00 +0000',
            'time_next_update_utc' => 'Sat, 28 Mar 2020 01:00:00 +0000',
        ]),
    ]);

    $helper = resolve(CurrencyExchangeHelper::class);
    $money = $helper->convertRwfToUsd(100000.0);

    expect($money)->toBeInstanceOf(Money::class)
        ->and($money->getCurrency()->getCode())->toBe('USD')
        ->and($money->getAmount())->toBe('7407');
    // 100000 * 0.00074074 * 100 rounded
});

it('uses cached rates on subsequent requests', function (): void {
    Http::fake([
        '*/pair/USD/RWF' => Http::response([
            'result' => 'success',
            'conversion_rate' => 1350.0,
            'time_last_update_utc' => 'Fri, 27 Mar 2020 00:00:00 +0000',
            'time_next_update_utc' => 'Sat, 28 Mar 2020 01:00:00 +0000',
        ]),
    ]);

    $helper = resolve(CurrencyExchangeHelper::class);

    // First call hits API
    $rate1 = $helper->getExchangeRate('USD', 'RWF');

    // Second call uses cache
    $rate2 = $helper->getExchangeRate('USD', 'RWF');

    expect($rate1)->toBe($rate2);
    Http::assertSentCount(1); // Only one API call should be made
});

it('throws exception for unsupported currency codes', function (): void {
    $helper = resolve(CurrencyExchangeHelper::class);

    $helper->getExchangeRate('USD', 'EUR');
})->throws(CurrencyExchangeException::class, "Currency code 'EUR' is not supported");

it('throws exception for negative amounts', function (): void {
    $helper = resolve(CurrencyExchangeHelper::class);

    $helper->convertUsdToRwf(-100.0);
})->throws(CurrencyExchangeException::class, 'Amount must be positive');

it('handles invalid API key error', function (): void {
    Http::fake([
        '*/pair/USD/RWF' => Http::response([
            'result' => 'error',
            'error-type' => 'invalid-key',
        ]),
    ]);

    $helper = resolve(CurrencyExchangeHelper::class);

    $helper->getExchangeRate('USD', 'RWF');
})->throws(CurrencyExchangeException::class, 'invalid');

it('handles quota reached error', function (): void {
    Http::fake([
        '*/pair/USD/RWF' => Http::response([
            'result' => 'error',
            'error-type' => 'quota-reached',
        ]),
    ]);

    $helper = resolve(CurrencyExchangeHelper::class);

    $helper->getExchangeRate('USD', 'RWF');
})->throws(CurrencyExchangeException::class, 'quota');

it('uses fallback rate when API fails', function (): void {
    Http::fake([
        '*/pair/USD/RWF' => Http::response(null, 500),
    ]);

    config(['currency_exchange.error_handling.use_fallback_on_error' => true]);
    config(['currency_exchange.fallback_rates.USD_TO_RWF' => 1350.0]);

    $helper = resolve(CurrencyExchangeHelper::class);
    $rate = $helper->getExchangeRate('USD', 'RWF');

    expect($rate)->toBe(1350.0);
});

it('formats Money objects correctly for USD', function (): void {
    $money = Money::USD(10050); // $100.50

    $helper = resolve(CurrencyExchangeHelper::class);
    $formatted = $helper->formatMoney($money);

    expect($formatted)->toBe('$100.50');
});

it('formats Money objects correctly for RWF', function (): void {
    $money = Money::RWF(135000); // 1,350 RWF

    $helper = resolve(CurrencyExchangeHelper::class);
    $formatted = $helper->formatMoney($money);

    expect($formatted)->toBe('FRW1,350');
});

it('returns rate metadata', function (): void {
    Http::fake([
        '*/pair/USD/RWF' => Http::response([
            'result' => 'success',
            'conversion_rate' => 1350.0,
            'time_last_update_utc' => 'Fri, 27 Mar 2020 00:00:00 +0000',
            'time_next_update_utc' => 'Sat, 28 Mar 2020 01:00:00 +0000',
        ]),
    ]);

    $helper = resolve(CurrencyExchangeHelper::class);
    $helper->getExchangeRate('USD', 'RWF');

    $metadata = $helper->getRateMetadata('USD', 'RWF');

    expect($metadata)->toHaveKeys(['from', 'to', 'last_updated', 'next_update', 'is_cached', 'is_fallback'])
        ->and($metadata['from'])->toBe('USD')
        ->and($metadata['to'])->toBe('RWF')
        ->and($metadata['is_cached'])->toBeTrue()
        ->and($metadata['is_fallback'])->toBeFalse();
});

it('clears cache for specific currency pair', function (): void {
    Http::fake([
        '*/pair/USD/RWF' => Http::response([
            'result' => 'success',
            'conversion_rate' => 1350.0,
            'time_last_update_utc' => 'Fri, 27 Mar 2020 00:00:00 +0000',
            'time_next_update_utc' => 'Sat, 28 Mar 2020 01:00:00 +0000',
        ]),
    ]);

    $helper = resolve(CurrencyExchangeHelper::class);
    $helper->getExchangeRate('USD', 'RWF');

    expect(Cache::has('exchange_rate:USD:RWF'))->toBeTrue();

    $helper->clearCache('USD', 'RWF');

    expect(Cache::has('exchange_rate:USD:RWF'))->toBeFalse();
});

it('converts with generic convertWithAmount method', function (): void {
    Http::fake([
        '*/pair/USD/RWF' => Http::response([
            'result' => 'success',
            'conversion_rate' => 1350.0,
            'time_last_update_utc' => 'Fri, 27 Mar 2020 00:00:00 +0000',
            'time_next_update_utc' => 'Sat, 28 Mar 2020 01:00:00 +0000',
        ]),
    ]);

    $helper = resolve(CurrencyExchangeHelper::class);
    $money = $helper->convertWithAmount('USD', 'RWF', 50.0);

    expect($money)->toBeInstanceOf(Money::class)
        ->and($money->getCurrency()->getCode())->toBe('RWF')
        ->and($money->getAmount())->toBe('6750000');
    // 50 * 1350 * 100
});

it('returns same amount when converting to same currency', function (): void {
    $helper = resolve(CurrencyExchangeHelper::class);
    $rate = $helper->getExchangeRate('USD', 'USD');

    expect($rate)->toBe(1.0);
});

it('handles malformed request error', function (): void {
    Http::fake([
        '*/pair/USD/RWF' => Http::response([
            'result' => 'error',
            'error-type' => 'malformed-request',
        ]),
    ]);

    $helper = resolve(CurrencyExchangeHelper::class);

    $helper->getExchangeRate('USD', 'RWF');
})->throws(CurrencyExchangeException::class, 'malformed');

it('handles inactive account error', function (): void {
    Http::fake([
        '*/pair/USD/RWF' => Http::response([
            'result' => 'error',
            'error-type' => 'inactive-account',
        ]),
    ]);

    $helper = resolve(CurrencyExchangeHelper::class);

    $helper->getExchangeRate('USD', 'RWF');
})->throws(CurrencyExchangeException::class, 'inactive');
