<?php

declare(strict_types=1);

namespace Database\Factories;

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
            'name' => fake()->domainName(),
            'uuid' => Str::uuid(),
            'owner_id' => User::factory(),
            'registered_at' => now(),
            'expires_at' => now()->addYear(),
            'status' => 'active',
            'is_locked' => false,
            'last_renewed_at' => null,
            'provider' => null,
            'auto_renew' => false,
        ];
    }
}
