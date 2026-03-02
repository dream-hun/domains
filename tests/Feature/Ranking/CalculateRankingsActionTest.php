<?php

declare(strict_types=1);

use App\Actions\Ranking\CalculateRankingsAction;
use App\Models\Game;
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

test('only approved games are counted', function (): void {
    $player = User::factory()->create();

    Game::factory()->create(['player_id' => $player->id, 'status' => 'approved', 'result' => 'win', 'format' => '1v1']);
    Game::factory()->create(['player_id' => $player->id, 'status' => 'pending', 'result' => 'win', 'format' => '1v1']);
    Game::factory()->create(['player_id' => $player->id, 'status' => 'rejected', 'result' => 'win', 'format' => '1v1']);

    (new CalculateRankingsAction())->handle($this->config);

    $ranking = PlayerRanking::query()
        ->where('player_id', $player->id)
        ->where('format', '1v1')
        ->first();

    expect($ranking)->not->toBeNull()
        ->and($ranking->total_games)->toBe(1)
        ->and($ranking->wins)->toBe(1);
});

test('wins and losses are counted correctly', function (): void {
    $player = User::factory()->create();

    Game::factory()->create(['player_id' => $player->id, 'status' => 'approved', 'result' => 'win', 'format' => '1v1']);
    Game::factory()->create(['player_id' => $player->id, 'status' => 'approved', 'result' => 'win', 'format' => '1v1']);
    Game::factory()->create(['player_id' => $player->id, 'status' => 'approved', 'result' => 'lost', 'format' => '1v1']);

    (new CalculateRankingsAction())->handle($this->config);

    $ranking = PlayerRanking::query()
        ->where('player_id', $player->id)
        ->where('format', '1v1')
        ->first();

    expect($ranking)->not->toBeNull()
        ->and($ranking->wins)->toBe(2)
        ->and($ranking->losses)->toBe(1)
        ->and($ranking->total_games)->toBe(3);
});

test('recent games are counted for games within last 30 days', function (): void {
    $player = User::factory()->create();

    Game::factory()->create([
        'player_id' => $player->id,
        'status' => 'approved',
        'result' => 'win',
        'format' => '1v1',
        'played_at' => now()->subDays(15),
    ]);
    Game::factory()->create([
        'player_id' => $player->id,
        'status' => 'approved',
        'result' => 'win',
        'format' => '1v1',
        'played_at' => now()->subDays(60),
    ]);

    (new CalculateRankingsAction())->handle($this->config);

    $ranking = PlayerRanking::query()
        ->where('player_id', $player->id)
        ->where('format', '1v1')
        ->first();

    expect($ranking)->not->toBeNull()
        ->and($ranking->total_games)->toBe(2)
        ->and($ranking->recent_games)->toBe(1);
});

test('score formula is applied correctly', function (): void {
    $player = User::factory()->create();

    // 2 wins, 1 loss, 3 total, 2 recent
    Game::factory()->create(['player_id' => $player->id, 'status' => 'approved', 'result' => 'win', 'format' => '1v1', 'played_at' => now()->subDays(5)]);
    Game::factory()->create(['player_id' => $player->id, 'status' => 'approved', 'result' => 'win', 'format' => '1v1', 'played_at' => now()->subDays(10)]);
    Game::factory()->create(['player_id' => $player->id, 'status' => 'approved', 'result' => 'lost', 'format' => '1v1', 'played_at' => now()->subDays(60)]);

    (new CalculateRankingsAction())->handle($this->config);

    $ranking = PlayerRanking::query()
        ->where('player_id', $player->id)
        ->where('format', '1v1')
        ->first();

    // score = (2 * 3.0) + (1 * 1.0) + (3 * 0.5) + (2 * 2.0) = 6 + 1 + 1.5 + 4 = 12.5
    expect($ranking)->not->toBeNull()
        ->and($ranking->score)->toBe(12.5);
});

test('rank 1 is assigned to the highest score', function (): void {
    $player1 = User::factory()->create();
    $player2 = User::factory()->create();

    Game::factory()->count(3)->create(['player_id' => $player1->id, 'status' => 'approved', 'result' => 'win', 'format' => '1v1']);
    Game::factory()->count(1)->create(['player_id' => $player2->id, 'status' => 'approved', 'result' => 'win', 'format' => '1v1']);

    (new CalculateRankingsAction())->handle($this->config);

    $rank1 = PlayerRanking::query()->where('player_id', $player1->id)->where('format', '1v1')->first();
    $rank2 = PlayerRanking::query()->where('player_id', $player2->id)->where('format', '1v1')->first();

    expect($rank1?->rank)->toBe(1)
        ->and($rank2?->rank)->toBe(2);
});

test('old snapshot rows are not deleted on recalculation', function (): void {
    $player = User::factory()->create();
    Game::factory()->create(['player_id' => $player->id, 'status' => 'approved', 'result' => 'win', 'format' => '1v1']);

    $action = new CalculateRankingsAction();
    $action->handle($this->config);
    $action->handle($this->config);

    $count = PlayerRanking::query()->where('player_id', $player->id)->count();

    expect($count)->toBe(2);
});

test('players with no approved games are excluded from snapshot', function (): void {
    $playerWithApproved = User::factory()->create();
    $playerWithoutApproved = User::factory()->create();

    Game::factory()->create(['player_id' => $playerWithApproved->id, 'status' => 'approved', 'result' => 'win', 'format' => '1v1']);
    Game::factory()->create(['player_id' => $playerWithoutApproved->id, 'status' => 'pending', 'result' => 'win', 'format' => '1v1']);

    (new CalculateRankingsAction())->handle($this->config);

    expect(PlayerRanking::query()->where('player_id', $playerWithApproved->id)->exists())->toBeTrue()
        ->and(PlayerRanking::query()->where('player_id', $playerWithoutApproved->id)->exists())->toBeFalse();
});

test('rankings are split by format', function (): void {
    $player = User::factory()->create();

    Game::factory()->create(['player_id' => $player->id, 'status' => 'approved', 'result' => 'win', 'format' => '1v1']);
    Game::factory()->create(['player_id' => $player->id, 'status' => 'approved', 'result' => 'win', 'format' => '3v3']);

    (new CalculateRankingsAction())->handle($this->config);

    expect(PlayerRanking::query()->where('player_id', $player->id)->where('format', '1v1')->exists())->toBeTrue()
        ->and(PlayerRanking::query()->where('player_id', $player->id)->where('format', '3v3')->exists())->toBeTrue();
});
