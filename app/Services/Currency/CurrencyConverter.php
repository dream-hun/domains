<?php

declare(strict_types=1);

namespace App\Services\Currency;

use App\Actions\Currency\UpdateExchangeRatesAction;
use App\Contracts\Currency\CurrencyConverterContract;
use App\Contracts\Currency\CurrencyFormatterContract;
use App\Contracts\Currency\ExchangeRateProviderContract;
use App\Events\ExchangeRatesUpdated;
use App\Exceptions\CurrencyExchangeException;
use App\Models\Currency;
use App\Traits\NormalizesCurrencyCode;
use Cknow\Money\Money;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Main currency service handling conversions, lookups, and exchange rate management.
 *
 * This is the primary service for all currency operations and implements
 * CurrencyConverterContract for dependency injection.
 */
final readonly class CurrencyConverter implements CurrencyConverterContract
{
    use NormalizesCurrencyCode;

    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private UpdateExchangeRatesAction $updateAction,
        private ExchangeRateProviderContract $exchangeRateProvider,
        private CurrencyFormatterContract $formatter
    ) {}

    /**
     * Get user's preferred currency.
     */
    public function getUserCurrency(): Currency
    {
        if (session()->has('selected_currency')) {
            $currency = $this->getCurrency(session('selected_currency'));

            if ($currency instanceof Currency) {
                return $currency;
            }
        }

        return $this->getBaseCurrency();
    }

    /**
     * Get all active currencies.
     *
     * @return Collection<int, Currency>
     */
    public function getActiveCurrencies(): Collection
    {
        // Check request-level cache first
        static $cachedCurrencies = null;
        if ($cachedCurrencies !== null) {
            return $cachedCurrencies;
        }

        $cachedCurrencies = Cache::remember('active_currencies', self::CACHE_TTL, Currency::getActiveCurrencies(...));

        return $cachedCurrencies;
    }

    /**
     * Get currency by code.
     */
    public function getCurrency(string $code): ?Currency
    {
        $code = $this->normalizeCurrencyCode($code);

        if (! $this->isValidCurrencyCode($code)) {
            return null;
        }

        if (RequestCache::hasCurrency($code)) {
            return RequestCache::getCurrency($code);
        }

        $cached = Cache::remember('currency_'.$code, self::CACHE_TTL, fn (): ?Currency => Currency::query()->where('code', $code)->first());

        $currency = $cached instanceof Currency ? $cached : null;

        if ($cached !== null && ! $currency instanceof Currency) {
            Cache::forget('currency_'.$code);
            $currency = Currency::query()->where('code', $code)->first();
        }

        RequestCache::setCurrency($code, $currency);

        return $currency;
    }

    /**
     * Get base currency.
     */
    public function getBaseCurrency(): Currency
    {
        if (RequestCache::hasBaseCurrency()) {
            return RequestCache::getBaseCurrency();
        }

        $currency = Cache::remember('base_currency', self::CACHE_TTL, Currency::getBaseCurrency(...));
        RequestCache::setBaseCurrency($currency);

        return $currency;
    }

    /**
     * Convert amount between currencies.
     *
     * Uses API-based conversion for USD/RWF pairs, database rates for others.
     *
     * @throws Exception
     * @throws Throwable
     */
    public function convert(float $amount, string $fromCurrency, string $targetCurrency): float
    {
        throw_if($amount < 0, Exception::class, 'Amount cannot be negative');

        $fromCurrency = $this->normalizeCurrencyCode($fromCurrency);
        $targetCurrency = $this->normalizeCurrencyCode($targetCurrency);

        if ($fromCurrency === $targetCurrency) {
            return $amount;
        }

        // Use API for USD/RWF pairs
        if ($this->isUsdRwfPair($fromCurrency, $targetCurrency)) {
            try {
                $rate = $this->exchangeRateProvider->getRate($fromCurrency, $targetCurrency);

                return round($amount * $rate, 2);
            } catch (CurrencyExchangeException) {
                // Fall through to database conversion
            }
        }

        // Use database rates
        return $this->convertUsingDatabase($amount, $fromCurrency, $targetCurrency);
    }

    /**
     * Convert amount between currencies and return Money object.
     *
     * @throws Exception
     * @throws Throwable
     */
    public function convertToMoney(float $amount, string $from, string $to): Money
    {
        throw_if($amount < 0, Exception::class, 'Amount cannot be negative');

        $from = $this->normalizeCurrencyCode($from);
        $to = $this->normalizeCurrencyCode($to);

        $convertedAmount = $this->convert($amount, $from, $to);
        $currency = $this->getCurrency($to);

        if ($currency instanceof Currency) {
            return $currency->toMoney($convertedAmount);
        }

        // Fallback for unsupported currencies
        $minorUnits = (int) round($convertedAmount * 100);

        return Money::{$to}($minorUnits);
    }

    /**
     * Format amount with currency symbol.
     */
    public function format(float $amount, string $currencyCode): string
    {
        return $this->formatter->format($amount, $currencyCode);
    }

    /**
     * Format amount as Money object.
     */
    public function formatAsMoney(float $amount, string $currencyCode): string
    {
        $currencyCode = $this->normalizeCurrencyCode($currencyCode);

        try {
            $money = $this->convertToMoney($amount, $currencyCode, $currencyCode);

            return $this->formatter->formatMoney($money);
        } catch (Exception) {
            return $this->format($amount, $currencyCode);
        }
    }

    /**
     * Update exchange rates from API.
     */
    public function updateExchangeRates(): bool
    {
        return $this->updateAction->handle();
    }

    /**
     * Update exchange rates only if stale.
     */
    public function updateExchangeRatesIfStale(): bool
    {
        if (! $this->ratesAreStale()) {
            return true;
        }

        return $this->updateExchangeRates();
    }

    /**
     * Check if exchange rates need updating.
     */
    public function ratesAreStale(): bool
    {
        $stalenessHours = config('services.exchange_rate.staleness_hours', 24);

        $lastUpdate = Cache::remember('last_rate_update', self::CACHE_TTL, fn () => Currency::query()
            ->where('is_base', false)
            ->whereNotNull('rate_updated_at')
            ->max('rate_updated_at'));

        if (! $lastUpdate) {
            return true;
        }

        return now()->diffInHours($lastUpdate) > $stalenessHours;
    }

    /**
     * Clear all user carts (backward compatibility).
     */
    public function clearAllCarts(): void
    {
        event(new ExchangeRatesUpdated(0, []));
    }

    /**
     * Get current exchange rates with metadata.
     *
     * @return SupportCollection<int, array<string, mixed>>
     */
    public function getCurrentRates(): SupportCollection
    {
        $stalenessHours = config('services.exchange_rate.staleness_hours', 24);

        return Cache::remember('current_rates', self::CACHE_TTL / 4, fn () => Currency::query()
            ->where('is_active', true)
            ->orderBy('is_base', 'desc')
            ->orderBy('code')
            ->get()
            ->map(function (Currency $currency) use ($stalenessHours): array {
                $hoursSinceUpdate = $currency->rate_updated_at
                    ? now()->diffInHours($currency->rate_updated_at)
                    : null;

                return [
                    'code' => $currency->code,
                    'name' => $currency->name,
                    'symbol' => $currency->symbol,
                    'is_base' => $currency->is_base,
                    'exchange_rate' => $currency->exchange_rate,
                    'rate_updated_at' => $currency->rate_updated_at,
                    'hours_since_update' => $hoursSinceUpdate,
                    'is_stale' => $hoursSinceUpdate === null || $hoursSinceUpdate > $stalenessHours,
                ];
            }));
    }

    /**
     * Check if currency pair is USD/RWF.
     */
    private function isUsdRwfPair(string $from, string $to): bool
    {
        $pair = [$from, $to];
        sort($pair);

        return $pair === ['RWF', 'USD'];
    }

    /**
     * Convert using database exchange rates.
     *
     * @throws Exception
     * @throws Throwable
     */
    private function convertUsingDatabase(float $amount, string $from, string $to): float
    {
        $fromCurrencyModel = $this->getCurrency($from);
        $targetCurrencyModel = $this->getCurrency($to);

        throw_unless($fromCurrencyModel instanceof Currency, Exception::class, 'Currency not found: '.$from);
        throw_unless($targetCurrencyModel instanceof Currency, Exception::class, 'Currency not found: '.$to);
        throw_if(! $fromCurrencyModel->is_active || ! $targetCurrencyModel->is_active, Exception::class, 'One or both currencies are inactive');

        return $fromCurrencyModel->convertTo($amount, $targetCurrencyModel);
    }
}
