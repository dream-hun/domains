<?php

declare(strict_types=1);

use App\Models\PlayerRanking;
use App\Models\RankingConfiguration;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('guests are redirected to the login page', function (): void {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('dashboard')
        ->has('stats')
        ->has('stats.total_games')
        ->has('stats.total_courts')
        ->has('stats.pending_games')
        ->has('stats.approved_games')
        ->has('recent_games')
        ->has('games_per_month')
        ->has('player_rankings')
    );
});

test('player_rankings prop is empty when player has no approved games', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('dashboard')
        ->where('player_rankings', [])
    );
});

test('player_rankings prop contains correct rank and stats for each format', function (): void {
    $user = User::factory()->create();

    $config = RankingConfiguration::query()->create([
        'win_weight' => 3.0,
        'loss_weight' => 1.0,
        'game_count_weight' => 0.5,
        'frequency_weight' => 2.0,
    ]);

    PlayerRanking::query()->create([
        'player_id' => $user->id,
        'format' => '1v1',
        'wins' => 5,
        'losses' => 2,
        'total_games' => 7,
        'recent_games' => 3,
        'score' => 20.5,
        'rank' => 1,
        'ranking_configuration_id' => $config->id,
        'calculated_at' => now(),
    ]);

    PlayerRanking::query()->create([
        'player_id' => $user->id,
        'format' => '3v3',
        'wins' => 2,
        'losses' => 1,
        'total_games' => 3,
        'recent_games' => 1,
        'score' => 9.5,
        'rank' => 2,
        'ranking_configuration_id' => $config->id,
        'calculated_at' => now(),
    ]);

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('dashboard')
        ->has('player_rankings.1v1')
        ->where('player_rankings.1v1.rank', 1)
        ->where('player_rankings.1v1.wins', 5)
        ->where('player_rankings.1v1.losses', 2)
        ->has('player_rankings.3v3')
        ->where('player_rankings.3v3.rank', 2)
    );
});

test('player_rankings returns only the latest snapshot per format', function (): void {
    $user = User::factory()->create();

    $config = RankingConfiguration::query()->create([
        'win_weight' => 3.0,
        'loss_weight' => 1.0,
        'game_count_weight' => 0.5,
        'frequency_weight' => 2.0,
    ]);

    // Old snapshot
    PlayerRanking::query()->create([
        'player_id' => $user->id,
        'format' => '1v1',
        'wins' => 1,
        'losses' => 0,
        'total_games' => 1,
        'recent_games' => 0,
        'score' => 3.5,
        'rank' => 1,
        'ranking_configuration_id' => $config->id,
        'calculated_at' => now()->subHour(),
    ]);

    // Latest snapshot
    PlayerRanking::query()->create([
        'player_id' => $user->id,
        'format' => '1v1',
        'wins' => 3,
        'losses' => 1,
        'total_games' => 4,
        'recent_games' => 2,
        'score' => 13.5,
        'rank' => 1,
        'ranking_configuration_id' => $config->id,
        'calculated_at' => now(),
    ]);

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('dashboard')
        ->where('player_rankings.1v1.wins', 3)
        ->where('player_rankings.1v1.score', 13.5)
    );
});
