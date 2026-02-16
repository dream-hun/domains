<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Tld;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Tld>
 */
final class DomainPriceFactory extends Factory
{
    private const PRICE_COLUMNS = [
        'register_price', 'renewal_price', 'transfer_price', 'redemption_price',
    ];

    public function create($attributes = [], ?Model $parent = null)
    {
        $priceData = [];
        if (is_array($attributes)) {
            foreach (self::PRICE_COLUMNS as $col) {
                if (array_key_exists($col, $attributes)) {
                    $priceData[$col] = $attributes[$col];
                    unset($attributes[$col]);
                }
            }
        }

        $instance = parent::create($attributes, $parent);

        if ($priceData !== []) {
            $this->createPriceCurrenciesFromData($instance, $priceData);
        }

        return $instance;
    }

    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'tld' => '.'.fake()->unique()->lexify('????'),
            'status' => 'active',
            'min_years' => 1,
            'max_years' => 10,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Tld $domainPrice): void {
            if ($domainPrice->domainPriceCurrencies()->exists()) {
                return;
            }

            $this->createPriceCurrenciesFromData($domainPrice, [
                'register_price' => fake()->numberBetween(1000, 10000),
                'renewal_price' => fake()->numberBetween(1000, 10000),
                'transfer_price' => fake()->numberBetween(1000, 10000),
            ]);
        });
    }

    public function withRwf(): self
    {
        return $this->state(fn (array $attributes): array => [
            'tld' => '.rw',
        ]);
    }

    public function withUsd(): self
    {
        return $this->state(fn (array $attributes): array => [
            'tld' => '.com',
        ]);
    }

    public function local(): self
    {
        return $this->withRwf();
    }

    public function international(): self
    {
        return $this->withUsd();
    }

    public function com(): self
    {
        return $this->withUsd();
    }

    private function createPriceCurrenciesFromData(Tld $domainPrice, array $priceData): void
    {
        $baseCode = $priceData['currency'] ?? ($domainPrice->isLocalTld() ? 'RWF' : 'USD');
        $currency = Currency::query()->where('code', $baseCode)->first();
        if (! $currency instanceof Currency) {
            return;
        }

        $domainPrice->domainPriceCurrencies()->delete();

        $register = (float) ($priceData['register_price'] ?? ($baseCode === 'USD' ? 20.0 : 26000.0));
        $renewal = (float) ($priceData['renewal_price'] ?? ($baseCode === 'USD' ? 20.0 : 26000.0));
        $transfer = (float) ($priceData['transfer_price'] ?? ($baseCode === 'USD' ? 20.0 : 26000.0));
        $redemption = isset($priceData['redemption_price']) ? (float) $priceData['redemption_price'] : null;

        $round = $baseCode === 'USD' ? fn (float $v): float => round($v, 2) : fn (float $v): float => round($v, 0);

        $domainPrice->domainPriceCurrencies()->create([
            'currency_id' => $currency->id,
            'registration_price' => $round($register),
            'renewal_price' => $round($renewal),
            'transfer_price' => $round($transfer),
            'redemption_price' => $redemption !== null ? $round($redemption) : null,
            'is_current' => true,
            'effective_date' => now()->toDateString(),
        ]);
    }
}
