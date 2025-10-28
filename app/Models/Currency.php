<?php

declare(strict_types=1);

namespace App\Models;

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
        return Cache::remember('base_currency', 3600, function () {
            return self::where('is_base', true)->first() ?? self::where('code', 'USD')->first();
        });
    }

    /**
     * Get active currencies
     */
    public static function getActiveCurrencies(): Collection
    {
        return Cache::remember('active_currencies', 3600, function () {
            return self::where('is_active', true)->orderBy('code')->get();
        });
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
        return $this->symbol.number_format($amount, 2);
    }
}
