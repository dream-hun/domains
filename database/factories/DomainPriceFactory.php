<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DomainType;
use App\Models\DomainPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DomainPrice>
 */
final class DomainPriceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'tld' => '.'.fake()->unique()->lexify('????'),
            'register_price' => fake()->numberBetween(1000, 10000),
            'renewal_price' => fake()->numberBetween(1000, 10000),
            'transfer_price' => fake()->numberBetween(1000, 10000),
            'type' => fake()->randomElement(DomainType::cases()),
            'status' => 'active',
        ];
    }

    public function local(): self
    {
        return $this->state(fn (array $attributes): array => [
            'type' => DomainType::Local,
            'tld' => '.rw',
        ]);
    }

    public function international(): self
    {
        return $this->state(fn (array $attributes): array => [
            'type' => DomainType::International,
            'tld' => '.com',
        ]);
    }

    public function com(): self
    {
        return $this->international();
    }
}
