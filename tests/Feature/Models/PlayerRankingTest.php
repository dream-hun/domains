<?php

declare(strict_types=1);

use App\Models\PlayerRanking;
use App\Models\RankingConfiguration;
use App\Models\User;

beforeEach(function (): void {
    $this->config = RankingConfiguration::query()->create([
        'win_weight' => 3.0,
        'loss_weight' => 1.0,
        'game_count_weight' => 0.5,
        'frequency_weight' => 2.0,
    ]);
});

test('player ranking belongs to a player', function (): void {
    $user = User::factory()->create();

    $ranking = PlayerRanking::query()->create([
        'player_id' => $user->id,
        'ranking_configuration_id' => $this->config->id,
        'format' => '1v1',
        'wins' => 1,
        'losses' => 0,
        'total_games' => 1,
        'recent_games' => 1,
        'score' => 5.5,
        'rank' => 1,
        'calculated_at' => now(),
    ]);

    expect($ranking->player)->toBeInstanceOf(User::class)
        ->and($ranking->player->id)->toBe($user->id);
});

test('player ranking belongs to a ranking configuration', function (): void {
    $user = User::factory()->create();

    $ranking = PlayerRanking::query()->create([
        'player_id' => $user->id,
        'ranking_configuration_id' => $this->config->id,
        'format' => '1v1',
        'wins' => 1,
        'losses' => 0,
        'total_games' => 1,
        'recent_games' => 1,
        'score' => 5.5,
        'rank' => 1,
        'calculated_at' => now(),
    ]);

    expect($ranking->configuration)->toBeInstanceOf(RankingConfiguration::class)
        ->and($ranking->configuration->id)->toBe($this->config->id);
});

test('player ranking integer fields are cast correctly', function (): void {
    $user = User::factory()->create();

    $ranking = PlayerRanking::query()->create([
        'player_id' => $user->id,
        'ranking_configuration_id' => $this->config->id,
        'format' => '1v1',
        'wins' => 3,
        'losses' => 2,
        'total_games' => 5,
        'recent_games' => 4,
        'score' => 12.5,
        'rank' => 1,
        'calculated_at' => now(),
    ]);

    expect($ranking->wins)->toBeInt()->toBe(3)
        ->and($ranking->losses)->toBeInt()->toBe(2)
        ->and($ranking->total_games)->toBeInt()->toBe(5)
        ->and($ranking->recent_games)->toBeInt()->toBe(4)
        ->and($ranking->rank)->toBeInt()->toBe(1);
});

test('player ranking score is cast to float', function (): void {
    $user = User::factory()->create();

    $ranking = PlayerRanking::query()->create([
        'player_id' => $user->id,
        'ranking_configuration_id' => $this->config->id,
        'format' => '1v1',
        'wins' => 1,
        'losses' => 0,
        'total_games' => 1,
        'recent_games' => 1,
        'score' => 7.25,
        'rank' => 1,
        'calculated_at' => now(),
    ]);

    expect($ranking->score)->toBeFloat()->toBe(7.25);
});
