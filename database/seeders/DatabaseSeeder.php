<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CountrySeeder::class,
            RolesAndPermissionsSeeder::class,
            RankingConfigurationSeeder::class,
            AllocationConfigurationSeeder::class,
            UserSeeder::class,
            PlayerSeeder::class,
            CourtSeeder::class,
            GameSeeder::class,
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ])->assignRole(Role::SuperAdmin->value);
    }
}
