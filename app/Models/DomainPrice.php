<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DomainType;
use App\Models\Scopes\DomainPriceScope;
use Exception;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ScopedBy(DomainPriceScope::class)]
final class DomainPrice extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'register_price' => 'integer',
        'renewal_price' => 'integer',
        'transfer_price' => 'integer',
        'redemption_price' => 'integer',
        'grace_period' => 'integer',
        'type' => DomainType::class,
    ];

    public function getFormattedPrice(string $priceType = 'register_price', ?string $targetCurrency = null): string
    {
        $priceAmount = $this->getPriceInBaseCurrency($priceType);
        $baseCurrency = $this->getBaseCurrency();

        // If no target currency specified, use user's preferred currency
        if (in_array($targetCurrency, [null, '', '0'], true)) {
            $targetCurrency = app(\App\Services\CurrencyService::class)->getUserCurrency()->code;
        }

        try {
            if ($targetCurrency !== $baseCurrency) {
                $convertedAmount = app(\App\Services\CurrencyService::class)->convert(
                    $priceAmount,
                    $baseCurrency,
                    $targetCurrency
                );

                return app(\App\Services\CurrencyService::class)->format($convertedAmount, $targetCurrency);
            }

            return app(\App\Services\CurrencyService::class)->format($priceAmount, $baseCurrency);
        } catch (Exception $e) {
            // Fallback to base currency if conversion fails
            return app(\App\Services\CurrencyService::class)->format($priceAmount, $baseCurrency);
        }
    }

    /**
     * Get price in specific currency as float value
     */
    public function getPriceInCurrency(string $priceType = 'register_price', string $targetCurrency = 'USD'): float
    {
        $priceAmount = $this->getPriceInBaseCurrency($priceType);
        $baseCurrency = $this->getBaseCurrency();

        if ($targetCurrency === $baseCurrency) {
            return $priceAmount;
        }

        try {
            return app(\App\Services\CurrencyService::class)->convert(
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
    public function getPriceInBaseCurrency(string $priceType = 'register_price'): float
    {
        $priceInCents = $this->{$priceType};

        // Convert from cents to the main currency unit
        return $priceInCents / 100;
    }

    /**
     * Get the base currency for this domain type
     */
    public function getBaseCurrency(): string
    {
        return $this->type === DomainType::Local ? 'RWF' : 'USD';
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
