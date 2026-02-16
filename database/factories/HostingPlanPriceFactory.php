<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Hosting\BillingCycle;
use App\Enums\Hosting\HostingPlanPriceStatus;
use App\Models\Currency;
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
            'currency_id' => Currency::factory(),
            'billing_cycle' => $this->faker->randomElement(BillingCycle::values()),
            'regular_price' => $this->faker->randomFloat(2, 10, 500),
            'renewal_price' => $this->faker->randomFloat(2, 10, 500),
            'status' => HostingPlanPriceStatus::Active->value,
            'is_current' => true,
            'effective_date' => now()->format('Y-m-d'),
        ];
    }
}
