<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Ranking\GetPlayerRankingsAction;
use App\Enums\GameStatus;
use App\Models\Court;
use App\Models\Game;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __invoke(Request $request, GetPlayerRankingsAction $rankingsAction): Response
    {
        /** @var User $user */
        $user = $request->user();

        $gamesPerMonth = $this->buildGamesPerMonth();

        return Inertia::render('dashboard', [
            'stats' => [
                'total_games' => Game::query()->count(),
                'total_courts' => Court::query()->count(),
                'pending_games' => Game::query()->where('status', GameStatus::Pending)->count(),
                'approved_games' => Game::query()->where('status', GameStatus::Approved)->count(),
            ],
            'recent_games' => Game::query()
                ->with(['court', 'player'])
                ->latest('played_at')
                ->limit(5)
                ->get()
                ->map(fn (Game $game): array => [
                    'id' => $game->id,
                    'uuid' => $game->uuid,
                    'title' => $game->title,
                    'status' => $game->status->value,
                    'played_at' => $game->played_at->toISOString(),
                    'court' => $game->court ? ['name' => $game->court->name] : null,
                    'player' => ['name' => $game->player->name],
                ]),
            'games_per_month' => $gamesPerMonth,
            'player_rankings' => $rankingsAction->handle($user->id),
        ]);
    }

    /** @return list<array{month: string, count: int}> */
    private function buildGamesPerMonth(): array
    {
        $formatExpr = DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', played_at)"
            : "DATE_FORMAT(played_at, '%Y-%m')"; // @codeCoverageIgnore

        $counts = Game::query()->selectRaw($formatExpr.' as month, COUNT(*) as count')
            ->where('played_at', '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        $result = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i)->format('Y-m');
            /** @var int $count */
            $count = $counts[$month] ?? 0;
            $result[] = [
                'month' => $month,
                'count' => $count,
            ];
        }

        return $result;
    }
}
