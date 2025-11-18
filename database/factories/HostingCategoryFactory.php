<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Hosting\CategoryStatus;
use App\Models\HostingCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HostingCategory>
 */
class HostingCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'icon' => $this->faker->optional()->url(),
            'description' => $this->faker->optional()->sentence(),
            'status' => $this->faker->randomElement(CategoryStatus::cases())->value,
            'sort' => $this->faker->numberBetween(0, 100),
        ];
    }
}
