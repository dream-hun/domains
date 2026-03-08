<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\GameModeration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameModeration>
 */
final class GameModerationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'moderator_id' => User::factory(),
            'status' => GameStatus::Approved,
            'reason' => fake()->sentence(),
            'is_override' => false,
        ];
    }
}
