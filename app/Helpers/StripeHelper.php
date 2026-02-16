<?php

declare(strict_types=1);

namespace App\Helpers;

final class StripeHelper
{
    private const array ZERO_DECIMAL_CURRENCIES = [
        'BIF', // Burundian Franc
        'CLP', // Chilean Peso
        'DJF', // Djiboutian Franc
        'GNF', // Guinean Franc
        'JPY', // Japanese Yen
        'KMF', // Comorian Franc
        'KRW', // South Korean Won
        'MGA', // Malagasy Ariary
        'PYG', // Paraguayan Guaraní
        'RWF', // Rwandan Franc
        'UGX', // Ugandan Shilling
        'VND', // Vietnamese Dong
        'VUV', // Vanuatu Vatu
        'XAF', // Central African CFA Franc
        'XOF', // West African CFA Franc
        'XPF', // CFP Franc
    ];

    /**
     * Convert an amount to Stripe's smallest currency unit
     *
     * For zero-decimal currencies (RWF, JPY, etc.), returns the amount as-is
     * For regular currencies, multiplies by 100 to convert to cents
     *
     * @param  float  $amount  The amount in the currency's main unit
     * @param  string  $currency  The three-letter currency code (e.g., 'USD', 'RWF')
     * @return int The amount in Stripe's smallest currency unit
     */
    public static function convertToStripeAmount(float $amount, string $currency): int
    {
        $currency = mb_strtoupper($currency);

        // Handle alternative currency codes
        if ($currency === 'FRW') {
            $currency = 'RWF';
        }

        // For zero-decimal currencies, return the amount as-is (rounded to integer)
        if (in_array($currency, self::ZERO_DECIMAL_CURRENCIES, true)) {
            return (int) round($amount);
        }

        // For regular currencies, multiply by 100 to convert to cents
        return (int) round($amount * 100);
    }

    /**
     * Check if a currency uses zero decimal places in Stripe
     *
     * @param  string  $currency  The three-letter currency code
     * @return bool True if the currency is zero-decimal
     */
    public static function isZeroDecimalCurrency(string $currency): bool
    {
        $currency = mb_strtoupper($currency);

        // Handle alternative currency codes
        if ($currency === 'FRW') {
            $currency = 'RWF';
        }

        return in_array($currency, self::ZERO_DECIMAL_CURRENCIES, true);
    }
}
