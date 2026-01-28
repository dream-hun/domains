<?php

declare(strict_types=1);

namespace App\Traits;

use App\Contracts\Currency\CurrencyConverterContract;
use App\Models\Currency;
use Throwable;

/**
 * Provides currency conversion and formatting methods to classes.
 *
 * This trait uses the CurrencyConverterContract for all operations,
 * ensuring consistent currency handling across the application.
 */
trait HasCurrency
{
    /**
     * Get the currency converter instance.
     */
    protected function currencyConverter(): CurrencyConverterContract
    {
        return resolve(CurrencyConverterContract::class);
    }

    /**
     * Get the currency service instance.
     *
     * @deprecated Use currencyConverter() instead.
     */
    protected function currencyService(): CurrencyConverterContract
    {
        return $this->currencyConverter();
    }

    /**
     * Get user's preferred currency.
     */
    protected function getUserCurrency(): Currency
    {
        return $this->currencyConverter()->getUserCurrency();
    }

    /**
     * Convert amount between currencies.
     *
     * @throws Throwable
     */
    protected function convertCurrency(float $amount, string $from, string $to): float
    {
        return $this->currencyConverter()->convert($amount, $from, $to);
    }

    /**
     * Format amount with currency.
     */
    protected function formatCurrency(float $amount, string $currency): string
    {
        return $this->currencyConverter()->format($amount, $currency);
    }

    /**
     * Convert amount from USD (database storage) to user's selected currency.
     *
     * All prices in the database are stored in USD.
     * This method converts them to the user's preferred currency (e.g., RWF for Rwandan users).
     *
     * @throws Throwable
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
     * Convert and format amount from USD to user's selected currency.
     *
     * @throws Throwable
     */
    protected function formatFromUSD(float $amountInUSD): string
    {
        $userCurrency = $this->getUserCurrency();
        $convertedAmount = $this->convertFromUSD($amountInUSD);

        return $this->formatCurrency($convertedAmount, $userCurrency->code);
    }
}
