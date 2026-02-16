<?php

declare(strict_types=1);

namespace App\Actions\Currency;

use App\Models\Currency;
use Illuminate\Support\Facades\Cache;

final class UpdateCurrencyAction
{
    public function handle(Currency $currency, array $data): Currency
    {
        // If setting as base currency, unset all other base currencies
        if (isset($data['is_base']) && $data['is_base'] && ! $currency->is_base) {
            Currency::query()->where('is_base', true)->where('uuid', '!=', $currency->uuid)->update(['is_base' => false]);
        }

        $currency->update($data);

        // Clear currency caches
        Cache::forget('active_currencies');
        Cache::forget('base_currency');
        Cache::forget('current_rates');
        Cache::forget('currency_'.$currency->code);

        return $currency->fresh();
    }
}
