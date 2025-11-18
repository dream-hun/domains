<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DomainType;
use App\Models\Scopes\DomainPriceScope;
use App\Services\CurrencyService;
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

    private ?int $pendingDomainId = null;

    public function getFormattedPrice(string $priceType = 'register_price', ?string $targetCurrency = null): string
    {
        $priceAmount = $this->getPriceInBaseCurrency($priceType);
        $baseCurrency = $this->getBaseCurrency();

        // If no target currency specified, use user's preferred currency
        if (in_array($targetCurrency, [null, '', '0'], true)) {
            $targetCurrency = app(CurrencyService::class)->getUserCurrency()->code;
        }

        try {
            if ($targetCurrency !== $baseCurrency) {
                $convertedAmount = app(CurrencyService::class)->convert(
                    $priceAmount,
                    $baseCurrency,
                    $targetCurrency
                );

                return app(CurrencyService::class)->format($convertedAmount, $targetCurrency);
            }

            return app(CurrencyService::class)->format($priceAmount, $baseCurrency);
        } catch (Exception) {
            // Fallback to base currency if conversion fails
            return app(CurrencyService::class)->format($priceAmount, $baseCurrency);
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
            return app(CurrencyService::class)->convert(
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
     * Get the base currency for prices stored in database.
     * Local domains are stored in RWF (zero-decimal), international in USD (cents).
     */
    public function getBaseCurrency(): string
    {
        return $this->type === DomainType::Local ? 'RWF' : 'USD';
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected static function booted(): void
    {
        self::created(function (DomainPrice $domainPrice): void {
            if ($domainPrice->pendingDomainId === null) {
                return;
            }

            Domain::query()
                ->whereKey($domainPrice->pendingDomainId)
                ->update(['domain_price_id' => $domainPrice->id]);
        });
    }

    protected function setDomainIdAttribute(int|string|null $value): void
    {
        $this->pendingDomainId = $value !== null ? (int) $value : null;
    }

    private function usesZeroDecimalCurrency(string $currency): bool
    {
        $currency = mb_strtoupper($currency);

        if ($currency === 'FRW') {
            $currency = 'RWF';
        }

        return $currency === 'RWF';
    }
}
