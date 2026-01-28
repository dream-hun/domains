<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Contracts\Currency\ExchangeRateProviderContract;
use App\Exceptions\CurrencyExchangeException;
use App\Services\Currency\RequestCache;
use App\Services\ExchangeRateClient;
use App\Traits\NormalizesCurrencyCode;
use Cknow\Money\Money;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Helper for USD/RWF currency exchange operations.
 *
 * @deprecated This helper is maintained for backward compatibility.
 *             For new code, inject ExchangeRateProviderContract for rate operations
 *             and CurrencyConverterContract for conversion operations.
 */
final readonly class CurrencyExchangeHelper implements ExchangeRateProviderContract
{
    use NormalizesCurrencyCode;

    private const SUPPORTED_CURRENCIES = ['USD', 'RWF'];

    public function __construct(
        private ExchangeRateClient $client
    ) {}

    /**
     * Get the current exchange rate between two currencies.
     *
     * @throws CurrencyExchangeException
     */
    public function getRate(string $from, string $to): float
    {
        return $this->getExchangeRate($from, $to);
    }

    /**
     * Get all exchange rates for a base currency.
     * Note: This helper only supports USD/RWF pairs.
     *
     * @return array<string, float>|null
     */
    public function getRates(string $baseCurrency): ?array
    {
        $baseCurrency = $this->normalizeCurrencyCode($baseCurrency);

        if (! in_array($baseCurrency, self::SUPPORTED_CURRENCIES, true)) {
            return null;
        }

        return $this->client->fetchRates($baseCurrency);
    }

    /**
     * Get the current exchange rate between two currencies.
     *
     * @deprecated Use getRate() instead. This method is maintained for backward compatibility.
     *
     * @throws CurrencyExchangeException
     */
    public function getExchangeRate(string $from, string $to): float
    {
        $from = $this->normalizeCurrencyCode($from);
        $to = $this->normalizeCurrencyCode($to);

        $this->validateCurrencies($from, $to);

        if ($from === $to) {
            return 1.0;
        }

        $cacheKey = $this->getCacheKey($from, $to);

        // Check request-level cache first to avoid duplicate queries
        if (RequestCache::hasRate($cacheKey)) {
            $rate = RequestCache::getRate($cacheKey);
            if ($rate !== null) {
                return $rate;
            }
        }

        if (config('currency_exchange.cache.enabled', true)) {
            $rate = Cache::get($cacheKey);
            if ($rate !== null) {
                $rate = (float) $rate;
                // Store in request-level cache
                RequestCache::setRate($cacheKey, $rate);

                return $rate;
            }
        }

        try {
            $response = $this->client->fetchPairConversion($from, $to);

            if ($response === null) {
                return $this->useFallbackRate($from, $to);
            }

            if (isset($response['error-type'])) {
                $this->handleApiError($response['error-type']);
            }

            if (! isset($response['conversion_rate'])) {
                throw CurrencyExchangeException::unexpectedResponse('Missing conversion_rate in response');
            }

            $rate = (float) $response['conversion_rate'];

            // Cache the rate and metadata
            $this->cacheRate($from, $to, $rate, $response);

            // Store in request-level cache
            RequestCache::setRate($cacheKey, $rate);

            return $rate;

        } catch (CurrencyExchangeException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Currency exchange error', [
                'from' => $from,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return $this->useFallbackRate($from, $to);
        }
    }

    /**
     * Convert USD to RWF
     */
    public function convertUsdToRwf(float $amount): Money
    {
        $this->validateAmount($amount);

        $rate = $this->getRate('USD', 'RWF');
        $convertedAmount = $amount * $rate;

        // Money expects amount in smallest unit (minor units)
        // For RWF, 1 RWF = 1 minor unit (no cents)
        return Money::RWF((int) round($convertedAmount * 100));
    }

    /**
     * Convert RWF to USD
     */
    public function convertRwfToUsd(float $amount): Money
    {
        $this->validateAmount($amount);

        $rate = $this->getRate('RWF', 'USD');
        $convertedAmount = $amount * $rate;

        // Money expects amount in smallest unit (cents for USD)
        return Money::USD((int) round($convertedAmount * 100));
    }

    /**
     * Generic conversion method with amount
     *
     * @throws CurrencyExchangeException
     */
    public function convertWithAmount(string $from, string $to, float $amount): Money
    {
        $from = $this->normalizeCurrencyCode($from);
        $to = $this->normalizeCurrencyCode($to);

        $this->validateCurrencies($from, $to);
        $this->validateAmount($amount);

        if ($from === $to) {
            return $this->createMoney($amount, $to);
        }

        $rate = $this->getRate($from, $to);
        $convertedAmount = $amount * $rate;

        return $this->createMoney($convertedAmount, $to);
    }

    /**
     * Format Money object with proper currency symbols
     */
    public function formatMoney(Money $money): string
    {
        $currency = $this->normalizeCurrencyCode($money->getCurrency()->getCode());
        $amount = (int) $money->getAmount() / 100;

        $config = config('currency_exchange.supported_currencies', []);

        $symbol = $config[$currency]['symbol'] ?? $currency;

        if ($currency === 'USD') {
            // Show decimals only if there are cents
            $decimals = (abs($amount - round($amount)) < 0.01) ? 0 : 2;
            // Round first to ensure consistency
            $amount = round($amount, $decimals);

            return sprintf('%s%s', $symbol, number_format($amount, $decimals));
        }

        // RWF typically doesn't use decimal places
        if ($currency === 'RWF') {
            // Always round to whole number for RWF
            $amount = round($amount, 0);

            return sprintf('%s%s', $symbol, number_format($amount, 0));
        }

        // For other currencies, check if decimals are needed
        $decimals = (abs($amount - round($amount)) < 0.01) ? 0 : 2;
        // Round first to ensure consistency
        $amount = round($amount, $decimals);

        return sprintf('%s%s', $symbol, number_format($amount, $decimals));
    }

    /**
     * Get rate metadata including last updated time
     *
     * @return array{from: string, to: string, last_updated: string|null, next_update: string|null, is_cached: bool, is_fallback: bool}
     */
    public function getRateMetadata(string $from, string $to): array
    {
        $from = $this->normalizeCurrencyCode($from);
        $to = $this->normalizeCurrencyCode($to);

        $this->validateCurrencies($from, $to);

        $metadataKey = $this->getMetadataKey($from, $to);
        $metadata = Cache::get($metadataKey);

        if ($metadata === null) {
            return [
                'from' => $from,
                'to' => $to,
                'last_updated' => null,
                'next_update' => null,
                'is_cached' => false,
                'is_fallback' => false,
            ];
        }

        return $metadata;
    }

    /**
     * Clear cached rates
     */
    public function clearCache(?string $from = null, ?string $to = null): void
    {
        if ($from !== null && $to !== null) {
            $from = $this->normalizeCurrencyCode($from);
            $to = $this->normalizeCurrencyCode($to);

            Cache::forget($this->getCacheKey($from, $to));
            Cache::forget($this->getMetadataKey($from, $to));

            return;
        }

        // Clear all currency exchange caches
        $prefix = config('currency_exchange.cache.prefix', 'exchange_rate');
        foreach (self::SUPPORTED_CURRENCIES as $fromCurrency) {
            foreach (self::SUPPORTED_CURRENCIES as $toCurrency) {
                if ($fromCurrency !== $toCurrency) {
                    Cache::forget(sprintf('%s:%s:%s', $prefix, $fromCurrency, $toCurrency));
                    Cache::forget(sprintf('%s_metadata:%s:%s', $prefix, $fromCurrency, $toCurrency));
                }
            }
        }
    }

    /**
     * Validate currency codes
     *
     * @throws CurrencyExchangeException
     */
    private function validateCurrencies(string ...$currencies): void
    {
        foreach ($currencies as $currency) {
            $normalized = $this->normalizeCurrencyCode($currency);

            if (! in_array($normalized, self::SUPPORTED_CURRENCIES, true)) {
                throw CurrencyExchangeException::unsupportedCurrency($currency);
            }
        }
    }

    /**
     * Validate amount
     *
     * @throws CurrencyExchangeException
     */
    private function validateAmount(float $amount): void
    {
        if ($amount < 0) {
            throw CurrencyExchangeException::invalidAmount($amount);
        }
    }

    /**
     * Handle API errors
     *
     * @throws CurrencyExchangeException
     */
    private function handleApiError(string $errorType): void
    {
        match ($errorType) {
            'unsupported-code' => throw CurrencyExchangeException::apiError($errorType, 'Currency code not supported by API'),
            'invalid-key' => throw CurrencyExchangeException::invalidApiKey(),
            'quota-reached' => throw CurrencyExchangeException::quotaReached(),
            'malformed-request' => throw CurrencyExchangeException::malformedRequest(),
            'inactive-account' => throw CurrencyExchangeException::inactiveAccount(),
            default => throw CurrencyExchangeException::apiError($errorType),
        };
    }

    /**
     * Use fallback rate when API fails
     */
    private function useFallbackRate(string $from, string $to): float
    {
        $from = $this->normalizeCurrencyCode($from);
        $to = $this->normalizeCurrencyCode($to);

        if (! config('currency_exchange.error_handling.use_fallback_on_error', true)) {
            throw CurrencyExchangeException::apiError('fallback_disabled', 'API failed and fallback is disabled');
        }

        Log::warning('Using fallback exchange rate', [
            'from' => $from,
            'to' => $to,
        ]);

        $fallbackKey = sprintf('%s_TO_%s', $from, $to);
        $rate = config('currency_exchange.fallback_rates.'.$fallbackKey);

        if ($rate === null) {
            throw CurrencyExchangeException::unexpectedResponse(sprintf('No fallback rate configured for %s to %s', $from, $to));
        }

        $fallbackRate = (float) $rate;

        // Cache the fallback rate
        $this->cacheRate($from, $to, $fallbackRate, [], true);

        // Store in request-level cache
        $cacheKey = $this->getCacheKey($from, $to);
        RequestCache::setRate($cacheKey, $fallbackRate);

        return $fallbackRate;
    }

    /**
     * Cache the exchange rate and metadata.
     *
     * @param  array<string, mixed>  $response
     */
    private function cacheRate(string $from, string $to, float $rate, array $response = [], bool $isFallback = false): void
    {
        $from = $this->normalizeCurrencyCode($from);
        $to = $this->normalizeCurrencyCode($to);

        if (! config('currency_exchange.cache.enabled', true)) {
            return;
        }

        $ttl = config('currency_exchange.cache.ttl', 3600);
        $cacheKey = $this->getCacheKey($from, $to);
        $metadataKey = $this->getMetadataKey($from, $to);

        Cache::put($cacheKey, $rate, $ttl);

        $metadata = [
            'from' => $from,
            'to' => $to,
            'rate' => $rate,
            'last_updated' => $response['time_last_update_utc'] ?? now()->toIso8601String(),
            'next_update' => $response['time_next_update_utc'] ?? now()->addSeconds($ttl)->toIso8601String(),
            'cached_at' => now()->toIso8601String(),
            'is_cached' => true,
            'is_fallback' => $isFallback,
        ];

        Cache::put($metadataKey, $metadata, $ttl);
    }

    /**
     * Get cache key for rate
     */
    private function getCacheKey(string $from, string $to): string
    {
        $from = $this->normalizeCurrencyCode($from);
        $to = $this->normalizeCurrencyCode($to);

        $prefix = config('currency_exchange.cache.prefix', 'exchange_rate');

        return sprintf('%s:%s:%s', $prefix, $from, $to);
    }

    /**
     * Get cache key for metadata
     */
    private function getMetadataKey(string $from, string $to): string
    {
        $from = $this->normalizeCurrencyCode($from);
        $to = $this->normalizeCurrencyCode($to);

        $prefix = config('currency_exchange.cache.prefix', 'exchange_rate');

        return sprintf('%s_metadata:%s:%s', $prefix, $from, $to);
    }

    /**
     * Create a Money object from amount and currency
     */
    private function createMoney(float $amount, string $currency): Money
    {
        $currency = $this->normalizeCurrencyCode($currency);

        // Convert to minor units (cents/smallest unit)
        $minorUnits = (int) round($amount * 100);

        return match ($currency) {
            'USD' => Money::USD($minorUnits),
            'RWF' => Money::RWF($minorUnits),
            default => throw CurrencyExchangeException::unsupportedCurrency($currency),
        };
    }
}
