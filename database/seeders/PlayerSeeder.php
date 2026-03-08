<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Country;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;

final class PlayerSeeder extends Seeder
{
    public function run(): void
    {
        $countryIds = Country::query()->inRandomOrder()->limit(10)->pluck('id');

        for ($i = 0; $i < 30; $i++) {
            $user = User::factory()->create()->assignRole(Role::Player->value);

            Profile::query()->create([
                'player_id' => $user->id,
                'country_id' => $countryIds->random(),
                'date_of_birth' => fake()->dateTimeBetween('-45 years', '-18 years')->format('Y-m-d'),
                'position' => fake()->randomElement(['Point Guard', 'Shooting Guard', 'Small Forward', 'Power Forward', 'Center']),
                'city' => fake()->city(),
                'phone_number' => fake()->phoneNumber(),
                'bio' => fake()->paragraph(),
            ]);
        }
    }
}
