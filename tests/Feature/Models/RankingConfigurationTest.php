<?php

declare(strict_types=1);

use App\Models\PlayerRanking;
use App\Models\RankingConfiguration;
use App\Models\User;

test('ranking configuration updated by belongs to a user', function (): void {
    $user = User::factory()->create();

    $config = RankingConfiguration::query()->create([
        'win_weight' => 3.0,
        'loss_weight' => 1.0,
        'game_count_weight' => 0.5,
        'frequency_weight' => 2.0,
        'updated_by' => $user->id,
    ]);

    expect($config->updatedBy)->toBeInstanceOf(User::class)
        ->and($config->updatedBy->id)->toBe($user->id);
});

test('ranking configuration updated by is null when not set', function (): void {
    $config = RankingConfiguration::query()->create([
        'win_weight' => 3.0,
        'loss_weight' => 1.0,
        'game_count_weight' => 0.5,
        'frequency_weight' => 2.0,
    ]);

    expect($config->updatedBy)->toBeNull();
});

test('ranking configuration has many player rankings', function (): void {
    $config = RankingConfiguration::query()->create([
        'win_weight' => 3.0,
        'loss_weight' => 1.0,
        'game_count_weight' => 0.5,
        'frequency_weight' => 2.0,
    ]);
    $user = User::factory()->create();

    PlayerRanking::query()->create([
        'player_id' => $user->id,
        'ranking_configuration_id' => $config->id,
        'format' => '1v1',
        'wins' => 1,
        'losses' => 0,
        'total_games' => 1,
        'recent_games' => 1,
        'score' => 5.0,
        'rank' => 1,
        'calculated_at' => now(),
    ]);

    expect($config->rankings)->toHaveCount(1)
        ->and($config->rankings->first())->toBeInstanceOf(PlayerRanking::class);
});

test('ranking configuration weights are cast to float', function (): void {
    $config = RankingConfiguration::query()->create([
        'win_weight' => 3.0,
        'loss_weight' => 1.0,
        'game_count_weight' => 0.5,
        'frequency_weight' => 2.0,
    ]);

    expect($config->win_weight)->toBeFloat()->toBe(3.0)
        ->and($config->loss_weight)->toBeFloat()->toBe(1.0)
        ->and($config->game_count_weight)->toBeFloat()->toBe(0.5)
        ->and($config->frequency_weight)->toBeFloat()->toBe(2.0);
});
