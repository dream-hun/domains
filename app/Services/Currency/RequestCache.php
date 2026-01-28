<?php

declare(strict_types=1);

namespace App\Services\Currency;

use App\Models\Currency;

/**
 * Unified request-level cache for currency data.
 *
 * This consolidates CurrencyRequestCache and ExchangeRateRequestCache
 * to prevent duplicate queries within a single request lifecycle.
 *
 * @internal This class is for internal use by currency services only.
 */
final class RequestCache
{
    /**
     * @var array<string, Currency|null>
     */
    private static array $currencies = [];

    /**
     * @var array<string, float|null>
     */
    private static array $rates = [];

    private static ?Currency $baseCurrency = null;

    // ========================================
    // Currency Model Caching
    // ========================================

    public static function getCurrency(string $code): ?Currency
    {
        return self::$currencies[$code] ?? null;
    }

    public static function setCurrency(string $code, mixed $currency): void
    {
        // Only store valid Currency models or null
        if ($currency instanceof Currency || $currency === null) {
            self::$currencies[$code] = $currency;
        }
    }

    public static function hasCurrency(string $code): bool
    {
        return array_key_exists($code, self::$currencies);
    }

    public static function getBaseCurrency(): ?Currency
    {
        return self::$baseCurrency;
    }

    public static function setBaseCurrency(Currency $currency): void
    {
        self::$baseCurrency = $currency;
    }

    public static function hasBaseCurrency(): bool
    {
        return self::$baseCurrency instanceof Currency;
    }

    // ========================================
    // Exchange Rate Caching
    // ========================================

    public static function getRate(string $cacheKey): ?float
    {
        return self::$rates[$cacheKey] ?? null;
    }

    public static function setRate(string $cacheKey, ?float $rate): void
    {
        self::$rates[$cacheKey] = $rate;
    }

    public static function hasRate(string $cacheKey): bool
    {
        return array_key_exists($cacheKey, self::$rates);
    }

    /**
     * Build a cache key for exchange rates.
     */
    public static function buildRateKey(string $from, string $to): string
    {
        return sprintf('rate:%s:%s', $from, $to);
    }

    // ========================================
    // Cache Management
    // ========================================

    /**
     * Clear all cached data (useful for testing).
     */
    public static function flush(): void
    {
        self::$currencies = [];
        self::$rates = [];
        self::$baseCurrency = null;
    }

    /**
     * Clear only rate cache.
     */
    public static function flushRates(): void
    {
        self::$rates = [];
    }

    /**
     * Clear only currency cache.
     */
    public static function flushCurrencies(): void
    {
        self::$currencies = [];
        self::$baseCurrency = null;
    }
}
