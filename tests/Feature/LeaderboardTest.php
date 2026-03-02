<?php

declare(strict_types=1);

use App\Models\Country;
use App\Models\PlayerRanking;
use App\Models\Profile;
use App\Models\RankingConfiguration;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

function createRankedPlayer(RankingConfiguration $config, string $format, int $rank, float $score, ?array $profileOverrides = null): User
{
    $player = User::factory()->create();
    $country = Country::factory()->create($profileOverrides ? ['region' => $profileOverrides['region'] ?? fake()->word()] : []);
    Profile::factory()->create(array_merge(
        ['player_id' => $player->id, 'country_id' => $country->id],
        $profileOverrides ?? [],
    ));

    PlayerRanking::query()->create([
        'player_id' => $player->id,
        'format' => $format,
        'wins' => 2,
        'losses' => 1,
        'total_games' => 3,
        'recent_games' => 2,
        'score' => $score,
        'rank' => $rank,
        'ranking_configuration_id' => $config->id,
        'calculated_at' => now(),
    ]);

    return $player;
}

beforeEach(function (): void {
    $this->config = RankingConfiguration::query()->create([
        'win_weight' => 3.0,
        'loss_weight' => 1.0,
        'game_count_weight' => 0.5,
        'frequency_weight' => 2.0,
    ]);
});

test('unauthenticated users are redirected to login', function (): void {
    $response = $this->get(route('leaderboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated user can view the leaderboard', function (): void {
    $viewer = User::factory()->create();
    $country = Country::factory()->create();
    Profile::factory()->create(['player_id' => $viewer->id, 'country_id' => $country->id]);
    $this->actingAs($viewer);

    $response = $this->get(route('leaderboard'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('leaderboard/index')
        ->has('entries')
        ->has('filters')
        ->has('formats')
    );
});

test('empty state when no rankings exist', function (): void {
    $viewer = User::factory()->create();
    $country = Country::factory()->create();
    Profile::factory()->create(['player_id' => $viewer->id, 'country_id' => $country->id]);
    $this->actingAs($viewer);

    $response = $this->get(route('leaderboard', ['format' => '1v1', 'geo' => 'national']));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('leaderboard/index')
        ->where('entries', [])
    );
});

test('format filter returns only the selected format', function (): void {
    $viewerCountry = Country::factory()->create();
    $viewer = User::factory()->create();
    Profile::factory()->create(['player_id' => $viewer->id, 'country_id' => $viewerCountry->id]);
    $this->actingAs($viewer);

    createRankedPlayer($this->config, '1v1', 1, 10.0, ['country_id' => $viewerCountry->id]);
    createRankedPlayer($this->config, '3v3', 1, 8.0, ['country_id' => $viewerCountry->id]);

    $response = $this->get(route('leaderboard', ['format' => '1v1', 'geo' => 'national']));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('leaderboard/index')
        ->has('entries', 1)
        ->where('filters.format', '1v1')
    );
});

test('geo=national filters by viewer country', function (): void {
    $viewerCountry = Country::factory()->create();
    $otherCountry = Country::factory()->create();

    $viewer = User::factory()->create();
    Profile::factory()->create(['player_id' => $viewer->id, 'country_id' => $viewerCountry->id]);
    $this->actingAs($viewer);

    $sameCountryPlayer = createRankedPlayer($this->config, '1v1', 1, 10.0, ['country_id' => $viewerCountry->id]);
    createRankedPlayer($this->config, '1v1', 2, 5.0, ['country_id' => $otherCountry->id]);

    $response = $this->get(route('leaderboard', ['format' => '1v1', 'geo' => 'national']));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('leaderboard/index')
        ->has('entries', 1)
        ->where('entries.0.player_id', $sameCountryPlayer->id)
    );
});

test('geo=local filters by viewer city', function (): void {
    $viewerCountry = Country::factory()->create();
    $viewer = User::factory()->create();
    Profile::factory()->create(['player_id' => $viewer->id, 'country_id' => $viewerCountry->id, 'city' => 'Paris']);
    $this->actingAs($viewer);

    $sameCity = createRankedPlayer($this->config, '1v1', 1, 10.0, ['country_id' => $viewerCountry->id, 'city' => 'Paris']);
    createRankedPlayer($this->config, '1v1', 2, 5.0, ['country_id' => $viewerCountry->id, 'city' => 'Lyon']);

    $response = $this->get(route('leaderboard', ['format' => '1v1', 'geo' => 'local']));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('leaderboard/index')
        ->has('entries', 1)
        ->where('entries.0.player_id', $sameCity->id)
    );
});

test('geo=continental filters by viewer region', function (): void {
    $viewerCountry = Country::factory()->create(['region' => 'Europe']);
    $sameRegionCountry = Country::factory()->create(['region' => 'Europe']);
    $otherRegionCountry = Country::factory()->create(['region' => 'Asia']);

    $viewer = User::factory()->create();
    Profile::factory()->create(['player_id' => $viewer->id, 'country_id' => $viewerCountry->id]);
    $this->actingAs($viewer);

    $sameRegionPlayer = createRankedPlayer($this->config, '1v1', 1, 10.0, ['country_id' => $sameRegionCountry->id]);
    createRankedPlayer($this->config, '1v1', 2, 5.0, ['country_id' => $otherRegionCountry->id]);

    $response = $this->get(route('leaderboard', ['format' => '1v1', 'geo' => 'continental']));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('leaderboard/index')
        ->has('entries', 1)
        ->where('entries.0.player_id', $sameRegionPlayer->id)
    );
});
