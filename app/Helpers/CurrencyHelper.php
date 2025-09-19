<?php

declare(strict_types=1);

namespace App\Helpers;

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

        $cacheKey = "exchange_rate_$currency";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($currency): array {
            try {
                $response = Http::timeout(10)->get('https://api.exchangerate-api.com/v4/latest/'.self::BASE_CURRENCY);

                if ($response->failed() || ! isset($response['rates'][$currency])) {
                    throw new Exception("Currency rate not found: $currency");
                }

                $rate = (float) $response['rates'][$currency];
                $symbol = self::getCurrencySymbol($currency);

                return ['rate' => $rate, 'symbol' => $symbol];
            } catch (ConnectionException $e) {
                Log::error('CurrencyAPI connection failed', ['currency' => $currency, 'error' => $e->getMessage()]);
                throw new Exception("Unable to fetch exchange rate for $currency", $e->getCode(), $e);
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

        if ($fromCurrency === $targetCurrency) {
            return $amount;
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
        $rateData = self::getRateAndSymbol($currency);

        return $rateData['symbol'].number_format($amount, 2);

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
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'RWF' => 'FRw',
            'KES' => 'KSh',
            'UGX' => 'USh',
            'TZS' => 'TSh',
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
     * Get user's preferred currency (checks session, user preferences, then defaults)
     */
    public static function getUserCurrency(): string
    {
        // Check session first
        if (session()->has('selected_currency')) {
            return session('selected_currency');
        }

        // Check user preferences if authenticated
        if (auth()->check() && auth()->user()->preferred_currency) {
            return auth()->user()->preferred_currency;
        }

        // Default to USD
        return self::BASE_CURRENCY;
    }
}
