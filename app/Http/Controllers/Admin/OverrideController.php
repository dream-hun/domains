<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Game\ListFlaggedGamesAction;
use App\Actions\Admin\Game\OverrideAction;
use App\Enums\GameStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Game\OverrideGameRequest;
use App\Models\Game;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class OverrideController extends Controller
{
    public function index(ListFlaggedGamesAction $action): Response
    {
        return Inertia::render('admin/override/index', [
            'games' => $action->handle(),
        ]);
    }

    public function show(Game $game): Response
    {
        $game->load(['court', 'player', 'moderation.moderator']);

        return Inertia::render('admin/override/show', ['game' => $game]);
    }

    public function update(OverrideGameRequest $request, OverrideAction $action, Game $game): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        /** @var string $status */
        $status = $request->validated('status');
        /** @var string $reason */
        $reason = $request->validated('reason');
        $action->handle(
            $game,
            GameStatus::from($status),
            $reason,
            $user->id,
        );

        return to_route('admin.override.index')->with('success', 'Override decision saved.');
    }
}
