<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Exceptions\CurrencyExchangeException;
use App\Services\PriceFormatter;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        $fromCurrency = self::normalizeCurrency($fromCurrency);
        $targetCurrency = self::normalizeCurrency($targetCurrency);

        if ($fromCurrency === $targetCurrency) {
            return $amount;
        }

        if (self::isUsdRwfPair($fromCurrency, $targetCurrency)) {
            try {
                $exchangeHelper = resolve(CurrencyExchangeHelper::class);
                $money = $exchangeHelper->convertWithAmount($fromCurrency, $targetCurrency, $amount);

                return (int) $money->getAmount() / 100; // Convert from minor units
            } catch (CurrencyExchangeException $e) {
                Log::warning('CurrencyExchangeHelper failed, falling back', [
                    'from' => $fromCurrency,
                    'to' => $targetCurrency,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($fromCurrency !== self::BASE_CURRENCY) {
            $fromRateData = self::getRateAndSymbol($fromCurrency);
            $amount /= $fromRateData['rate'];
        }

        return self::convertFromUSD($amount, $targetCurrency);
    }

    /**
     * Format amount with currency symbol
     */
    public static function formatMoney(float $amount, string $currency): string
    {
        return resolve(PriceFormatter::class)->format($amount, $currency);
    }

    /**
     * Get currency symbol for a currency code
     */
    public static function getCurrencySymbol(string $currencyCode): string
    {
        return resolve(PriceFormatter::class)->getSymbol($currencyCode);
    }

    /**
     * Get user's preferred currency
     */
    public static function getUserCurrency(): string
    {
        if (session()->has('selected_currency')) {
            return session('selected_currency');
        }

        return self::BASE_CURRENCY;
    }

    /**
     * Normalize a currency code
     */
    public static function normalizeCurrency(string $currency): string
    {
        return resolve(PriceFormatter::class)->normalizeCurrency($currency);
    }

    /**
     * Check if currency pair is USD/RWF
     */
    private static function isUsdRwfPair(string $from, string $to): bool
    {
        $from = self::normalizeCurrency($from);
        $to = self::normalizeCurrency($to);

        $pair = [$from, $to];
        sort($pair);

        return $pair === ['RWF', 'USD'];
    }
}
