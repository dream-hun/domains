<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Ranking\CalculateRankingsAction;
use App\Models\RankingConfiguration;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class RecalculateRankingsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $rankingConfigurationId) {}

    public function handle(CalculateRankingsAction $action): void
    {
        $config = RankingConfiguration::query()->findOrFail($this->rankingConfigurationId);

        $action->handle($config);
    }
}
