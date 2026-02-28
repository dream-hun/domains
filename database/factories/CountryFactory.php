<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Country>
 */
final class CountryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $continents = ['Asia', 'Europe', 'Africa', 'America', 'Atlantic'];

        return [
            'uuid' => Str::uuid(),
            'iso_code' => fake()->unique()->lexify('???'),
            'iso_alpha2' => fake()->unique()->lexify('??'),
            'name' => fake()->country(),
            'capital' => fake()->city(),
            'region' => fake()->randomElement($continents),
            'flag' => fake()->imageUrl(),
        ];
    }
}
