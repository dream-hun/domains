<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\HostingFeature;
use App\Models\HostingPlan;
use App\Models\HostingPlanFeature;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HostingPlanFeature>
 */
class HostingPlanFeatureFactory extends Factory
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
            'hosting_plan_id' => HostingPlan::factory(),
            'hosting_feature_id' => HostingFeature::factory(),
            'feature_value' => $this->faker->optional()->randomElement(['100 GB', 'Unlimited', '50', 'true']),
            'is_unlimited' => $this->faker->boolean(20),
            'custom_text' => $this->faker->optional()->sentence(),
            'is_included' => $this->faker->boolean(90),
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }
}
