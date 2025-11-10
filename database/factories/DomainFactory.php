<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Domain;
use App\Models\DomainPrice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Domain>
 */
final class DomainFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->domainName(),
            'uuid' => Str::uuid(),
            'owner_id' => User::factory(),
            'domain_price_id' => DomainPrice::factory(),
            'registered_at' => now(),
            'expires_at' => now()->addYear(),
            'status' => 'active',
            'is_locked' => false,
            'last_renewed_at' => null,
            'provider' => null,
            'auto_renew' => false,
        ];
    }

    public function rwDomain(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => fake()->unique()->domainWord().'.rw',
        ])->for(DomainPrice::factory()->local(), 'domainPrice');
    }

    public function comDomain(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => fake()->unique()->domainWord().'.com',
        ])->for(DomainPrice::factory()->international(), 'domainPrice');
    }
}
