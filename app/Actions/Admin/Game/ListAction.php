<?php

declare(strict_types=1);

namespace App\Actions\Admin\Game;

use App\Models\Game;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListAction
{
    /** @return LengthAwarePaginator<int, Game> */
    public function handle(?string $search = null): LengthAwarePaginator
    {
        return Game::query()
            ->with(['court', 'player'])
            ->when($search, function (Builder $query, string $search): void {
                $query->where('title', 'like', sprintf('%%%s%%', $search));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }
}
