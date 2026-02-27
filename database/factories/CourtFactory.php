<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CourtStatus;
use App\Models\Court;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Court>
 */
final class CourtFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /** @var CourtStatus $status */
        $status = fake()->randomElement(CourtStatus::cases());

        return [
            'uuid' => Str::uuid(),
            'name' => fake()->company().' Court',
            'country' => fake()->country(),
            'city' => fake()->city(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'status' => $status->value,
            'created_by' => User::factory(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CourtStatus::ACTIVE->value,
        ]);
    }

    public function pilot(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CourtStatus::PILOT->value,
        ]);
    }

    public function priority(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CourtStatus::PRIORITY->value,
        ]);
    }
}
