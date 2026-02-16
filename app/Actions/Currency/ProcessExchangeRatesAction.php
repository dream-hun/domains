<?php

declare(strict_types=1);

namespace App\Actions\Currency;

use App\Models\Currency;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class ProcessExchangeRatesAction
{
    /**
     * No longer writes to database; clears currency caches only.
     *
     * @param  array<string, float>  $rates
     */
    public function handle(array $rates): bool
    {
        Log::info('Clearing currency caches (exchange rates no longer stored in database)', [
            'rates_received' => count($rates),
        ]);

        $this->clearCaches();

        return true;
    }

    private function clearCaches(): void
    {
        Cache::forget('active_currencies');
        Cache::forget('base_currency');
        Cache::forget('current_rates');
        Cache::forget('last_rate_update');

        $currencies = Currency::query()->pluck('code');
        foreach ($currencies as $code) {
            Cache::forget('currency_'.$code);
        }
    }
}
