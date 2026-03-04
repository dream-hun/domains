<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
final class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'order_id' => Order::factory(),
            'stripe_session_id' => fake()->optional()->uuid(),
            'stripe_payment_intent_id' => fake()->optional()->uuid(),
            'stripe_charge_id' => fake()->optional()->uuid(),
            'kpay_transaction_id' => fake()->optional()->uuid(),
            'kpay_ref_id' => fake()->optional()->uuid(),
            'amount' => fake()->numberBetween(1000, 100000),
            'currency' => fake()->randomElement(['USD', 'RWF']),
            'status' => 'pending',
            'payment_method' => fake()->randomElement(['stripe', 'kpay']),
            'metadata' => null,
            'paid_at' => null,
            'attempt_number' => 1,
            'failure_details' => null,
            'last_attempted_at' => null,
        ];
    }

    /**
     * Indicate that the payment succeeded.
     */
    public function succeeded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'succeeded',
            'paid_at' => now(),
        ]);
    }

    /**
     * Indicate that the payment failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'failed',
            'failure_details' => [
                'code' => 'card_declined',
                'message' => 'Your card was declined.',
            ],
            'last_attempted_at' => now(),
        ]);
    }
}
