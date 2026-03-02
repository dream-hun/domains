<?php

declare(strict_types=1);

namespace App\Actions\Ranking;

use App\Models\PlayerRanking;
use App\Models\User;

final class GetLeaderboardAction
{
    /**
     * @return array<int, array{rank: int, player_id: int, player_name: string, wins: int, losses: int, total_games: int, score: float}>
     */
    public function handle(string $format, string $geo, User $viewer): array
    {
        $latestCalculatedAt = PlayerRanking::query()
            ->where('format', $format)
            ->max('calculated_at');

        if ($latestCalculatedAt === null) {
            return [];
        }

        $query = PlayerRanking::query()
            ->where('player_rankings.format', $format)
            ->where('player_rankings.calculated_at', $latestCalculatedAt)
            ->join('profiles', 'profiles.player_id', '=', 'player_rankings.player_id')
            ->join('countries', 'countries.id', '=', 'profiles.country_id')
            ->with('player')
            ->select('player_rankings.*')
            ->orderBy('player_rankings.rank');

        $viewerProfile = $viewer->profile;

        match ($geo) {
            'local' => $query->where('profiles.city', $viewerProfile?->city),
            'continental' => $query->where('countries.region', fn (\Illuminate\Database\Query\Builder $q): \Illuminate\Database\Query\Builder => $q->select('region')->from('countries')->join('profiles', 'profiles.country_id', '=', 'countries.id')->where('profiles.player_id', $viewer->id)->limit(1)),
            default => $query->where('profiles.country_id', $viewerProfile?->country_id),
        };

        return $query->get()->map(fn (PlayerRanking $row): array => [
            'rank' => $row->rank,
            'player_id' => $row->player_id,
            'player_name' => $row->player->name,
            'wins' => $row->wins,
            'losses' => $row->losses,
            'total_games' => $row->total_games,
            'score' => $row->score,
        ])->values()->all();
    }
}
