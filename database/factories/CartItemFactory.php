<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CartItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CartItem>
 */
final class CartItemFactory extends Factory
{
    protected $model = CartItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tlds = ['com', 'net', 'org', 'io', 'co', 'app', 'dev'];
        $tld = fake()->randomElement($tlds);

        return [
            'user_id' => User::factory(),
            'session_id' => null,
            'domain_name' => fake()->domainWord().'.'.$tld,
            'domain_type' => 'registration',
            'tld' => $tld,
            'base_price' => fake()->randomFloat(2, 8, 50),
            'base_currency' => 'USD',
            'eap_fee' => 0,
            'premium_fee' => 0,
            'privacy_fee' => 0,
            'years' => 1,
            'quantity' => 1,
            'attributes' => [
                'added_at' => now()->timestamp,
            ],
        ];
    }

    /**
     * Indicate that the cart item is for a guest (session-based)
     */
    public function forGuest(): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => null,
            'session_id' => fake()->uuid(),
        ]);
    }

    /**
     * Indicate that the cart item is a transfer
     */
    public function transfer(): static
    {
        return $this->state(fn (array $attributes): array => [
            'domain_type' => 'transfer',
        ]);
    }

    /**
     * Indicate that the cart item is a renewal
     */
    public function renewal(): static
    {
        return $this->state(fn (array $attributes): array => [
            'domain_type' => 'renewal',
        ]);
    }

    /**
     * Indicate that the cart item has an EAP fee
     */
    public function withEapFee(): static
    {
        return $this->state(fn (array $attributes): array => [
            'eap_fee' => fake()->randomFloat(2, 50, 500),
        ]);
    }

    /**
     * Indicate that the cart item has a premium fee
     */
    public function withPremiumFee(): static
    {
        return $this->state(fn (array $attributes): array => [
            'premium_fee' => fake()->randomFloat(2, 100, 5000),
        ]);
    }

    /**
     * Indicate that the cart item has privacy protection
     */
    public function withPrivacy(): static
    {
        return $this->state(fn (array $attributes): array => [
            'privacy_fee' => fake()->randomFloat(2, 5, 15),
        ]);
    }

    /**
     * Indicate that the cart item is a premium domain with EAP fee
     */
    public function premiumWithEap(): static
    {
        return $this->state(fn (array $attributes): array => [
            'premium_fee' => fake()->randomFloat(2, 100, 5000),
            'eap_fee' => fake()->randomFloat(2, 50, 500),
        ]);
    }

    /**
     * Set the number of years for the cart item
     */
    public function years(int $years): static
    {
        return $this->state(fn (array $attributes): array => [
            'years' => $years,
        ]);
    }

    /**
     * Set a specific currency for the cart item
     */
    public function currency(string $currency): static
    {
        return $this->state(fn (array $attributes): array => [
            'base_currency' => $currency,
        ]);
    }
}
