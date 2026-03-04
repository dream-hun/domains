<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Allocation;
use App\Models\AllocationConfiguration;
use App\Models\Game;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Allocation>
 */
final class AllocationFactory extends Factory
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
            'player_id' => User::factory(),
            'total_amount' => 1.00,
            'insurance_amount' => 0.25,
            'savings_amount' => 0.25,
            'pathway_amount' => 0.25,
            'administration_amount' => 0.25,
            'allocation_configuration_id' => AllocationConfiguration::factory(),
        ];
    }
}
