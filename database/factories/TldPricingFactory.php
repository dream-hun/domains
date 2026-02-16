<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Tld;
use App\Models\TldPricing;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TldPricing>
 */
final class TldPricingFactory extends Factory
{
    protected $model = TldPricing::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'tld_id' => Tld::factory(),
            'currency_id' => Currency::factory(),
            'register_price' => fake()->numberBetween(1000, 20000),
            'renew_price' => fake()->numberBetween(1000, 20000),
            'transfer_price' => fake()->numberBetween(500, 10000),
            'redemption_price' => null,
            'is_current' => true,
            'effective_date' => now(),
        ];
    }
}
