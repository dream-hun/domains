<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
final class AddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'company' => fake()->optional()->company(),
            'phone_number' => fake()->phoneNumber(),
            'address_line_one' => fake()->streetAddress(),
            'address_line_two' => fake()->optional()->secondaryAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'country_code' => fake()->countryCode(),
            'preferred_currency' => null,
        ];
    }
}
