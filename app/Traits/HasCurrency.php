<?php

declare(strict_types=1);

namespace App\Traits;

use App\Helpers\CurrencyHelper;
use App\Models\Currency;

trait HasCurrency
{
    protected function getUserCurrency(): Currency
    {
        $code = CurrencyHelper::getUserCurrency();
        $currency = Currency::getActiveCurrencies()->firstWhere('code', $code);

        if ($currency instanceof Currency) {
            return $currency;
        }

        return Currency::getBaseCurrency();
    }

    protected function convertCurrency(float $amount, string $fromCurrency, string $toCurrency): float
    {
        return CurrencyHelper::convert($amount, $fromCurrency, $toCurrency);
    }

    protected function formatCurrency(float $amount, string $currencyCode): string
    {
        return CurrencyHelper::formatMoney($amount, $currencyCode);
    }
}
