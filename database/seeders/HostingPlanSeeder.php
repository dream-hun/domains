<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\HostingPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HostingPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $hostingPlans = [
            [
                'uuid' => Str::uuid(),
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Starter hosting plan',
                'tagline' => 'For personal websites and small businesses',
                'is_popular' => false,
                'status' => 'active',
                'category_id' => 1,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Starter Plus',
                'slug' => 'starter-plus',
                'description' => 'Starter Plus hosting plan',
                'tagline' => 'The ultimate hosting solution for businesses',
                'is_popular' => true,
                'status' => 'active',
                'category_id' => 1,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Starter Pro',
                'slug' => 'starter-pro',
                'description' => 'Starter Pro hosting plan',
                'tagline' => 'Starter Pro hosting plan',
                'is_popular' => false,
                'status' => 'active',
                'category_id' => 1,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Reseller Starter',
                'slug' => 'reseller-starter',
                'description' => 'Reseller Starter hosting plan',
                'tagline' => 'Reseller Starter hosting plan',
                'is_popular' => false,
                'status' => 'active',
                'category_id' => 2,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Reseller Business',
                'slug' => 'reseller-business',
                'description' => 'Reseller Business hosting plan',
                'tagline' => 'Reseller Business hosting plan',
                'is_popular' => true,
                'status' => 'active',
                'category_id' => 2,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Reseller Gold',
                'slug' => 'reseller-gold',
                'description' => 'Reseller Gold hosting plan',
                'tagline' => 'Reseller Gold hosting plan',
                'is_popular' => false,
                'status' => 'active',
                'category_id' => 2,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'VPS Starter',
                'slug' => 'vps-starter',
                'description' => 'VPS Starter hosting plan',
                'tagline' => 'VPS Starter hosting plan',
                'is_popular' => false,
                'status' => 'active',
                'category_id' => 3,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'VPS Business',
                'slug' => 'vps-business',
                'description' => 'VPS Business hosting plan',
                'tagline' => 'VPS Business hosting plan',
                'is_popular' => true,
                'status' => 'active',
                'category_id' => 3,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'VPS Gold',
                'slug' => 'vps-gold',
                'description' => 'VPS Gold hosting plan',
                'tagline' => 'VPS Gold hosting plan',
                'is_popular' => false,
                'status' => 'active',
                'category_id' => 3,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Dedicated Starter',
                'slug' => 'dedicated-starter',
                'description' => 'Dedicated Starter hosting plan',
                'tagline' => 'Dedicated Starter hosting plan',
                'is_popular' => false,
                'status' => 'active',
                'category_id' => 4,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Dedicated Business',
                'slug' => 'dedicated-business',
                'description' => 'Dedicated Business hosting plan',
                'tagline' => 'Dedicated Business hosting plan',
                'is_popular' => true,
                'status' => 'active',
                'category_id' => 4,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Dedicated Gold',
                'slug' => 'dedicated-gold',
                'description' => 'Dedicated Gold hosting plan',
                'tagline' => 'Dedicated Gold hosting plan',
                'is_popular' => false,
                'status' => 'active',
                'category_id' => 4,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Cloud Starter',
                'slug' => 'cloud-starter',
                'description' => 'Cloud Starter hosting plan',
                'tagline' => 'Cloud Starter hosting plan',
                'is_popular' => false,
                'status' => 'active',
                'category_id' => 5,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Cloud Business',
                'slug' => 'cloud-business',
                'description' => 'Cloud Business hosting plan',
                'tagline' => 'Cloud Business hosting plan',
                'is_popular' => true,
                'status' => 'active',
                'category_id' => 5,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Cloud Gold',
                'slug' => 'cloud-gold',
                'description' => 'Cloud Gold hosting plan',
                'tagline' => 'Cloud Gold hosting plan',
                'is_popular' => false,
                'status' => 'active',
                'category_id' => 5,
            ],
        ];

        HostingPlan::query()->insert($hostingPlans);

    }
}
