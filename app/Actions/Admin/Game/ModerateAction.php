<?php

declare(strict_types=1);

namespace App\Actions\Admin\Game;

use App\Enums\GameStatus;
use App\Jobs\CreateGameAllocationJob;
use App\Jobs\RecalculateRankingsJob;
use App\Models\Game;
use App\Models\RankingConfiguration;

final class ModerateAction
{
    public function handle(Game $game, GameStatus $status, string $reason, int $moderatorId): void
    {
        $game->moderation()->create([
            'moderator_id' => $moderatorId,
            'status' => $status,
            'reason' => $reason,
        ]);

        $game->update(['status' => $status]);

        if ($status === GameStatus::Approved) {
            $config = RankingConfiguration::query()->latest()->firstOrFail();
            dispatch(new RecalculateRankingsJob($config->id));
            dispatch(new CreateGameAllocationJob($game->id));
        }
    }
}
