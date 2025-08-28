<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DomainPrice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Domain>
 */
final class DomainFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'name' => $this->faker->domainName(),
            'auth_code' => Str::random(12),
            'registrar' => $this->faker->randomElement(['Namecheap', 'Godaddy', 'Local Registry']),
            'provider' => $this->faker->randomElement(['namecheap', 'epp']),
            'years' => $this->faker->numberBetween(1, 5),
            'status' => $this->faker->randomElement(['active', 'pending', 'transfer_pending', 'expired']),
            'auto_renew' => $this->faker->boolean(),
            'is_premium' => $this->faker->boolean(10), // 10% chance of being premium
            'is_locked' => $this->faker->boolean(80), // 80% chance of being locked
            'registered_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'expires_at' => $this->faker->dateTimeBetween('now', '+2 years'),
            'last_renewed_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'domain_price_id' => DomainPrice::factory(),
            'owner_id' => User::factory(),
        ];
    }

    public function rwDomain(): self
    {
        return $this->state(function (array $attributes): array {
            return [
                'name' => $this->faker->unique()->word().'.rw',
                'provider' => 'epp',
                'registrar' => 'Local Registry',
            ];
        });
    }

    public function comDomain(): self
    {
        return $this->state(function (array $attributes): array {
            return [
                'name' => $this->faker->unique()->word().'.com',
                'provider' => 'namecheap',
                'registrar' => 'Namecheap',
            ];
        });
    }

    public function active(): self
    {
        return $this->state(function (array $attributes): array {
            return [
                'status' => 'active',
                'expires_at' => $this->faker->dateTimeBetween('now', '+2 years'),
            ];
        });
    }

    public function expired(): self
    {
        return $this->state(function (array $attributes): array {
            return [
                'status' => 'expired',
                'expires_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            ];
        });
    }
}
