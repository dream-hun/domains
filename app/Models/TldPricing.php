<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property-read string $uuid
 * @property-read int|null $tld_id
 * @property-read int $currency_id
 * @property-read int $register_price
 * @property-read int $renew_price
 * @property-read int|null $redemption_price
 * @property-read int|null $transfer_price
 * @property-read bool $is_current
 * @property-read CarbonInterface $effective_date
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
class TldPricing extends Model
{
    use HasFactory;

    protected $table = 'tld_pricing';

    protected $guarded = [];

    protected $with = ['currency'];

    public function tld(): BelongsTo
    {
        return $this->belongsTo(Tld::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * @return HasMany<DomainPriceHistory, static>
     */
    public function domainPriceHistories(): HasMany
    {
        return $this->hasMany(DomainPriceHistory::class, 'tld_pricing_id');
    }

    #[Scope]
    protected function current(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }

    protected function casts(): array
    {
        return [
            'register_price' => 'integer',
            'renew_price' => 'integer',
            'redemption_price' => 'integer',
            'transfer_price' => 'integer',
            'is_current' => 'boolean',
            'effective_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
