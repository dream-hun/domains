<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
final class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'domain_id' => null,
            'domain_name' => fake()->domainWord().'.'.fake()->randomElement(['com', 'net', 'org']),
            'domain_type' => fake()->randomElement(['registration', 'renewal', 'transfer']),
            'price' => fake()->randomFloat(2, 5, 50),
            'currency' => 'USD',
            'exchange_rate' => 1.0,
            'quantity' => 1,
            'years' => fake()->numberBetween(1, 5),
            'total_amount' => fn (array $attributes): int|float => $attributes['price'] * $attributes['quantity'],
        ];
    }

    /**
     * Create a registration order item.
     */
    public function registration(): static
    {
        return $this->state(fn (array $attributes): array => [
            'domain_type' => 'registration',
        ]);
    }

    /**
     * Create a renewal order item.
     */
    public function renewal(): static
    {
        return $this->state(fn (array $attributes): array => [
            'domain_type' => 'renewal',
        ]);
    }

    /**
     * Create a transfer order item.
     */
    public function transfer(): static
    {
        return $this->state(fn (array $attributes): array => [
            'domain_type' => 'transfer',
        ]);
    }
}
