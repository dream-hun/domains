<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Admin\Allocation\CreateAllocation;
use App\Models\Game;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class CreateGameAllocationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $gameId) {}

    public function handle(CreateAllocation $action): void
    {
        $game = Game::query()->findOrFail($this->gameId);

        $action->handle($game);
    }
}
