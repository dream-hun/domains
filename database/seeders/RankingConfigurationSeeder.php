<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\RankingConfiguration;
use Illuminate\Database\Seeder;

final class RankingConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        RankingConfiguration::query()->create([
            'win_weight' => 3.0,
            'loss_weight' => 1.0,
            'game_count_weight' => 0.5,
            'frequency_weight' => 2.0,
        ]);
    }
}
