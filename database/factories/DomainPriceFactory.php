<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DomainType;
use App\Models\DomainPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DomainPrice>
 */
final class DomainPriceFactory extends Factory
{
    protected $model = DomainPrice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'tld' => '.'.fake()->word(),
            'type' => fake()->randomElement(DomainType::cases()),
            'register_price' => fake()->numberBetween(1000, 5000), // $10.00 to $50.00 in cents
            'renewal_price' => fake()->numberBetween(1000, 5000),
            'transfer_price' => fake()->numberBetween(1000, 3000),
            'redemption_price' => fake()->numberBetween(3000, 10000),
            'min_years' => 1,
            'max_years' => 10,
            'status' => 'active',
            'description' => fake()->sentence(),
        ];
    }

    /**
     * Create a .com domain price.
     */
    public function com(): static
    {
        return $this->state(fn (array $attributes) => [
            'tld' => '.com',
            'type' => DomainType::International,
            'register_price' => 1500, // $15.00
            'renewal_price' => 1500,
            'transfer_price' => 1200,
        ]);
    }

    /**
     * Create a .rw domain price.
     */
    public function rw(): static
    {
        return $this->state(fn (array $attributes) => [
            'tld' => '.rw',
            'type' => DomainType::Local,
            'register_price' => 2000, // 2000 RWF
            'renewal_price' => 2000,
            'transfer_price' => 1500,
        ]);
    }

    /**
     * Create an international domain type.
     */
    public function international(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DomainType::International,
        ]);
    }

    /**
     * Create a local domain type.
     */
    public function local(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DomainType::Local,
        ]);
    }
}
