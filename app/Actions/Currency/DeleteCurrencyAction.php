<?php

declare(strict_types=1);

namespace App\Actions\Currency;

use App\Models\Currency;
use Exception;
use Illuminate\Support\Facades\Cache;

final class DeleteCurrencyAction
{
    public function handle(Currency $currency): void
    {
        // Prevent deletion of base currency
        throw_if($currency->is_base, Exception::class, 'Cannot delete the base currency. Please set another currency as base first.');

        $code = $currency->code;

        $currency->delete();

        // Clear currency caches
        Cache::forget('active_currencies');
        Cache::forget('base_currency');
        Cache::forget('current_rates');
        Cache::forget('currency_'.$code);
    }
}
