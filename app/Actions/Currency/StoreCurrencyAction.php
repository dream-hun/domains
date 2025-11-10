<?php

declare(strict_types=1);

namespace App\Actions\Currency;

use App\Models\Currency;
use Illuminate\Support\Facades\Cache;

final class StoreCurrencyAction
{
    public function handle(array $data): Currency
    {
        // If setting as base currency, unset all other base currencies
        if (isset($data['is_base']) && $data['is_base']) {
            Currency::query()->where('is_base', true)->update(['is_base' => false]);
        }

        $currency = Currency::query()->create($data);

        // Clear currency caches
        Cache::forget('active_currencies');
        Cache::forget('base_currency');
        Cache::forget('current_rates');

        return $currency;
    }
}
