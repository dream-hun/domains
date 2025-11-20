<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\HostingPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\HostingPromotion>
 */
class HostingPromotionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->addDays($this->faker->numberBetween(0, 10));

        return [
            'uuid' => (string) Str::uuid(),
            'hosting_plan_id' => HostingPlan::factory(),
            'billing_cycle' => $this->faker->randomElement(['monthly', 'quarterly', 'semi-annually', 'annually', 'biennially', 'triennially']),
            'discount_percentage' => $this->faker->randomFloat(2, 5, 80),
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->addDays($this->faker->numberBetween(5, 60)),
        ];
    }
}
