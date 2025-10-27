<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Currency;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class CurrencyService
{
    private const API_TIMEOUT = 30;

    private const EXTENDED_TIMEOUT = 45;

    private const CACHE_TTL = 3600; // 1 hour

    private const RATE_STALENESS_HOURS = 24;

    /**
     * Get user's preferred currency with proper caching
     */
    public function getUserCurrency(): Currency
    {
        if (session()->has('selected_currency')) {
            $currency = $this->getCurrencyFromCache(session('selected_currency'));
            if ($currency instanceof Currency) {
                return $currency;
            }
        }

        // No user preference lookup, fallback to base currency
        return $this->getBaseCurrency();
    }

    /**
     * Convert amount between currencies with validation
     *
     * @throws Exception
     */
    public function convert(float $amount, string $fromCurrency, string $targetCurrency): float
    {
        // Validate amount
        if ($amount < 0) {
            throw new Exception('Amount cannot be negative');
        }

        if ($fromCurrency === $targetCurrency) {
            return $amount;
        }

        // Validate currency codes format
        if (! $this->isValidCurrencyCode($fromCurrency) || ! $this->isValidCurrencyCode($targetCurrency)) {
            throw new Exception('Invalid currency code format');
        }

        $fromCurrencyModel = $this->getCurrencyFromCache($fromCurrency);
        $targetCurrencyModel = $this->getCurrencyFromCache($targetCurrency);

        if (! $fromCurrencyModel instanceof Currency || ! $targetCurrencyModel instanceof Currency) {
            throw new Exception("Currency not found: $fromCurrency or $targetCurrency");
        }

        // Check if currencies are active
        if (! $fromCurrencyModel->is_active || ! $targetCurrencyModel->is_active) {
            throw new Exception("Inactive currency: $fromCurrency or $targetCurrency");
        }

        return $fromCurrencyModel->convertTo($amount, $targetCurrencyModel);
    }

    /**
     * Format amount with currency with better error handling
     */
    public function format(float $amount, string $currencyCode): string
    {
        if (! $this->isValidCurrencyCode($currencyCode)) {
            return number_format($amount, 2);
        }

        $currency = $this->getCurrencyFromCache($currencyCode);

        if (! $currency instanceof Currency) {
            return $currencyCode.' '.number_format($amount, 2);
        }

        return $currency->format($amount);
    }

    /**
     * Update exchange rates from external API with improved error handling
     */
    public function updateExchangeRates(): bool
    {
        $baseCurrency = $this->getBaseCurrency();

        if (! $baseCurrency instanceof Currency) {
            Log::error('No base currency found');

            return false;
        }

        // Try primary API first
        if ($this->tryUpdateFromPrimaryApi($baseCurrency)) {
            return true;
        }

        // If primary fails, try with retry logic
        Log::warning('Primary API failed, attempting retry with longer timeout');

        return $this->tryUpdateWithRetry($baseCurrency);
    }

    /**
     * Get all active currencies with caching
     */
    public function getActiveCurrencies(): Collection
    {
        return Cache::remember('active_currencies', self::CACHE_TTL, function (): Collection {
            return Currency::getActiveCurrencies();
        });
    }

    /**
     * Get currency by code with caching
     */
    public function getCurrency(string $code): ?Currency
    {
        return $this->getCurrencyFromCache($code);
    }

    /**
     * Check if exchange rates are stale and need updating
     */
    public function ratesAreStale(): bool
    {
        $lastUpdate = Cache::remember('last_rate_update', self::CACHE_TTL, function () {
            return Currency::where('is_base', false)
                ->whereNotNull('rate_updated_at')
                ->max('rate_updated_at');
        });

        if (! $lastUpdate) {
            return true; // No rates have been updated yet
        }

        // Consider rates stale if they're older than configured hours
        return now()->diffInHours($lastUpdate) > self::RATE_STALENESS_HOURS;
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
    public function getCurrentRates(): SupportCollection
    {
        return Cache::remember('current_rates', self::CACHE_TTL / 4, function () {
            return Currency::where('is_active', true)
                ->orderBy('is_base', 'desc')
                ->orderBy('code')
                ->get()
                ->map(function (Currency $currency): array {
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
                        'is_stale' => ! $currency->rate_updated_at || now()->diffInHours($currency->rate_updated_at) > self::RATE_STALENESS_HOURS,
                    ];
                });
        });
    }

    /**
     * Get base currency with caching
     */
    private function getBaseCurrency(): ?Currency
    {
        return Cache::remember('base_currency', self::CACHE_TTL, function (): Currency {
            return Currency::getBaseCurrency();
        });
    }

    /**
     * Get currency from cache or database
     */
    private function getCurrencyFromCache(string $code): ?Currency
    {
        return Cache::remember("currency_$code", self::CACHE_TTL, function () use ($code): ?Currency {
            return Currency::where('code', $code)->first();
        });
    }

    /**
     * Validate currency code format (ISO 4217)
     */
    private function isValidCurrencyCode(string $code): bool
    {
        return preg_match('/^[A-Z]{3}$/', $code) === 1;
    }

    /**
     * Try updating from the primary exchanger-api.com with better error handling
     */
    private function tryUpdateFromPrimaryApi(Currency $baseCurrency): bool
    {
        try {
            $response = Http::timeout(self::API_TIMEOUT)
                ->retry(2, 1000)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => config('app.name', 'Laravel').'/1.0',
                ])
                ->get("https://api.exchangerate-api.com/v4/latest/$baseCurrency->code");

            if ($response->successful()) {
                $data = $response->json();

                // Validate response structure
                if (! isset($data['rates']) || ! is_array($data['rates'])) {
                    Log::error('Invalid API response structure', ['response' => $data]);

                    return false;
                }

                return $this->processExchangeRates($data['rates']);
            }

            Log::warning('Primary API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;

        } catch (Exception $e) {
            Log::warning('Primary API exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Try updating with extended timeout and retry logic
     */
    private function tryUpdateWithRetry(Currency $baseCurrency): bool
    {
        try {
            $response = Http::timeout(self::EXTENDED_TIMEOUT)
                ->retry(3, 2000)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => config('app.name', 'Laravel').'/1.0',
                ])
                ->get("https://api.exchangerate-api.com/v4/latest/$baseCurrency->code");

            if ($response->successful()) {
                $data = $response->json();

                if (! isset($data['rates']) || ! is_array($data['rates'])) {
                    Log::error('Invalid API response structure on retry', ['response' => $data]);

                    return false;
                }

                return $this->processExchangeRates($data['rates']);
            }

            Log::error('All retry attempts failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;

        } catch (Exception $e) {
            Log::error('Error updating exchange rates after retries', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Process and save exchange rates with database transaction
     */
    private function processExchangeRates(array $rates): bool
    {
        if ($rates === []) {
            Log::error('No exchange rates received from API');

            return false;
        }

        try {
            return DB::transaction(function () use ($rates): bool {
                $updatedCurrencies = [];
                $updatedCount = 0;
                $now = now();

                // Get all currencies that need updating in one query
                $currencies = Currency::whereIn('code', array_keys($rates))
                    ->where('is_base', false)
                    ->get()
                    ->keyBy('code');

                foreach ($rates as $currencyCode => $rate) {
                    if (! isset($currencies[$currencyCode])) {
                        continue;
                    }

                    $currency = $currencies[$currencyCode];

                    // Validate rate
                    if (! is_numeric($rate) || $rate <= 0) {
                        Log::warning("Invalid exchange rate for $currencyCode: $rate");

                        continue;
                    }

                    $oldRate = $currency->exchange_rate;
                    $newRate = (float) $rate;

                    // Only update if rate has changed significantly (more than 0.0001% difference)
                    if ($oldRate && abs(($newRate - $oldRate) / $oldRate) < 0.000001) {
                        continue;
                    }

                    $currency->update([
                        'exchange_rate' => $newRate,
                        'rate_updated_at' => $now,
                    ]);

                    $updatedCurrencies[] = [
                        'code' => $currencyCode,
                        'name' => $currency->name,
                        'old_rate' => $oldRate,
                        'new_rate' => $newRate,
                        'change' => $oldRate ?: null,
                    ];

                    $updatedCount++;
                }

                // Clear relevant caches
                $this->clearCurrencyCaches();

                // Log summary
                Log::info("Updated $updatedCount exchange rates", [
                    'total_rates_received' => count($rates),
                    'currencies_updated' => $updatedCount,
                    'timestamp' => $now->toISOString(),
                ]);

                // Log detailed currency information (limit to avoid log spam)
                foreach (array_slice($updatedCurrencies, 0, 10) as $currencyInfo) {
                    $changeText = $currencyInfo['change'] !== null
                        ? ($currencyInfo['change'] >= 0 ? "+{$currencyInfo['change']}%" : "{$currencyInfo['change']}%")
                        : 'new';

                    Log::info("Currency rate updated: {$currencyInfo['code']} ({$currencyInfo['name']})", [
                        'old_rate' => $currencyInfo['old_rate'],
                        'new_rate' => $currencyInfo['new_rate'],
                        'change_percent' => $changeText,
                    ]);
                }

                if (count($updatedCurrencies) > 10) {
                    Log::info('... and '.(count($updatedCurrencies) - 10).' more currencies updated');
                }

                return $updatedCount > 0;
            });

        } catch (Exception $e) {
            Log::error('Error processing exchange rates', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        } catch (Throwable $e) {
            Log::error('Unexpected error processing exchange rates', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Clear all currency-related caches
     */
    private function clearCurrencyCaches(): void
    {
        Cache::forget('active_currencies');
        Cache::forget('base_currency');
        Cache::forget('current_rates');
        Cache::forget('last_rate_update');

        // Clear individual currency caches (you might want to implement a more efficient way)
        $currencies = Currency::pluck('code');
        foreach ($currencies as $code) {
            Cache::forget("currency_$code");
        }
    }
}
