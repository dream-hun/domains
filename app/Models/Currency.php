<?php

declare(strict_types=1);

namespace App\Models;

use Cknow\Money\Money;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

final class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'exchange_rate',
        'is_base',
        'is_active',
        'rate_updated_at',
    ];

    protected $casts = [
        'exchange_rate' => 'float',
        'is_base' => 'boolean',
        'is_active' => 'boolean',
        'rate_updated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the base currency
     */
    public static function getBaseCurrency(): self
    {
        return Cache::remember('base_currency', 3600, static function (): self {
            $baseCurrency = self::query()->where('is_base', true)->first()
                ?? self::query()->where('code', 'USD')->first();

            if ($baseCurrency instanceof self) {
                return $baseCurrency;
            }

            return self::query()->make([
                'code' => 'USD',
                'name' => 'US Dollar',
                'symbol' => '$',
                'exchange_rate' => 1.0,
                'is_base' => true,
                'is_active' => true,
            ]);
        });
    }

    /**
     * Get active currencies
     */
    public static function getActiveCurrencies(): Collection
    {
        return Cache::remember('active_currencies', 3600, fn () => self::query()->where('is_active', true)->orderBy('code')->get());
    }

    public function formattedBaseRate(): Money
    {
        return Money::USD($this->exchange_rate);

    }

    public function formattedRate(): Money
    {
        return Money::RWF($this->exchange_rate);

    }

    /**
     * Convert amount from this currency to target currency
     */
    public function convertTo(float $amount, self $targetCurrency): float
    {
        if ($this->code === $targetCurrency->code) {
            return $amount;
        }

        $baseCurrency = self::getBaseCurrency();

        // Convert to base currency first if not already base
        if ($this->code !== $baseCurrency->code) {
            $amount /= $this->exchange_rate;
        }

        // Convert from base to target currency
        if ($targetCurrency->code !== $baseCurrency->code) {
            $amount *= $targetCurrency->exchange_rate;
        }

        return round($amount, 2);
    }

    /**
     * Format amount with currency symbol
     */
    public function format(float $amount): string
    {
        // Determine decimal places based on currency and amount
        $decimals = $this->getDecimalPlaces($amount);

        // Round to the appropriate decimal places to ensure consistency
        $amount = round($amount, $decimals);

        return $this->symbol.number_format($amount, $decimals);
    }

    /**
     * Convert amount to Money object
     */
    public function toMoney(float $amount): Money
    {
        // Convert to minor units (cents/smallest unit)
        $minorUnits = (int) round($amount * 100);

        return Money::{$this->code}($minorUnits);
    }

    /**
     * Get the appropriate number of decimal places for this currency
     */
    private function getDecimalPlaces(float $amount): int
    {
        // Currencies that don't use decimal places - ALWAYS 0 decimals
        $noDecimalCurrencies = [
            'RWF', // Rwandan Franc
            'RWF', // Rwandan Franc (alternative code)
            'JPY', // Japanese Yen
            'KRW', // South Korean Won
            'VND', // Vietnamese Dong
            'CLP', // Chilean Peso
            'ISK', // Icelandic Króna
            'UGX', // Ugandan Shilling
            'KES', // Kenyan Shilling
            'TZS', // Tanzanian Shilling
        ];

        // These currencies NEVER use decimals, even with fractional amounts
        if (in_array($this->code, $noDecimalCurrencies, true)) {
            return 0;
        }

        // For other currencies, check if the amount has meaningful decimals
        // If the fractional part is effectively zero, don't show decimals
        if (abs($amount - round($amount)) < 0.01) {
            return 0;
        }

        // Default to 2 decimal places
        return 2;
    }
}
