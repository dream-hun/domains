<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Currency\UpdateExchangeRatesAction;
use App\Events\ExchangeRatesUpdated;
use App\Exceptions\CurrencyExchangeException;
use App\Helpers\CurrencyExchangeHelper;
use App\Models\Currency;
use Carbon\Carbon;
use Cknow\Money\Money;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;
use Throwable;

final readonly class CurrencyService
{
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private UpdateExchangeRatesAction $updateAction,
        private CurrencyExchangeHelper $exchangeHelper
    ) {}

    /**
     * Get user's preferred currency
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
     * Get all active currencies
     */
    public function getActiveCurrencies(): Collection
    {
        // Fetch from persistent cache
        return Cache::remember('active_currencies', self::CACHE_TTL, Currency::getActiveCurrencies(...));
    }

    /**
     * Get currency by code
     */
    public function getCurrency(string $code): ?Currency
    {
        $code = $this->normalizeCurrencyCode($code);

        if (! $this->isValidCurrencyCode($code)) {
            return null;
        }

        // Check request-level cache first to avoid duplicate queries
        if (CurrencyRequestCache::hasCurrency($code)) {
            return CurrencyRequestCache::getCurrency($code);
        }

        // Fetch from persistent cache and store in request-level cache
        $currency = Cache::remember('currency_'.$code, self::CACHE_TTL, fn (): ?Currency => Currency::query()->where('code', $code)->first());
        CurrencyRequestCache::setCurrency($code, $currency);

        return $currency;
    }

    /**
     * Get base currency
     */
    public function getBaseCurrency(): Currency
    {
        // Check request-level cache first to avoid duplicate queries
        if (CurrencyRequestCache::hasBaseCurrency()) {
            return CurrencyRequestCache::getBaseCurrency();
        }

        // Fetch from persistent cache and store in request-level cache
        $currency = Cache::remember('base_currency', self::CACHE_TTL, Currency::getBaseCurrency(...));
        CurrencyRequestCache::setBaseCurrency($currency);

        return $currency;
    }

    /**
     * Convert amount between currencies
     * Uses API-based conversion for USD/RWF pairs, database for others
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

        if ($this->isUsdRwfPair($fromCurrency, $targetCurrency)) {
            try {
                $money = $this->exchangeHelper->convertWithAmount($fromCurrency, $targetCurrency, $amount);

                return (int) $money->getAmount() / 100;
            } catch (CurrencyExchangeException) {
            }
        }

        $fromCurrencyModel = $this->getCurrency($fromCurrency);
        $targetCurrencyModel = $this->getCurrency($targetCurrency);

        throw_unless($fromCurrencyModel instanceof Currency, Exception::class, 'Currency not found: '.$fromCurrency);

        throw_unless($targetCurrencyModel instanceof Currency, Exception::class, 'Currency not found: '.$targetCurrency);

        throw_if(! $fromCurrencyModel->is_active || ! $targetCurrencyModel->is_active, Exception::class, 'One or both currencies are inactive');

        return $fromCurrencyModel->convertTo($amount, $targetCurrencyModel);
    }

    /**
     * Format amount with currency
     */
    public function format(float $amount, string $currencyCode): string
    {
        $currencyCode = $this->normalizeCurrencyCode($currencyCode);

        $currency = $this->getCurrency($currencyCode);

        if (! $currency instanceof Currency) {
            return $currencyCode.' '.number_format($amount, 2);
        }

        return $currency->format($amount);
    }

    /**
     * Update exchange rates from API
     */
    public function updateExchangeRates(): bool
    {
        return $this->updateAction->handle();
    }

    /**
     * Update exchange rates only if stale
     */
    public function updateExchangeRatesIfStale(): bool
    {
        if (! $this->ratesAreStale()) {
            return true;
        }

        return $this->updateExchangeRates();
    }

    /**
     * Check if exchange rates need updating
     */
    public function ratesAreStale(): bool
    {
        $stalenessHours = config('services.exchange_rate.staleness_hours', 24);

        $lastUpdate = Cache::remember('last_rate_update', self::CACHE_TTL, fn () => Currency::query()->where('is_base', false)
            ->whereNotNull('rate_updated_at')
            ->max('rate_updated_at'));

        if (! $lastUpdate) {
            return true;
        }

        return now()->diffInHours($lastUpdate) > $stalenessHours;
    }

    /**
     * Clear all user carts (backward compatibility method)
     * This dispatches an event to trigger the ClearUserCarts listener
     */
    public function clearAllCarts(): void
    {
        event(new ExchangeRatesUpdated(0, []));
    }

    /**
     * Get current exchange rates with metadata
     *
     * @phpstan-return SupportCollection<int, array{code: string, name: string, symbol: string, is_base: bool, exchange_rate: float, rate_updated_at: Carbon|null, hours_since_update: float|null, is_stale: bool}>
     */
    public function getCurrentRates(): SupportCollection
    {
        $stalenessHours = config('services.exchange_rate.staleness_hours', 24);

        return Cache::remember('current_rates', self::CACHE_TTL / 4, fn () => Currency::query()->where('is_active', true)
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
     * Convert amount between currencies and return Money object
     *
     * @throws Exception
     * @throws Throwable
     */
    public function convertToMoney(float $amount, string $from, string $to): Money
    {
        throw_if($amount < 0, Exception::class, 'Amount cannot be negative');

        $from = $this->normalizeCurrencyCode($from);
        $to = $this->normalizeCurrencyCode($to);

        if ($this->isUsdRwfPair($from, $to)) {
            return $this->exchangeHelper->convertWithAmount($from, $to, $amount);
        }

        $convertedAmount = $this->convert($amount, $from, $to);
        $currency = $this->getCurrency($to);
        if ($currency instanceof Currency) {
            return $currency->toMoney($convertedAmount);
        }

        throw new Exception('Currency not found: '.$to);
    }

    /**
     * Format amount as Money object
     */
    public function formatAsMoney(float $amount, string $currencyCode): string
    {
        $currencyCode = $this->normalizeCurrencyCode($currencyCode);

        // Use CurrencyExchangeHelper formatting for USD/RWF
        if (in_array($currencyCode, ['USD', 'RWF'], true)) {
            try {
                $money = $this->exchangeHelper->convertWithAmount($currencyCode, $currencyCode, $amount);

                return $this->exchangeHelper->formatMoney($money);
            } catch (CurrencyExchangeException) {
                // Fall back to regular format
            }
        }

        return $this->format($amount, $currencyCode);
    }

    /**
     * Check if currency pair is USD/RWF
     */
    private function isUsdRwfPair(string $from, string $to): bool
    {
        $from = $this->normalizeCurrencyCode($from);
        $to = $this->normalizeCurrencyCode($to);

        $pair = [$from, $to];
        sort($pair);

        return $pair === ['RWF', 'USD'];
    }

    /**
     * Validate currency code format (ISO 4217)
     */
    private function isValidCurrencyCode(string $code): bool
    {
        return preg_match('/^[A-Z]{3}$/', $code) === 1;
    }

    private function normalizeCurrencyCode(string $code): string
    {
        $code = mb_strtoupper($code);

        return $code === 'FRW' ? 'RWF' : $code;
    }
}
