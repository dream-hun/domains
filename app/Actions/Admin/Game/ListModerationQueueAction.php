<?php

declare(strict_types=1);

namespace App\Actions\Admin\Game;

use App\Enums\GameStatus;
use App\Models\Game;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListModerationQueueAction
{
    /** @return LengthAwarePaginator<int, Game> */
    public function handle(): LengthAwarePaginator
    {
        return Game::query()
            ->with(['court', 'player'])
            ->where('status', GameStatus::Pending)
            ->oldest()
            ->paginate(15)
            ->withQueryString();
    }
}
