<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\HostingCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HostingCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'uuid' => Str::uuid(),
                'name' => 'Shared Hosting',
                'slug' => 'shared-hosting',
                'icon' => 'bi bi-share',
                'description' => 'Shared hosting is a type of hosting where multiple websites share the same server resources.',
                'status' => 'active',
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Reseller Hosting',
                'slug' => 'reseller-hosting',
                'icon' => 'bi bi-person-lines-fill',
                'description' => 'Reseller hosting is a type of hosting where multiple websites share the same server resources.',
                'status' => 'active',
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'VPS Hosting',
                'slug' => 'vps-hosting',
                'icon' => 'bi bi-server',
                'description' => 'VPS hosting is a type of hosting where multiple websites share the same server resources.',
                'status' => 'active',
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Dedicated Hosting',
                'slug' => 'dedicated-hosting',
                'icon' => 'bi bi-cpu',
                'description' => 'Dedicated hosting is a type of hosting where multiple websites share the same server resources.',
                'status' => 'active',
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Cloud Hosting',
                'slug' => 'cloud-hosting',
                'icon' => 'bi bi-cloud',
                'description' => 'Cloud hosting is a type of hosting where multiple websites share the same server resources.',
                'status' => 'active',
            ],
        ];
        HostingCategory::query()->insert($categories);
    }
}
