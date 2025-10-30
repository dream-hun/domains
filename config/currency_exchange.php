<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    |
    | List of currency codes supported by the currency exchange helper.
    | Currently limited to USD and FRW (Rwandan Franc) pair conversions.
    |
    */

    'supported_currencies' => [
        'USD' => [
            'name' => 'US Dollar',
            'symbol' => '$',
        ],
        'FRW' => [
            'name' => 'Rwandan Franc',
            'symbol' => 'FRW',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how long exchange rates should be cached to minimize API calls.
    | Duration is in seconds (3600 = 1 hour).
    |
    */

    'cache' => [
        'enabled' => env('CURRENCY_EXCHANGE_CACHE_ENABLED', true),
        'ttl' => env('CURRENCY_EXCHANGE_CACHE_TTL', 3600), // 1 hour
        'prefix' => 'exchange_rate',
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Exchange Rates
    |--------------------------------------------------------------------------
    |
    | Fallback rates used when the API is unavailable or returns an error.
    | These should be updated periodically to reflect approximate real rates.
    |
    */

    'fallback_rates' => [
        'USD_TO_FRW' => env('FALLBACK_USD_TO_FRW', 1350.0),
        'FRW_TO_USD' => env('FALLBACK_FRW_TO_USD', 0.00074074),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the ExchangeRate-API pair conversion endpoint.
    |
    */

    'api' => [
        'key' => env('EXCHANGE_RATE_API_KEY'),
        'base_url' => env('EXCHANGE_RATE_API_BASE_URL', 'https://v6.exchangerate-api.com/v6'),
        'endpoint' => 'pair', // Pair conversion endpoint
        'timeout' => env('EXCHANGE_RATE_API_TIMEOUT', 30),
        'retry_times' => env('EXCHANGE_RATE_API_RETRY', 2),
        'retry_delay' => 1000, // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    |
    | Configure how errors should be handled.
    |
    */

    'error_handling' => [
        'log_failures' => true,
        'use_fallback_on_error' => true,
        'throw_on_validation_error' => true,
    ],

];
