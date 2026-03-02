<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Ranking\GetLeaderboardAction;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class LeaderboardController extends Controller
{
    public function __invoke(Request $request, GetLeaderboardAction $action): Response
    {
        $format = $request->string('format', '1v1')->toString();
        $geo = $request->string('geo', 'national')->toString();

        /** @var User $user */
        $user = $request->user();

        return Inertia::render('leaderboard/index', [
            'entries' => $action->handle($format, $geo, $user),
            'filters' => ['format' => $format, 'geo' => $geo],
            'formats' => ['1v1', '2v2', '3v3', '4v4', '5v5'],
        ]);
    }
}
