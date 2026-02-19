<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Hosting\HostingPlanPriceStatus;
use App\Helpers\CurrencyHelper;
use Carbon\CarbonInterface;
use Database\Factories\HostingPlanPriceFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property-read string $uuid
 * @property-read int $hosting_plan_id
 * @property-read int $currency_id
 * @property-read string $billing_cycle
 * @property-read float $regular_price
 * @property-read float $renewal_price
 * @property-read HostingPlanPriceStatus $status
 * @property-read bool $is_current
 * @property-read CarbonInterface $effective_date
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
class HostingPlanPrice extends Model
{
    /** @use HasFactory<HostingPlanPriceFactory> */
    use HasFactory;

    protected $table = 'hosting_plan_pricing';

    protected $guarded = [];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(HostingPlan::class, 'hosting_plan_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Get formatted price string with currency symbol.
     */
    public function getFormattedPrice(string $priceType = 'regular_price', ?string $targetCurrency = null): string
    {
        $priceAmount = $this->getPriceInBaseCurrency($priceType);

        if (! in_array($targetCurrency, [null, '', '0'], true)) {
            $displayCurrency = $targetCurrency;
        } else {
            $displayCurrency = CurrencyHelper::getUserCurrency();
        }

        return CurrencyHelper::formatMoney($priceAmount, $displayCurrency);
    }

    /**
     * Get price in specific currency. Returns base price.
     */
    public function getPriceInCurrency(string $priceType = 'regular_price'): float
    {
        return $this->getPriceInBaseCurrency($priceType);
    }

    /**
     * Get price in the base currency.
     * Prices are stored in major units (e.g., 10.99 for $10.99).
     */
    public function getPriceInBaseCurrency(string $priceType = 'regular_price'): float
    {
        $rawPrice = $this->{$priceType};

        if ($rawPrice === null) {
            return 0.0;
        }

        return (float) $rawPrice;
    }

    /**
     * Get the base currency for this hosting plan price.
     */
    public function getBaseCurrency(): string
    {
        return $this->currency?->code ?? 'USD';
    }

    public function hostingPlanPriceHistories(): HasMany
    {
        return $this->hasMany(HostingPlanPriceHistory::class, 'hosting_plan_pricing_id');
    }

    protected function casts(): array
    {
        return [
            'regular_price' => 'float',
            'renewal_price' => 'float',
            'status' => HostingPlanPriceStatus::class,
            'is_current' => 'boolean',
            'effective_date' => 'date',
        ];
    }

    #[Scope]
    protected function current(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }
}
