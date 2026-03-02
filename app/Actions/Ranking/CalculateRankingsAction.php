<?php

declare(strict_types=1);

namespace App\Actions\Ranking;

use App\Enums\GameStatus;
use App\Enums\ResultStatus;
use App\Models\Game;
use App\Models\PlayerRanking;
use App\Models\RankingConfiguration;
use Illuminate\Support\Collection;

final class CalculateRankingsAction
{
    public function handle(RankingConfiguration $config): void
    {
        $calculatedAt = now();
        $thirtyDaysAgo = now()->subDays(30);

        /** @var Collection<int, Game> $games */
        $games = Game::query()
            ->where('status', GameStatus::Approved)
            ->select(['player_id', 'format', 'result', 'played_at'])
            ->get();

        /** @var array<string, array<int, array{wins: int, losses: int, total: int, recent: int}>> $grouped */
        $grouped = [];

        foreach ($games as $game) {
            $format = $game->format;
            $playerId = $game->player_id;

            if (! isset($grouped[$format][$playerId])) {
                $grouped[$format][$playerId] = [
                    'wins' => 0,
                    'losses' => 0,
                    'total' => 0,
                    'recent' => 0,
                ];
            }

            $grouped[$format][$playerId]['total']++;

            if ($game->result === ResultStatus::WIN) {
                $grouped[$format][$playerId]['wins']++;
            } elseif ($game->result === ResultStatus::LOST) {
                $grouped[$format][$playerId]['losses']++;
            }

            if ($game->played_at >= $thirtyDaysAgo) {
                $grouped[$format][$playerId]['recent']++;
            }
        }

        $rows = [];

        foreach ($grouped as $format => $players) {
            $scored = [];

            foreach ($players as $playerId => $stats) {
                $score = ($stats['wins'] * $config->win_weight)
                    + ($stats['losses'] * $config->loss_weight)
                    + ($stats['total'] * $config->game_count_weight)
                    + ($stats['recent'] * $config->frequency_weight);

                $scored[] = [
                    'player_id' => $playerId,
                    'wins' => $stats['wins'],
                    'losses' => $stats['losses'],
                    'total_games' => $stats['total'],
                    'recent_games' => $stats['recent'],
                    'score' => $score,
                ];
            }

            usort($scored, fn (array $a, array $b): int => $b['score'] <=> $a['score']);

            foreach ($scored as $rank => $entry) {
                $rows[] = [
                    'player_id' => $entry['player_id'],
                    'format' => $format,
                    'wins' => $entry['wins'],
                    'losses' => $entry['losses'],
                    'total_games' => $entry['total_games'],
                    'recent_games' => $entry['recent_games'],
                    'score' => round($entry['score'], 4),
                    'rank' => $rank + 1,
                    'ranking_configuration_id' => $config->id,
                    'calculated_at' => $calculatedAt,
                    'created_at' => $calculatedAt,
                    'updated_at' => $calculatedAt,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            PlayerRanking::query()->insert($chunk);
        }
    }
}
