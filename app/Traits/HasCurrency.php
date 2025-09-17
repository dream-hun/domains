<?php

declare(strict_types=1);

namespace App\Traits;

use App\Services\CurrencyService;

trait HasCurrency
{
    /**
     * Get the currency service instance
     */
    protected function currencyService(): CurrencyService
    {
        return app(CurrencyService::class);
    }

    /**
     * Get user's preferred currency
     */
    protected function getUserCurrency(): \App\Models\Currency
    {
        return $this->currencyService()->getUserCurrency();
    }

    /**
     * Convert amount between currencies
     */
    protected function convertCurrency(float $amount, string $from, string $to): float
    {
        return $this->currencyService()->convert($amount, $from, $to);
    }

    /**
     * Format amount with currency
     */
    protected function formatCurrency(float $amount, string $currency): string
    {
        return $this->currencyService()->format($amount, $currency);
    }
}
