<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Exceptions\CurrencyExchangeException;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use NumberFormatter;

final class CurrencyHelper
{
    private const CACHE_TTL = 3600; // 1 hour

    private const BASE_CURRENCY = 'USD';

    /**
     * Get exchange rate and symbol for a currency
     *
     * @throws Exception
     */
    public static function getRateAndSymbol(string $currency): array
    {
        $currency = mb_strtoupper($currency);

        // Return 1:1 for USD since it's our base currency
        if ($currency === self::BASE_CURRENCY) {
            return [
                'rate' => 1.0,
                'symbol' => self::getCurrencySymbol($currency),
            ];
        }

        $cacheKey = 'exchange_rate_'.$currency;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($currency): array {
            try {
                $response = Http::timeout(10)->get('https://api.exchangerate-api.com/v4/latest/'.self::BASE_CURRENCY);

                throw_if($response->failed() || ! isset($response['rates'][$currency]), Exception::class, 'Currency rate not found: '.$currency);

                $rate = (float) $response['rates'][$currency];
                $symbol = self::getCurrencySymbol($currency);

                return ['rate' => $rate, 'symbol' => $symbol];
            } catch (ConnectionException $connectionException) {
                Log::error('CurrencyAPI connection failed', ['currency' => $currency, 'error' => $connectionException->getMessage()]);
                throw new Exception('Unable to fetch exchange rate for '.$currency, $connectionException->getCode(), $connectionException);
            }
        });
    }

    /**
     * Convert USD amount to target currency
     *
     * @throws Exception
     */
    public static function convertFromUSD(float $usdAmount, string $targetCurrency): float
    {
        $targetCurrency = mb_strtoupper($targetCurrency);

        if ($targetCurrency === self::BASE_CURRENCY) {
            return $usdAmount;
        }

        $rateData = self::getRateAndSymbol($targetCurrency);

        return round($usdAmount * $rateData['rate'], 2);
    }

    /**
     * Convert any amount from one currency to another
     *
     * @throws Exception
     */
    public static function convert(float $amount, string $fromCurrency, string $targetCurrency): float
    {
        $fromCurrency = mb_strtoupper($fromCurrency);
        $targetCurrency = mb_strtoupper($targetCurrency);

        if ($fromCurrency === 'FRW') {
            $fromCurrency = 'RWF';
        }

        if ($targetCurrency === 'FRW') {
            $targetCurrency = 'RWF';
        }

        if ($fromCurrency === $targetCurrency) {
            return $amount;
        }

        // Use CurrencyExchangeHelper for USD/RWF conversions
        if (self::isUsdRwfPair($fromCurrency, $targetCurrency)) {
            try {
                $exchangeHelper = app(CurrencyExchangeHelper::class);
                $money = $exchangeHelper->convertWithAmount($fromCurrency, $targetCurrency, $amount);

                return $money->getAmount() / 100; // Convert from minor units
            } catch (CurrencyExchangeException $e) {
                Log::warning('CurrencyExchangeHelper failed, falling back to old method', [
                    'from' => $fromCurrency,
                    'to' => $targetCurrency,
                    'error' => $e->getMessage(),
                ]);
                // Fall through to old method
            }
        }

        // Convert to USD first if not already USD
        if ($fromCurrency !== self::BASE_CURRENCY) {
            $fromRateData = self::getRateAndSymbol($fromCurrency);
            $amount /= $fromRateData['rate']; // Convert to USD
        }

        // Convert from USD to target currency
        return self::convertFromUSD($amount, $targetCurrency);
    }

    /**
     * Format amount with currency symbol
     *
     * @throws Exception
     */
    public static function formatMoney(float $amount, string $currency): string
    {
        $currency = mb_strtoupper($currency);

        if ($currency === 'FRW') {
            $currency = 'RWF';
        }

        // Use CurrencyExchangeHelper formatting for USD/RWF
        if (in_array($currency, ['USD', 'RWF'], true)) {
            try {
                $exchangeHelper = app(CurrencyExchangeHelper::class);
                $money = $exchangeHelper->convertWithAmount($currency, $currency, $amount);

                return $exchangeHelper->formatMoney($money);
            } catch (CurrencyExchangeException $e) {
                Log::warning('CurrencyExchangeHelper formatting failed, falling back', [
                    'currency' => $currency,
                    'error' => $e->getMessage(),
                ]);
                // Fall through to old method
            }
        }

        $rateData = self::getRateAndSymbol($currency);

        // Determine decimal places based on currency
        $decimals = self::getDecimalPlaces($currency, $amount);

        // Round to the appropriate decimal places to ensure consistency
        $amount = round($amount, $decimals);

        return $rateData['symbol'].number_format($amount, $decimals);
    }

    /**
     * Get currency symbol for a currency code
     */
    public static function getCurrencySymbol(string $currencyCode): string
    {
        $currencyCode = mb_strtoupper($currencyCode);

        // Common currency symbols for better performance
        $commonSymbols = [
            'USD' => '$',
            'FRW' => 'FRW',
            'RWF' => 'FRW',

        ];

        if (isset($commonSymbols[$currencyCode])) {
            return $commonSymbols[$currencyCode];
        }

        // Fallback to NumberFormatter for other currencies
        try {
            $formatter = new NumberFormatter('en', NumberFormatter::CURRENCY);
            $formatted = $formatter->formatCurrency(0, $currencyCode);

            return ! in_array(preg_replace('/[\d.,\s]/', '', $formatted), ['', '0'], true) && preg_replace('/[\d.,\s]/', '', $formatted) !== [] ? preg_replace('/[\d.,\s]/', '', $formatted) : $currencyCode;
        } catch (Exception) {
            return $currencyCode;
        }
    }

    /**
     * Get user's preferred currency (checks session, then defaults)
     */
    public static function getUserCurrency(): string
    {
        // Check session first
        if (session()->has('selected_currency')) {
            return session('selected_currency');
        }

        // Default to USD
        return self::BASE_CURRENCY;
    }

    /**
     * Check if currency pair is USD/RWF
     */
    private static function isUsdRwfPair(string $from, string $to): bool
    {
        if ($from === 'FRW') {
            $from = 'RWF';
        }

        if ($to === 'FRW') {
            $to = 'RWF';
        }

        $pair = [$from, $to];
        sort($pair);

        return $pair === ['RWF', 'USD'];
    }

    /**
     * Get the appropriate number of decimal places for a currency
     */
    private static function getDecimalPlaces(string $currency, float $amount): int
    {
        // Currencies that don't use decimal places
        $noDecimalCurrencies = [
            'RWF', // Rwandan Franc
            'JPY', // Japanese Yen
            'KRW', // South Korean Won
        ];

        if ($currency === 'FRW') {
            $currency = 'RWF';
        }

        if (in_array($currency, $noDecimalCurrencies, true)) {
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
}
