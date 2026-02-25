<?php

declare(strict_types=1);

namespace App\Actions\Admin\Game;

use App\Enums\GameStatus;
use App\Models\Game;

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
    }
}
