<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            PermissionSeeder::class,
            RolesSeeder::class,
            PermissionRoleSeeder::class,
            RoleUserSeeder::class,
            DomainPricingSeeder::class,
            SettingSeeder::class,
            DomainPriceSeeder::class,
            DomainSeeder::class,
            CurrencySeeder::class,
        ]);
    }
}
