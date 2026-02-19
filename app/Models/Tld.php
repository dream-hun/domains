<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TldStatus;
use App\Enums\TldType;
use App\Helpers\CurrencyHelper;
use App\Services\PriceFormatter;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property-read string $uuid
 * @property-read string $name
 * @property-read string $tld
 * @property-read string $status
 * @property-read string $type
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class Tld extends Model
{
    use HasFactory;

    private const PRICE_TYPE_TO_COLUMN = [
        'register_price' => 'register_price',
        'renewal_price' => 'renew_price',
        'renew_price' => 'renew_price',
        'transfer_price' => 'transfer_price',
    ];

    protected $table = 'tld';

    protected $with = ['tldPricings'];

    protected $guarded = [];

    /**
     * @return HasMany<TldPricing, static>
     */
    public function tldPricings(): HasMany
    {
        return $this->hasMany(TldPricing::class);
    }

    /**
     * Current (active) pricing rows per currency.
     *
     * @return HasMany<TldPricing, static>
     */
    public function currentTldPricings(): HasMany
    {
        return $this->hasMany(TldPricing::class)->current();
    }

    public function isLocalTld(): bool
    {
        return $this->type === TldType::Local;
    }

    public function getPriceForCurrency(string $currencyCode, string $priceType): float
    {
        $pricing = $this->getCurrentPricingForCurrency($currencyCode);

        if (! $pricing instanceof TldPricing) {
            return 0.0;
        }

        $column = self::PRICE_TYPE_TO_COLUMN[$priceType] ?? $priceType;
        $raw = $pricing->{$column};

        if ($raw === null) {
            return 0.0;
        }

        // Prices are stored in minor units (cents for USD/EUR/etc.).
        // Zero-decimal currencies (RWF, JPY, etc.) store the major-unit value directly.
        if ($this->usesZeroDecimalCurrency($currencyCode)) {
            return (float) $raw;
        }

        return (float) $raw / 100;
    }

    public function getFormattedPriceForCurrency(string $priceType, string $currencyCode): string
    {
        $amount = $this->getPriceForCurrency($currencyCode, $priceType);

        return resolve(PriceFormatter::class)->format($amount, $currencyCode);
    }

    public function getFormattedPrice(string $priceType = 'register_price', ?string $targetCurrency = null): string
    {
        if (in_array($targetCurrency, [null, ''], true)) {
            $targetCurrency = CurrencyHelper::getUserCurrency();
        }

        return $this->getFormattedPriceForCurrency($priceType, $targetCurrency);
    }

    public function getBaseCurrency(): string
    {
        if ($this->relationLoaded('tldPricings')) {
            $pricing = $this->tldPricings->where('is_current', true)->first();
            if ($pricing !== null) {
                if (! $pricing->relationLoaded('currency')) {
                    $pricing->loadMissing('currency');
                }

                if ($pricing->currency) {
                    return $pricing->currency->code;
                }
            }
        } else {
            $pricing = $this->currentTldPricings()->with('currency')->first();
            if (null) {
                return $pricing->currency->code;
            }
        }

        return $this->type === TldType::Local ? 'RWF' : 'USD';
    }

    public function getPriceInBaseCurrency(string $priceType): float
    {
        return $this->getPriceForCurrency($this->getBaseCurrency(), $priceType);
    }

    public function getPriceInCurrency(string $priceType, string $targetCurrency): float
    {
        $storedPrice = $this->getPriceForCurrency($targetCurrency, $priceType);

        if ($storedPrice > 0.0) {
            return $storedPrice;
        }

        return $this->getPriceInBaseCurrency($priceType);
    }

    /**
     * Resolve display price with fallback: preferred → app base → TLD base.
     *
     * @return array{amount: float, currency_code: string}
     */
    public function getDisplayPriceForCurrency(string $preferredCurrency, string $priceType): array
    {
        $preferredCurrency = mb_strtoupper($preferredCurrency);
        if ($preferredCurrency === 'FRW') {
            $preferredCurrency = 'RWF';
        }

        $amount = $this->getPriceForCurrency($preferredCurrency, $priceType);
        if ($amount > 0.0) {
            return ['amount' => $amount, 'currency_code' => $preferredCurrency];
        }

        $appBaseCode = Currency::getBaseCurrency()->code;
        $appBaseCode = mb_strtoupper($appBaseCode);
        if ($appBaseCode === 'FRW') {
            $appBaseCode = 'RWF';
        }

        $amount = $this->getPriceForCurrency($appBaseCode, $priceType);
        if ($amount > 0.0) {
            return ['amount' => $amount, 'currency_code' => $appBaseCode];
        }

        $tldBaseCode = $this->getBaseCurrency();
        $tldBaseCode = mb_strtoupper($tldBaseCode);
        if ($tldBaseCode === 'FRW') {
            $tldBaseCode = 'RWF';
        }

        $amount = $this->getPriceInBaseCurrency($priceType);

        return ['amount' => $amount, 'currency_code' => $tldBaseCode];
    }

    public function getFormattedPriceWithFallback(string $priceType, string $preferredCurrency): string
    {
        $resolved = $this->getDisplayPriceForCurrency($preferredCurrency, $priceType);

        return resolve(PriceFormatter::class)->format($resolved['amount'], $resolved['currency_code']);
    }

    #[Scope]
    protected function localTlds(Builder $query): Builder
    {
        return $query->where('type', TldType::Local);
    }

    #[Scope]
    protected function internationalTlds(Builder $query): Builder
    {
        return $query->where('type', TldType::International);
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'type' => TldType::class,
            'status' => TldStatus::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected function tld(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->name,
        );
    }

    private function getCurrentPricingForCurrency(string $currencyCode): ?TldPricing
    {
        $currencyCode = mb_strtoupper($currencyCode);
        if ($currencyCode === 'FRW') {
            $currencyCode = 'RWF';
        }

        $pricings = $this->relationLoaded('tldPricings')
            ? $this->tldPricings->where('is_current', true)
            : $this->currentTldPricings()->with('currency')->get();

        foreach ($pricings as $pricing) {
            if (! $pricing->relationLoaded('currency')) {
                $pricing->loadMissing('currency');
            }

            if ($pricing->currency && mb_strtoupper((string) $pricing->currency->code) === $currencyCode) {
                return $pricing;
            }
        }

        return null;
    }

    private function usesZeroDecimalCurrency(string $code): bool
    {
        $code = mb_strtoupper($code);
        if ($code === 'FRW') {
            $code = 'RWF';
        }

        return in_array($code, ['RWF', 'JPY', 'KRW', 'VND', 'CLP', 'ISK', 'UGX', 'KES', 'TZS'], true);
    }
}
