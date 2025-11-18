<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Hosting\CategoryStatus;
use App\Models\FeatureCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FeatureCategory>
 */
class FeatureCategoryFactory extends Factory
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
            'sort_order' => $this->faker->numberBetween(0, 100),
            'icon' => $this->faker->optional()->randomElement(['bi bi-stars', 'bi bi-cpu', 'bi bi-heart', 'bi bi-shield']),
            'status' => $this->faker->randomElement(CategoryStatus::cases())->value,
        ];
    }
}
