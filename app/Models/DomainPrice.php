<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DomainType;
use App\Models\Scopes\DomainPriceScope;
use App\Services\CurrencyService;
use Cknow\Money\Money;
use Exception;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Throwable;

#[ScopedBy(DomainPriceScope::class)]
final class DomainPrice extends Model
{
    use HasFactory;

    protected $guarded = [];

    private ?int $pendingDomainId = null;

    public function getFormattedPrice(string $priceType = 'register_price', ?string $targetCurrency = null): string
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
    public function getPriceInCurrency(string $priceType = 'register_price', string $targetCurrency = 'USD'): float
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
        } catch (Throwable) {
            return $priceAmount;
        }
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function formatRegistrationPrice(): Money
    {
        $currency = $this->getBaseCurrency();

        return match ($currency) {
            'RWF' => Money::RWF($this->register_price),
            default => Money::USD($this->register_price),
        };
    }

    public function formatRenewalPrice(): Money
    {
        $currency = $this->getBaseCurrency();

        return match ($currency) {
            'RWF' => Money::RWF($this->renewal_price),
            default => Money::USD($this->renewal_price),
        };
    }

    public function formatTransferPrice(): Money
    {
        $currency = $this->getBaseCurrency();

        return match ($currency) {
            'RWF' => Money::RWF($this->transfer_price),
            default => Money::USD($this->transfer_price),
        };
    }

    public function formatRedemptionPrice(): Money
    {
        $currency = $this->getBaseCurrency();

        return match ($currency) {
            'RWF' => Money::RWF($this->redemption_price),
            default => Money::USD($this->redemption_price),
        };
    }

    /**
     * Get the base currency for prices stored in database.
     * Local domains are stored in RWF (zero-decimal), international in USD (cents).
     */
    public function getBaseCurrency(): string
    {
        return $this->type === DomainType::Local ? 'RWF' : 'USD';
    }

    public function domainPriceHistories(): HasMany
    {
        return $this->hasMany(DomainPriceHistory::class);
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

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'register_price' => 'integer',
            'renewal_price' => 'integer',
            'transfer_price' => 'integer',
            'redemption_price' => 'integer',
            'grace_period' => 'integer',
            'type' => DomainType::class,
        ];
    }
}
