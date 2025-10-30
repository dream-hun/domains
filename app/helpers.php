<?php

declare(strict_types=1);

use App\Helpers\CurrencyExchangeHelper;
use Cknow\Money\Money;

if (! function_exists('currency_convert')) {
    /**
     * Convert amount between currencies and return Money object
     *
     * @throws App\Exceptions\CurrencyExchangeException
     */
    function currency_convert(float $amount, string $from, string $to): Money
    {
        $helper = app(CurrencyExchangeHelper::class);

        return $helper->convertWithAmount($from, $to, $amount);
    }
}

if (! function_exists('format_money')) {
    /**
     * Format Money object with proper currency symbols
     */
    function format_money(Money $money): string
    {
        $helper = app(CurrencyExchangeHelper::class);

        return $helper->formatMoney($money);
    }
}

if (! function_exists('usd_to_frw')) {
    /**
     * Convert USD to FRW and return Money object
     *
     * @throws App\Exceptions\CurrencyExchangeException
     */
    function usd_to_frw(float $amount): Money
    {
        $helper = app(CurrencyExchangeHelper::class);

        return $helper->convertUsdToFrw($amount);
    }
}

if (! function_exists('frw_to_usd')) {
    /**
     * Convert FRW to USD and return Money object
     *
     * @throws App\Exceptions\CurrencyExchangeException
     */
    function frw_to_usd(float $amount): Money
    {
        $helper = app(CurrencyExchangeHelper::class);

        return $helper->convertFrwToUsd($amount);
    }
}
