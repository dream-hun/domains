<?php

declare(strict_types=1);

namespace App\Contracts\Currency;

use Cknow\Money\Money;

interface CurrencyFormatterContract
{
    /**
     * Format amount with currency symbol
     */
    public function format(float $amount, string $currencyCode): string;

    /**
     * Format Money object with proper currency symbols
     */
    public function formatMoney(Money $money): string;

    /**
     * Get currency symbol for a currency code
     */
    public function getCurrencySymbol(string $currencyCode): string;

    /**
     * Get the appropriate number of decimal places for a currency
     */
    public function getDecimalPlaces(string $currencyCode, float $amount): int;
}
