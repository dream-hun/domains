<?php

declare(strict_types=1);

use App\Helpers\StripeHelper;

it('converts regular currencies to cents by multiplying by 100', function (): void {
    expect(StripeHelper::convertToStripeAmount(10.50, 'USD'))->toBe(1050);
    expect(StripeHelper::convertToStripeAmount(99.99, 'EUR'))->toBe(9999);
    expect(StripeHelper::convertToStripeAmount(1.23, 'GBP'))->toBe(123);
});

it('handles zero-decimal currencies without multiplying by 100', function (): void {
    // RWF should not be multiplied by 100
    expect(StripeHelper::convertToStripeAmount(72770, 'RWF'))->toBe(72770);
    expect(StripeHelper::convertToStripeAmount(54577.50, 'RWF'))->toBe(54578);

    // JPY should not be multiplied by 100
    expect(StripeHelper::convertToStripeAmount(1000, 'JPY'))->toBe(1000);

    // KRW should not be multiplied by 100
    expect(StripeHelper::convertToStripeAmount(50000, 'KRW'))->toBe(50000);
});

it('handles alternative currency code FRW as RWF', function (): void {
    expect(StripeHelper::convertToStripeAmount(72770, 'FRW'))->toBe(72770);
    expect(StripeHelper::isZeroDecimalCurrency('FRW'))->toBeTrue();
});

it('correctly identifies zero-decimal currencies', function (): void {
    expect(StripeHelper::isZeroDecimalCurrency('RWF'))->toBeTrue();
    expect(StripeHelper::isZeroDecimalCurrency('JPY'))->toBeTrue();
    expect(StripeHelper::isZeroDecimalCurrency('KRW'))->toBeTrue();
    expect(StripeHelper::isZeroDecimalCurrency('VND'))->toBeTrue();
    expect(StripeHelper::isZeroDecimalCurrency('CLP'))->toBeTrue();

    expect(StripeHelper::isZeroDecimalCurrency('USD'))->toBeFalse();
    expect(StripeHelper::isZeroDecimalCurrency('EUR'))->toBeFalse();
    expect(StripeHelper::isZeroDecimalCurrency('GBP'))->toBeFalse();
});

it('rounds amounts correctly', function (): void {
    expect(StripeHelper::convertToStripeAmount(10.499, 'USD'))->toBe(1050);
    expect(StripeHelper::convertToStripeAmount(10.501, 'USD'))->toBe(1050);
    expect(StripeHelper::convertToStripeAmount(10.555, 'USD'))->toBe(1056);

    expect(StripeHelper::convertToStripeAmount(72770.4, 'RWF'))->toBe(72770);
    expect(StripeHelper::convertToStripeAmount(72770.6, 'RWF'))->toBe(72771);
});

it('handles case-insensitive currency codes', function (): void {
    expect(StripeHelper::convertToStripeAmount(10.50, 'usd'))->toBe(1050);
    expect(StripeHelper::convertToStripeAmount(72770, 'rwf'))->toBe(72770);
    expect(StripeHelper::isZeroDecimalCurrency('rwf'))->toBeTrue();
    expect(StripeHelper::isZeroDecimalCurrency('USD'))->toBeFalse();
});
