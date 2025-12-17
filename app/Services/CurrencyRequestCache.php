<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Currency;

/**
 * Request-level cache for currency lookups to prevent duplicate queries
 */
final class CurrencyRequestCache
{
    /**
     * @var array<string, Currency|null>
     */
    private static array $currencyCache = [];

    private static ?Currency $baseCurrencyCache = null;

    public static function getCurrency(string $code): ?Currency
    {
        return self::$currencyCache[$code] ?? null;
    }

    public static function setCurrency(string $code, ?Currency $currency): void
    {
        self::$currencyCache[$code] = $currency;
    }

    public static function hasCurrency(string $code): bool
    {
        return isset(self::$currencyCache[$code]);
    }

    public static function getBaseCurrency(): ?Currency
    {
        return self::$baseCurrencyCache;
    }

    public static function setBaseCurrency(Currency $currency): void
    {
        self::$baseCurrencyCache = $currency;
    }

    public static function hasBaseCurrency(): bool
    {
        return self::$baseCurrencyCache instanceof Currency;
    }

    /**
     * Clear all cached currencies (useful for testing)
     */
    public static function clear(): void
    {
        self::$currencyCache = [];
        self::$baseCurrencyCache = null;
    }

    /**
     * Clear a specific currency from cache
     */
    public static function forgetCurrency(string $code): void
    {
        unset(self::$currencyCache[$code]);
    }
}
