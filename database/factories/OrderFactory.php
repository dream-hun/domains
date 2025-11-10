<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
final class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $totalAmount = $this->faker->randomFloat(2, 10, 500);

        return [
            'user_id' => User::factory(),
            'order_number' => Order::generateOrderNumber(),
            'status' => $this->faker->randomElement(['pending', 'processing', 'completed', 'cancelled']),
            'payment_method' => $this->faker->randomElement(['stripe', 'mtn_mobile_money']),
            'payment_status' => $this->faker->randomElement(['pending', 'paid', 'failed', 'cancelled', 'refunded']),
            'stripe_payment_intent_id' => $this->faker->optional()->uuid(),
            'stripe_session_id' => $this->faker->optional()->uuid(),
            'subtotal' => $totalAmount,
            'total_amount' => $totalAmount,
            'currency' => 'USD',
            'billing_email' => $this->faker->email(),
            'billing_name' => $this->faker->name(),
            'billing_address' => $this->faker->address(),
            'billing_city' => $this->faker->city(),
            'billing_country' => $this->faker->country(),
            'billing_postal_code' => $this->faker->postcode(),
            'notes' => $this->faker->optional()->sentence(),
            'processed_at' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the order is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'payment_status' => 'paid',
            'status' => 'processing',
            'processed_at' => now(),
        ]);
    }

    /**
     * Indicate that the order is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the order failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'payment_status' => 'failed',
            'status' => 'cancelled',
        ]);
    }
}
