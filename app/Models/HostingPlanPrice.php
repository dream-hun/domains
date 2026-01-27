<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Hosting\HostingPlanPriceStatus;
use App\Services\CurrencyService;
use Database\Factories\HostingPlanPriceFactory;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Throwable;

class HostingPlanPrice extends Model
{
    /** @use HasFactory<HostingPlanPriceFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'regular_price' => 'integer',
        'renewal_price' => 'integer',
        'status' => HostingPlanPriceStatus::class,
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(HostingPlan::class, 'hosting_plan_id');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Get formatted price string with currency symbol
     */
    public function getFormattedPrice(string $priceType = 'regular_price', ?string $targetCurrency = null): string
    {
        $priceAmount = $this->getPriceInBaseCurrency($priceType);
        $baseCurrency = $this->getBaseCurrency();

        // If no target currency specified, use user's preferred currency
        if (in_array($targetCurrency, [null, '', '0'], true)) {
            $targetCurrency = resolve(CurrencyService::class)->getUserCurrency()->code;
        }

        try {
            if ($targetCurrency !== $baseCurrency) {
                $convertedAmount = resolve(CurrencyService::class)->convert(
                    $priceAmount,
                    $baseCurrency,
                    $targetCurrency
                );

                return resolve(CurrencyService::class)->format($convertedAmount, $targetCurrency);
            }

            return resolve(CurrencyService::class)->format($priceAmount, $baseCurrency);
        } catch (Exception) {
            // Fallback to base currency if conversion fails
            return resolve(CurrencyService::class)->format($priceAmount, $baseCurrency);
        } catch (Throwable) {
            return resolve(CurrencyService::class)->format($priceAmount, $baseCurrency);
        }
    }

    /**
     * Get price in specific currency as float value
     */
    public function getPriceInCurrency(string $priceType = 'regular_price', string $targetCurrency = 'USD'): float
    {
        $priceAmount = $this->getPriceInBaseCurrency($priceType);
        $baseCurrency = $this->getBaseCurrency();

        if ($targetCurrency === $baseCurrency) {
            return $priceAmount;
        }

        try {
            return resolve(CurrencyService::class)->convert(
                $priceAmount,
                $baseCurrency,
                $targetCurrency
            );
        } catch (Exception) {
            return $priceAmount; // Fallback to base price
        }
    }

    /**
     * Get price in the base currency (properly converted from cents)
     */
    public function getPriceInBaseCurrency(string $priceType = 'regular_price'): float
    {
        $rawPrice = $this->{$priceType};

        if ($rawPrice === null) {
            return 0.0;
        }

        $baseCurrency = $this->getBaseCurrency();

        if ($this->usesZeroDecimalCurrency($baseCurrency)) {
            return (float) $rawPrice;
        }

        // Convert from cents to the main currency unit
        return (float) $rawPrice / 100;
    }

    /**
     * Get the base currency for hosting plan prices.
     * Hosting plans are stored in USD (cents).
     */
    public function getBaseCurrency(): string
    {
        return 'USD';
    }

    public function hostingPlanPriceHistories(): HasMany|self
    {
        return $this->hasMany(HostingPlanPriceHistory::class);
    }

    /**
     * Check if currency uses zero decimal places
     */
    private function usesZeroDecimalCurrency(string $currency): bool
    {
        $currency = mb_strtoupper($currency);

        if ($currency === 'FRW') {
            $currency = 'RWF';
        }

        return $currency === 'RWF';
    }
}
