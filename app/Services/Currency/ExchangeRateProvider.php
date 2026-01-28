<?php

declare(strict_types=1);

namespace App\Services\Currency;

use App\Contracts\Currency\ExchangeRateProviderContract;
use App\Exceptions\CurrencyExchangeException;
use App\Services\ExchangeRateClient;
use App\Traits\NormalizesCurrencyCode;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Provides exchange rates from external API with caching.
 *
 * This is the primary implementation of ExchangeRateProviderContract,
 * handling USD/RWF currency pair conversions via ExchangeRate-API.
 */
final readonly class ExchangeRateProvider implements ExchangeRateProviderContract
{
    use NormalizesCurrencyCode;

    /**
     * @var array<int, string>
     */
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
        $from = $this->normalizeCurrencyCode($from);
        $to = $this->normalizeCurrencyCode($to);

        $this->validateCurrencies($from, $to);

        if ($from === $to) {
            return 1.0;
        }

        $cacheKey = $this->getCacheKey($from, $to);

        // Check request-level cache first
        if (RequestCache::hasRate($cacheKey)) {
            $rate = RequestCache::getRate($cacheKey);
            if ($rate !== null) {
                return $rate;
            }
        }

        // Check persistent cache
        if ($this->isCacheEnabled()) {
            $rate = Cache::get($cacheKey);
            if ($rate !== null) {
                $rate = (float) $rate;
                RequestCache::setRate($cacheKey, $rate);

                return $rate;
            }
        }

        // Fetch from API
        return $this->fetchAndCacheRate($from, $to, $cacheKey);
    }

    /**
     * Get all exchange rates for a base currency.
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
     * Get rate metadata including last updated time.
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
     * Clear cached rates.
     */
    public function clearCache(?string $from = null, ?string $to = null): void
    {
        if ($from !== null && $to !== null) {
            $from = $this->normalizeCurrencyCode($from);
            $to = $this->normalizeCurrencyCode($to);

            Cache::forget($this->getCacheKey($from, $to));
            Cache::forget($this->getMetadataKey($from, $to));
            RequestCache::flushRates();

            return;
        }

        // Clear all currency exchange caches
        $prefix = $this->getCachePrefix();
        foreach (self::SUPPORTED_CURRENCIES as $fromCurrency) {
            foreach (self::SUPPORTED_CURRENCIES as $toCurrency) {
                if ($fromCurrency !== $toCurrency) {
                    Cache::forget(sprintf('%s:%s:%s', $prefix, $fromCurrency, $toCurrency));
                    Cache::forget(sprintf('%s_metadata:%s:%s', $prefix, $fromCurrency, $toCurrency));
                }
            }
        }

        RequestCache::flushRates();
    }

    /**
     * Check if a currency pair is supported.
     */
    public function isSupported(string $currency): bool
    {
        return in_array($this->normalizeCurrencyCode($currency), self::SUPPORTED_CURRENCIES, true);
    }

    /**
     * Get supported currencies.
     *
     * @return array<int, string>
     */
    public function getSupportedCurrencies(): array
    {
        return self::SUPPORTED_CURRENCIES;
    }

    /**
     * Fetch rate from API and cache it.
     *
     * @throws CurrencyExchangeException
     */
    private function fetchAndCacheRate(string $from, string $to, string $cacheKey): float
    {
        try {
            $response = $this->client->fetchPairConversion($from, $to);

            if ($response === null) {
                return $this->useFallbackRate($from, $to, $cacheKey);
            }

            if (isset($response['error-type'])) {
                $this->handleApiError($response['error-type']);
            }

            if (! isset($response['conversion_rate'])) {
                throw CurrencyExchangeException::unexpectedResponse('Missing conversion_rate in response');
            }

            $rate = (float) $response['conversion_rate'];

            $this->cacheRate($from, $to, $rate, $response);
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

            return $this->useFallbackRate($from, $to, $cacheKey);
        }
    }

    /**
     * Use fallback rate when API fails.
     *
     * @throws CurrencyExchangeException
     */
    private function useFallbackRate(string $from, string $to, string $cacheKey): float
    {
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
            throw CurrencyExchangeException::unexpectedResponse(
                sprintf('No fallback rate configured for %s to %s', $from, $to)
            );
        }

        $fallbackRate = (float) $rate;

        $this->cacheRate($from, $to, $fallbackRate, [], true);
        RequestCache::setRate($cacheKey, $fallbackRate);

        return $fallbackRate;
    }

    /**
     * Validate currency codes.
     *
     * @throws CurrencyExchangeException
     */
    private function validateCurrencies(string ...$currencies): void
    {
        foreach ($currencies as $currency) {
            if (! in_array($currency, self::SUPPORTED_CURRENCIES, true)) {
                throw CurrencyExchangeException::unsupportedCurrency($currency);
            }
        }
    }

    /**
     * Handle API errors.
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
     * Cache the exchange rate and metadata.
     *
     * @param  array<string, mixed>  $response
     */
    private function cacheRate(string $from, string $to, float $rate, array $response = [], bool $isFallback = false): void
    {
        if (! $this->isCacheEnabled()) {
            return;
        }

        $ttl = $this->getCacheTtl();
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

    private function getCacheKey(string $from, string $to): string
    {
        return sprintf('%s:%s:%s', $this->getCachePrefix(), $from, $to);
    }

    private function getMetadataKey(string $from, string $to): string
    {
        return sprintf('%s_metadata:%s:%s', $this->getCachePrefix(), $from, $to);
    }

    private function getCachePrefix(): string
    {
        return config('currency_exchange.cache.prefix', 'exchange_rate');
    }

    private function getCacheTtl(): int
    {
        return (int) config('currency_exchange.cache.ttl', 3600);
    }

    private function isCacheEnabled(): bool
    {
        return (bool) config('currency_exchange.cache.enabled', true);
    }
}
