<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Nameserver;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Nameserver>
 */
final class NameserverFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'name' => $this->faker->unique()->domainName(),
            'type' => 'default',
            'ipv4' => $this->faker->ipv4(),
            'ipv6' => $this->faker->ipv6(),
            'priority' => $this->faker->numberBetween(1, 10),
            'status' => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'inactive',
        ]);
    }

    public function priority(int $priority): static
    {
        return $this->state(fn (array $attributes): array => [
            'priority' => $priority,
        ]);
    }
}
