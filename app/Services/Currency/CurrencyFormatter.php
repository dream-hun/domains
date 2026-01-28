<?php

declare(strict_types=1);

namespace App\Services\Currency;

use App\Contracts\Currency\CurrencyFormatterContract;
use App\Models\Currency;
use App\Traits\NormalizesCurrencyCode;
use Cknow\Money\Money;
use Exception;
use NumberFormatter;

/**
 * Handles all currency formatting operations.
 *
 * This consolidates formatting logic that was previously scattered across
 * CurrencyHelper, CurrencyExchangeHelper, and Currency model.
 */
final readonly class CurrencyFormatter implements CurrencyFormatterContract
{
    use NormalizesCurrencyCode;

    /**
     * Currencies that never use decimal places.
     *
     * @var array<int, string>
     */
    private const NO_DECIMAL_CURRENCIES = [
        'RWF', // Rwandan Franc
        'JPY', // Japanese Yen
        'KRW', // South Korean Won
        'VND', // Vietnamese Dong
        'CLP', // Chilean Peso
        'ISK', // Icelandic Króna
        'UGX', // Ugandan Shilling
        'KES', // Kenyan Shilling
        'TZS', // Tanzanian Shilling
    ];

    /**
     * Common currency symbols for performance.
     *
     * @var array<string, string>
     */
    private const COMMON_SYMBOLS = [
        'USD' => '$',
        'RWF' => 'FRW',
        'EUR' => '€',
        'GBP' => '£',
    ];

    /**
     * Format amount with currency symbol.
     */
    public function format(float $amount, string $currencyCode): string
    {
        $currencyCode = $this->normalizeCurrencyCode($currencyCode);
        $symbol = $this->getCurrencySymbol($currencyCode);
        $decimals = $this->getDecimalPlaces($currencyCode, $amount);

        // Round to the appropriate decimal places
        $amount = round($amount, $decimals);

        return $symbol.number_format($amount, $decimals);
    }

    /**
     * Format Money object with proper currency symbols.
     */
    public function formatMoney(Money $money): string
    {
        $currencyCode = $this->normalizeCurrencyCode($money->getCurrency()->getCode());
        $amount = (int) $money->getAmount() / 100;

        return $this->format($amount, $currencyCode);
    }

    /**
     * Get currency symbol for a currency code.
     */
    public function getCurrencySymbol(string $currencyCode): string
    {
        $currencyCode = $this->normalizeCurrencyCode($currencyCode);

        if (isset(self::COMMON_SYMBOLS[$currencyCode])) {
            return self::COMMON_SYMBOLS[$currencyCode];
        }

        // Try to get symbol from database
        $currency = $this->getCurrencyFromDatabase($currencyCode);
        if ($currency instanceof Currency && $currency->symbol !== '') {
            return $currency->symbol;
        }

        // Fallback to NumberFormatter for other currencies
        return $this->getSymbolFromNumberFormatter($currencyCode);
    }

    /**
     * Get the appropriate number of decimal places for a currency.
     */
    public function getDecimalPlaces(string $currencyCode, float $amount): int
    {
        $currencyCode = $this->normalizeCurrencyCode($currencyCode);

        // These currencies NEVER use decimals
        if (in_array($currencyCode, self::NO_DECIMAL_CURRENCIES, true)) {
            return 0;
        }

        // For other currencies, check if the amount has meaningful decimals
        // If the fractional part is effectively zero, don't show decimals
        if (abs($amount - round($amount)) < 0.01) {
            return 0;
        }

        // Default to 2 decimal places
        return 2;
    }

    /**
     * Get currency from database with request-level caching.
     */
    private function getCurrencyFromDatabase(string $currencyCode): ?Currency
    {
        if (RequestCache::hasCurrency($currencyCode)) {
            return RequestCache::getCurrency($currencyCode);
        }

        $currency = Currency::query()->where('code', $currencyCode)->first();
        RequestCache::setCurrency($currencyCode, $currency);

        return $currency;
    }

    /**
     * Get symbol using PHP's NumberFormatter as fallback.
     */
    private function getSymbolFromNumberFormatter(string $currencyCode): string
    {
        try {
            $formatter = new NumberFormatter('en', NumberFormatter::CURRENCY);
            $formatted = $formatter->formatCurrency(0, $currencyCode);
            $symbol = preg_replace('/[\d.,\s]/', '', $formatted);

            if ($symbol === null || in_array($symbol, ['', '0'], true)) {
                return $currencyCode;
            }

            return $symbol;
        } catch (Exception) {
            return $currencyCode;
        }
    }
}
