<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Game\CompleteUploadAction;
use App\Actions\Admin\Game\DeleteAction;
use App\Actions\Admin\Game\InitiateVimeoUploadAction;
use App\Actions\Admin\Game\ListAction;
use App\Actions\Admin\Game\StoreAction;
use App\Actions\Admin\Game\UpdateAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Game\CompleteUploadRequest;
use App\Http\Requests\Admin\Game\DeleteGameRequest;
use App\Http\Requests\Admin\Game\StoreGameRequest;
use App\Http\Requests\Admin\Game\UpdateGameRequest;
use App\Http\Requests\Admin\Game\UploadGameRequest;
use App\Models\Court;
use App\Models\Game;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class GameController extends Controller
{
    public function index(Request $request, ListAction $action): Response
    {
        $search = $request->string('search')->toString() ?: null;

        return Inertia::render('admin/games/index', [
            'games' => $action->handle($search),
            'filters' => ['search' => $search],
            'users' => User::query()->select(['id', 'name'])->orderBy('name')->get(),
            'courts' => Court::query()->select(['id', 'name'])->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/games/create', [
            'users' => User::query()->select(['id', 'name'])->orderBy('name')->get(),
            'courts' => Court::query()->select(['id', 'name'])->orderBy('name')->get(),
        ]);
    }

    public function store(StoreGameRequest $request, StoreAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('admin.games.index')->with('success', 'Game created successfully.');
    }

    public function edit(Game $game): Response
    {
        return Inertia::render('admin/games/edit', [
            'game' => $game,
        ]);
    }

    public function update(UpdateGameRequest $request, UpdateAction $action, Game $game): RedirectResponse
    {
        $action->handle($game, $request->validated());

        return to_route('admin.games.index');
    }

    public function destroy(DeleteGameRequest $request, DeleteAction $action, Game $game): RedirectResponse
    {
        $action->handle($game);

        return back();
    }

    public function showUpload(Game $game): Response
    {
        return Inertia::render('admin/games/upload', [
            'game' => $game,
        ]);
    }

    public function initiateUpload(UploadGameRequest $request, InitiateVimeoUploadAction $action, Game $game): JsonResponse
    {
        $result = $action->handle($game, $request->validated('file_size'));

        return response()->json($result);
    }

    public function completeUpload(CompleteUploadRequest $request, CompleteUploadAction $action, Game $game): RedirectResponse
    {
        $action->handle($game);

        return to_route('admin.games.index');
    }
}
