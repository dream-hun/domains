<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Currency;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class CurrencyService
{
    private const API_TIMEOUT = 30;

    /**
     * Get user's preferred currency
     */
    public function getUserCurrency(): Currency
    {
        if (session()->has('selected_currency')) {
            $currency = Currency::where('code', session('selected_currency'))->first();
            if ($currency) {
                return $currency;
            }
        }

        if (auth()->check() && auth()->user()->preferred_currency) {
            $currency = Currency::where('code', auth()->user()->preferred_currency)->first();
            if ($currency) {
                return $currency;
            }
        }

        return Currency::getBaseCurrency();
    }

    /**
     * Convert amount between currencies
     *
     * @throws Exception
     */
    public function convert(float $amount, string $fromCurrency, string $targetCurrency): float
    {
        if ($fromCurrency === $targetCurrency) {
            return $amount;
        }

        $fromCurrencyModel = Currency::where('code', $fromCurrency)->first();
        $targetCurrencyModel = Currency::where('code', $targetCurrency)->first();

        if (! $fromCurrencyModel || ! $targetCurrencyModel) {
            throw new Exception("Currency not found: $fromCurrency or $targetCurrency");
        }

        return $fromCurrencyModel->convertTo($amount, $targetCurrencyModel);
    }

    /**
     * Format amount with currency
     */
    public function format(float $amount, string $currencyCode): string
    {
        $currency = Currency::where('code', $currencyCode)->first();

        if (! $currency) {
            return $currencyCode.' '.number_format($amount, 2);
        }

        return $currency->format($amount);
    }

    /**
     * Update exchange rates from external API with fallback support
     */
    public function updateExchangeRates(): bool
    {
        $baseCurrency = Currency::getBaseCurrency();

        // Try primary API first
        if ($this->tryUpdateFromPrimaryApi($baseCurrency)) {
            return true;
        }

        // If primary fails, try with retry logic
        Log::warning('Primary API failed, attempting retry with longer timeout');

        return $this->tryUpdateWithRetry($baseCurrency);
    }

    /**
     * Get all active currencies for display
     */
    public function getActiveCurrencies(): Collection
    {
        return Currency::getActiveCurrencies();
    }

    /**
     * Get currency by code
     */
    public function getCurrency(string $code): ?Currency
    {
        return Currency::where('code', $code)->first();
    }

    /**
     * Check if exchange rates are stale and need updating
     */
    public function ratesAreStale(): bool
    {
        $lastUpdate = Currency::where('is_base', false)
            ->whereNotNull('rate_updated_at')
            ->max('rate_updated_at');

        if (! $lastUpdate) {
            return true; // No rates have been updated yet
        }

        // Consider rates stale if they're older than 24 hours
        return now()->diffInHours($lastUpdate) > 24;
    }

    /**
     * Update exchange rates only if they're stale
     */
    public function updateExchangeRatesIfStale(): bool
    {
        if (! $this->ratesAreStale()) {
            Log::info('Exchange rates are still fresh, skipping update');

            return true;
        }

        return $this->updateExchangeRates();
    }

    /**
     * Get current exchange rates with update information
     */
    public function getCurrentRates(): \Illuminate\Support\Collection
    {
        return Currency::where('is_active', true)
            ->orderBy('is_base', 'desc')
            ->orderBy('code')
            ->get()
            ->map(function ($currency) {
                return [
                    'code' => $currency->code,
                    'name' => $currency->name,
                    'symbol' => $currency->symbol,
                    'is_base' => $currency->is_base,
                    'exchange_rate' => $currency->exchange_rate,
                    'rate_updated_at' => $currency->rate_updated_at,
                    'hours_since_update' => $currency->rate_updated_at
                        ? now()->diffInHours($currency->rate_updated_at)
                        : null,
                ];
            });
    }

    /**
     * Try updating from the primary exchangerate-api.com
     */
    private function tryUpdateFromPrimaryApi(Currency $baseCurrency): bool
    {
        try {
            $response = Http::timeout(self::API_TIMEOUT)
                ->retry(2, 1000) // Retry 2 times with 1-second delay
                ->get("https://api.exchangerate-api.com/v4/latest/{$baseCurrency->code}");

            if ($response->successful()) {
                return $this->processExchangeRates($response->json('rates', []));
            }

            Log::warning('Primary API request failed', ['status' => $response->status()]);

            return false;

        } catch (Exception $e) {
            Log::warning('Primary API exception', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Try updating with extended timeout and retry logic
     */
    private function tryUpdateWithRetry(Currency $baseCurrency): bool
    {
        try {
            // Try with extended timeout
            $response = Http::timeout(45)
                ->retry(3, 2000) // Retry 3 times with 2 second delay
                ->get("https://api.exchangerate-api.com/v4/latest/{$baseCurrency->code}");

            if ($response->successful()) {
                return $this->processExchangeRates($response->json('rates', []));
            }

            Log::error('All retry attempts failed', ['status' => $response->status()]);

            return false;

        } catch (Exception $e) {
            Log::error('Error updating exchange rates after retries', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Process and save exchange rates
     */
    private function processExchangeRates(array $rates): bool
    {
        if (empty($rates)) {
            Log::error('No exchange rates received from API');

            return false;
        }

        $updatedCurrencies = [];
        $updatedCount = 0;

        foreach ($rates as $currencyCode => $rate) {
            $currency = Currency::where('code', $currencyCode)->first();
            if ($currency && ! $currency->is_base) {
                $oldRate = $currency->exchange_rate;

                $currency->update([
                    'exchange_rate' => $rate,
                    'rate_updated_at' => now(),
                ]);

                $updatedCurrencies[] = [
                    'code' => $currencyCode,
                    'name' => $currency->name,
                    'old_rate' => $oldRate,
                    'new_rate' => $rate,
                    'change' => $oldRate ? round((($rate - $oldRate) / $oldRate) * 100, 2) : null,
                ];

                $updatedCount++;
            }
        }

        Cache::forget('active_currencies');
        Cache::forget('base_currency');

        // Log summary
        Log::info("Updated $updatedCount exchange rates", [
            'total_rates_received' => count($rates),
            'currencies_updated' => $updatedCount,
        ]);

        // Log detailed currency information
        foreach ($updatedCurrencies as $currencyInfo) {
            $changeText = $currencyInfo['change'] !== null
                ? ($currencyInfo['change'] >= 0 ? "+{$currencyInfo['change']}%" : "{$currencyInfo['change']}%")
                : 'new';

            Log::info("Currency rate updated: {$currencyInfo['code']} ({$currencyInfo['name']})", [
                'old_rate' => $currencyInfo['old_rate'],
                'new_rate' => $currencyInfo['new_rate'],
                'change_percent' => $changeText,
            ]);
        }

        return true;
    }
}
