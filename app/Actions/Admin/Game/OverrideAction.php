<?php

declare(strict_types=1);

namespace App\Actions\Admin\Game;

use App\Enums\GameStatus;
use App\Jobs\CreateGameAllocationJob;
use App\Jobs\RecalculateRankingsJob;
use App\Models\Game;
use App\Models\RankingConfiguration;
use Illuminate\Support\Facades\DB;

final class OverrideAction
{
    public function handle(Game $game, GameStatus $status, string $reason, int $adminId): void
    {
        DB::transaction(function () use ($game, $status, $reason, $adminId): void {
            $game->moderation()->create([
                'moderator_id' => $adminId,
                'status' => $status,
                'reason' => $reason,
                'is_override' => true,
            ]);

            $game->update(['status' => $status]);
        });

        if ($status === GameStatus::Approved) {
            $config = RankingConfiguration::query()->latest()->firstOrFail();
            dispatch(new RecalculateRankingsJob($config->id));
            dispatch(new CreateGameAllocationJob($game->id));
        }
    }
}
