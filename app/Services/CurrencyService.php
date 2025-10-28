<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Currency\UpdateExchangeRatesAction;
use App\Models\Currency;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;

final class CurrencyService
{
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private UpdateExchangeRatesAction $updateAction
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
        return Cache::remember('active_currencies', self::CACHE_TTL, function (): Collection {
            return Currency::getActiveCurrencies();
        });
    }

    /**
     * Get currency by code
     */
    public function getCurrency(string $code): ?Currency
    {
        if (! $this->isValidCurrencyCode($code)) {
            return null;
        }

        return Cache::remember("currency_$code", self::CACHE_TTL, function () use ($code): ?Currency {
            return Currency::where('code', $code)->first();
        });
    }

    /**
     * Get base currency
     */
    public function getBaseCurrency(): Currency
    {
        return Cache::remember('base_currency', self::CACHE_TTL, function (): Currency {
            return Currency::getBaseCurrency();
        });
    }

    /**
     * Convert amount between currencies
     *
     * @throws Exception
     */
    public function convert(float $amount, string $fromCurrency, string $targetCurrency): float
    {
        if ($amount < 0) {
            throw new Exception('Amount cannot be negative');
        }

        if ($fromCurrency === $targetCurrency) {
            return $amount;
        }

        $fromCurrencyModel = $this->getCurrency($fromCurrency);
        $targetCurrencyModel = $this->getCurrency($targetCurrency);

        if (! $fromCurrencyModel instanceof Currency) {
            throw new Exception("Currency not found: $fromCurrency");
        }

        if (! $targetCurrencyModel instanceof Currency) {
            throw new Exception("Currency not found: $targetCurrency");
        }

        if (! $fromCurrencyModel->is_active || ! $targetCurrencyModel->is_active) {
            throw new Exception('One or both currencies are inactive');
        }

        return $fromCurrencyModel->convertTo($amount, $targetCurrencyModel);
    }

    /**
     * Format amount with currency
     */
    public function format(float $amount, string $currencyCode): string
    {
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

        $lastUpdate = Cache::remember('last_rate_update', self::CACHE_TTL, function () {
            return Currency::where('is_base', false)
                ->whereNotNull('rate_updated_at')
                ->max('rate_updated_at');
        });

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
        event(new \App\Events\ExchangeRatesUpdated(0, []));
    }

    /**
     * Get current exchange rates with metadata
     */
    public function getCurrentRates(): SupportCollection
    {
        $stalenessHours = config('services.exchange_rate.staleness_hours', 24);

        return Cache::remember('current_rates', self::CACHE_TTL / 4, function () use ($stalenessHours) {
            return Currency::where('is_active', true)
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
                });
        });
    }

    /**
     * Validate currency code format (ISO 4217)
     */
    private function isValidCurrencyCode(string $code): bool
    {
        return preg_match('/^[A-Z]{3}$/', $code) === 1;
    }
}
