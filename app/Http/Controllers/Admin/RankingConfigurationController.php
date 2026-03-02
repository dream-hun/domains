<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Ranking\UpdateRankingConfigurationRequest;
use App\Jobs\RecalculateRankingsJob;
use App\Models\RankingConfiguration;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class RankingConfigurationController extends Controller
{
    public function edit(): Response
    {
        $config = RankingConfiguration::query()->latest()->firstOrFail();

        return Inertia::render('admin/ranking/edit', [
            'config' => $config,
        ]);
    }

    public function update(UpdateRankingConfigurationRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $config = RankingConfiguration::query()->create([
            ...$request->validated(),
            'updated_by' => $user->id,
        ]);

        dispatch(new RecalculateRankingsJob($config->id));

        return to_route('admin.ranking.edit')->with('success', 'Ranking configuration saved. Recalculation is queued.');
    }
}
