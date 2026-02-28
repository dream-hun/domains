<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Country;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
final class ProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'player_id' => User::factory(),
            'date_of_birth' => fake()->date(max: '-18 years'),
            'country_id' => Country::factory(),
            'city' => fake()->city(),
            'phone_number' => fake()->phoneNumber(),
            'bio' => fake()->paragraph(),
            'position' => fake()->randomElement(['Point Guard', 'Shooting Guard', 'Small Forward', 'Power Forward', 'Center']),
        ];
    }
}
