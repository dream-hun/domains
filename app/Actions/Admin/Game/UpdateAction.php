<?php

declare(strict_types=1);

namespace App\Actions\Admin\Game;

use App\Models\Game;

final class UpdateAction
{
    /** @param array<string, mixed> $data */
    public function handle(Game $game, array $data): Game
    {
        $game->update($data);
        $game->refresh();

        return $game;
    }
}
