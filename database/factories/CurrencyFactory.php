<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Currency>
 */
final class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => mb_strtoupper(fake()->unique()->lexify('???')),
            'name' => fake()->currencyCode().' Currency',
            'symbol' => fake()->randomElement(['$', '€', '£', '¥', '₹', '₽']),
            'exchange_rate' => fake()->randomFloat(6, 0.1, 10),
            'is_base' => false,
            'is_active' => true,
            'rate_updated_at' => now(),
        ];
    }

    /**
     * Indicate that the currency is the base currency.
     */
    public function base(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_base' => true,
            'exchange_rate' => 1.000000,
        ]);
    }

    /**
     * Indicate that the currency is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
