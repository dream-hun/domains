<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Game\ListModerationQueueAction;
use App\Actions\Admin\Game\ModerateAction;
use App\Enums\GameStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Game\ModerateGameRequest;
use App\Models\Game;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class ModerationController extends Controller
{
    public function index(ListModerationQueueAction $action): Response
    {
        return Inertia::render('admin/moderation/index', [
            'games' => $action->handle(),
        ]);
    }

    public function show(Game $game): Response
    {
        $game->load(['court', 'player']);

        return Inertia::render('admin/moderation/show', ['game' => $game]);
    }

    public function update(ModerateGameRequest $request, ModerateAction $action, Game $game): RedirectResponse
    {
        $action->handle(
            $game,
            GameStatus::from($request->validated('status')),
            $request->validated('reason'),
            $request->user()->id,
        );

        return to_route('admin.moderation.index')->with('success', 'Game moderation decision saved.');
    }
}
