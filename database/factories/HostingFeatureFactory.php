<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\FeatureCategory;
use App\Models\HostingFeature;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HostingFeature>
 */
class HostingFeatureFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(2, true);

        return [
            'uuid' => (string) Str::uuid(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->optional()->sentence(),
            'icon' => $this->faker->optional()->randomElement(['bi bi-check', 'bi bi-star', 'bi bi-shield']),
            'category' => $this->faker->randomElement(['resources', 'security', 'email', 'performance']),
            'feature_category_id' => FeatureCategory::factory(),
            'value_type' => $this->faker->randomElement(['boolean', 'numeric', 'text', 'unlimited']),
            'unit' => $this->faker->optional()->randomElement(['GB', 'accounts', 'websites', 'MB']),
            'sort_order' => $this->faker->numberBetween(0, 100),
            'is_highlighted' => $this->faker->boolean(20),
        ];
    }
}
