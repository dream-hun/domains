<?php

declare(strict_types=1);

namespace App\Traits;

trait NormalizesCurrencyCode
{
    /**
     * Normalize currency code to standard format.
     * Handles legacy codes like FRW -> RWF.
     */
    protected function normalizeCurrencyCode(string $code): string
    {
        $code = mb_strtoupper(mb_trim($code));

        return match ($code) {
            'FRW' => 'RWF',
            default => $code,
        };
    }

    /**
     * Validate currency code format (ISO 4217 - 3 uppercase letters)
     */
    protected function isValidCurrencyCode(string $code): bool
    {
        return preg_match('/^[A-Z]{3}$/', $code) === 1;
    }
}
