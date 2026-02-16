<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\HostingPlan;
use App\Models\HostingPlanPrice;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
final class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plan = HostingPlan::factory()->create();
        $planPrice = HostingPlanPrice::factory()->create(['hosting_plan_id' => $plan->id]);

        $startsAt = $this->faker->dateTimeBetween('-1 month', 'now');
        $expiresAt = (clone $startsAt)->modify('+1 month');

        return [
            'uuid' => $this->faker->uuid(),
            'user_id' => User::factory(),
            'hosting_plan_id' => $plan->id,
            'hosting_plan_pricing_id' => $planPrice->id,
            'product_snapshot' => [
                'plan' => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                ],
                'price' => [
                    'id' => $planPrice->id,
                    'regular_price' => $planPrice->regular_price,
                    'renewal_price' => $planPrice->renewal_price,
                    'billing_cycle' => $planPrice->billing_cycle,
                ],
            ],
            'billing_cycle' => $this->faker->randomElement(['monthly', 'quarterly', 'annually']),
            'domain' => $this->faker->optional()->domainName(),
            'status' => 'active',
            'auto_renew' => false,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'next_renewal_at' => $expiresAt,
            'provider_resource_id' => null,
            'last_renewal_attempt_at' => null,
            'cancelled_at' => null,
        ];
    }

    /**
     * Indicate that the subscription is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'active',
            'cancelled_at' => null,
        ]);
    }

    /**
     * Indicate that the subscription is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'expired',
            'expires_at' => $this->faker->dateTimeBetween('-1 month', '-1 day'),
        ]);
    }

    /**
     * Indicate that the subscription is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'cancelled',
            'cancelled_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the subscription has auto-renewal enabled.
     */
    public function autoRenew(): static
    {
        return $this->state(fn (array $attributes): array => [
            'auto_renew' => true,
        ]);
    }
}
