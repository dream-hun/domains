<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Hosting\CategoryStatus;
use App\Models\FeatureCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FeatureCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [

                'uuid' => Str::uuid(),
                'name' => 'Features',
                'slug' => 'features',
                'description' => 'Features',
                'icon' => 'bi bi-stars',
                'status' => CategoryStatus::Active->value,
                'sort_order' => 1,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Tech Specs',
                'slug' => 'tech-specs',
                'description' => 'Tech Specs',
                'icon' => 'bi bi-cpu',
                'status' => CategoryStatus::Active->value,
                'sort_order' => 2,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Benefits',
                'slug' => 'benefits',
                'description' => 'Benefits',
                'icon' => 'bi bi-heart',
                'status' => CategoryStatus::Active->value,
                'sort_order' => 3,
            ],
        ];
        FeatureCategory::query()->insert($categories);
    }
}
