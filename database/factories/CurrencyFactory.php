<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
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
            'is_base' => false,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the currency is the base currency.
     */
    public function base(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_base' => true,
        ]);
    }

    /**
     * Indicate that the currency is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
