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

    /**
     * Convert amount from USD (database storage) to user's selected currency
     * All prices in the database are stored in USD.
     * This method converts them to the user's preferred currency (e.g., FRW for Rwandan users)
     */
    protected function convertFromUSD(float $amountInUSD): float
    {
        $userCurrency = $this->getUserCurrency();

        if ($userCurrency->code === 'USD') {
            return $amountInUSD;
        }

        return $this->convertCurrency($amountInUSD, 'USD', $userCurrency->code);
    }

    /**
     * Convert and format amount from USD to user's selected currency
     */
    protected function formatFromUSD(float $amountInUSD): string
    {
        $userCurrency = $this->getUserCurrency();
        $convertedAmount = $this->convertFromUSD($amountInUSD);

        return $this->formatCurrency($convertedAmount, $userCurrency->code);
    }
}
