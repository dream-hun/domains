<?php

declare(strict_types=1);

namespace App\Actions\Admin\Game;

use App\Models\Game;

final class DeleteAction
{
    public function handle(Game $game): void
    {
        $game->delete();
    }
}
