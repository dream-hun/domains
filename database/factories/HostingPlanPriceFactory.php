<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HostingPlanPrice>
 */
class HostingPlanPriceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'hosting_plan_id' => \App\Models\HostingPlan::factory(),
            'billing_cycle' => $this->faker->randomElement(['monthly', 'quarterly', 'semi-annually', 'annually', 'biennially', 'triennially']),
            'regular_price' => $this->faker->numberBetween(1000, 50000),
            'promotional_price' => $this->faker->optional()->numberBetween(500, 45000),
            'renewal_price' => $this->faker->numberBetween(1000, 50000),
            'discount_percentage' => $this->faker->optional()->numberBetween(0, 100),
            'promotional_start_date' => $this->faker->optional()->date(),
            'promotional_end_date' => $this->faker->optional()->date(),
            'status' => \App\Enums\Hosting\HostingPlanPriceStatus::Active->value,
        ];
    }
}
