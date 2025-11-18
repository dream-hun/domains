<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Hosting\HostingPlanStatus;
use App\Models\HostingCategory;
use App\Models\HostingPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HostingPlan>
 */
class HostingPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => $this->faker->unique()->words(2, true),
            'slug' => $this->faker->unique()->slug(),
            'description' => $this->faker->sentence(),
            'tagline' => $this->faker->sentence(8),
            'is_popular' => $this->faker->boolean(),
            'status' => $this->faker->randomElement(HostingPlanStatus::cases())->value,
            'sort_order' => $this->faker->numberBetween(1, 100),
            'category_id' => HostingCategory::factory(),
        ];
    }
}
