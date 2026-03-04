<?php

declare(strict_types=1);

namespace App\Actions\Admin\Allocation;

use App\Models\Allocation;
use App\Models\AllocationConfiguration;
use App\Models\Game;
use Illuminate\Support\Facades\DB;

final class CreateAllocation
{
    public function handle(Game $game): Allocation
    {
        $config = AllocationConfiguration::query()->latest('id')->firstOrFail();

        return DB::transaction(fn (): Allocation => Allocation::query()->create([
            'game_id' => $game->id,
            'player_id' => $game->player_id,
            'total_amount' => 1.00,
            'insurance_amount' => round($config->insurance_percentage / 100, 4),
            'savings_amount' => round($config->savings_percentage / 100, 4),
            'pathway_amount' => round($config->pathway_percentage / 100, 4),
            'administration_amount' => round($config->administration_percentage / 100, 4),
            'allocation_configuration_id' => $config->id,
        ]));
    }
}
