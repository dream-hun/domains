<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    |
    | List of currency codes supported by the currency exchange helper.
    | Currently limited to USD and RWF (Rwandan Franc) pair conversions.
    |
    | Each currency has:
    | - name: Human-readable name
    | - symbol: Currency symbol for display
    | - decimals: Number of decimal places (0 for currencies without minor units)
    | - symbol_position: 'before' or 'after' the amount
    | - aliases: Alternative codes that map to this currency
    |
    */

    'currencies' => [
        'USD' => [
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimals' => 2,
            'symbol_position' => 'before',
            'aliases' => [],
        ],
        'RWF' => [
            'name' => 'Rwandan Franc',
            'symbol' => 'FRW',
            'decimals' => 0,
            'symbol_position' => 'before',
            'aliases' => ['FRW'],
        ],
        'EUR' => [
            'name' => 'Euro',
            'symbol' => '€',
            'decimals' => 2,
            'symbol_position' => 'before',
            'aliases' => [],
        ],
        'GBP' => [
            'name' => 'British Pound',
            'symbol' => '£',
            'decimals' => 2,
            'symbol_position' => 'before',
            'aliases' => [],
        ],
        'JPY' => [
            'name' => 'Japanese Yen',
            'symbol' => '¥',
            'decimals' => 0,
            'symbol_position' => 'before',
            'aliases' => [],
        ],
        'KRW' => [
            'name' => 'South Korean Won',
            'symbol' => '₩',
            'decimals' => 0,
            'symbol_position' => 'before',
            'aliases' => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Aliases
    |--------------------------------------------------------------------------
    |
    | Map alternative currency codes to their canonical codes.
    | This ensures consistent handling when different codes are used.
    |
    */

    'aliases' => [
        'FRW' => 'RWF',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default/base currency used for pricing and conversions.
    |
    */

    'default_currency' => env('DEFAULT_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Supported Currencies (Legacy - kept for backward compatibility)
    |--------------------------------------------------------------------------
    */

    'supported_currencies' => [
        'USD' => [
            'name' => 'US Dollar',
            'symbol' => '$',
        ],
        'RWF' => [
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
        'USD_TO_RWF' => env('FALLBACK_USD_TO_RWF', env('FALLBACK_USD_TO_FRW', 1350.0)),
        'RWF_TO_USD' => env('FALLBACK_RWF_TO_USD', env('FALLBACK_FRW_TO_USD', 0.00074074)),
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
