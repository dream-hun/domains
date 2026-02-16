<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CountrySeeder::class,
            TldSeeder::class,
            CurrencySeeder::class,
            UserSeeder::class,
            PermissionSeeder::class,
            RolesSeeder::class,
            PermissionRoleSeeder::class,
            RoleUserSeeder::class,
            TldPricingSeeder::class,
            SettingSeeder::class,
            DomainSeeder::class,
            CouponSeeder::class,
            HostingCategorySeeder::class,
            HostingPlanSeeder::class,
            HostingPlanPricingSeeder::class,
            HostingPromotionSeeder::class,
            FeatureCategorySeeder::class,
            HostingFeatureSeeder::class,
            HostingPlanFeatureSeeder::class,
        ]);
    }
}
