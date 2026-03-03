<?php

declare(strict_types=1);

namespace App\Actions\Admin\Game;

use App\Enums\GameStatus;
use App\Models\Game;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListFlaggedGamesAction
{
    /** @return LengthAwarePaginator<int, Game> */
    public function handle(): LengthAwarePaginator
    {
        return Game::query()
            ->with(['court', 'player', 'moderation.moderator'])
            ->where('status', GameStatus::Flagged)
            ->oldest()
            ->paginate(15)
            ->withQueryString();
    }
}
