<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Currency;
use Exception;
use NumberFormatter;

final readonly class PriceFormatter
{
    /**
     * Format an amount with its currency symbol.
     */
    public function format(float $amount, string $currency): string
    {
        $currency = $this->normalizeCurrency($currency);
        $config = $this->getCurrencyConfig($currency);

        $decimals = $this->getDecimalPlaces($currency, $amount, $config);
        $symbol = $this->resolveSymbol($currency, $config);
        $position = $config['symbol_position'] ?? 'before';

        $amount = round($amount, $decimals);
        $formattedNumber = number_format($amount, $decimals);

        return $position === 'before'
            ? $symbol.$formattedNumber
            : $formattedNumber.$symbol;
    }

    /**
     * Format an amount from minor units (cents).
     */
    public function formatFromMinorUnits(int $amountInMinorUnits, string $currency): string
    {
        $majorUnits = $amountInMinorUnits / 100;

        return $this->format($majorUnits, $currency);
    }

    /**
     * Convert minor units (cents) to major units (dollars).
     */
    public function minorToMajorUnits(int $amountInMinorUnits): float
    {
        return $amountInMinorUnits / 100;
    }

    /**
     * Convert and format a price from minor units in source currency to target currency.
     */
    public function convertAndFormatFromMinorUnits(
        int $amountInMinorUnits,
        string $sourceCurrency,
        string $targetCurrency,
        ?callable $converter = null
    ): string {
        $sourceCurrency = $this->normalizeCurrency($sourceCurrency);
        $targetCurrency = $this->normalizeCurrency($targetCurrency);
        $majorUnits = $this->minorToMajorUnits($amountInMinorUnits);

        if ($sourceCurrency === $targetCurrency) {
            return $this->format($majorUnits, $targetCurrency);
        }

        if ($converter !== null) {
            try {
                $convertedAmount = $converter($majorUnits, $sourceCurrency, $targetCurrency);

                return $this->format($convertedAmount, $targetCurrency);
            } catch (Exception) {
                return $this->format($majorUnits, $sourceCurrency);
            }
        }

        return $this->format($majorUnits, $sourceCurrency);
    }

    /**
     * Get the currency symbol for a currency code.
     */
    public function getSymbol(string $currency): string
    {
        $currency = $this->normalizeCurrency($currency);
        $config = $this->getCurrencyConfig($currency);

        return $this->resolveSymbol($currency, $config);
    }

    /**
     * Get the number of decimal places for a currency.
     */
    public function getDecimals(string $currency, ?float $amount = null): int
    {
        $currency = $this->normalizeCurrency($currency);
        $config = $this->getCurrencyConfig($currency);

        return $this->getDecimalPlaces($currency, $amount ?? 0.0, $config);
    }

    /**
     * Check if a currency uses decimal places.
     */
    public function currencyHasDecimals(string $currency): bool
    {
        $currency = $this->normalizeCurrency($currency);
        $config = $this->getCurrencyConfig($currency);

        // Currencies with configured 0 decimals never use decimals
        return ($config['decimals'] ?? 2) > 0;
    }

    /**
     * Normalize currency code (e.g., FRW -> RWF).
     */
    public function normalizeCurrency(string $currency): string
    {
        $currency = mb_strtoupper($currency);

        /** @var array<string, string> $aliases */
        $aliases = config('currency_exchange.aliases', []);

        return $aliases[$currency] ?? $currency;
    }

    /**
     * Get the configuration for a currency.
     *
     * @return array{name?: string, symbol?: string, decimals?: int, symbol_position?: string, aliases?: array<string>}
     */
    private function getCurrencyConfig(string $currency): array
    {
        /** @var array<string, array{name?: string, symbol?: string, decimals?: int, symbol_position?: string, aliases?: array<string>}> $currencies */
        $currencies = config('currency_exchange.currencies', []);

        return $currencies[$currency] ?? [];
    }

    /**
     * Determine the appropriate number of decimal places.
     *
     * @param  array{name?: string, symbol?: string, decimals?: int, symbol_position?: string, aliases?: array<string>}  $config
     */
    private function getDecimalPlaces(string $currency, float $amount, array $config): int
    {
        if (isset($config['decimals'])) {
            if ($config['decimals'] === 0) {
                return 0;
            }

            if (abs($amount - round($amount)) < 0.01) {
                return 0;
            }

            return $config['decimals'];
        }

        $noDecimalCurrencies = ['RWF', 'JPY', 'KRW', 'VND', 'CLP', 'ISK', 'UGX', 'KES', 'TZS'];

        if (in_array($currency, $noDecimalCurrencies, true)) {
            return 0;
        }

        if (abs($amount - round($amount)) < 0.01) {
            return 0;
        }

        return 2;
    }

    /**
     * Resolve symbol: Currency model first, then config, then formatter fallback.
     *
     * @param  array{name?: string, symbol?: string, decimals?: int, symbol_position?: string, aliases?: array<string>}  $config
     */
    private function resolveSymbol(string $currency, array $config): string
    {
        $currencyModel = Currency::query()->where('code', $currency)->first();

        if ($currencyModel instanceof Currency && (string) $currencyModel->symbol !== '') {
            return (string) $currencyModel->symbol;
        }

        return $config['symbol'] ?? $this->getSymbolFromFormatter($currency);
    }

    /**
     * Get currency symbol using PHP's NumberFormatter as fallback.
     */
    private function getSymbolFromFormatter(string $currency): string
    {
        $commonSymbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'RWF' => 'FRW',
            'KRW' => '₩',
        ];

        if (isset($commonSymbols[$currency])) {
            return $commonSymbols[$currency];
        }

        try {
            $formatter = new NumberFormatter('en', NumberFormatter::CURRENCY);
            $formatted = $formatter->formatCurrency(0, $currency);

            if ($formatted === false) {
                return $currency;
            }

            $symbol = preg_replace('/[\d.,\s]/', '', $formatted);

            if (in_array($symbol, [null, '', '0'], true)) {
                return $currency;
            }

            return $symbol;
        } catch (Exception) {
            return $currency;
        }
    }
}
