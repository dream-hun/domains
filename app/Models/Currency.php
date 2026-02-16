<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\PriceFormatter;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

/**
 * @property-read int $id
 * @property-read string $uuid
 * @property-read string $code
 * @property-read string $name
 * @property-read bool $is_active
 * @property-read bool $is_base
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 **/
final class Currency extends Model
{
    use HasFactory;

    /**
     * Currencies that don't use decimal places.
     *
     * @var array<int, string>
     */
    private const NO_DECIMAL_CURRENCIES = [
        'RWF', 'JPY', 'KRW', 'VND', 'CLP', 'ISK', 'UGX', 'KES', 'TZS',
    ];

    protected $guarded = [];

    protected $casts = [
        'is_base' => 'boolean',
        'is_active' => 'boolean',
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

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Format amount with currency symbol.
     */
    public function format(float $amount): string
    {
        try {
            return resolve(PriceFormatter::class)->format($amount, $this->code);
        } catch (Throwable) {
            $decimals = $this->getDecimalPlaces($amount);

            return ($this->symbol ?? $this->code.' ').number_format(round($amount, $decimals), $decimals);
        }
    }

    protected static function booted(): void
    {
        self::creating(function (Currency $currency): void {
            if (empty($currency->uuid)) {
                $currency->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the appropriate number of decimal places for this currency.
     */
    private function getDecimalPlaces(float $amount): int
    {
        if (in_array($this->code, self::NO_DECIMAL_CURRENCIES, true)) {
            return 0;
        }

        if (abs($amount - round($amount)) < 0.01) {
            return 0;
        }

        return 2;
    }
}
