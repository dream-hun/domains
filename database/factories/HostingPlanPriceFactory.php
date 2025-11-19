<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Hosting\HostingPlanPriceStatus;
use App\Models\HostingPlan;
use App\Models\HostingPlanPrice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HostingPlanPrice>
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
            'uuid' => (string) Str::uuid(),
            'hosting_plan_id' => HostingPlan::factory(),
            'billing_cycle' => $this->faker->randomElement(['monthly', 'quarterly', 'semi-annually', 'annually', 'biennially', 'triennially']),
            'regular_price' => $this->faker->numberBetween(1000, 50000),
            'promotional_price' => $this->faker->optional()->numberBetween(500, 45000),
            'renewal_price' => $this->faker->numberBetween(1000, 50000),
            'discount_percentage' => $this->faker->optional()->numberBetween(0, 100),
            'promotional_start_date' => $this->faker->optional()->date(),
            'promotional_end_date' => $this->faker->optional()->date(),
            'status' => HostingPlanPriceStatus::Active->value,
        ];
    }
}
