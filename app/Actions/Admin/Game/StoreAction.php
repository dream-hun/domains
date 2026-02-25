<?php

declare(strict_types=1);

namespace App\Actions\Admin\Game;

use App\Models\Game;
use Illuminate\Support\Str;

final class StoreAction
{
    /** @param array<string, mixed> $data */
    public function handle(array $data): Game
    {
        return Game::query()->create(array_merge($data, [
            'uuid' => Str::uuid(),
        ]));
    }
}
