<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Admin\Allocation\CreateAllocation;
use App\Actions\Ranking\CalculateRankingsAction;
use App\Enums\GameStatus;
use App\Enums\ResultStatus;
use App\Enums\Role;
use App\Models\Court;
use App\Models\Game;
use App\Models\GameModeration;
use App\Models\RankingConfiguration;
use App\Models\User;
use Illuminate\Database\Seeder;

final class GameSeeder extends Seeder
{
    public function run(): void
    {
        $players = User::role(Role::Player->value)->get();
        $moderators = User::role(Role::Moderator->value)->get();
        $courts = Court::query()->get();
        $config = RankingConfiguration::query()->latest('id')->firstOrFail();
        $createAllocation = app(CreateAllocation::class);
        $formats = ['1v1', '2v2', '3v3', '4v4', '5v5'];

        // Step A — Approved games (60 total, 12 per format)
        $rejectedGames = collect();

        foreach ($formats as $format) {
            for ($i = 0; $i < 12; $i++) {
                $playedAt = $i < 6
                    ? fake()->dateTimeBetween('-30 days', 'now')
                    : fake()->dateTimeBetween('-1 year', '-31 days');

                $game = Game::factory()->create([
                    'player_id' => $players->random()->id,
                    'format' => $format,
                    'status' => GameStatus::Approved->value,
                    'result' => fake()->randomElement([ResultStatus::WIN->value, ResultStatus::LOST->value]),
                    'court_id' => rand(1, 10) > 3 ? $courts->random()->id : null,
                    'played_at' => $playedAt,
                ]);

                GameModeration::query()->create([
                    'game_id' => $game->id,
                    'moderator_id' => $moderators->random()->id,
                    'status' => GameStatus::Approved->value,
                    'reason' => fake()->sentence(),
                    'is_override' => false,
                ]);

                $createAllocation->handle($game);
            }
        }

        // Step B — Rejected games (15 total, 3 per format)
        foreach ($formats as $format) {
            for ($i = 0; $i < 3; $i++) {
                $game = Game::factory()->create([
                    'player_id' => $players->random()->id,
                    'format' => $format,
                    'status' => GameStatus::Rejected->value,
                    'result' => null,
                    'court_id' => rand(1, 10) > 3 ? $courts->random()->id : null,
                    'played_at' => fake()->dateTimeBetween('-1 year', 'now'),
                ]);

                GameModeration::query()->create([
                    'game_id' => $game->id,
                    'moderator_id' => $moderators->random()->id,
                    'status' => GameStatus::Rejected->value,
                    'reason' => fake()->sentence(),
                    'is_override' => false,
                ]);

                $rejectedGames->push($game);
            }
        }

        // Step C — Flagged games (10 total, 2 per format)
        foreach ($formats as $format) {
            for ($i = 0; $i < 2; $i++) {
                $game = Game::factory()->create([
                    'player_id' => $players->random()->id,
                    'format' => $format,
                    'status' => GameStatus::Flagged->value,
                    'result' => null,
                    'court_id' => rand(1, 10) > 3 ? $courts->random()->id : null,
                    'played_at' => fake()->dateTimeBetween('-1 year', 'now'),
                ]);

                GameModeration::query()->create([
                    'game_id' => $game->id,
                    'moderator_id' => $moderators->random()->id,
                    'status' => GameStatus::Flagged->value,
                    'reason' => fake()->sentence(),
                    'is_override' => false,
                ]);
            }
        }

        // Step D — Pending games (15 total, 3 per format)
        foreach ($formats as $format) {
            for ($i = 0; $i < 3; $i++) {
                Game::factory()->create([
                    'player_id' => $players->random()->id,
                    'format' => $format,
                    'status' => GameStatus::Pending->value,
                    'result' => null,
                    'court_id' => rand(1, 10) > 3 ? $courts->random()->id : null,
                    'played_at' => fake()->dateTimeBetween('-1 year', 'now'),
                ]);
            }
        }

        // Step E — Override scenario (3 rejected games get approved via override)
        $overrideGames = $rejectedGames->take(3);

        foreach ($overrideGames as $game) {
            GameModeration::query()->create([
                'game_id' => $game->id,
                'moderator_id' => $moderators->random()->id,
                'status' => GameStatus::Approved->value,
                'reason' => fake()->sentence(),
                'is_override' => true,
            ]);

            $game->update([
                'status' => GameStatus::Approved->value,
                'result' => fake()->randomElement([ResultStatus::WIN->value, ResultStatus::LOST->value]),
            ]);

            $createAllocation->handle($game);
        }

        // Step F — Calculate Rankings
        app(CalculateRankingsAction::class)->handle($config);
    }
}
