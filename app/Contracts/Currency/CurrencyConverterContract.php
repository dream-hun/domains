<?php

declare(strict_types=1);

namespace App\Contracts\Currency;

use App\Models\Currency;
use Cknow\Money\Money;

interface CurrencyConverterContract
{
    /**
     * Convert amount between currencies
     */
    public function convert(float $amount, string $fromCurrency, string $targetCurrency): float;

    /**
     * Convert amount between currencies and return Money object
     */
    public function convertToMoney(float $amount, string $from, string $to): Money;

    /**
     * Format amount with currency symbol
     */
    public function format(float $amount, string $currencyCode): string;

    /**
     * Format amount as Money object
     */
    public function formatAsMoney(float $amount, string $currencyCode): string;

    /**
     * Get currency by code
     */
    public function getCurrency(string $code): ?Currency;

    /**
     * Get base currency
     */
    public function getBaseCurrency(): Currency;

    /**
     * Get user's preferred currency
     */
    public function getUserCurrency(): Currency;
}
