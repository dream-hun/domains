<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Game;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Game>
 */
final class GameFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'title' => fake()->sentence(3),
            'format' => fake()->randomElement(['1v1', '2v2', '3v3', '4v4', '5v5']),
            'court_id' => null,
            'player_id' => User::factory(),
            'played_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'status' => 'pending',
            'vimeo_uri' => null,
            'vimeo_status' => null,
        ];
    }
}
