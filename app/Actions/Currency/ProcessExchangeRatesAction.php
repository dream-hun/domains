<?php

declare(strict_types=1);

namespace App\Actions\Currency;

use App\Events\ExchangeRatesUpdated;
use App\Models\Currency;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ProcessExchangeRatesAction
{
    /**
     * Process and save exchange rates
     *
     * @param  array<string, float>  $rates
     */
    public function handle(array $rates): bool
    {
        if ($rates === []) {
            Log::error('No exchange rates received');

            return false;
        }

        return DB::transaction(function () use ($rates): bool {
            $now = now();
            $updatedCurrencies = [];
            $updatedCount = 0;
            $skippedCount = 0;

            // Get all currencies that need updating
            $currencies = Currency::query()->whereIn('code', array_keys($rates))
                ->where('is_base', false)
                ->get()
                ->keyBy('code');

            Log::info('Processing exchange rates', [
                'total_rates' => count($rates),
                'currencies_in_db' => $currencies->count(),
            ]);

            foreach ($rates as $currencyCode => $rate) {
                if (! isset($currencies[$currencyCode])) {
                    Log::debug(sprintf('Currency %s not found in database, skipping', $currencyCode));

                    continue;
                }

                $currency = $currencies[$currencyCode];

                if (! $this->isValidRate($rate, $currencyCode)) {
                    continue;
                }

                $newRate = (float) $rate;
                $oldRate = (float) $currency->exchange_rate;

                // Skip if rate hasn't changed significantly
                if (! $this->hasSignificantChange($oldRate, $newRate)) {
                    $skippedCount++;
                    Log::debug(sprintf('Rate change for %s too small, skipping', $currencyCode), [
                        'old' => $oldRate,
                        'new' => $newRate,
                    ]);

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
                ];

                $updatedCount++;
            }

            Log::info('Exchange rate processing complete', [
                'updated' => $updatedCount,
                'skipped' => $skippedCount,
            ]);

            if ($updatedCount === 0) {
                Log::warning('No exchange rates were updated', [
                    'total_received' => count($rates),
                    'skipped_due_to_insignificant_change' => $skippedCount,
                ]);

                return false;
            }

            // Clear caches
            $this->clearCaches();

            // Dispatch event for side effects
            event(new ExchangeRatesUpdated($updatedCount, $updatedCurrencies));

            // Log results
            $this->logResults($rates, $updatedCount, $updatedCurrencies, $now);

            return true;
        });
    }

    private function isValidRate(mixed $rate, string $currencyCode): bool
    {
        if (! is_numeric($rate) || $rate <= 0) {
            Log::warning('Invalid exchange rate for '.$currencyCode, ['rate' => $rate]);

            return false;
        }

        return true;
    }

    private function hasSignificantChange(?float $oldRate, float $newRate): bool
    {
        if ($oldRate === null || $oldRate === 0.0) {
            return true;
        }

        // Only update if rate has changed more than 0.0001%
        return abs(($newRate - $oldRate) / $oldRate) >= 0.000001;
    }

    private function clearCaches(): void
    {
        Cache::forget('active_currencies');
        Cache::forget('base_currency');
        Cache::forget('current_rates');
        Cache::forget('last_rate_update');

        // Clear individual currency caches
        $currencies = Currency::query()->pluck('code');
        foreach ($currencies as $code) {
            Cache::forget('currency_'.$code);
        }
    }

    private function logResults(array $rates, int $updatedCount, array $updatedCurrencies, mixed $now): void
    {
        Log::info(sprintf('Updated %d exchange rates', $updatedCount), [
            'total_rates_received' => count($rates),
            'currencies_updated' => $updatedCount,
            'timestamp' => $now->toISOString(),
        ]);

        // Log first 10 updated currencies
        foreach (array_slice($updatedCurrencies, 0, 10) as $currency) {
            Log::info(sprintf('Currency rate updated: %s (%s)', $currency['code'], $currency['name']), [
                'old_rate' => $currency['old_rate'],
                'new_rate' => $currency['new_rate'],
            ]);
        }

        if (count($updatedCurrencies) > 10) {
            Log::info('... and '.(count($updatedCurrencies) - 10).' more currencies updated');
        }
    }
}
