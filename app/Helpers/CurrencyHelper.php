<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Currency;
use App\Services\PriceFormatter;

/**
 * Currency helper using stored prices and session. No external conversion.
 */
final class CurrencyHelper
{
    /**
     * User's selected currency code (from session).
     */
    public static function getUserCurrency(): string
    {
        $code = (string) session('selected_currency', 'USD');

        return mb_strtoupper($code) === 'FRW' ? 'RWF' : $code;
    }

    /**
     * Format amount for display in the given currency.
     */
    public static function formatMoney(float $amount, string $currencyCode): string
    {
        $code = mb_strtoupper($currencyCode);
        if ($code === 'FRW') {
            $code = 'RWF';
        }

        $currency = Currency::getActiveCurrencies()->firstWhere('code', $code);

        if ($currency instanceof Currency) {
            return $currency->format($amount);
        }

        return resolve(PriceFormatter::class)->format($amount, $code);
    }

    /**
     * No conversion: returns amount as-is. Use stored prices (e.g. Tld::getPriceForCurrency)
     * when multi-currency pricing exists.
     */
    public static function convert(float $amount): float
    {
        return $amount;
    }
}
