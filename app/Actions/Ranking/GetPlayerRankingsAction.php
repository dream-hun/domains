<?php

declare(strict_types=1);

namespace App\Actions\Ranking;

use App\Models\PlayerRanking;
use Illuminate\Support\Facades\DB;

final class GetPlayerRankingsAction
{
    /**
     * @return array<string, array{format: string, rank: int, score: float, wins: int, losses: int}>
     */
    public function handle(int $playerId): array
    {
        /** @var \Illuminate\Support\Collection<int, object{format: string, max_calculated_at: string}> $latestPerFormat */
        $latestPerFormat = PlayerRanking::query()
            ->where('player_id', $playerId)
            ->select('format', DB::raw('MAX(calculated_at) as max_calculated_at'))
            ->groupBy('format')
            ->get();

        $result = [];

        foreach ($latestPerFormat as $row) {
            $ranking = PlayerRanking::query()
                ->where('player_id', $playerId)
                ->where('format', $row->format)
                ->where('calculated_at', $row->max_calculated_at)
                ->first();

            if ($ranking !== null) {
                $result[$row->format] = [
                    'format' => $row->format,
                    'rank' => $ranking->rank,
                    'score' => $ranking->score,
                    'wins' => $ranking->wins,
                    'losses' => $ranking->losses,
                ];
            }
        }

        return $result;
    }
}
