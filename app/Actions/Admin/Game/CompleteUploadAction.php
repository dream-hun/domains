<?php

declare(strict_types=1);

namespace App\Actions\Admin\Game;

use App\Models\Game;

final class CompleteUploadAction
{
    public function handle(Game $game): void
    {
        $game->update([
            'vimeo_status' => 'complete',
        ]);
    }
}
