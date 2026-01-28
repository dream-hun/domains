<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Contracts\Currency\CurrencyConverterContract;
use App\Contracts\Currency\CurrencyFormatterContract;
use App\Traits\NormalizesCurrencyCode;
use Exception;

/**
 * Static facade for currency operations.
 *
 * This class provides static methods for backward compatibility.
 * All operations delegate to the CurrencyConverterContract service.
 *
 * For new code, prefer injecting CurrencyConverterContract directly.
 */
final class CurrencyHelper
{
    use NormalizesCurrencyCode;

    /**
     * Convert amount from one currency to another.
     *
     * @throws Exception
     */
    public static function convert(float $amount, string $fromCurrency, string $targetCurrency): float
    {
        $fromCurrency = self::normalizeCurrency($fromCurrency);
        $targetCurrency = self::normalizeCurrency($targetCurrency);

        if ($fromCurrency === $targetCurrency) {
            return $amount;
        }

        return self::getConverter()->convert($amount, $fromCurrency, $targetCurrency);
    }

    /**
     * Convert USD amount to target currency.
     *
     * @throws Exception
     */
    public static function convertFromUSD(float $usdAmount, string $targetCurrency): float
    {
        $targetCurrency = self::normalizeCurrency($targetCurrency);

        if ($targetCurrency === 'USD') {
            return $usdAmount;
        }

        return self::getConverter()->convert($usdAmount, 'USD', $targetCurrency);
    }

    /**
     * Format amount with currency symbol.
     */
    public static function formatMoney(float $amount, string $currency): string
    {
        return self::getFormatter()->format($amount, $currency);
    }

    /**
     * Get currency symbol for a currency code.
     */
    public static function getCurrencySymbol(string $currencyCode): string
    {
        return self::getFormatter()->getCurrencySymbol($currencyCode);
    }

    /**
     * Get user's preferred currency code.
     */
    public static function getUserCurrency(): string
    {
        return self::getConverter()->getUserCurrency()->code;
    }

    /**
     * Normalize currency code (handle legacy codes like FRW -> RWF).
     */
    private static function normalizeCurrency(string $currency): string
    {
        $currency = mb_strtoupper(mb_trim($currency));

        return match ($currency) {
            'FRW' => 'RWF',
            default => $currency,
        };
    }

    /**
     * Get the currency converter service.
     */
    private static function getConverter(): CurrencyConverterContract
    {
        return resolve(CurrencyConverterContract::class);
    }

    /**
     * Get the currency formatter service.
     */
    private static function getFormatter(): CurrencyFormatterContract
    {
        return resolve(CurrencyFormatterContract::class);
    }
}
