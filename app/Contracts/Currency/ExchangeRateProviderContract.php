<?php

declare(strict_types=1);

namespace App\Contracts\Currency;

interface ExchangeRateProviderContract
{
    /**
     * Get exchange rate between two currencies
     */
    public function getRate(string $from, string $to): float;

    /**
     * Get all exchange rates for a base currency
     *
     * @return array<string, float>|null
     */
    public function getRates(string $baseCurrency): ?array;

    /**
     * Get rate metadata including last updated time
     *
     * @return array{from: string, to: string, last_updated: string|null, next_update: string|null, is_cached: bool, is_fallback: bool}
     */
    public function getRateMetadata(string $from, string $to): array;

    /**
     * Clear cached rates
     */
    public function clearCache(?string $from = null, ?string $to = null): void;
}
